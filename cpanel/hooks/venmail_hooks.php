<?php
/**
 * Venmail cPanel Standardized Hooks
 *
 * Hooks into cPanel account lifecycle events to auto-provision
 * Venmail email accounts.
 *
 * Compatible with PHP 7.4+ and PHP 8.2+.
 *
 * Install: /usr/local/cpanel/3rdparty/bin/php /path/to/register_hooks.php
 *
 * cPanel Hook Registration:
 *   Accounts::Create::post   → venmail_account_create
 *   Accounts::Suspend::post  → venmail_account_suspend
 *   Accounts::UnSuspend::post→ venmail_account_unsuspend
 *   Accounts::Remove::post   → venmail_account_remove
 */

require_once dirname(__DIR__, 2) . '/lib/VenmailApi.php';

use Venmail\PartnerSDK\VenmailApi;

/**
 * Load Venmail config from /etc/venmail.conf
 */
function venmail_load_config()
{
    $configFile = '/etc/venmail.conf';
    if (!file_exists($configFile)) {
        return [];
    }

    $config = [];
    $lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        $parts = explode('=', $line, 2);
        $config[trim($parts[0])] = trim($parts[1]);
    }

    return $config;
}

/**
 * @return VenmailApi|null
 */
function venmail_get_api()
{
    $config = venmail_load_config();
    if (empty($config['api_url']) || empty($config['api_key'])) {
        return null;
    }

    return new VenmailApi($config['api_url'], $config['api_key'], 'cpanel');
}

// ---------------------------------------------------------------------------
// Hook: Account Created
// ---------------------------------------------------------------------------

function venmail_account_create($context, $data)
{
    $api = venmail_get_api();
    if (!$api) return;

    $domain = isset($data['domain']) ? $data['domain'] : '';
    $user   = isset($data['user']) ? $data['user'] : '';
    $email  = isset($data['contactemail']) ? $data['contactemail'] : ($user . '@' . $domain);
    $name   = isset($data['contactname']) ? $data['contactname'] : (isset($data['user']) ? $data['user'] : '');

    if ($domain === '') return;

    try {
        $api->createAccount([
            'email'        => $email,
            'fullName'     => $name !== '' ? $name : $domain,
            'domain'       => $domain,
            'organization' => $domain,
        ]);
    } catch (\Exception $e) {
        error_log('Venmail cPanel: create failed for ' . $domain . ' — ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Hook: Account Suspended
// ---------------------------------------------------------------------------

function venmail_account_suspend($context, $data)
{
    $api = venmail_get_api();
    if (!$api) return;

    $domain = isset($data['domain']) ? $data['domain'] : '';
    if ($domain === '') return;

    try {
        $api->suspendAccount($domain);
    } catch (\Exception $e) {
        error_log('Venmail cPanel: suspend failed for ' . $domain . ' — ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Hook: Account Unsuspended
// ---------------------------------------------------------------------------

function venmail_account_unsuspend($context, $data)
{
    $api = venmail_get_api();
    if (!$api) return;

    $domain = isset($data['domain']) ? $data['domain'] : '';
    if ($domain === '') return;

    try {
        $api->unsuspendAccount($domain);
    } catch (\Exception $e) {
        error_log('Venmail cPanel: unsuspend failed for ' . $domain . ' — ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Hook: Account Removed
// ---------------------------------------------------------------------------

function venmail_account_remove($context, $data)
{
    $api = venmail_get_api();
    if (!$api) return;

    $domain = isset($data['domain']) ? $data['domain'] : '';
    if ($domain === '') return;

    try {
        $api->terminateAccount($domain);
    } catch (\Exception $e) {
        error_log('Venmail cPanel: terminate failed for ' . $domain . ' — ' . $e->getMessage());
    }
}
