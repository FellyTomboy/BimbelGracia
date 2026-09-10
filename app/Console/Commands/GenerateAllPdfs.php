<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Salary;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Pdf\InvoiceService;
use Illuminate\Console\Command;

class GenerateAllPdfs extends Command
{
    protected $signature = 'app:generate-all-pdfs {--month=8} {--year=2026}';

    protected $description = 'Generate all PDF invoices for parents and salary slips for teachers';

    public function handle(): int
    {
        $month = (int) $this->option('month');
        $year = (int) $this->option('year');

        $this->info("Generating PDFs for month {$month}/{$year}");
        $this->newLine();

        // ====================================
        // 1. Parent Invoices
        // ====================================
        $this->info('--- PARENT INVOICES ---');

        $invoiceService = app(InvoiceService::class);
        $parents = ParentModel::with('students')->get();
        $parentCount = 0;

        foreach ($parents as $parent) {
            $students = $parent->students;

            if ($students->isEmpty()) {
                $this->warn("  Parent {$parent->name}: No students, skipped.");
                continue;
            }

            $studentIds = $students->pluck('id');

            $attendances = MonthlyAttendance::query()
                ->with(['enrollment.program', 'enrollment.teacher', 'students'])
                ->whereIn('status_validation', ['terima', 'terlambat'])
                ->where('month', $month)
                ->where('year', $year)
                ->whereHas('students', fn ($q) => $q->whereIn('students.id', $studentIds))
                ->get();

            if ($attendances->isEmpty()) {
                $this->warn("  Parent {$parent->name}: No attendance data for period, skipped.");
                continue;
            }

            try {
                $invoice = Invoice::firstOrCreate(
                    ['parent_id' => $parent->id, 'month' => $month, 'year' => $year],
                    ['filename' => '']
                );
                $result = $invoiceService->generateParentInvoice(
                    $students, $month, $year, $attendances,
                    $invoice->filename ?: null
                );
                // Use full storage path (not just basename) so subsequent runs reuse same file
                if ($invoice->filename !== $result['storage_path']) {
                    $invoice->filename = $result['storage_path'];
                    $invoice->regenerated_at = now();
                    $invoice->save();
                }
                $this->info("  ✓ {$parent->name} -> storage/app/public/{$result['storage_path']}");
                $parentCount++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$parent->name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Total parent invoices generated: {$parentCount}");
        $this->newLine();

        // ====================================
        // 2. Teacher Salary Slips
        // ====================================
        $this->info('--- TEACHER SALARY SLIPS ---');

        $teachers = Teacher::where('status', 'active')->get();
        $teacherCount = 0;

        foreach ($teachers as $teacher) {
            $attendances = MonthlyAttendance::query()
                ->with(['enrollment.program', 'enrollment.teacher', 'students'])
                ->whereIn('status_validation', ['terima', 'terlambat'])
                ->where('month', $month)
                ->where('year', $year)
                ->whereHas('enrollment', fn ($q) => $q->where('teacher_id', $teacher->id))
                ->get();

            if ($attendances->isEmpty()) {
                $this->warn("  Teacher {$teacher->full_name}: No attendance data for period, skipped.");
                continue;
            }

            try {
                $salary = Salary::firstOrCreate(
                    ['teacher_id' => $teacher->id, 'month' => $month, 'year' => $year],
                    ['filename' => '']
                );
                $result = $invoiceService->generateTeacherSalarySlip(
                    $teacher, $month, $year, $attendances,
                    $salary->filename ?: null
                );
                // Use full storage path (not just basename) so subsequent runs reuse same file
                if ($salary->filename !== $result['storage_path']) {
                    $salary->filename = $result['storage_path'];
                    $salary->regenerated_at = now();
                    $salary->save();
                }
                $this->info("  ✓ {$teacher->full_name} -> storage/app/public/{$result['storage_path']}");
                $teacherCount++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$teacher->full_name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Total teacher salary slips generated: {$teacherCount}");
        $this->newLine();
        $this->info('=== ALL PDFs GENERATED SUCCESSFULLY ===');

        return Command::SUCCESS;
    }
}