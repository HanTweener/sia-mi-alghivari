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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 32)->unique(); // Nomor Induk Siswa
            $table->string('name', 255);
            $table->string('gender', 1)->default('L'); // L / P
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 64)->nullable();
            $table->string('status', 16)->default('active'); // active, graduated, transferred, inactive
            $table->timestamps();

            $table->index('status');
            $table->index('name');
        });

        Schema::create('student_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('guardian_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship', 32)->default('wali'); // ayah, ibu, wali
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'guardian_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
        Schema::dropIfExists('students');
    }
};
