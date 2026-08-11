<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewStudent;
use App\Models\Student;
use App\Models\ParentModel;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewStudentController extends Controller
{
    /**
     * Permanent registration link - always the same token
     */
    public const PERMANENT_TOKEN = 'daftar-murid-bimbel-gracia';

    public function index(): View
    {
        $newStudents = NewStudent::latest()->paginate(20);
        $permanentLink = route('register-student.form', self::PERMANENT_TOKEN);
        return view('admin.new-students.index', compact('newStudents', 'permanentLink'));
    }

    public function convert(NewStudent $newStudent): RedirectResponse
    {
        if ($newStudent->converted) {
            return back()->with('status', 'Data ini sudah dikonversi sebelumnya.');
        }

        $parentName = $newStudent->parent_name;
        $parentWhatsapp = $newStudent->whatsapp;
        $address = $newStudent->address;
        $studentsData = $newStudent->students_data ?? [];

        if (empty($studentsData)) {
            return back()->with('error', 'Tidak ada data murid untuk dikonversi.');
        }

        // Cari atau buat parent
        $parentId = null;
        if ($parentWhatsapp) {
            $parent = ParentModel::whereHas('user', fn ($q) => $q->where('phone', $parentWhatsapp))->first();
            if (!$parent) {
                // Buat user parent baru
                $user = User::create([
                    'name' => $parentName ?? 'Orang Tua',
                    'email' => 'parent_' . Str::random(8) . '@bimbelgracia.com',
                    'password' => Hash::make(Str::random(16)),
                    'role' => UserRole::Parent,
                    'phone' => $parentWhatsapp,
                ]);
                $parent = ParentModel::create([
                    'user_id' => $user->id,
                    'name' => $parentName,
                ]);
            }
            $parentId = $parent->id;
        }

        $convertedCount = 0;
        foreach ($studentsData as $studentData) {
            $studentName = $studentData['name'] ?? '';
            if (empty($studentName)) continue;

            // Check existing student by name + parent
            $existingStudent = null;
            if ($parentId) {
                $existingStudent = Student::where('parent_id', $parentId)
                    ->where('name', $studentName)
                    ->first();
            }

            if (!$existingStudent) {
                Student::create([
                    'name' => $studentName,
                    'parent_id' => $parentId,
                    'address' => $address ?? ($parent?->students()->first()?->address ?? null),
                    'status' => 'active',
                ]);
                $convertedCount++;
            }
        }

        $newStudent->update(['converted' => true]);

        return redirect()
            ->route('admin.new-students.index')
            ->with('status', "{$convertedCount} murid berhasil ditambahkan ke data murid.");
    }

    public function destroy(NewStudent $newStudent): RedirectResponse
    {
        $newStudent->delete();
        return redirect()
            ->route('admin.new-students.index')
            ->with('status', 'Data pendaftar dihapus.');
    }

    public function destroyAll(): RedirectResponse
    {
        NewStudent::truncate();
        return redirect()
            ->route('admin.new-students.index')
            ->with('status', 'Semua data pendaftar berhasil dihapus.');
    }
}