#!/usr/bin/env php
<?php
/**
 * Venmail DirectAdmin SSO Handler
 *
 * Usage: php venmail_sso.php <domain>
 * Returns JSON with SSO URL.
 * Compatible with PHP 7.4+.
 */

require_once dirname(__DIR__, 2) . '/lib/VenmailApi.php';

use Venmail\PartnerSDK\VenmailApi;

$domain = isset($argv[1]) ? $argv[1] : '';
if ($domain === '') {
    echo json_encode(['success' => false, 'message' => 'Domain required']);
    exit(1);
}

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
    echo json_encode(['success' => false, 'message' => 'Missing config']);
    exit(1);
}

$api = new VenmailApi($config['api_url'], $config['api_key'], 'directadmin');
echo json_encode($api->getSsoUrl($domain));
