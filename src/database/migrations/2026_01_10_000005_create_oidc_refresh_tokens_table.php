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
        Schema::create('oidc_refresh_tokens', function (Blueprint $table) {
            $table->string('id', 100)->primary();

            // Link to access token
            $table->string('access_token_id', 100)->index();

            // Refresh token details
            $table->boolean('revoked')->default(false);
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->foreign('access_token_id')
                ->references('id')
                ->on('oidc_access_tokens')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oidc_refresh_tokens');
    }
};
