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
        // 1. Ekstrakurikuler (kegiatan, predikat A/B/C/D, deskripsi kualitatif)
        Schema::create('extracurricular_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->string('activity_name', 100);
            $table->string('predicate', 2); // A, B, C, D
            $table->text('description');
            $table->timestamps();

            $table->index('report_card_id');
        });

        // 2. Asesmen Kokurikuler P5-PRA (Kurikulum Merdeka)
        Schema::create('p5_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->string('project_title', 255);
            $table->string('dimension', 100);
            $table->string('predicate', 8); // BB, MB, BSH, SAB
            $table->text('description');
            $table->timestamps();

            $table->index('report_card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p5_assessments');
        Schema::dropIfExists('extracurricular_assessments');
    }
};
