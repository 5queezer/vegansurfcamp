<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Booking\AppointmentDomainService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class UpdateAppointmentStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class UpdateAppointmentStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status'
    ];

    /**
     * @param UpdateAppointmentStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateAppointmentStatusCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWriteStatus(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to update appointment status');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');

        $appointmentId = (int)$command->getArg('id');
        $requestedStatus = $command->getField('status');

        /** @var Appointment $appointment */
        $appointment = $appointmentRepo->getById($appointmentId);

        $serviceId = $appointment->getServiceId()->getValue();
        $providerId = $appointment->getProviderId()->getValue();

        $service = $serviceRepository->getProviderServiceWithExtras($serviceId, $providerId);

        $bookings = $appointment->getBookings();
        foreach ($bookings->toArray() as $booking) {
            $booking = $bookings->getItem($booking['id']);
            $booking->setStatus(new BookingStatus($requestedStatus));
        }

        $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

        $appointmentStatus = $appointmentDS->getAppointmentStatusWhenApprovingAppointment(
            $service,
            $bookingsCount,
            $requestedStatus
        );

        if ($appointmentStatus === BookingStatus::APPROVED &&
            array_sum($bookingsCount) > $service->getMaxCapacity()->getValue()) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Appointment status not updated');
            $result->setData([
                Entities::APPOINTMENT => $appointment->toArray(),
                'status'              => $appointment->getStatus()->getValue(),
                'message'             => 'Maximum capacity is ' . $service->getMaxCapacity()->getValue()
            ]);

            return $result;
        }

        $appointment->setStatus(new BookingStatus($appointmentStatus));

        $appointmentRepo->beginTransaction();

        try {
            $bookingRepository->updateStatusByAppointmentId($appointmentId, $requestedStatus);
            $appointmentRepo->updateStatusById($appointmentId, $appointmentStatus);
        } catch (QueryExecutionException $e) {
            $appointmentRepo->rollback();
            throw $e;
        }

        $appointmentRepo->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated appointment status');
        $result->setData([
            Entities::APPOINTMENT => $appointment->toArray(),
            'status'              => $appointmentStatus,
            'message'             =>
                ($requestedStatus === BookingStatus::APPROVED && $appointmentStatus !== BookingStatus::APPROVED) ?
                    BackendStrings::getAppointmentStrings()['minimum_number_of_persons']
                    . ' ' . $service->getMinCapacity()->getValue() :
                    BackendStrings::getAppointmentStrings()['appointment_status_changed'] . $requestedStatus
        ]);

        return $result;
    }
}
