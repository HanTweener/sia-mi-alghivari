<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Scoring components catalog (F-04)
        Schema::create('scoring_components', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('source_type'); // 'score' or 'attendance'
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Grading policies (F-04)
        Schema::create('grading_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('revision')->unique();
            $table->decimal('kkm', 5, 2);
            $table->string('status')->default('draft'); // 'draft', 'active', 'retired'
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // 3. Grading policy components (weights per component)
        Schema::create('grading_policy_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_policy_id')->constrained('grading_policies')->cascadeOnDelete();
            $table->foreignId('scoring_component_id')->constrained('scoring_components')->restrictOnDelete();
            $table->decimal('weight_percentage', 5, 2);
            $table->timestamps();

            $table->unique(['grading_policy_id', 'scoring_component_id'], 'gpc_policy_component_unique');
        });

        // Seed initial standard components if empty
        $now = now();
        DB::table('scoring_components')->insertOrIgnore([
            [
                'code' => 'assignment',
                'name' => 'Tugas',
                'source_type' => 'score',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'daily_test',
                'name' => 'Ulangan Harian',
                'source_type' => 'score',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'midterm',
                'name' => 'UTS',
                'source_type' => 'score',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'final',
                'name' => 'UAS',
                'source_type' => 'score',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'attendance',
                'name' => 'Kehadiran',
                'source_type' => 'attendance',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_policy_components');
        Schema::dropIfExists('grading_policies');
        Schema::dropIfExists('scoring_components');
    }
};
