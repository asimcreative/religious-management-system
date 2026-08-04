<x-mail::message>
# Security Alert — Password Changed

Dear {{ $user->name }},

Your RAMS account password was recently changed.

If you made this change, no action is required.

If you did **not** make this change, please contact your company administrator immediately to secure your account.

<x-mail::button :url="url('/')">
Login to RAMS
</x-mail::button>

Thanks,<br>
RAMS Team
</x-mail::message>
