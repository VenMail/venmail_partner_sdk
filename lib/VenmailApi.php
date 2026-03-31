<?php
/**
 * Venmail Provisioning API Client
 *
 * Shared PHP client used by all hosting platform integrations
 * (WHMCS, cPanel, Plesk, DirectAdmin).
 *
 * Compatible with PHP 7.4+ (WHMCS 8) and PHP 8.2+ (WHMCS 9).
 *
 * @package Venmail\PartnerSDK
 */

namespace Venmail\PartnerSDK;

class VenmailApi
{
    /** @var string */
    private $baseUrl;

    /** @var string */
    private $apiKey;

    /** @var int */
    private $timeout;

    /** @var string */
    private $platform;

    /**
     * @param string $baseUrl  Venmail instance URL (e.g. https://m.venmail.io)
     * @param string $apiKey   Partner API key (64-character string)
     * @param string $platform Platform identifier for tracking (whmcs|cpanel|plesk|directadmin|custom)
     * @param int    $timeout  HTTP request timeout in seconds
     */
    public function __construct(string $baseUrl, string $apiKey = '', string $platform = 'custom', int $timeout = 30)
    {
        $this->baseUrl  = rtrim($baseUrl, '/');
        $this->apiKey   = $apiKey;
        $this->timeout  = $timeout;
        $this->platform = $platform;
    }

    // ------------------------------------------------------------------
    // Authentication (public — no API key required)
    // ------------------------------------------------------------------

    /**
     * Login as an existing partner and retrieve the API key.
     *
     * @param string $email
     * @param string $password
     * @return array{success: bool, data?: array, message?: string}
     */
    public function partnerLogin(string $email, string $password): array
    {
        return $this->request('POST', '/provisioning/auth/login', [
            'email'    => $email,
            'password' => $password,
        ], false);
    }

    /**
     * Sign up as a new partner.
     *
     * @param array $data  Keys: email, password, name, company, platform_type, reseller_domain, website
     * @return array{success: bool, data?: array, message?: string}
     */
    public function partnerSignup(array $data): array
    {
        return $this->request('POST', '/provisioning/auth/signup', $data, false);
    }

    /**
     * Identify whether a reseller domain is recognized.
     *
     * @param string $domain  The reseller's panel domain
     * @return array{success: bool, data?: array}
     */
    public function identifyPartner(string $domain): array
    {
        return $this->request('POST', '/provisioning/auth/identify', [
            'domain' => $domain,
        ], false);
    }

    // ------------------------------------------------------------------
    // Account lifecycle
    // ------------------------------------------------------------------

    /**
     * Provision a new Venmail account (user + organization + domain).
     *
     * @param array $data  Keys: email, fullName, domain, organization, password?, plan_id?, subscription_mode?
     * @return array{success: bool, data?: array, message?: string}
     */
    public function createAccount(array $data): array
    {
        $data['platform'] = $this->platform;
        return $this->request('POST', '/provisioning/accounts', $data);
    }

    /**
     * @param string $domain  Domain name
     * @return array{success: bool, data?: array}
     */
    public function getAccount(string $domain): array
    {
        return $this->request('GET', '/provisioning/accounts/' . rawurlencode($domain));
    }

    /**
     * @param string $domain
     * @return array{success: bool, message?: string}
     */
    public function suspendAccount(string $domain): array
    {
        return $this->request('POST', '/provisioning/accounts/' . rawurlencode($domain) . '/suspend');
    }

    /**
     * @param string $domain
     * @return array{success: bool, message?: string}
     */
    public function unsuspendAccount(string $domain): array
    {
        return $this->request('POST', '/provisioning/accounts/' . rawurlencode($domain) . '/unsuspend');
    }

    /**
     * @param string $domain
     * @return array{success: bool, message?: string}
     */
    public function terminateAccount(string $domain): array
    {
        return $this->request('POST', '/provisioning/accounts/' . rawurlencode($domain) . '/terminate');
    }

    /**
     * @param string $domain
     * @param int    $planId
     * @param string $mode   monthly|yearly
     * @return array{success: bool, data?: array, message?: string}
     */
    public function changePlan(string $domain, int $planId, string $mode = 'monthly'): array
    {
        return $this->request('PUT', '/provisioning/accounts/' . rawurlencode($domain) . '/plan', [
            'plan_id'           => $planId,
            'subscription_mode' => $mode,
        ]);
    }

    // ------------------------------------------------------------------
    // DNS & Verification
    // ------------------------------------------------------------------

    /**
     * @param string $domain
     * @return array{success: bool, data?: array}
     */
    public function getDnsRecords(string $domain): array
    {
        return $this->request('GET', '/provisioning/accounts/' . rawurlencode($domain) . '/dns-records');
    }

    /**
     * @param string $domain
     * @return array{success: bool, status?: array}
     */
    public function verifyDomain(string $domain): array
    {
        return $this->request('POST', '/provisioning/accounts/' . rawurlencode($domain) . '/verify');
    }

    // ------------------------------------------------------------------
    // Usage & Plans
    // ------------------------------------------------------------------

    /**
     * @param string $domain
     * @return array{success: bool, data?: array}
     */
    public function getUsage(string $domain): array
    {
        return $this->request('GET', '/provisioning/accounts/' . rawurlencode($domain) . '/usage');
    }

    /**
     * @return array{success: bool, data?: array}
     */
    public function getPlans(): array
    {
        return $this->request('GET', '/provisioning/plans');
    }

    // ------------------------------------------------------------------
    // SSO
    // ------------------------------------------------------------------

    /**
     * @param string $domain
     * @return array{success: bool, data?: array{sso_url: string, token: string, expires_in: int}}
     */
    public function getSsoUrl(string $domain): array
    {
        return $this->request('POST', '/provisioning/sso/' . rawurlencode($domain));
    }

    // ------------------------------------------------------------------
    // Utilities
    // ------------------------------------------------------------------

    /**
     * @return array{success: bool, message: string, version: string}
     */
    public function health(): array
    {
        return $this->request('GET', '/provisioning/health');
    }

    /**
     * @return array{success: bool, data?: array{api_key: string}}
     */
    public function regenerateApiKey(): array
    {
        return $this->request('POST', '/provisioning/auth/regenerate-key');
    }

    // ------------------------------------------------------------------
    // HTTP transport
    // ------------------------------------------------------------------

    /**
     * @param string $method        HTTP method (GET|POST|PUT|DELETE)
     * @param string $path          API path (appended to /api/v1)
     * @param array  $data          Request body (JSON-encoded for POST/PUT)
     * @param bool   $authenticated Whether to send the API key header
     * @return array
     */
    private function request(string $method, string $path, array $data = [], bool $authenticated = true): array
    {
        $url = $this->baseUrl . '/api/v1' . $path;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Platform: ' . $this->platform,
        ];

        if ($authenticated && $this->apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0) {
            return [
                'success'   => false,
                'message'   => 'cURL error (' . $errno . '): ' . $error,
                'http_code' => 0,
            ];
        }

        if ($response === false || $response === '') {
            return [
                'success'   => false,
                'message'   => 'Empty response from Venmail API (HTTP ' . $httpCode . ')',
                'http_code' => $httpCode,
            ];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success'   => false,
                'message'   => 'Invalid JSON response from Venmail API',
                'http_code' => $httpCode,
                'raw'       => substr($response, 0, 500),
            ];
        }

        $decoded['http_code'] = $httpCode;
        return $decoded;
    }
}
