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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('source_audit_id')->nullable()->constrained('audit_logs')->nullOnDelete();
            $table->uuid('batch_id')->nullable();
            $table->string('event_category', 64);
            $table->string('scope_type', 64)->nullable();
            $table->string('scope_id', 64)->nullable();
            $table->text('sanitized_summary');
            $table->unsignedInteger('visible_item_count')->default(1);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'read_at']);
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
