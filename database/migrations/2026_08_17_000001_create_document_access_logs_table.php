<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 20); // 'view', 'download', 'preview'
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['document_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_logs');
    }
};
</｜DSML｜>
<task_progress>
- [x] Audit existing document security implementation
- [x] Read and understand all document-related files
- [x] Identify bugs, authorization bypasses, and missing features
- [ ] Implement server-side authorization fixes
- [ ] Add download/print/copy prevention, watermark, no-store headers
- [ ] Ensure admin full access preserved
- [ ] Run migrations, route check, tests/lint
- [ ] Regression check on document features
</task_progress>
</write_to_file>