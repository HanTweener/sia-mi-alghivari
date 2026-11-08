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
        // 1. Report Cards table (D-28, Q7.1, F-08)
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('grading_policy_id')->constrained('grading_policies')->restrictOnDelete();
            $table->foreignId('homeroom_assignment_id')->nullable()->constrained('homeroom_assignments')->nullOnDelete();
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('supersedes_id')->nullable()->constrained('report_cards')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft, finalized, published, revoked
            $table->string('availability', 20)->default('current'); // current, needs_revision, superseded
            $table->unsignedInteger('version')->default(1); // optimistic concurrency control
            $table->string('source_version', 100)->nullable(); // SHA-256 consistency token of source data
            $table->text('homeroom_note')->nullable();
            $table->string('calculation_version', 20)->default('1.0');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('lifecycle_reason')->nullable();
            $table->json('attendance_summary')->nullable();
            $table->json('identity_snapshot')->nullable();
            $table->string('private_file_path')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'revision']);
            $table->index(['enrollment_id', 'status']);
            $table->index(['term_id', 'status']);
            $table->index(['status', 'availability']);
        });

        // 2. Report Card Items table (One item per class_subject, frozen snapshot)
        Schema::create('report_card_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->foreignId('class_subject_id')->constrained('class_subjects')->cascadeOnDelete();
            $table->json('subject_snapshot');
            $table->json('teaching_history_snapshot')->nullable();
            $table->json('component_breakdown_snapshot');
            $table->decimal('kkm_snapshot', 5, 2);
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('kkm_status', 50);
            $table->timestamps();

            $table->unique(['report_card_id', 'class_subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_items');
        Schema::dropIfExists('report_cards');
    }
};
