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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_subject_id')->constrained('class_subjects')->cascadeOnDelete();
            $table->foreignId('teaching_assignment_id')->nullable()->constrained('teaching_assignments')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scoring_component_id')->constrained('scoring_components')->cascadeOnDelete();
            $table->string('title');
            $table->date('assessed_on');
            $table->string('status', 20)->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['class_subject_id', 'status', 'assessed_on', 'id'], 'assessments_lookup_idx');
            $table->index('scoring_component_id');
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->unsigned();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delegation_id')->nullable()->constrained('data_entry_delegations')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assessment_id', 'enrollment_id']);
            $table->index('enrollment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
        Schema::dropIfExists('assessments');
    }
};
