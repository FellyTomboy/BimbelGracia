<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Salary;
use App\Models\Teacher;
use App\Services\Pdf\InvoiceService;
use Illuminate\Console\Command;

class RegenerateMonthlyPdfs extends Command
{
    protected $signature = 'app:regenerate-monthly-pdfs {--month=} {--year=}';

    protected $description = 'Regenerate all PDF invoices and salary slips for a month';

    public function handle(InvoiceService $invoiceService): int
    {
        $month = (int) ($this->option('month') ?? now()->month);
        $year = (int) ($this->option('year') ?? now()->year);

        $this->info("Regenerating PDFs for {$month}/{$year}");
        $this->newLine();

        $this->regenerateParentInvoices($month, $year, $invoiceService);
        $this->regenerateTeacherSalaries($month, $year, $invoiceService);

        $this->newLine();
        $this->info('Done.');

        return Command::SUCCESS;
    }

    private function regenerateParentInvoices(int $month, int $year, InvoiceService $invoiceService): void
    {
        $this->info('--- PARENT INVOICES ---');

        $parents = ParentModel::with('students')->get();
        $count = 0;

        foreach ($parents as $parent) {
            $students = $parent->students;
            if ($students->isEmpty()) {
                continue;
            }

            $attendances = MonthlyAttendance::query()
                ->with(['enrollment.program', 'enrollment.teacher', 'sessionTeacher', 'students'])
                ->whereIn('status_validation', ['terima', 'terlambat'])
                ->where('month', $month)
                ->where('year', $year)
                ->whereHas('students', fn ($q) => $q->whereIn('students.id', $students->pluck('id')))
                ->get();

            if ($attendances->isEmpty()) {
                continue;
            }

            $invoice = Invoice::firstOrCreate(
                ['parent_id' => $parent->id, 'month' => $month, 'year' => $year],
                ['filename' => '']
            );

            $result = $invoiceService->generateParentInvoice(
                $students, $month, $year, $attendances,
                $invoice->filename ?: null
            );

            $newBasename = basename($result['storage_path']);
            if ($invoice->filename !== $newBasename) {
                $invoice->filename = $newBasename;
                $invoice->regenerated_at = now();
                $invoice->save();
            }

            $count++;
            $this->line("  ✓ {$parent->name}");
        }

        $this->info("  Total: {$count}");
    }

    private function regenerateTeacherSalaries(int $month, int $year, InvoiceService $invoiceService): void
    {
        $this->info('--- TEACHER SALARY SLIPS ---');

        $teachers = Teacher::where('status', 'active')->get();
        $count = 0;

        foreach ($teachers as $teacher) {
            $enrolledAttendances = MonthlyAttendance::query()
                ->with(['enrollment.program', 'enrollment.teacher', 'students'])
                ->whereIn('status_validation', ['terima', 'terlambat'])
                ->where('month', $month)
                ->where('year', $year)
                ->whereHas('enrollment', fn ($q) => $q->where('teacher_id', $teacher->id))
                ->get();

            $teacherOnly = MonthlyAttendance::query()
                ->whereIn('status_validation', ['terima', 'terlambat'])
                ->where('month', $month)
                ->where('year', $year)
                ->whereNull('enrollment_id')
                ->where('session_teacher_id', $teacher->id)
                ->get();

            $allAttendances = $enrolledAttendances->merge($teacherOnly);

            if ($allAttendances->isEmpty()) {
                continue;
            }

            $salary = Salary::firstOrCreate(
                ['teacher_id' => $teacher->id, 'month' => $month, 'year' => $year],
                ['filename' => '']
            );

            $result = $invoiceService->generateTeacherSalarySlip(
                $teacher, $month, $year, $allAttendances,
                $salary->filename ?: null
            );

            $newBasename = basename($result['storage_path']);
            if ($salary->filename !== $newBasename) {
                $salary->filename = $newBasename;
                $salary->regenerated_at = now();
                $salary->save();
            }

            $count++;
            $this->line("  ✓ {$teacher->displayName}");
        }

        $this->info("  Total: {$count}");
    }
}
