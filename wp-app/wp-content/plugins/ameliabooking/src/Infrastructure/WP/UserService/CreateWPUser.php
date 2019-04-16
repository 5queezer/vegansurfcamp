<?php

namespace AmeliaBooking\Infrastructure\WP\UserService;

/**
 * Class CreateWPUser
 *
 * @package AmeliaBooking\Infrastructure\WP\UserService
 */
class CreateWPUser
{
    /**
     * @param string      $email
     * @param string|null $role
     *
     * @return mixed
     */
    public function create($email, $role = null)
    {
        if (username_exists($email)) {
            return null;
        }

        $userId = wp_create_user($email, wp_generate_password(), $email);

        $this->setRole($role, $userId);

        wp_new_user_notification($userId, null, 'user');

        return (int)$userId;
    }

    /**
     * @param string $role
     * @param int    $userId
     */
    private function setRole($role, $userId)
    {
        if ($role) {
            $user = new \WP_User($userId);
            if (get_role($role)) {
                $user->set_role($role);
            }
        }
    }
}
