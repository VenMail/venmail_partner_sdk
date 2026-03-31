<?php
/**
 * Venmail Plesk Extension — Admin Controller
 *
 * Settings page for configuring the Venmail API connection.
 * Supports login/signup for partners without API keys.
 * Compatible with PHP 7.4+ and Plesk Obsidian 18.0+.
 */

require_once dirname(__DIR__) . '/models/VenmailApi.php';

class IndexController extends pm_Controller_Action
{
    public function indexAction()
    {
        $this->view->pageTitle = 'Venmail Email Hosting';

        $settings = $this->loadSettings();
        $this->view->apiUrl    = isset($settings['api_url']) ? $settings['api_url'] : 'https://m.venmail.io';
        $this->view->apiKey    = isset($settings['api_key']) ? $settings['api_key'] : '';
        $this->view->connected = !empty($settings['api_key']);

        // Test connection if configured
        if (!empty($settings['api_key'])) {
            $apiUrl = isset($settings['api_url']) ? $settings['api_url'] : 'https://m.venmail.io';
            $api = new \Venmail\PartnerSDK\VenmailApi($apiUrl, $settings['api_key'], 'plesk');
            $health = $api->health();
            $this->view->connectionStatus = !empty($health['success'])
                ? 'Connected'
                : 'Error: ' . (isset($health['message']) ? $health['message'] : 'Unknown');
        }
    }

    public function saveAction()
    {
        $apiUrl = $this->getRequest()->getParam('api_url', 'https://m.venmail.io');
        $apiKey = $this->getRequest()->getParam('api_key', '');

        $this->saveSettings([
            'api_url' => $apiUrl,
            'api_key' => $apiKey,
        ]);

        $this->_status->addInfo('Settings saved successfully.');
        $this->_redirect('index');
    }

    public function loginAction()
    {
        $email    = $this->getRequest()->getParam('email');
        $password = $this->getRequest()->getParam('password');

        $api    = new \Venmail\PartnerSDK\VenmailApi('https://m.venmail.io', '', 'plesk');
        $result = $api->partnerLogin($email, $password);

        if (!empty($result['success']) && isset($result['data']['api_key'])) {
            $this->saveSettings([
                'api_url' => 'https://m.venmail.io',
                'api_key' => $result['data']['api_key'],
            ]);
            $this->_status->addInfo('Logged in successfully. API key saved.');
        } else {
            $msg = isset($result['message']) ? $result['message'] : 'Invalid credentials';
            $this->_status->addError('Login failed: ' . $msg);
        }

        $this->_redirect('index');
    }

    public function signupAction()
    {
        $data = [
            'email'           => $this->getRequest()->getParam('email'),
            'password'        => $this->getRequest()->getParam('password'),
            'name'            => $this->getRequest()->getParam('name'),
            'company'         => $this->getRequest()->getParam('company'),
            'platform_type'   => 'plesk',
            'reseller_domain' => $this->getRequest()->getParam('reseller_domain'),
        ];

        $api    = new \Venmail\PartnerSDK\VenmailApi('https://m.venmail.io', '', 'plesk');
        $result = $api->partnerSignup($data);

        if (!empty($result['success']) && isset($result['data']['api_key'])) {
            $this->saveSettings([
                'api_url' => 'https://m.venmail.io',
                'api_key' => $result['data']['api_key'],
            ]);
            $this->_status->addInfo('Partner account created! API key saved.');
        } else {
            $msg = isset($result['message']) ? $result['message'] : 'Unknown error';
            $this->_status->addError('Signup failed: ' . $msg);
        }

        $this->_redirect('index');
    }

    /**
     * @return array
     */
    private function loadSettings()
    {
        $file = pm_Context::getVarDir() . 'venmail_settings.json';
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array $settings
     */
    private function saveSettings($settings)
    {
        $file = pm_Context::getVarDir() . 'venmail_settings.json';
        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
    }
}
