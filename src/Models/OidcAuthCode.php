<?php

namespace Mrmaniak\Seat\IdentityProvider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OidcAuthCode extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'oidc_auth_codes';

    protected $fillable = [
        'id',
        'user_id',
        'client_id',
        'scopes',
        'revoked',
        'expires_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the application that owns the auth code.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(OidcApplication::class, 'client_id', 'client_id');
    }
}
