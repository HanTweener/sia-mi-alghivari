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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level'); // 1 to 6
            $table->string('section', 32); // e.g. "A", "Ibnu Sina"
            $table->unsignedInteger('roster_version')->default(1);
            $table->timestamps();

            $table->unique(['term_id', 'grade_level', 'section']);
            $table->index(['grade_level', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
