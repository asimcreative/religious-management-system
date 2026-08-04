<x-mail::message>
# Attendance Reminder

Dear {{ $user->name }},

This is a reminder that attendance for **{{ $moduleName }}** on **{{ $date }}** has not been submitted yet.

Please log in to RAMS and submit the attendance as soon as possible.

<x-mail::button :url="url('/')">
Login to RAMS
</x-mail::button>

Thanks,<br>
RAMS Team
</x-mail::message>
