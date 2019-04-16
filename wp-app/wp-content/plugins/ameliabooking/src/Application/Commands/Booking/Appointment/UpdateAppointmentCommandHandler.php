<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class UpdateAppointmentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class UpdateAppointmentCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'serviceId',
        'providerId',
        'id'
    ];

    /**
     * @param UpdateAppointmentCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateAppointmentCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to add appointment');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        $service = $serviceRepository->getProviderServiceWithExtras(
            $command->getFields()['serviceId'],
            $command->getFields()['providerId']
        );

        $appointment = $appointmentAS->build($command->getFields(), $service);

        $oldAppointment = $appointmentRepo->getById($appointment->getId()->getValue());

        if (!$appointment instanceof Appointment || !$oldAppointment instanceof Appointment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update appointment');

            return $result;
        }

        $appointment->setGoogleCalendarEventId($oldAppointment->getGoogleCalendarEventId());

        $appointmentRepo->beginTransaction();

        try {
            $appointmentAS->update($oldAppointment, $appointment, $service, $command->getField('payment'));
        } catch (QueryExecutionException $e) {
            $appointmentRepo->rollback();
            throw $e;
        }

        $appointmentStatusChanged = $appointmentAS->isAppointmentStatusChanged($appointment, $oldAppointment);

        $appRescheduled = $appointmentAS->isAppointmentRescheduled($appointment, $oldAppointment);

        $appointmentArray = $appointment->toArray();
        $oldAppointmentArray = $oldAppointment->toArray();
        $bookingsWithChangedStatus = $bookingAS->getBookingsWithChangedStatus($appointmentArray, $oldAppointmentArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated appointment');
        $result->setData([
            Entities::APPOINTMENT       => $appointmentArray,
            'appointmentStatusChanged'  => $appointmentStatusChanged,
            'appointmentRescheduled'    => $appRescheduled,
            'bookingsWithChangedStatus' => $bookingsWithChangedStatus
        ]);

        $appointmentRepo->commit();

        return $result;
    }
}
