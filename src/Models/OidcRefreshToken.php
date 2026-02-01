<?php

namespace Mrmaniak\Seat\IdentityProvider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OidcRefreshToken extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'oidc_refresh_tokens';

    protected $fillable = [
        'id',
        'access_token_id',
        'revoked',
        'expires_at',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the access token that owns the refresh token.
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(OidcAccessToken::class, 'access_token_id', 'id');
    }
}
