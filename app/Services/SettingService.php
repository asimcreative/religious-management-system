<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Company settings, as a small typed catalogue rather than free key/value.
 *
 * The keys are declared here because two services already read them by name
 * (AttendanceLockService and the two attendance services). A settings screen
 * that let anyone invent a key would produce rows nothing ever reads, and
 * typos that look like configuration.
 */
class SettingService
{
    /**
     * Every setting the application understands, with its validation.
     *
     * @return array<string, array{rules: array<int, string>, default: string, group: string}>
     */
    public static function catalogue(): array
    {
        return [
            'attendance_lock_time' => [
                'rules' => ['required', 'date_format:H:i'],
                'default' => AttendanceLockService::DEFAULT_LOCK_TIME,
                'group' => 'attendance',
            ],
            'max_backdated_attendance_days' => [
                'rules' => ['required', 'integer', 'min:0', 'max:365'],
                'default' => '7',
                'group' => 'attendance',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::catalogue());
    }

    /**
     * Current values for a company, with defaults filled in.
     *
     * @return array<string, string>
     */
    public function all(int $companyId): array
    {
        $stored = Setting::query()
            ->where('company_id', $companyId)
            ->pluck('value', 'key')
            ->all();

        $values = [];

        foreach (self::catalogue() as $key => $meta) {
            $values[$key] = (string) ($stored[$key] ?? $meta['default']);
        }

        return $values;
    }

    /**
     * Write the given settings, ignoring anything not in the catalogue.
     *
     * @param  array<string, mixed>  $values
     */
    public function update(User $actor, array $values): void
    {
        $companyId = (int) $actor->getAttribute('company_id');

        DB::transaction(function () use ($companyId, $values): void {
            foreach (self::catalogue() as $key => $meta) {
                if (! array_key_exists($key, $values)) {
                    continue;
                }

                Setting::updateOrCreate(
                    ['company_id' => $companyId, 'key' => $key],
                    ['value' => (string) $values[$key]],
                );
            }
        });

        // The attendance services read these on every save; a stale cache
        // would keep a closed day open.
        Cache::forget("settings.{$companyId}");
    }
}
