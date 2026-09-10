<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\MonthlyAttendance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly MonthlyAttendance $attendance,
        public readonly string            $rejectionReason,
        public readonly ?string          $rejectedByName,
    ) {}

    public function envelope(): Envelope
    {
        $studentName = $this->attendance->students->first()?->display_name ?? 'Murid';

        return new Envelope(
            subject: "[Bimbel Gracia] Penolakan Presensi - {$studentName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance-rejection',
            text: 'emails.attendance-rejection-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
