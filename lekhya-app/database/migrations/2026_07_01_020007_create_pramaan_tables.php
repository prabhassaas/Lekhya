<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lekhya Pramaan (CA Edition) tables
    public function up(): void
    {
        Schema::create('udin_register', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('udin', 25)->unique();
            $table->string('membership_number', 15);
            $table->string('document_type', 100); // Tax Audit, Balance Sheet, etc.
            $table->date('document_date');
            $table->string('client_name');
            $table->string('client_pan', 10)->nullable();
            $table->text('particulars')->nullable();
            $table->enum('status', ['generated', 'revoked'])->default('generated');
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dsc_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('holder_name');
            $table->string('cn'); // Common Name from certificate
            $table->date('valid_from');
            $table->date('valid_to');
            $table->string('certificate_path'); // encrypted storage path
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('form_type', 20); // 3CA, 3CB, 3CD, 3CEB, Schedule_III
            $table->string('financial_year', 7); // 2024-25
            $table->enum('status', ['draft', 'under_review', 'signed', 'filed'])->default('draft');
            $table->foreignId('preparer_id')->constrained('users');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('udin_id')->nullable()->constrained('udin_register')->nullOnDelete();
            $table->foreignId('dsc_id')->nullable()->constrained('dsc_certificates')->nullOnDelete();
            $table->json('report_data')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('compliance_calendar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); // CA firm tenant
            $table->foreignId('client_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('client_name')->nullable(); // for external clients
            $table->string('compliance_type', 50); // GST, TDS, ROC, AdvanceTax, Audit, ITR
            $table->string('period', 20);
            $table->date('due_date');
            $table->enum('status', ['pending', 'in_progress', 'filed', 'overdue'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'due_date', 'status']);
        });

        Schema::create('working_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audit_report_id')->nullable()->constrained('audit_reports')->nullOnDelete();
            $table->string('title');
            $table->string('category', 50)->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('notice_tracker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('notice_type', 100); // GST Notice, IT Notice, etc.
            $table->string('notice_number', 50)->nullable();
            $table->date('notice_date');
            $table->date('response_due_date')->nullable();
            $table->string('authority', 100)->nullable(); // GST Dept, IT Dept
            $table->text('subject')->nullable();
            $table->enum('status', ['received', 'in_progress', 'replied', 'closed', 'appealed'])->default('received');
            $table->string('file_path')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_tracker');
        Schema::dropIfExists('working_papers');
        Schema::dropIfExists('compliance_calendar');
        Schema::dropIfExists('audit_reports');
        Schema::dropIfExists('dsc_certificates');
        Schema::dropIfExists('udin_register');
    }
};
