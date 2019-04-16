<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class DeleteAppointmentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class DeleteAppointmentCommandHandler extends CommandHandler
{
    /**
     * @param DeleteAppointmentCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(DeleteAppointmentCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanDelete(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to delete appointment');
        }

        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var Appointment $appointment */
        $appointment = $appointmentRepo->getById($command->getArg('id'));

        if (!$appointment instanceof Appointment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete appointment');

            return $result;
        }

        // Set status to rejected, to send the notification that appointment is rejected
        $appointment->setStatus(new BookingStatus(BookingStatus::REJECTED));

        $bookings = $appointment->getBookings();
        foreach ($bookings->toArray() as $booking) {
            $booking = $bookings->getItem($booking['id']);
            $booking->setStatus(new BookingStatus(BookingStatus::REJECTED));
        }

        $appointmentRepo->delete($command->getArg('id'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted appointment');
        $result->setData([
            Entities::APPOINTMENT => $appointment->toArray()
        ]);

        return $result;
    }
}
