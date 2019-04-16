<?php

namespace AmeliaBooking\Application\Services\User;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;

/**
 * Class CustomerApplicationService
 *
 * @package AmeliaBooking\Application\Services\User
 */
class CustomerApplicationService
{
    private $container;

    /**
     * ProviderApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $customers
     *
     * @return array
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function removeAllExceptCurrentUser($customers)
    {
        /** @var Provider $currentUser */
        $currentUser = $this->container->get('logged.in.user');

        if ($currentUser === null) {
            return [];
        }

        if ($currentUser->getType() === 'customer'
            && !$this->container->getPermissionsService()->currentUserCanReadOthers(Entities::APPOINTMENTS)
        ) {
            if ($currentUser->getId() === null) {
                return [];
            }

            $currentUserId = $currentUser->getId()->getValue();
            foreach ($customers as $key => $provider) {
                if ($provider['id'] !== $currentUserId) {
                    unset($customers[$key]);
                }
            }
        }

        return array_values($customers);
    }

    /**
     * Create a new user if doesn't exists. For adding appointment from the front-end.
     *
     * @param array         $userData
     * @param CommandResult $result
     *
     * @return AbstractUser
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getNewOrExistingCustomer($userData, $result)
    {
        /** @var AbstractUser $user */
        $loggedInUser = $this->container->get('logged.in.user');

        if ($loggedInUser) {
            if ($loggedInUser->getType() === AbstractUser::USER_ROLE_ADMIN) {
                $userData['type'] = AbstractUser::USER_ROLE_ADMIN;
            } elseif ($loggedInUser->getType() === AbstractUser::USER_ROLE_MANAGER) {
                $userData['type'] = AbstractUser::USER_ROLE_MANAGER;
            }
        }

        $user = UserFactory::create($userData);

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        // Check if email already exists
        $userWithSameMail = $userRepository->getByEmail($user->getEmail()->getValue());

        if ($userWithSameMail) {
            /** @var SettingsService $settingsService */
            $settingsService = $this->container->get('domain.settings.service');

            // If email already exists, check if First Name and Last Name from request are same with the First Name
            // and Last Name from $userWithSameMail. If these are not same return error message.
            if ($settingsService->getSetting('roles', 'inspectCustomerInfo') &&
                ($userWithSameMail->getFirstName()->getValue() !== $user->getFirstName()->getValue() ||
                    $userWithSameMail->getLastName()->getValue() !== $user->getLastName()->getValue())
            ) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setData(['emailError' => true]);
            }

            return $userWithSameMail;
        }

        return $user;
    }
}
