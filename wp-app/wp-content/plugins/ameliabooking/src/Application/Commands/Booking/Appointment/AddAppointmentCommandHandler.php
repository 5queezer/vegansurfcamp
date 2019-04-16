<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class AddAppointmentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class AddAppointmentCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'serviceId',
        'providerId'
    ];

    /**
     * @param AddAppointmentCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle(AddAppointmentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        $appointmentData = $command->getFields();

        $service = $serviceRepository->getProviderServiceWithExtras(
            $appointmentData['serviceId'],
            $appointmentData['providerId']
        );

        $appointment = $appointmentAS->build($appointmentData, $service);

        if (!$appointment instanceof Appointment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create new appointment');

            return $result;
        }

        if (!$appointmentAS->canBeBooked($appointment)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['time_slot_unavailable']);
            $result->setData([
                'timeSlotUnavailable' => true
            ]);

            return $result;
        }

        $appointmentRepo->beginTransaction();

        try {
            $appointmentAS->add($appointment, $service, $command->getField('payment'));
        } catch (QueryExecutionException $e) {
            $appointmentRepo->rollback();
            throw $e;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new appointment');
        $result->setData(
            [
                Entities::APPOINTMENT => $appointment->toArray()
            ]
        );

        $appointmentRepo->commit();

        return $result;
    }
}
