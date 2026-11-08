<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Data Entry Delegations table (Q6.3)
        Schema::create('data_entry_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teaching_assignment_id')->constrained('teaching_assignments')->cascadeOnDelete();
            $table->string('capability', 50); // 'attendance_entry', 'grade_entry'
            $table->date('valid_from');
            $table->date('valid_until');
            $table->foreignId('granted_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'teaching_assignment_id', 'capability'], 'delegation_user_assignment_cap_idx');
        });

        // 2. Attendance Sessions table (F-05)
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_subject_id')->constrained('class_subjects')->cascadeOnDelete();
            $table->foreignId('teaching_assignment_id')->nullable()->constrained('teaching_assignments')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->integer('meeting_number');
            $table->date('held_on');
            $table->string('topic')->nullable();
            $table->string('status', 20)->default('draft'); // 'draft', 'finalized'
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->unique(['class_subject_id', 'meeting_number']);
            $table->index(['class_subject_id', 'status', 'held_on']);
        });

        // 3. Attendance Records table (F-05)
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('status', 20); // 'present', 'excused', 'sick', 'absent'
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('delegation_id')->nullable()->constrained('data_entry_delegations')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'enrollment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('data_entry_delegations');
    }
};
