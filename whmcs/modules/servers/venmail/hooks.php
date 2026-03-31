<?php
/**
 * Venmail WHMCS Hooks
 *
 * Registers action hooks for post-provisioning notifications
 * and periodic domain verification checks.
 *
 * Compatible with WHMCS 8.x and 9.x.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

/**
 * After a Venmail account is created, log the event.
 */
add_hook('AfterModuleCreate', 1, function (array $params) {
    // $params['params']['server']['type'] may not always be populated;
    // check module name if available
    $moduleName = isset($params['params']['server']['type']) ? $params['params']['server']['type'] : '';
    if ($moduleName !== '' && $moduleName !== 'venmail') {
        return;
    }

    if (!empty($params['completed'])) {
        $domain = isset($params['params']['domain']) ? $params['params']['domain'] : 'unknown';
        logActivity('Venmail: Account provisioned for domain ' . $domain);
    }
});

/**
 * Daily cron: check domain verification status for pending Venmail domains.
 * Only runs if the "Auto Verify DNS" config option is enabled (configoption3).
 */
add_hook('DailyCronJob', 1, function () {
    // Load the API client once at the top
    $apiFile = __DIR__ . '/lib/VenmailApi.php';
    if (!file_exists($apiFile)) {
        return;
    }
    require_once $apiFile;

    try {
        $services = Capsule::table('tblhosting')
            ->join('tblservers', 'tblhosting.server', '=', 'tblservers.id')
            ->where('tblservers.type', 'venmail')
            ->where('tblhosting.domainstatus', 'Active')
            ->select([
                'tblhosting.domain',
                'tblservers.hostname',
                'tblservers.accesshash',
                'tblservers.password',
            ])
            ->get();

        foreach ($services as $service) {
            if (empty($service->domain)) {
                continue;
            }

            try {
                $host   = isset($service->hostname) ? $service->hostname : '';
                $apiKey = (isset($service->accesshash) && $service->accesshash !== '')
                    ? $service->accesshash
                    : (isset($service->password) ? $service->password : '');

                $baseUrl = $host;
                if ($baseUrl !== '' && strpos($baseUrl, '://') === false) {
                    $baseUrl = 'https://' . $baseUrl;
                }

                $api = new \Venmail\PartnerSDK\VenmailApi($baseUrl, $apiKey, 'whmcs');
                $api->verifyDomain($service->domain);
            } catch (\Exception $inner) {
                // Log per-domain errors but don't stop the loop
                logActivity('Venmail DailyCron: Error verifying ' . $service->domain . ' — ' . $inner->getMessage());
            }
        }
    } catch (\Exception $e) {
        logActivity('Venmail DailyCron: ' . $e->getMessage());
    }
});
