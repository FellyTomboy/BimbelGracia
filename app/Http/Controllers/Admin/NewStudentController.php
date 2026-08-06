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
    public function index(): View
    {
        $newStudents = NewStudent::latest()->paginate(20);
        return view('admin.new-students.index', compact('newStudents'));
    }

    public function generateLink(): RedirectResponse
    {
        $newStudent = NewStudent::create([
            'name' => 'Formulir Pendaftaran',
            'token' => Str::random(32),
        ]);

        return redirect()
            ->route('admin.new-students.index')
            ->with('status', 'Link form berhasil dibuat. Salin link di bawah.')
            ->with('generated_link', $newStudent->form_url);
    }

    public function convert(NewStudent $newStudent): RedirectResponse
    {
        if ($newStudent->converted) {
            return back()->with('status', 'Data ini sudah dikonversi sebelumnya.');
        }

        // Cek apakah nomor whatsapp sudah ada di students
        $existingStudent = null;
        if ($newStudent->whatsapp) {
            $existingStudent = Student::where('whatsapp', $newStudent->whatsapp)->first();
        }

        if ($existingStudent) {
            // Nomor sudah ada, berarti satu parent yang sama
            // Update parent_id jika ada parent_whatsapp
            if ($newStudent->parent_whatsapp) {
                $parent = ParentModel::whereHas('user', fn ($q) => $q->where('phone', $newStudent->parent_whatsapp))->first();
                if ($parent) {
                    $existingStudent->update(['parent_id' => $parent->id]);
                }
            }
            $newStudent->update(['converted' => true]);
            return redirect()
                ->route('admin.new-students.index')
                ->with('status', 'Nomor WA sudah terdaftar. Murid digabung ke parent yang sama.');
        }

        // Buat student baru
        // Cari atau buat parent
        $parentId = null;
        if ($newStudent->parent_whatsapp) {
            $parent = ParentModel::whereHas('user', fn ($q) => $q->where('phone', $newStudent->parent_whatsapp))->first();
            if (!$parent) {
                // Buat user parent baru
                $user = User::create([
                    'name' => $newStudent->parent_name ?? 'Orang Tua ' . $newStudent->name,
                    'email' => 'parent_' . Str::random(8) . '@bimbelgracia.com',
                    'password' => Hash::make(Str::random(16)),
                    'role' => UserRole::Parent,
                    'phone' => $newStudent->parent_whatsapp,
                ]);
                $parent = ParentModel::create([
                    'user_id' => $user->id,
                    'name' => $newStudent->parent_name,
                ]);
            }
            $parentId = $parent->id;
        }

        Student::create([
            'name' => $newStudent->name,
            'whatsapp' => $newStudent->whatsapp,
            'parent_id' => $parentId,
            'school' => $newStudent->school,
            'grade' => $newStudent->grade,
            'status' => 'active',
        ]);

        $newStudent->update(['converted' => true]);

        return redirect()
            ->route('admin.new-students.index')
            ->with('status', 'Data berhasil ditambahkan ke murid.');
    }

    public function destroy(NewStudent $newStudent): RedirectResponse
    {
        $newStudent->delete();
        return redirect()
            ->route('admin.new-students.index')
            ->with('status', 'Data pendaftar dihapus.');
    }
}