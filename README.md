# SeAT Identity Provider

A SAML 2.0 Identity Provider plugin for [SeAT](https://github.com/eveseat/seat) that allows you to use your SeAT installation as an enterprise identity provider for third-party services.

## Features

- **SAML 2.0 SSO** - Single Sign-On using SAML 2.0 protocol
- **SAML 2.0 SLO** - Single Logout support
- **Multiple Applications** - Configure multiple Service Providers
- **Automatic Certificate Generation** - RSA 2048-bit X.509 certificates generated per application
- **Metadata Import** - Fetch and parse SP metadata from URL
- **IdP Metadata Export** - Download IdP metadata XML for SP configuration
- **Signed Assertions** - All SAML responses signed with RSA-SHA256

## SAML Attributes

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

## Configuration

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

## Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/saml/{application}/sso` | GET, POST | Single Sign-On endpoint |
| `/saml/{application}/slo` | GET, POST | Single Logout endpoint |
| `/seat-identity-provider/saml/applications/{application}/metadata` | GET | IdP metadata download |

## Permissions

| Permission | Description |
|------------|-------------|
| `seat-identity-provider.view` | View SAML applications list |
| `seat-identity-provider.security` | Create, edit, and delete SAML applications |

## Security Considerations

- All SAML assertions are signed using RSA-SHA256
- Each SAML application has its own unique X.509 certificate
- SP metadata fetch includes SSRF mitigations:
  - HTTPS-only URLs
  - Private IP range blocking
  - 3-second timeout
- Optional signature verification for incoming AuthnRequestsp

## License

This project is licensed under the GPL-3.0-or-later license.
