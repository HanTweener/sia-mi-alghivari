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
        // 1. Add non-unique index first so MySQL foreign key on student_id remains satisfied
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['student_id', 'term_id'], 'enrollments_student_id_term_id_idx');
        });

        // 2. Drop unique constraint and add transfer audit columns
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollments_student_id_term_id_unique');
            $table->foreignId('transferred_from_enrollment_id')
                ->nullable()
                ->after('status')
                ->constrained('enrollments')
                ->nullOnDelete();
            $table->text('transfer_reason')
                ->nullable()
                ->after('transferred_from_enrollment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['transferred_from_enrollment_id']);
            $table->dropColumn(['transferred_from_enrollment_id', 'transfer_reason']);
            $table->unique(['student_id', 'term_id'], 'enrollments_student_id_term_id_unique');
            $table->dropIndex('enrollments_student_id_term_id_idx');
        });
    }
};
