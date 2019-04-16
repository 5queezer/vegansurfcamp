<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class SuccessfulBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class SuccessfulBookingCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'appointmentStatusChanged',
    ];

    /**
     * @param SuccessfulBookingCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle(SuccessfulBookingCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var Appointment $appointment */
        $appointment = $appointmentRepo->getByBookingId(
            (int)$command->getArg('id')
        );

        if (!$appointment instanceof Appointment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not retrieve appointment');

            return $result;
        }

        /** @var CustomerBooking $booking */
        $booking = $appointment->getBookings()->getItem(
            (int)$command->getArg('id')
        );

        if (!$booking instanceof CustomerBooking) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not retrieve booking');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully get booking');
        $result->setData([
            Entities::APPOINTMENT      => $appointment->toArray(),
            Entities::BOOKING          => $booking->toArray(),
            'appointmentStatusChanged' => $command->getFields()['appointmentStatusChanged']
        ]);

        return $result;
    }
}
