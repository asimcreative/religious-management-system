<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $queue = 'high';

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly User $user,
        public readonly string $moduleName,
        public readonly string $date,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Attendance Reminder — {$this->moduleName} ({$this->date})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance-reminder',
        );
    }
}
