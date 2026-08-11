<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherRegistrant;
use App\Models\Teacher;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherRegistrantController extends Controller
{
    /**
     * Permanent registration link token
     */
    public const PERMANENT_TOKEN = 'daftar-guru-bimbel-gracia';

    public function index(): View
    {
        $teacherRegistrants = TeacherRegistrant::latest()->paginate(20);
        $permanentLink = route('register-teacher.form', self::PERMANENT_TOKEN);
        return view('admin.teacher-registrants.index', compact('teacherRegistrants', 'permanentLink'));
    }

    public function convert(TeacherRegistrant $teacherRegistrant): RedirectResponse
    {
        if ($teacherRegistrant->converted) {
            return back()->with('status', 'Data ini sudah dikonversi sebelumnya.');
        }

        // Check if whatsapp already exists
        $existingTeacher = Teacher::where('whatsapp', $teacherRegistrant->whatsapp)->first();
        if ($existingTeacher) {
            $teacherRegistrant->update(['converted' => true]);
            return redirect()
                ->route('admin.teacher-registrants.index')
                ->with('status', 'Nomor WA sudah terdaftar sebagai guru.');
        }

        // Create teacher user
        $user = User::create([
            'name' => $teacherRegistrant->name,
            'email' => 'guru_' . Str::random(8) . '@bimbelgracia.com',
            'password' => Hash::make(Str::random(16)),
            'role' => UserRole::Guru,
            'phone' => $teacherRegistrant->whatsapp,
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'name' => $teacherRegistrant->name,
            'whatsapp' => $teacherRegistrant->whatsapp,
            'major' => $teacherRegistrant->major,
            'subjects' => $teacherRegistrant->subjects,
            'address' => $teacherRegistrant->address,
            'bank_name' => $teacherRegistrant->bank_name,
            'bank_account' => $teacherRegistrant->bank_account,
            'bank_owner' => $teacherRegistrant->bank_owner,
            'status' => 'active',
        ]);

        $teacherRegistrant->update(['converted' => true]);

        return redirect()
            ->route('admin.teacher-registrants.index')
            ->with('status', 'Data guru berhasil ditambahkan.');
    }

    public function destroy(TeacherRegistrant $teacherRegistrant): RedirectResponse
    {
        $teacherRegistrant->delete();
        return redirect()
            ->route('admin.teacher-registrants.index')
            ->with('status', 'Data pendaftar guru dihapus.');
    }

    public function destroyAll(): RedirectResponse
    {
        TeacherRegistrant::truncate();
        return redirect()
            ->route('admin.teacher-registrants.index')
            ->with('status', 'Semua data pendaftar guru berhasil dihapus.');
    }
}