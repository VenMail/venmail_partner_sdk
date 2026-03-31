<?php
/**
 * WHMCS-bundled copy of the shared Venmail API client.
 *
 * This file includes the shared lib/VenmailApi.php so the WHMCS module
 * directory can be distributed standalone or as part of the full SDK.
 *
 * When distributing WHMCS-only: copy the contents of the root
 * lib/VenmailApi.php into this file instead.
 */

// When used as part of the full SDK (development)
$sdkPath = dirname(__DIR__, 4) . '/lib/VenmailApi.php';
if (file_exists($sdkPath)) {
    require_once $sdkPath;
    return;
}

// When distributed standalone: the class should be defined inline below.
// Run `php build.php` to create a standalone package.
if (!class_exists('Venmail\\PartnerSDK\\VenmailApi')) {
    throw new \RuntimeException(
        'Venmail API client not found. Ensure lib/VenmailApi.php contains the full '
        . 'VenmailApi class, or place the SDK at the expected path.'
    );
}
