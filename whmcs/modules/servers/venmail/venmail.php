<?php
/**
 * Venmail WHMCS Provisioning Module
 *
 * Provisions Venmail email accounts when customers purchase mail hosting products.
 * Compatible with WHMCS 8.x (PHP 7.4+) and WHMCS 9.x (PHP 8.2+).
 *
 * @see https://developers.whmcs.com/provisioning-modules/
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/VenmailApi.php';

use Venmail\PartnerSDK\VenmailApi;

// ---------------------------------------------------------------------------
// Module metadata
// ---------------------------------------------------------------------------

function venmail_MetaData(): array
{
    return [
        'DisplayName'             => 'Venmail Email Hosting',
        'APIVersion'              => '1.1',
        'RequiresServer'          => true,
        'DefaultNonSSLPort'       => '443',
        'DefaultSSLPort'          => '443',
        'ServiceSingleSignOnLabel' => 'Login to Venmail',
    ];
}

// ---------------------------------------------------------------------------
// Configuration options shown when creating a product.
// WHMCS requires numeric indices (1-based), NOT named keys.
// Values are accessed via $params['configoption1'], $params['configoption2'], etc.
// ---------------------------------------------------------------------------

function venmail_ConfigOptions(): array
{
    return [
        [
            'FriendlyName' => 'Plan ID',
            'Type'         => 'text',
            'Size'         => '10',
            'Description'  => 'Venmail Plan ID. <a href="https://m.venmail.io/api/v1/provisioning/plans" target="_blank">View available plans</a>',
            'Default'      => '',
        ],
        [
            'FriendlyName' => 'Billing Cycle',
            'Type'         => 'dropdown',
            'Options'      => 'monthly,yearly',
            'Default'      => 'monthly',
            'Description'  => 'Subscription billing cycle passed to Venmail',
        ],
        [
            'FriendlyName' => 'Auto Verify DNS',
            'Type'         => 'yesno',
            'Description'  => 'Tick to run daily DNS verification checks automatically',
            'Default'      => 'on',
        ],
    ];
}

// ---------------------------------------------------------------------------
// Helper: build API client from WHMCS server params.
//
// Server credentials are configured via Setup > Servers in WHMCS admin:
//   Hostname   → $params['serverhostname']   (e.g. m.venmail.io)
//   Access Hash → $params['serveraccesshash'] (partner API key)
//   Password   → $params['serverpassword']   (fallback for API key)
// ---------------------------------------------------------------------------

function venmail_getApi(array $params): VenmailApi
{
    $host   = isset($params['serverhostname']) ? $params['serverhostname'] : '';
    $apiKey = isset($params['serveraccesshash']) && $params['serveraccesshash'] !== ''
        ? $params['serveraccesshash']
        : (isset($params['serverpassword']) ? $params['serverpassword'] : '');

    // Ensure the base URL has a scheme
    $baseUrl = $host;
    if ($baseUrl !== '' && strpos($baseUrl, '://') === false) {
        $baseUrl = 'https://' . $baseUrl;
    }
    $baseUrl = rtrim($baseUrl, '/');

    return new VenmailApi($baseUrl, $apiKey, 'whmcs');
}

/**
 * Extract the domain from WHMCS params.
 * Checks $params['domain'] first (standard), then custom fields.
 */
function venmail_getDomain(array $params): string
{
    if (isset($params['domain']) && $params['domain'] !== '') {
        return $params['domain'];
    }

    // Check custom fields array
    if (isset($params['customfields']) && is_array($params['customfields'])) {
        foreach ($params['customfields'] as $key => $value) {
            if (stripos($key, 'domain') !== false && $value !== '') {
                return $value;
            }
        }
    }

    return '';
}

// ---------------------------------------------------------------------------
// Test Connection
// ---------------------------------------------------------------------------

