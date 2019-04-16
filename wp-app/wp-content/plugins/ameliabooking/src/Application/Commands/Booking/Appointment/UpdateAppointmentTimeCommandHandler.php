<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class UpdateAppointmentTimeCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class UpdateAppointmentTimeCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookingStart'
    ];

    /**
     * @param UpdateAppointmentTimeCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle(UpdateAppointmentTimeCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWriteTime(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to update appointment');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');

        /** @var AbstractUser $user */
        $user = $this->container->get('logged.in.user');

        if ($user->getType() === Entities::CUSTOMER &&
            !$settingsService->getSetting('roles', 'allowCustomerReschedule')
        ) {
            throw new AccessDeniedException('You are not allowed to update appointment');
        }

        $appointmentId = (int)$command->getArg('id');
        $bookingStart = $command->getField('bookingStart');

        /** @var Appointment $appointment */
        $appointment = $appointmentRepo->getById($appointmentId);

        /** @var Service $service */
        $service = $serviceRepository->getProviderServiceWithExtras(
            $appointment->getServiceId()->getValue(),
            $appointment->getProviderId()->getValue()
        );

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            if ($user->getType() === Entities::CUSTOMER &&
                $booking->getCustomerId()->getValue() !== $user->getId()->getValue() &&
                in_array($booking->getStatus()->getValue(), [BookingStatus::APPROVED, BookingStatus::PENDING], true) &&
                ($service->getMinCapacity()->getValue() !== 1 || $service->getMaxCapacity()->getValue() !==1)
            ) {
                throw new AccessDeniedException('You are not allowed to update appointment');
            }
        }

        if (!$appointment instanceof Appointment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get appointment');

            return $result;
        }

        $appointment->setBookingStart(
            new DateTimeValue(
                DateTimeService::getCustomDateTimeObject(
                    $bookingStart
                )
            )
        );

        $appointment->setBookingEnd(
            new DateTimeValue(
                DateTimeService::getCustomDateTimeObject($bookingStart)
                    ->modify('+' . $appointmentAS->getAppointmentLengthTime($appointment, $service) . ' second')
            )
        );

        if (!$appointmentAS->canBeBooked($appointment)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['time_slot_unavailable']);
            $result->setData([
                'timeSlotUnavailable' => true
            ]);

            return $result;
        }

        try {
            $appointmentRepo->update($appointmentId, $appointment);
        } catch (QueryExecutionException $e) {
            throw $e;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated appointment time');
        $result->setData([
            Entities::APPOINTMENT => $appointment->toArray()
        ]);

        return $result;
    }
}
