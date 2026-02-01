# SeAT Identity Provider

An Identity Provider plugin for [SeAT](https://github.com/eveseat/seat) that allows you to use your SeAT installation as an enterprise identity provider for third-party services. Supports both **SAML 2.0** and **OpenID Connect (OIDC)** protocols.

## Features

### SAML 2.0
- **SAML 2.0 SSO** - Single Sign-On using SAML 2.0 protocol
- **SAML 2.0 SLO** - Single Logout support
- **Multiple Applications** - Configure multiple Service Providers
- **Automatic Certificate Generation** - RSA 2048-bit X.509 certificates generated per application
- **Metadata Import** - Fetch and parse SP metadata from URL
- **IdP Metadata Export** - Download IdP metadata XML for SP configuration
- **Signed Assertions** - All SAML responses signed with RSA-SHA256

### OpenID Connect
- **Authorization Code Flow** - Standard OIDC authorization code grant
- **Refresh Tokens** - Long-lived sessions with refresh token support
- **Discovery Endpoint** - Auto-configuration via `/.well-known/openid-configuration`
- **JWKS Endpoint** - Public key distribution for token validation
- **UserInfo Endpoint** - Standard claims endpoint
- **Custom Scopes** - EVE Online and SeAT-specific claims
- **Key Management** - Generate and rotate signing keys via UI

## Requirements

- SeAT 5.x
- PHP 8.1+
- OpenSSL extension

## Installation

### Via Composer

```bash
composer require mrmaniak/seat-identity-provider
```

### Manual Installation

1. Clone or download this repository to `packages/mrmaniak/seat-identity-provider`
2. Add the following to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/mrmaniak/seat-identity-provider"
        }
    ]
}
```

3. Run:

```bash
composer require mrmaniak/seat-identity-provider
```

4. Publish and run migrations:

```bash
php artisan migrate
```

---

## SAML 2.0

### SAML Attributes

The following attributes are included in SAML assertions:

| Attribute | Description |
|-----------|-------------|
| `user_id` | SeAT user ID |
| `email` | Generated email in format `seatuser.{user_id}@{seat_domain}` |
| `name` | User's main character name |
| `squads` | Multi-valued attribute containing all squad names the user belongs to |
| `character_id` | EVE Online character ID of the user's main character |
| `corporation_id` | EVE Online corporation ID of the user's main character |
| `is_admin` | Whether the user is a SeAT administrator |

### Creating a SAML Application

1. Navigate to **Identity Provider > SAML Applications** in SeAT
2. Click **Create Application**
3. Fill in the application details:
   - **Application Name**: A friendly name for the application
   - **Entity ID**: The SP's unique identifier (from SP metadata)
   - **ACS URL**: Assertion Consumer Service URL (from SP metadata)
   - **SLO URL**: Single Logout URL (optional)
   - **Name ID Format**: Choose the appropriate format for the SP
   - **SP Certificate**: The SP's X.509 certificate for signature verification (optional)

Alternatively, enter the SP's **Metadata URL** and click **Fetch** to auto-populate fields.

### Configuring the Service Provider

1. From the application edit page, click **Download IdP Metadata**
2. Import the metadata XML into your Service Provider
3. Or manually configure using:
   - **IdP Entity ID**: `https://your-seat-url/saml/idp`
   - **SSO URL**: `https://your-seat-url/saml/{application_id}/sso`
   - **SLO URL**: `https://your-seat-url/saml/{application_id}/slo`
   - **Certificate**: Download from IdP metadata

### SAML Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/saml/{application}/sso` | GET, POST | Single Sign-On endpoint |
| `/saml/{application}/slo` | GET, POST | Single Logout endpoint |
| `/seat-identity-provider/saml/applications/{application}/metadata` | GET | IdP metadata download |

---

## OpenID Connect

### OIDC Scopes and Claims

#### Standard Scopes

| Scope | Claims |
|-------|--------|
| `openid` | `sub` (required) |
| `profile` | `name`, `preferred_username`, `updated_at` |
| `email` | `email`, `email_verified` |

#### Custom SeAT/EVE Scopes

| Scope | Claims | Description |
|-------|--------|-------------|
| `seat:user` | `is_admin` | SeAT administrator status |
| `seat:character` | `character_id`, `character_name` | EVE main character info |
| `seat:corporation` | `corporation_id`, `alliance_id` | EVE corporation/alliance info |
| `seat:squads` | `squads` | Array of SeAT squad names |

### Creating an OIDC Application

1. Navigate to **Identity Provider > OIDC Applications** in SeAT
2. Click **Create Application**
3. Fill in the application details:
   - **Application Name**: A friendly name for the application
   - **Description**: Optional description
   - **Redirect URIs**: One or more authorized callback URLs (one per line)
   - **Allowed Scopes**: Select which scopes this application can request
4. Save the application and **copy the Client Secret** - it will only be shown once

### Configuring the OIDC Client

Use the discovery endpoint for auto-configuration:

```
https://your-seat-url/.well-known/openid-configuration
```

Or manually configure using:

| Setting | Value |
|---------|-------|
| **Issuer** | `https://your-seat-url` |
| **Authorization Endpoint** | `https://your-seat-url/oauth2/authorize` |
| **Token Endpoint** | `https://your-seat-url/oauth2/token` |
| **UserInfo Endpoint** | `https://your-seat-url/oidc/userinfo` |
| **JWKS URI** | `https://your-seat-url/oidc/jwks` |

### OIDC Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/.well-known/openid-configuration` | GET | Discovery document |
| `/oauth2/authorize` | GET | Authorization endpoint |
| `/oauth2/token` | POST | Token endpoint |
| `/oidc/userinfo` | GET | UserInfo endpoint (requires Bearer token) |
| `/oidc/jwks` | GET | JSON Web Key Set |

### Key Management

OIDC tokens are signed with RSA-256 keys. To manage signing keys:

1. Navigate to **Identity Provider > OIDC Keys**
2. View all keypairs with their status (active/inactive)
3. **Generate New Key** - Creates an inactive keypair
4. **Activate** - Make a keypair the active signing key
5. **Delete** - Remove inactive keypairs

**Note**: Rotating keys will invalidate tokens signed with the previous key. Clients should fetch the JWKS periodically to handle key rotation.

---

## Permissions

| Permission | Description |
|------------|-------------|
| `seat-identity-provider.view` | View applications and keys |
| `seat-identity-provider.security` | Create, edit, delete applications and manage keys |

## Security Considerations

### SAML
- All SAML assertions are signed using RSA-SHA256
- Each SAML application has its own unique X.509 certificate
- SP metadata fetch includes SSRF mitigations:
  - HTTPS-only URLs
  - Private IP range blocking
  - 3-second timeout
- Optional signature verification for incoming AuthnRequests

### OIDC
- All ID tokens signed with RS256 (RSA-SHA256)
- Access tokens validated on each request
- Tokens are revoked when user is deleted or deactivated
- Client secrets are hashed (bcrypt) in the database
- Supports `client_secret_basic` and `client_secret_post` authentication

## License

This project is licensed under the GPL-3.0-or-later license.
