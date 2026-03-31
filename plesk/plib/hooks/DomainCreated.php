<?php
/**
 * Venmail Plesk Extension — Event Listener
 *
 * Provisions/manages Venmail accounts on domain lifecycle events.
 * Compatible with PHP 7.4+ and Plesk Obsidian 18.0+.
 */

require_once dirname(__DIR__) . '/models/VenmailApi.php';

class Modules_Venmail_EventListener implements EventListener
{
    public function filterActions()
    {
        return [
            'domain_create',
            'domain_delete',
            'site_suspend',
            'site_resume',
        ];
    }

    public function handleEvent($objectType, $objectId, $action, $oldValues, $newValues)
    {
        $api = $this->getApi();
        if (!$api) return;

        try {
            switch ($action) {
                case 'domain_create':
                    $domain = isset($newValues['name']) ? $newValues['name'] : '';
                    $email  = isset($newValues['email']) ? $newValues['email'] : '';
                    if ($domain === '') return;

                    $api->createAccount([
                        'email'        => $email !== '' ? $email : 'admin@' . $domain,
                        'fullName'     => isset($newValues['owner_name']) ? $newValues['owner_name'] : $domain,
                        'domain'       => $domain,
                        'organization' => $domain,
                    ]);
                    break;

                case 'domain_delete':
                    $domain = isset($oldValues['name']) ? $oldValues['name'] : '';
                    if ($domain !== '') {
                        $api->terminateAccount($domain);
                    }
                    break;

                case 'site_suspend':
                    $domain = isset($newValues['name']) ? $newValues['name'] : (isset($oldValues['name']) ? $oldValues['name'] : '');
                    if ($domain !== '') {
                        $api->suspendAccount($domain);
                    }
                    break;

                case 'site_resume':
                    $domain = isset($newValues['name']) ? $newValues['name'] : (isset($oldValues['name']) ? $oldValues['name'] : '');
                    if ($domain !== '') {
                        $api->unsuspendAccount($domain);
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Log but don't break Plesk operations
            if (class_exists('pm_Log')) {
                pm_Log::err('Venmail: ' . $action . ' failed — ' . $e->getMessage());
            }
        }
    }

    /**
     * @return \Venmail\PartnerSDK\VenmailApi|null
     */
    private function getApi()
    {
        $settings = $this->loadSettings();
        if (empty($settings['api_url']) || empty($settings['api_key'])) {
            return null;
        }

        return new \Venmail\PartnerSDK\VenmailApi(
            $settings['api_url'],
            $settings['api_key'],
            'plesk'
        );
    }

    /**
     * @return array
     */
    private function loadSettings()
    {
        // Try pm_Context first (standard Plesk extension storage)
        if (class_exists('pm_Context')) {
            $file = pm_Context::getVarDir() . 'venmail_settings.json';
        } else {
            $file = dirname(__DIR__, 2) . '/venmail_settings.json';
        }

        if (!file_exists($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }
}
