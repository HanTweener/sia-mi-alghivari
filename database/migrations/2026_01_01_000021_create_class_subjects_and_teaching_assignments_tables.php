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
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['classroom_id', 'subject_id']);
        });

        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_subject_id')->constrained('class_subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable(); // NULL = berlangsung aktif
            $table->string('status', 16)->default('assigned'); // assigned, cancelled
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['class_subject_id', 'status']);
            $table->index(['teacher_id', 'status']);
        });

        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_subject_id')->constrained('class_subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1 = Senin, 2 = Selasa, ... 7 = Ahad
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();

            $table->index(['class_subject_id', 'weekday']);
            $table->index(['weekday', 'starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_slots');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('class_subjects');
    }
};
