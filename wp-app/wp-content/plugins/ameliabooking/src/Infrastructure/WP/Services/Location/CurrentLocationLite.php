<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\Services\Location;

use AmeliaBooking\Application\Services\Location\CurrentLocation;

/**
 * Class CurrentLocationLite
 *
 * @package AmeliaBooking\Infrastructure\WP\Services\Location
 */
class CurrentLocationLite extends CurrentLocation
{
    /**
     * Get country ISO code by public IP address
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getCurrentLocationCountryIso()
    {
        try {
            $response = wp_remote_get('https://www.iplocate.io/api/lookup/' . $_SERVER['REMOTE_ADDR'], [] );

            $result = json_decode($response['body']);

            return !isset($result->country_code) ? '' : strtolower($result->country_code);
        } catch (\Exception $e) {
            return '';
        }
    }
}
