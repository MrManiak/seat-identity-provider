<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saml_applications', function (Blueprint $table) {
            $table->text('idp_x509_certificate')->nullable()->after('certificate');
            $table->text('idp_private_key')->nullable()->after('idp_x509_certificate');
        });
    }

    public function down(): void
    {
        Schema::table('saml_applications', function (Blueprint $table) {
            $table->dropColumn(['idp_x509_certificate', 'idp_private_key']);
        });
    }
};
