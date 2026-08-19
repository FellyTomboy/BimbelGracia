<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AttendanceFineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private AttendanceFineService $fineService) {}
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $founders = collect();
        if ($request->user()?->role?->value === 'admin') {
            $founders = \App\Models\Teacher::query()
                ->where('is_founder', true)
                ->orderBy('id')
                ->get();

            // Auto-create 2 founder slots if none exist
            if ($founders->isEmpty()) {
                $defaultNames = ['Founder 1', 'Founder 2'];
                foreach ($defaultNames as $name) {
                    $founder = \App\Models\Teacher::create([
                        'name' => $name,
                        'status' => 'active',
                        'is_founder' => true,
                        'profile_photo_approved' => true,
                    ]);
                    $founders->push($founder);
                }
            }
        }

        return view('profile.edit', [
            'user' => $request->user(),
            'founders' => $founders,
            'fineSettings' => [
                'attendance_penalty_enabled' => $this->fineService->isAttendancePenaltyEnabled(),
                'late_penalty_enabled' => $this->fineService->isLatePenaltyEnabled(),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Upload profile photo for teacher.
     */
    public function uploadPhoto(Request $request): RedirectResponse
    {
        $teacher = $request->user()?->teacher;
        abort_unless((bool) $teacher, 403);

        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        // Delete old photo if exists
        if ($teacher->profile_photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($teacher->profile_photo_path);
        }

        $file = $validated['profile_photo'];
        $extension = $file->getClientOriginalExtension();
        $teacherSlug = str_replace(' ', '_', strtolower($teacher->name));
        $path = sprintf('photo/profile/%s.%s', $teacherSlug, $extension);
        $file->storeAs(dirname($path), basename($path), 'public');

        $teacher->update([
            'profile_photo_path' => $path,
            'profile_photo_approved' => false, // Reset approval on new upload
        ]);

        return Redirect::route('profile.edit')->with('status', 'photo-uploaded');
    }

    /**
     * Delete profile photo for teacher.
     */
    public function deletePhoto(Request $request): RedirectResponse
    {
        $teacher = $request->user()?->teacher;
        abort_unless((bool) $teacher, 403);

        if ($teacher->profile_photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($teacher->profile_photo_path);
        }

        $teacher->update([
            'profile_photo_path' => null,
            'profile_photo_approved' => false,
        ]);

        return Redirect::route('profile.edit')->with('status', 'photo-deleted');
    }

    /**
     * Upload founder photo (admin only).
     */
    public function uploadFounderPhoto(Request $request, \App\Models\Teacher $teacher): RedirectResponse
    {
        abort_unless($request->user()?->role?->value === 'admin', 403);

        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        if ($teacher->profile_photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($teacher->profile_photo_path);
        }

        $file = $validated['profile_photo'];
        $extension = $file->getClientOriginalExtension();
        $teacherSlug = str_replace(' ', '_', strtolower($teacher->name));
        $path = sprintf('photo/profile/%s.%s', $teacherSlug, $extension);
        $file->storeAs(dirname($path), basename($path), 'public');

        $teacher->update([
            'profile_photo_path' => $path,
            'profile_photo_approved' => true,
        ]);

        return Redirect::route('profile.edit')->with('status', 'photo-uploaded');
    }

    /**
     * Update founder information (admin only).
     */
    public function updateFounder(Request $request, \App\Models\Teacher $teacher): RedirectResponse
    {
        abort_unless($request->user()?->role?->value === 'admin', 403);

        $validated = $request->validate([
            'founder_name' => ['required', 'string', 'max:255'],
            'founder_major' => ['nullable', 'string', 'max:255'],
            'founder_description' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacher->update([
            'name' => $validated['founder_name'],
            'major' => $validated['founder_major'],
            'founder_description' => $validated['founder_description'],
        ]);

        return Redirect::route('profile.edit')->with('status', 'founder-updated');
    }

    /**
     * Update the teacher's bank information.
     */
    public function updateBank(Request $request): RedirectResponse
    {
        $teacher = $request->user()?->teacher;

        abort_unless((bool) $teacher, 403);

        $validated = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_owner' => ['nullable', 'string', 'max:255'],
        ]);

        $teacher->update($validated);

        return Redirect::route('profile.edit')->with('status', 'bank-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
