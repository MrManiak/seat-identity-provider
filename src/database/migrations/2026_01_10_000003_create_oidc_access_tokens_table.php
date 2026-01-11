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
        Schema::create('oidc_access_tokens', function (Blueprint $table) {
            $table->string('id', 100)->primary();

            // Token ownership
            $table->unsignedBigInteger('user_id')->index();
            $table->string('client_id', 36)->index();

            // Token details
            $table->json('scopes')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->foreign('client_id')
                ->references('client_id')
                ->on('oidc_applications')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oidc_access_tokens');
    }
};
