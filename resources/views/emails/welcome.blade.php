<x-mail::message>
# Welcome to RAMS

Dear {{ $user->name }},

Your account has been created successfully on the **Religious Affairs Management System (RAMS)**.

You can now log in using your credentials.

<x-mail::button :url="url('/')">
Login to RAMS
</x-mail::button>

If you have any questions, please contact your company administrator.

Thanks,<br>
RAMS Team
</x-mail::message>
