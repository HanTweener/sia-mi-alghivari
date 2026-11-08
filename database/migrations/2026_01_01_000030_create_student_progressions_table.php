<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_progressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('source_academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('source_report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->string('type', 20); // promotion, graduation, retention
            $table->foreignId('target_academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('target_classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft, submitted, approved, rejected, applied, cancelled, needs_review
            $table->unsignedInteger('version')->default(1); // optimistic locking
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'source_academic_year_id'], 'sp_student_year_unique');
            $table->index('status');
            $table->index(['student_id', 'status'], 'sp_student_status_idx');
            $table->index(['source_academic_year_id', 'status', 'id'], 'sp_source_year_status_idx');
            $table->index(['target_academic_year_id', 'target_classroom_id'], 'sp_target_year_class_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progressions');
    }
};
