<?php

namespace AmeliaBooking\Application\Commands\User;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;

/**
 * Class DeleteUserCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User
 */
class DeleteUserCommandHandler extends CommandHandler
{
    /**
     * @param DeleteUserCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function handle(DeleteUserCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanDelete(Entities::EMPLOYEES) &&
            !$this->getContainer()->getPermissionsService()->currentUserCanDelete(Entities::CUSTOMERS)
        ) {
            throw new AccessDeniedException('You are not allowed to read user');
        }

        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        $appointmentsCount = $userAS->getAppointmentsCountForUser($command->getArg('id'));

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        $userRepository->beginTransaction();

        if ($appointmentsCount['futureAppointments']) {
            $result->setResult(CommandResult::RESULT_CONFLICT);
            $result->setMessage('Could not delete user.');
            $result->setData([]);

            return $result;
        }

        if (!$userRepository->delete($command->getArg('id'))) {
            $userRepository->rollback();
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete user.');
            $userRepository->rollback();

            return $result;
        }

        $emptyAppointments = $appointmentRepo->getAppointmentsWithoutBookings();

        foreach ($emptyAppointments->keys() as $appointmentKey) {
            $appointment = $emptyAppointments->getItem($appointmentKey);

            if (!$appointmentRepo->delete($appointment->getId()->getValue())) {
                $userRepository->rollback();
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not delete user.');
                $userRepository->rollback();

                return $result;
            }
        }

        $userRepository->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted user.');
        $result->setData([]);

        return $result;
    }
}
