<?php

/**
 * Database hook for activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;

/**
 * Class AutoUpdateHook
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class AutoUpdateHook
{
    /**
     * The plugin remote update path
     *
     * @var string
     */
    public static $updatePath = 'http://wpamelia.com/auto-update/info.php';

    /**
     * Add our self-hosted auto update plugin to the filter transient
     *
     * @param $transient
     *
     * @return object $ transient
     */
    public static function checkUpdate($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        $purchaseCode = $settingsService->getSetting('activation', 'purchaseCode');

        // Check for new version if purchase code is added
        if ($purchaseCode) {
            // Get the remote info
            $remoteInformation = self::getRemoteInformation($purchaseCode);

            // If a newer version is available, add the update
            if ($remoteInformation && version_compare(AMELIA_VERSION, $remoteInformation->new_version, '<')) {
                $remoteInformation->package = $remoteInformation->download_link;
                $transient->response[AMELIA_PLUGIN_SLUG] = $remoteInformation;
            }
        }

        return $transient;
    }

    /**
     * Add our self-hosted description to the filter
     *
     * @param bool  $response
     * @param array $action
     * @param       $args
     *
     * @return bool|object
     */
    public static function checkInfo($response, $action, $args)
    {
        if ('plugin_information' !== $action) {
            return $response;
        }

        if (empty($args->slug)) {
            return $response;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        $purchaseCode = $settingsService->getSetting('activation', 'purchaseCode');

        if ($args->slug === AMELIA_PLUGIN_SLUG) {
            return self::getRemoteInformation($purchaseCode);
        }

        return $response;
    }

    /**
     * Get information about the remote version
     *
     * @param $purchaseCode
     *
     * @return bool|object
     */
    public static function getRemoteInformation($purchaseCode)
    {
        $request = wp_remote_post(
            self::$updatePath,
            [
                'body' => [
                    'action'       => 'info',
                    'purchaseCode' => $purchaseCode
                ]
            ]
        );

        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return unserialize($request['body']);
        }

        return false;
    }
}
