<?php

namespace Mrmaniak\Seat\IdentityProvider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Mrmaniak\Seat\IdentityProvider\OAuth\Repositories\ScopeRepository;

class OidcApplication extends Model
{
    protected $table = 'oidc_applications';

    protected $fillable = [
        'name',
        'description',
        'client_id',
        'client_secret_hash',
        'redirect_uris',
        'allowed_scopes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'allowed_scopes' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'client_secret_hash',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $application) {
            if (empty($application->client_id)) {
                $application->client_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Generate a new client secret and return the plain text version.
     */
    public function regenerateSecret(): string
    {
        $plainSecret = Str::random(40);
        $this->client_secret_hash = hash('sha256', $plainSecret);
        $this->save();

        return $plainSecret;
    }

    /**
     * Verify a client secret.
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->client_secret_hash, hash('sha256', $secret));
    }

    /**
     * Find an application by client ID.
     */
    public static function findByClientId(string $clientId): ?self
    {
        return self::where('client_id', $clientId)->first();
    }

    /**
     * Backward compatibility: passport_client_id maps to client_id.
     */
    public function getPassportClientIdAttribute(): string
    {
        return $this->client_id;
    }
}
