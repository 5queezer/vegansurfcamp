<?php

namespace AmeliaBooking\Infrastructure\WP\UserRoles;

/**
 * Class UserRoles
 *
 * @package AmeliaBooking\Infrastructure\WP
 */
class UserRoles
{
    /**
     * @param $roles
     */
    public static function init($roles)
    {
        /** @var array $roles */
        foreach ($roles as $role) {
            remove_role($role['name']);
            add_role($role['name'], $role['label'], $role['capabilities']);
        }
    }
}
