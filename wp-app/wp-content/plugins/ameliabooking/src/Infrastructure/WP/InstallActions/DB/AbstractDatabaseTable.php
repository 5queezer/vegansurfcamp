<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class AbstractDatabaseTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB
 */
class AbstractDatabaseTable
{
    const TABLE = '';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function getTableName()
    {
        if (!static::TABLE) {
            throw new InvalidArgumentException('Table name is not provided');
        }

        global $wpdb;
        return $wpdb->prefix . 'amelia_' . static::TABLE;
    }

    /**
     * Create new table in the database
     */
    public static function init()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta(static::buildTable());
    }
}
