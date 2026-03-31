<?php
/**
 * Plesk-bundled reference to the shared Venmail API client.
 */
// Installed path: extension/lib/VenmailApi.php
$installedPath = dirname(__DIR__, 2) . '/lib/VenmailApi.php';
// SDK development path: repo_root/lib/VenmailApi.php
$sdkPath = dirname(__DIR__, 3) . '/lib/VenmailApi.php';
if (file_exists($installedPath)) {
    require_once $installedPath;
} elseif (file_exists($sdkPath)) {
    require_once $sdkPath;
} else {
    throw new \RuntimeException('Venmail: VenmailApi.php not found');
}
