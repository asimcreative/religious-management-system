<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * The platform account viewing a tenant as one of its own users.
 *
 * The platform account administers companies; it does not hold their data. To
 * answer "what does this company actually see?" it signs in as that company's
 * administrator rather than being granted a cross-tenant view — so every
 * existing policy, scope and permission applies unchanged, and there is no
 * second code path that could disagree with the first.
 *
 * Three facts live in the session for the duration:
 *
 *   impersonator_id  who really is at the keyboard
 *   read_only        whether they may change anything (they may not)
 *   company_name     which company is on screen, for the banner
 *
 * They are read on every request. Nothing is stored on the impersonated user,
 * so ending the session — or losing it — leaves no trace on their account.
 */
class Impersonation
{
    private const IMPERSONATOR = 'impersonation.impersonator_id';

    private const READ_ONLY = 'impersonation.read_only';

    private const COMPANY = 'impersonation.company_name';

    /**
     * Policy abilities that change something.
     *
     * Matched by name, which covers every module at once: `@can('create',
     * Branch::class)` asks for "create" whichever policy answers it. The read
     * abilities in this application — viewAny, view, download, export,
     * viewOthers, viewHistory — are deliberately absent.
     *
     * @var array<int, string>
     */
    private const WRITE_ABILITIES = [
        'create', 'update', 'delete', 'forceDelete', 'restore',
        'import', 'assignRoles', 'lock',
    ];

    /**
     * Permission names that change something, matched by suffix.
     *
     * ".manage" is deliberately absent. The seven master modules express both
     * reading and writing as a single `<entity>.manage` permission, so denying
     * it would close the list itself rather than just its buttons. Their write
     * controls are gated by policy abilities — create, update, delete — which
     * the list above already covers, and the HTTP boundary refuses the writes
     * regardless of what the page offers.
     *
     * @var array<int, string>
     */
    private const WRITE_SUFFIXES = [
        '.create', '.update', '.delete', '.restore', '.import',
        '.lock', '.send', '.read', '.assign', '.assign_branch',
        '.assign_class', '.generate_token', '.revoke_token',
    ];

    public static function isActive(): bool
    {
        return Session::has(self::IMPERSONATOR);
    }

    public static function isReadOnly(): bool
    {
        return self::isActive() && Session::get(self::READ_ONLY, true) === true;
    }

    public static function impersonatorId(): ?int
    {
        $id = Session::get(self::IMPERSONATOR);

        return $id === null ? null : (int) $id;
    }

    /** The company being viewed, for the banner. */
    public static function companyName(): ?string
    {
        return Session::get(self::COMPANY);
    }

    /**
     * Begin viewing as another user.
     *
     * The caller is responsible for authorising this; by the time it is called
     * the decision has already been made.
     */
    public static function start(User $impersonator, User $target, string $companyName, bool $readOnly = true): void
    {
        // login() migrates the session id, keeping its data. Writing the three
        // keys afterwards makes that ordering explicit rather than assumed —
        // losing them would strand the platform account inside the tenant with
        // no way back to itself.
        Auth::login($target);

        Session::put(self::IMPERSONATOR, $impersonator->id);
        Session::put(self::READ_ONLY, $readOnly);
        Session::put(self::COMPANY, $companyName);
    }

    /**
     * Return to the platform account.
     *
     * @return User|null The restored user, or null if the session was stale.
     */
    public static function stop(): ?User
    {
        $id = self::impersonatorId();
        self::forget();

        if ($id === null) {
            return null;
        }

        $original = User::withoutGlobalScopes()->find($id);

        if ($original === null) {
            // The platform account has gone away. Signing out is the only safe
            // answer: staying signed in as the tenant user would leave someone
            // holding an identity they were never issued.
            Auth::logout();
            Session::invalidate();

            return null;
        }

        Auth::login($original);

        return $original;
    }

    public static function forget(): void
    {
        Session::forget([self::IMPERSONATOR, self::READ_ONLY, self::COMPANY]);
    }

    /**
     * Whether an ability would change something.
     *
     * Used to hide controls that would fail, so the screens read as what the
     * tenant sees minus the buttons. It is a naming heuristic and does not have
     * to be exhaustive: EnforceReadOnlyImpersonation refuses every unsafe
     * request whatever the page offered, so a miss here costs a stale button,
     * never a write.
     */
    public static function isWriteAbility(string $ability): bool
    {
        if (in_array($ability, self::WRITE_ABILITIES, true)) {
            return true;
        }

        foreach (self::WRITE_SUFFIXES as $suffix) {
            if (str_ends_with($ability, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
