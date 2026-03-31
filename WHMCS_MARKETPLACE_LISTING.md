# WHMCS Marketplace Listing — Venmail Email Hosting

## Product Name
Venmail Email Hosting

## Short Description (140 chars)
Provision and manage Venmail email accounts automatically. Full lifecycle, DNS setup, SSO, and client area integration.

## Category
Server/VPS Management > Email

## Tags
email, email hosting, mail server, provisioning, domain, dns, dkim, spf, sso

## Price
Free

---

## Full Description

### Venmail Email Hosting for WHMCS

Give your customers professional email hosting powered by [Venmail](https://venmail.io) — a modern email platform with calendars, contacts, file storage, forms, and more.

This **free** provisioning module automates the entire email account lifecycle directly from WHMCS.

---

### What It Does

**Automatic provisioning** — When a customer purchases an email hosting product, the module creates their Venmail account, organization, and domain in one step. No manual setup required.

**Full lifecycle management:**
- **Create** — Provisions user, organization, and domain on order activation
- **Suspend / Unsuspend** — Automatically suspends on overdue invoices and reactivates on payment
- **Terminate** — Cleanly removes the partner association on cancellation
- **Renew** — Handles subscription renewals seamlessly
- **Upgrade / Downgrade** — Changes plans instantly via the Change Package action

**Client area integration** — Customers see their required DNS records (MX, SPF, DKIM, CNAME) directly in the WHMCS client area with copy-ready values. Real-time verification status shows exactly which records are configured.

**Single Sign-On (SSO)** — One-click login from WHMCS into the full Venmail dashboard. No separate credentials needed.

**Admin tools** — View account status, verification progress, plan details, and email usage stats. A "Check DNS Verification" button lets you manually trigger verification checks.

**Daily auto-verification** — An optional daily cron hook automatically checks DNS verification for all active Venmail domains.

---

### Getting Started

1. **Upload** the module to `modules/servers/venmail/` in your WHMCS installation
2. **Add a server** in Setup > Servers:
   - Hostname: `m.venmail.io`
   - Access Hash: Your partner API key
3. **Create a product** using the Venmail module
4. **Configure** the Plan ID and billing cycle

Don't have a partner account yet? Sign up for free:

```
POST https://m.venmail.io/api/v1/provisioning/auth/signup
```

Or log in to retrieve your API key:

```
POST https://m.venmail.io/api/v1/provisioning/auth/login
```

---

### Compatibility

- WHMCS 8.x (PHP 7.4+)
- WHMCS 9.x (PHP 8.2+)
- Smarty 4 compatible templates
- Works with both Venmail and AWS SES email backends

---

### Support

- Documentation: https://github.com/nicdev/venmail-partner-sdk
- Issues: https://github.com/nicdev/venmail-partner-sdk/issues

---

### Also Available

Free plugins for other hosting platforms using the same Venmail Provisioning API:
- **cPanel/WHM** — Standardized hooks with user/admin panels
- **Plesk** — Extension with built-in login/signup
- **DirectAdmin** — Hook scripts with admin settings panel

All modules are open-source and available at https://github.com/nicdev/venmail-partner-sdk
