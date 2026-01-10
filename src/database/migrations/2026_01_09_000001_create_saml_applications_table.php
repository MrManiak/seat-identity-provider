<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saml_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('entity_id')->unique();
            $table->string('acs_url');
            $table->string('slo_url')->nullable();
            $table->text('certificate')->nullable();
            $table->string('metadata_url')->nullable();
            $table->string('name_id_format')->default('urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saml_applications');
    }
};
