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
        Schema::create('oidc_keypair', function (Blueprint $table) {
            $table->id();

            // RSA key pair for signing tokens
            $table->text('public_key');
            $table->text('private_key');

            // Key metadata
            $table->string('algorithm')->default('RS256');
            $table->string('key_id', 64);

            // Key status
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->unique('key_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oidc_keypair');
    }
};
