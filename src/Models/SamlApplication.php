<?php

namespace Mrmaniak\Seat\IdentityProvider\Models;

use Illuminate\Database\Eloquent\Model;

class SamlApplication extends Model
{
    protected $table = 'saml_applications';

    protected $fillable = [
        'name',
        'entity_id',
        'acs_url',
        'slo_url',
        'certificate',
        'metadata_url',
        'name_id_format',
        'is_active',
        'idp_x509_certificate',
        'idp_private_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'idp_private_key',
    ];

    public static function generateCertificate(): array
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKey = openssl_pkey_new($config);

        $dn = [
            'commonName' => config('app.name', 'SeAT') . ' SAML IdP',
            'organizationName' => config('app.name', 'SeAT'),
        ];

        $csr = openssl_csr_new($dn, $privateKey, $config);
        $x509 = openssl_csr_sign($csr, null, $privateKey, 3650, $config);

        openssl_x509_export($x509, $certificate);
        openssl_pkey_export($privateKey, $privateKeyPem);

        // Extract just the certificate content without headers
        $certificateContent = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"],
            '',
            $certificate
        );

        return [
            'certificate' => $certificateContent,
            'private_key' => $privateKeyPem,
        ];
    }
}
