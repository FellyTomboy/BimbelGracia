<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupOldFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_command_deletes_old_files(): void
    {
        Storage::fake('public');

        // Create old files (older than 6 months)
        $oldFile = 'payment-proofs/old-proof.jpg';
        Storage::disk('public')->put($oldFile, 'old content');
        Storage::disk('public')->lastModified($oldFile); // just to ensure it exists

        // Manually set the timestamp to 7 months ago by creating a file and then using touch
        touch(Storage::disk('public')->path($oldFile), now()->subMonths(7)->timestamp);

        // Create new file (should not be deleted)
        $newFile = 'payment-proofs/new-proof.jpg';
        Storage::disk('public')->put($newFile, 'new content');
        touch(Storage::disk('public')->path($newFile), now()->subMonth()->timestamp);

        // Run the cleanup command
        $this->artisan('cleanup:old-files')
            ->assertSuccessful();

        // Assert old file was deleted
        $this->assertFalse(Storage::disk('public')->exists($oldFile));

        // Assert new file still exists
        $this->assertTrue(Storage::disk('public')->exists($newFile));
    }

    public function test_cleanup_handles_empty_directories(): void
    {
        Storage::fake('public');

        Storage::disk('public')->makeDirectory('invoice/test');
        Storage::disk('public')->makeDirectory('salary');

        $this->artisan('cleanup:old-files')
            ->assertSuccessful();
    }

    public function test_cleanup_removes_invoice_and_salary_files(): void
    {
        Storage::fake('public');

        // Create old invoice
        $oldInvoice = 'invoice/1/Student_2026-01.pdf';
        Storage::disk('public')->put($oldInvoice, 'old invoice');
        touch(Storage::disk('public')->path($oldInvoice), now()->subMonths(7)->timestamp);

        // Create old salary slip
        $oldSalary = 'salary/1/Teacher_2026-01.pdf';
        Storage::disk('public')->put($oldSalary, 'old salary');
        touch(Storage::disk('public')->path($oldSalary), now()->subMonths(7)->timestamp);

        $this->artisan('cleanup:old-files')
            ->assertSuccessful();

        $this->assertFalse(Storage::disk('public')->exists($oldInvoice));
        $this->assertFalse(Storage::disk('public')->exists($oldSalary));
    }
}