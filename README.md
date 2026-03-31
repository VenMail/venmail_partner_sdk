# Venmail Partner SDK

Free, open-source hosting platform integrations for [Venmail](https://m.venmail.io) email hosting.

## Supported Platforms

| Platform | Directory | Status |
|----------|-----------|--------|
| **WHMCS** | `whmcs/` | Provisioning module with SSO, DNS, client area |
| **cPanel/WHM** | `cpanel/` | Standardized hooks + user/admin panels |
| **Plesk** | `plesk/` | Extension with event handlers + settings UI |
| **DirectAdmin** | `directadmin/` | Hook scripts + user/admin HTML panels |

All modules share a single PHP API client (`lib/VenmailApi.php`).

## Quick Start

### 1. Get Your Partner API Key

**Option A — Sign up via API:**

```bash
curl -X POST https://m.venmail.io/api/v1/provisioning/auth/signup \
  -H "Content-Type: application/json" \
  -d '{
    "email": "you@example.com",
    "password": "your-password",
    "name": "Your Name",
    "company": "Your Company",
    "platform_type": "whmcs",
    "reseller_domain": "panel.yourcompany.com"
  }'
```

**Option B — Login if you already have a partner account:**

```bash
curl -X POST https://m.venmail.io/api/v1/provisioning/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "you@example.com", "password": "your-password"}'
```

Both return your `api_key` in the response.

**Option C — Auto-identify by reseller domain:**

```bash
curl -X POST https://m.venmail.io/api/v1/provisioning/auth/identify \
  -H "Content-Type: application/json" \
  -d '{"domain": "panel.yourcompany.com"}'
```

Returns partner info if the domain is recognized.

### 2. Install Your Platform Module

#### WHMCS

1. Copy `whmcs/modules/servers/venmail/` to your WHMCS `modules/servers/` directory
2. In WHMCS Admin: **Setup > Products/Services > Servers** — add a server:
   - Hostname: `m.venmail.io`
   - Access Hash: Your partner API key
3. Create a product using the "Venmail" module
4. Configure the Plan ID in product settings

#### cPanel/WHM

```bash
sudo bash cpanel/install.sh
sudo nano /etc/venmail.conf  # Set your API key
```

#### Plesk

1. Package the `plesk/` directory as a Plesk extension
2. Install via **Extensions > My Extensions > Upload Extension**
3. Configure API key in the Venmail settings page (supports login/signup directly)

#### DirectAdmin

1. Copy `directadmin/` to `/usr/local/directadmin/plugins/venmail/`
2. Edit `venmail.conf` with your API key
3. Make hooks executable: `chmod +x directadmin/hooks/*.sh`
4. Link hooks to DirectAdmin's custom hooks directory

## Provisioning API Reference

All endpoints under `https://m.venmail.io/api/v1/provisioning/`.

### Authentication (Public — No API Key)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | Login, returns API key |
| POST | `/auth/signup` | Create partner account |
| POST | `/auth/identify` | Identify partner by reseller domain |

### Account Management (Requires API Key)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/accounts` | Create account (user + org + domain + plan) |
| GET | `/accounts/{domain}` | Get account details |
| POST | `/accounts/{domain}/suspend` | Suspend account |
| POST | `/accounts/{domain}/unsuspend` | Unsuspend account |
| POST | `/accounts/{domain}/terminate` | Remove partner association |
| PUT | `/accounts/{domain}/plan` | Change plan |
| GET | `/accounts/{domain}/dns-records` | Get DNS records |
| POST | `/accounts/{domain}/verify` | Trigger domain verification |
| GET | `/accounts/{domain}/usage` | Get email usage stats |
| GET | `/plans` | List available plans |
| POST | `/sso/{domain}` | Generate SSO login URL |
| POST | `/auth/regenerate-key` | Regenerate API key |
| GET | `/health` | Health check |

### Authentication Header

```
Authorization: Bearer YOUR_PARTNER_API_KEY
```

### Create Account Example

```bash
curl -X POST https://m.venmail.io/api/v1/provisioning/accounts \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "fullName": "John Doe",
    "domain": "example.com",
    "organization": "Example Inc",
    "plan_id": 1,
    "subscription_mode": "monthly"
  }'
```

### SSO Example

```bash
curl -X POST https://m.venmail.io/api/v1/provisioning/sso/example.com \
  -H "Authorization: Bearer YOUR_API_KEY"
```

Returns a short-lived (5 min) SSO URL that logs the user into Venmail.

## Shared API Client

```php
use Venmail\PartnerSDK\VenmailApi;

$api = new VenmailApi('https://m.venmail.io', 'YOUR_API_KEY', 'custom');

// Create account
$result = $api->createAccount([...]);

// Get DNS records
$records = $api->getDnsRecords('example.com');

// SSO
$sso = $api->getSsoUrl('example.com');
// → $sso['data']['sso_url']
```

## License

MIT
