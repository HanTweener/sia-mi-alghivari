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
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_reset_proof_hash', 64)->nullable()->after('pending_session_choice_flow');
            $table->timestamp('password_reset_proof_expires_at')->nullable()->after('password_reset_proof_hash');
            $table->unsignedInteger('password_reset_proof_version')->nullable()->after('password_reset_proof_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'password_reset_proof_hash',
                'password_reset_proof_expires_at',
                'password_reset_proof_version',
            ]);
        });
    }
};
