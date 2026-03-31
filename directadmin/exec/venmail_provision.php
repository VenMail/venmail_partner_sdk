#!/usr/bin/env php
<?php
/**
 * Venmail DirectAdmin Provisioning Script
 *
 * Called by DirectAdmin hooks to provision/manage Venmail accounts.
 * Compatible with PHP 7.4+ and PHP 8.2+.
 *
 * Usage: php venmail_provision.php <action> <domain> [username] [email]
 */

require_once dirname(__DIR__, 2) . '/lib/VenmailApi.php';

use Venmail\PartnerSDK\VenmailApi;

$action   = isset($argv[1]) ? $argv[1] : '';
$domain   = isset($argv[2]) ? $argv[2] : '';
$username = isset($argv[3]) ? $argv[3] : '';
$email    = isset($argv[4]) ? $argv[4] : '';

if ($action === '' || $domain === '') {
    fwrite(STDERR, "Usage: venmail_provision.php <create|suspend|unsuspend|terminate> <domain> [username] [email]\n");
    exit(1);
}

// Load config — check standard DirectAdmin plugin path first, then local
$configFile = '/usr/local/directadmin/plugins/venmail/venmail.conf';
if (!file_exists($configFile)) {
    $configFile = dirname(__DIR__) . '/venmail.conf';
}

$config = [];
if (file_exists($configFile)) {
    $lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            $parts = explode('=', $line, 2);
            $config[trim($parts[0])] = trim($parts[1]);
        }
    }
}

if (empty($config['api_url']) || empty($config['api_key'])) {
    fwrite(STDERR, "Venmail: Missing api_url or api_key in config\n");
    exit(1);
}

$api = new VenmailApi($config['api_url'], $config['api_key'], 'directadmin');

$result = [];

switch ($action) {
    case 'create':
        $result = $api->createAccount([
            'email'        => $email !== '' ? $email : $username . '@' . $domain,
            'fullName'     => $username !== '' ? $username : $domain,
            'domain'       => $domain,
            'organization' => $domain,
        ]);
        break;

    case 'suspend':
        $result = $api->suspendAccount($domain);
        break;

    case 'unsuspend':
        $result = $api->unsuspendAccount($domain);
        break;

    case 'terminate':
        $result = $api->terminateAccount($domain);
        break;

    default:
        fwrite(STDERR, "Unknown action: {$action}\n");
        exit(1);
}

if (!empty($result['success'])) {
    echo "Venmail: {$action} completed for {$domain}\n";
} else {
    $msg = isset($result['message']) ? $result['message'] : 'unknown error';
    if (is_array($msg)) {
        $msg = json_encode($msg);
    }
    fwrite(STDERR, "Venmail: {$action} failed for {$domain} — " . $msg . "\n");
}
