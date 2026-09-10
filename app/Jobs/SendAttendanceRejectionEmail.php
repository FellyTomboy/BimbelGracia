<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\AttendanceRejectionMail;
use App\Models\MonthlyAttendance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAttendanceRejectionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff  = 60;
    public int $timeout  = 30;

    public function __construct(
        public readonly MonthlyAttendance $attendance,
        public readonly string            $rejectionReason,
        public readonly ?string           $rejectedByName,
    ) {}

    public function handle(): void
    {
        $admins = User::where('role', UserRole::Admin)
            ->whereNotNull('email')
            ->get();

        if ($admins->isEmpty()) {
            Log::warning('SendAttendanceRejectionEmail: no admin users with email found.', [
                'attendance_id' => $this->attendance->id,
            ]);

            return;
        }

        $mailable = new AttendanceRejectionMail(
            $this->attendance,
            $this->rejectionReason,
            $this->rejectedByName,
        );

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send($mailable);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendAttendanceRejectionEmail job failed permanently.', [
            'attendance_id' => $this->attendance->id,
            'error' => $e->getMessage(),
        ]);
    }
}