function venmail_TestConnection(array $params): array
{
    try {
        $api    = venmail_getApi($params);
        $result = $api->health();

        if (!empty($result['success'])) {
            return ['success' => true, 'error' => ''];
        }

        return [
            'success' => false,
            'error'   => isset($result['message']) ? $result['message'] : 'Unable to connect to Venmail API',
        ];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Create Account
// ---------------------------------------------------------------------------

function venmail_CreateAccount(array $params): string
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return 'Domain is required for provisioning';
        }

        $clientDetails = isset($params['clientsdetails']) && is_array($params['clientsdetails'])
            ? $params['clientsdetails']
            : [];

        $email     = isset($clientDetails['email']) ? $clientDetails['email'] : '';
        $firstName = isset($clientDetails['firstname']) ? $clientDetails['firstname'] : '';
        $lastName  = isset($clientDetails['lastname']) ? $clientDetails['lastname'] : '';
        $company   = isset($clientDetails['companyname']) ? $clientDetails['companyname'] : '';
        $fullName  = trim($firstName . ' ' . $lastName);

        $result = $api->createAccount([
            'email'             => $email,
            'fullName'          => $fullName !== '' ? $fullName : $domain,
            'domain'            => $domain,
            'organization'      => $company !== '' ? $company : $domain,
            'password'          => isset($params['password']) ? $params['password'] : null,
            'plan_id'           => isset($params['configoption1']) && $params['configoption1'] !== '' ? (int) $params['configoption1'] : null,
            'subscription_mode' => isset($params['configoption2']) ? $params['configoption2'] : 'monthly',
        ]);

        if (!empty($result['success'])) {
            return 'success';
        }

        $msg = isset($result['message']) ? $result['message'] : 'Failed to create account';
        // If the message is an array of validation errors, flatten it
        if (is_array($msg)) {
            $flat = [];
            foreach ($msg as $field => $errors) {
                $flat[] = $field . ': ' . (is_array($errors) ? implode(', ', $errors) : $errors);
            }
            $msg = implode('; ', $flat);
        }

        return $msg;
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// Suspend Account
// ---------------------------------------------------------------------------

function venmail_SuspendAccount(array $params): string
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return 'Domain is required';
        }

        $result = $api->suspendAccount($domain);
        return !empty($result['success']) ? 'success' : (isset($result['message']) ? $result['message'] : 'Suspend failed');
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// Unsuspend Account
// ---------------------------------------------------------------------------

function venmail_UnsuspendAccount(array $params): string
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return 'Domain is required';
        }

        $result = $api->unsuspendAccount($domain);
        return !empty($result['success']) ? 'success' : (isset($result['message']) ? $result['message'] : 'Unsuspend failed');
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// Terminate Account
// ---------------------------------------------------------------------------

function venmail_TerminateAccount(array $params): string
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return 'Domain is required';
        }

        $result = $api->terminateAccount($domain);
        return !empty($result['success']) ? 'success' : (isset($result['message']) ? $result['message'] : 'Terminate failed');
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// Renew (called on invoice payment for renewals)
// ---------------------------------------------------------------------------

function venmail_Renew(array $params): string
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return 'Domain is required';
        }

        $planId = isset($params['configoption1']) && $params['configoption1'] !== '' ? (int) $params['configoption1'] : null;

        if ($planId) {
            $mode   = isset($params['configoption2']) ? $params['configoption2'] : 'monthly';
            $result = $api->changePlan($domain, $planId, $mode);
            return !empty($result['success']) ? 'success' : (isset($result['message']) ? $result['message'] : 'Renew failed');
        }

        // If no plan configured, just ensure account is active
        $result = $api->unsuspendAccount($domain);
        return !empty($result['success']) ? 'success' : (isset($result['message']) ? $result['message'] : 'Renew failed');
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// Change Package (upgrade/downgrade)
// ---------------------------------------------------------------------------

function venmail_ChangePackage(array $params): string
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return 'Domain is required';
        }

        $planId = isset($params['configoption1']) && $params['configoption1'] !== '' ? (int) $params['configoption1'] : null;

        if (!$planId) {
            return 'Plan ID is required for package change';
        }

        $mode   = isset($params['configoption2']) ? $params['configoption2'] : 'monthly';
        $result = $api->changePlan($domain, $planId, $mode);

        return !empty($result['success']) ? 'success' : (isset($result['message']) ? $result['message'] : 'Package change failed');
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// Admin custom buttons (shown in admin service view)
// ---------------------------------------------------------------------------

function venmail_AdminCustomButtonArray(): array
{
    return [
        'Check DNS Verification' => 'AdminVerifyDns',
    ];
}

function venmail_AdminVerifyDns(array $params): string
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return 'Domain is required';
        }

        $result = $api->verifyDomain($domain);
        return !empty($result['success']) ? 'success' : (isset($result['message']) ? $result['message'] : 'Verification check failed');
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// Admin Area output
// ---------------------------------------------------------------------------

