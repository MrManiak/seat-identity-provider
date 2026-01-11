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
        Schema::create('oidc_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();

            // OAuth2 client credentials
            $table->uuid('client_id')->unique();
            $table->string('client_secret_hash', 64);

            // Redirect URIs (JSON array)
            $table->json('redirect_uris');

            // Allowed scopes for this application
            $table->json('allowed_scopes')->default('["openid"]');

            // Application status
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oidc_applications');
    }
};
