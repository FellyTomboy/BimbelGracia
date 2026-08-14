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
        $parentWhatsapp = $this->cleanPhone($newStudent->whatsapp);
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
            $nickname = trim((string) ($studentData['nickname'] ?? $studentData['name'] ?? ''));
            if ($nickname === '') continue;

            $fullName = trim((string) ($studentData['full_name'] ?? '')) ?: null;

            // Check existing student by nickname + parent
            $existingStudent = null;
            if ($parentId) {
                $existingStudent = Student::where('parent_id', $parentId)
                    ->where('nickname', $nickname)
                    ->first();
            }

            if (!$existingStudent) {
                Student::create([
                    'nickname' => $nickname,
                    'full_name' => $fullName,
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

    private function cleanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Keep as 08XXXXXXXXX format in database
        if (strlen($phone) > 13) {
            $phone = substr($phone, -13);
        }

        // Ensure starts with 08
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        } elseif (! str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}
