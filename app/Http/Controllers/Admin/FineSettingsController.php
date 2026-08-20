<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFineSettingsRequest;
use App\Services\AttendanceFineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class FineSettingsController extends Controller
{
    public function __construct(private AttendanceFineService $fineService) {}

    public function update(UpdateFineSettingsRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->role?->value === 'admin', 403);

        $attendanceEnabled = $request->boolean('attendance_penalty_enabled') ? 'true' : 'false';
        $lateEnabled = $request->boolean('late_penalty_enabled') ? 'true' : 'false';

        \DB::table('settings')->updateOrInsert(
            ['key' => AttendanceFineService::KEY_ATTENDANCE_PENALTY],
            ['value' => $attendanceEnabled, 'updated_at' => now()]
        );
        \DB::table('settings')->updateOrInsert(
            ['key' => AttendanceFineService::KEY_LATE_PENALTY],
            ['value' => $lateEnabled, 'updated_at' => now()]
        );

        $this->fineService->invalidateCache();

        return Redirect::route('profile.edit')->with('status', 'fine-settings-updated');
    }

    public function reset(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()?->role?->value === 'admin', 403);

        \DB::table('settings')->updateOrInsert(
            ['key' => AttendanceFineService::KEY_ATTENDANCE_PENALTY],
            ['value' => 'false', 'updated_at' => now()]
        );
        \DB::table('settings')->updateOrInsert(
            ['key' => AttendanceFineService::KEY_LATE_PENALTY],
            ['value' => 'false', 'updated_at' => now()]
        );

        $this->fineService->invalidateCache();

        return Redirect::route('profile.edit')->with('status', 'fine-settings-updated');
    }
}