function venmail_AdminArea(array $params): string
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return '<p>No domain configured for this service.</p>';
        }

        $account = $api->getAccount($domain);
        $usage   = $api->getUsage($domain);

        $html = '<h3>Venmail Account: ' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '</h3>';

        if (!empty($account['success']) && isset($account['data']) && is_array($account['data'])) {
            $d = $account['data'];
            $isActive = !empty($d['is_active']);
            $verification = isset($d['verification']) && is_array($d['verification']) ? $d['verification'] : [];

            $html .= '<table class="datatable" style="width:auto">';
            $html .= '<tr><td class="fieldlabel">Status</td><td class="fieldarea">' . ($isActive ? '<span style="color:green;font-weight:bold">Active</span>' : '<span style="color:red;font-weight:bold">Suspended</span>') . '</td></tr>';
            $html .= '<tr><td class="fieldlabel">Plan</td><td class="fieldarea">' . htmlspecialchars(isset($d['plan']) ? $d['plan'] : 'N/A', ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $html .= '<tr><td class="fieldlabel">Backend</td><td class="fieldarea">' . htmlspecialchars(isset($d['backend']) ? $d['backend'] : 'N/A', ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $html .= '<tr><td class="fieldlabel">CNAME Verified</td><td class="fieldarea">' . (!empty($verification['cname_verified']) ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>') . '</td></tr>';
            $html .= '<tr><td class="fieldlabel">DKIM Verified</td><td class="fieldarea">' . (!empty($verification['dkim_verified']) ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>') . '</td></tr>';
            $html .= '<tr><td class="fieldlabel">Subscription</td><td class="fieldarea">' . htmlspecialchars(isset($d['subscription_mode']) ? $d['subscription_mode'] : 'N/A', ENT_QUOTES, 'UTF-8') . ' (ends: ' . htmlspecialchars(isset($d['subscription_ends_at']) ? $d['subscription_ends_at'] : 'N/A', ENT_QUOTES, 'UTF-8') . ')</td></tr>';
            $html .= '</table>';
        } else {
            $html .= '<p>Unable to load account details.</p>';
        }

        if (!empty($usage['success']) && isset($usage['data']['stats']) && is_array($usage['data']['stats']) && count($usage['data']['stats']) > 0) {
            $html .= '<h4 style="margin-top:15px">Usage (Last 3 Months)</h4>';
            $html .= '<table class="datatable" style="width:auto"><tr><th>Period</th><th>Sent</th><th>Received</th></tr>';
            foreach ($usage['data']['stats'] as $stat) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((isset($stat['month']) ? $stat['month'] : '?') . '/' . (isset($stat['year']) ? $stat['year'] : '?'), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . (int) (isset($stat['emails_sent']) ? $stat['emails_sent'] : 0) . '</td>';
                $html .= '<td>' . (int) (isset($stat['emails_received']) ? $stat['emails_received'] : 0) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        return $html;
    } catch (\Exception $e) {
        return '<p>Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    }
}

// ---------------------------------------------------------------------------
// Client Area output
//
// WHMCS expects an ARRAY return (not string) with keys:
//   'tabOverviewReplacementTemplate' => template filename (no extension)
//   'templateVariables'              => array of template vars
// OR the legacy format:
//   'templatefile' => filename (no extension)
//   'vars'         => array
// ---------------------------------------------------------------------------

function venmail_ClientArea(array $params): array
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return [
                'tabOverviewReplacementTemplate' => '',
                'templateVariables'              => [
                    'error' => 'No domain configured for this service.',
                ],
            ];
        }

        $dns     = $api->getDnsRecords($domain);
        $account = $api->getAccount($domain);

        $verification = [];
        if (isset($account['data']['verification']) && is_array($account['data']['verification'])) {
            $verification = $account['data']['verification'];
        }

        $records = [];
        if (isset($dns['data']['records']) && is_array($dns['data']['records'])) {
            $records = $dns['data']['records'];
        }

        return [
            'templatefile' => 'clientarea',
            'vars'         => [
                'domain'       => $domain,
                'records'      => $records,
                'backend'      => isset($dns['data']['backend']) ? $dns['data']['backend'] : 'unknown',
                'verification' => $verification,
                'is_active'    => !empty($account['data']['is_active']),
            ],
        ];
    } catch (\Exception $e) {
        return [
            'templatefile' => 'clientarea',
            'vars'         => [
                'domain'       => venmail_getDomain($params),
                'records'      => [],
                'backend'      => 'unknown',
                'verification' => [],
                'is_active'    => false,
                'error'        => $e->getMessage(),
            ],
        ];
    }
}

// ---------------------------------------------------------------------------
// Single Sign-On
//
// Return array with 'success' => true + 'redirectTo' => URL
// OR 'success' => false + 'errorMsg' => message
// ---------------------------------------------------------------------------

function venmail_ServiceSingleSignOn(array $params): array
{
    try {
        $api    = venmail_getApi($params);
        $domain = venmail_getDomain($params);

        if ($domain === '') {
            return ['success' => false, 'errorMsg' => 'Domain is required for SSO'];
        }

        $result = $api->getSsoUrl($domain);

        if (!empty($result['success']) && isset($result['data']['sso_url']) && $result['data']['sso_url'] !== '') {
            return [
                'success'    => true,
                'redirectTo' => $result['data']['sso_url'],
            ];
        }

        return [
            'success'  => false,
            'errorMsg' => isset($result['message']) ? $result['message'] : 'SSO failed — unable to generate login URL',
        ];
    } catch (\Exception $e) {
        return ['success' => false, 'errorMsg' => $e->getMessage()];
    }
}
