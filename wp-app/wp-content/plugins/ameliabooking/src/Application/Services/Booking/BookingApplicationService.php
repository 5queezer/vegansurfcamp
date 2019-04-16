<?php

namespace AmeliaBooking\Application\Services\Booking;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Services\Booking\AppointmentDomainService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class BookingApplicationService
 *
 * @package AmeliaBooking\Application\Services\Booking
 */
class BookingApplicationService
{
    private $container;

    /**
     * AppointmentApplicationService constructor.
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
     * @param int    $bookingId
     * @param string $requestedStatus
     * @param string $token
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function updateStatus($bookingId, $requestedStatus, $token)
    {
        $result = new CommandResult();

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var ServiceRepository $serviceRepo */
        $serviceRepo = $this->container->get('domain.bookable.service.repository');
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var CustomerBooking $booking */
        $booking = $bookingRepository->getById($bookingId);

        if (($token !== null && $token !== $booking->getToken()->getValue()) ||
            (
                $token === null && $this->container->get('logged.in.user') &&
                $this->container->get('logged.in.user')->getId() !== null &&
                $this->container->get('logged.in.user')->getId()->getValue() !== $booking->getCustomerId()->getValue()
            )
        ) {
            throw new AccessDeniedException('You are not allowed to update booking status');
        }

        $appointmentId = $booking->getAppointmentId()->getValue();

        /** @var Appointment $appointment */
        $appointment = $appointmentRepo->getById($appointmentId);

        $minimumCancelTime = $settingsService->getCategorySettings('general')['minimumTimeRequirementPriorToCanceling'];

        /** @var \DateTime $bookingStart */
        $bookingStart = $appointment->getBookingStart()->getValue();
        if (DateTimeService::getNowDateTimeObject() >= $bookingStart->modify("-{$minimumCancelTime} second")) {
            $result->setResult(CommandResult::RESULT_ERROR);

            return $result;
        }

        $serviceId = $appointment->getServiceId()->getValue();
        $providerId = $appointment->getProviderId()->getValue();

        $service = $serviceRepo->getProviderServiceWithExtras($serviceId, $providerId);

        $appointment->getBookings()->getItem($bookingId)->setStatus(new BookingStatus($requestedStatus));
        $booking->setStatus(new BookingStatus($requestedStatus));

        $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

        $appointmentStatus = $appointmentDS->getAppointmentStatusWhenChangingBookingStatus(
            $service,
            $bookingsCount,
            $appointment->getStatus()->getValue()
        );

        $appointmentRepo->beginTransaction();

        try {
            $bookingRepository->updateStatusById($bookingId, $requestedStatus);
            $appointmentRepo->updateStatusById($appointmentId, $appointmentStatus);
        } catch (QueryExecutionException $e) {
            $appointmentRepo->rollback();
            throw $e;
        }

        $appStatusChanged = false;
        if ($appointment->getStatus()->getValue() !== $appointmentStatus) {
            $appointment->setStatus(new BookingStatus($appointmentStatus));
            $appStatusChanged = true;
        }

        $appointmentRepo->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated booking status');
        $result->setData([
            Entities::APPOINTMENT      => $appointment->toArray(),
            'appointmentStatusChanged' => $appStatusChanged,
            Entities::BOOKING          => $booking->toArray(),
            'status'                   => $requestedStatus,
            'message'                  =>
                BackendStrings::getAppointmentStrings()['appointment_status_changed'] . $requestedStatus
        ]);

        return $result;
    }

    /**
     * @param array $appointment
     * @param array $oldAppointment
     *
     * @return array
     */
    public function getBookingsWithChangedStatus($appointment, $oldAppointment)
    {
        $bookings = [];

        foreach ((array)$appointment['bookings'] as $booking) {
            $oldBookingKey = array_search($booking['id'], array_column($oldAppointment['bookings'], 'id'), true);

            if ($oldBookingKey === false ||
                $booking['status'] !== $oldAppointment['bookings'][$oldBookingKey]['status']
            ) {
                $bookings[] = $booking;
            }
        }

        foreach ((array)$oldAppointment['bookings'] as $oldBooking) {
            $newBookingKey = array_search($oldBooking['id'], array_column($appointment['bookings'], 'id'), true);

            if (($newBookingKey === false) && $oldBooking['status'] !== BookingStatus::REJECTED) {
                $oldBooking['status'] = BookingStatus::REJECTED;
                $bookings[] = $oldBooking;
            }
        }

        return $bookings;
    }

    /**
     * @param $bookingsArray
     *
     * @return array
     */
    public function filterApprovedBookings($bookingsArray)
    {
        return array_intersect_key(
            $bookingsArray,
            array_flip(array_keys(array_column($bookingsArray, 'status'), 'approved'))
        );
    }

    /**
     * @param array $bookingsArray
     * @param array $statuses
     *
     * @return mixed
     */
    public function removeBookingsByStatuses($bookingsArray, $statuses)
    {
        foreach ($statuses as $status) {
            foreach ($bookingsArray as $bookingKey => $bookingArray) {
                if ($bookingArray['status'] === $status) {
                    unset($bookingsArray[$bookingKey]);
                }
            }
        }

        return $bookingsArray;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param CommandResult         $result
     * @param AppointmentRepository $appointmentRepo
     * @param array                 $appointmentData
     * @param bool                  $inspectTimeSlot
     * @param bool                  $inspectCoupon
     * @param bool                  $save
     *
     * @return array
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function processBooking($result, $appointmentRepo, $appointmentData, $inspectTimeSlot, $inspectCoupon, $save)
    {
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var CouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        if (isset($appointmentData['payment']) &&
            isset($appointmentData['payment']['gateway']) === PaymentType::ON_SITE &&
            !$settingsService->getSetting('payments', 'onSite')) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setData(['paymentError' => true]);

            return null;
        }

        /** @var Coupon $coupon */
        $coupon = null;

        $appointmentStatusChanged = false;

        // Inspect if coupon is existing and valid if sent from the front-end.
        if ($appointmentData['couponCode']) {
            $coupon = $couponAS->processCoupon(
                $appointmentData['couponCode'],
                $appointmentData['serviceId'],
                $inspectCoupon,
                $result
            );

            if ($result->getResult() === CommandResult::RESULT_ERROR) {
                return null;
            }
        }

        /** @var AbstractUser $user */
        $user = null;

        $userId = null;

        // Create a new user if doesn't exists. For adding appointment from the front-end.
        if (!$appointmentData['bookings'][0]['customerId'] && !$appointmentData['bookings'][0]['customer']['id']) {
            /** @var CustomerApplicationService $customerAS */
            $customerAS = $this->container->get('application.user.customer.service');

            /** @var UserRepository $userRepository */
            $userRepository = $this->container->get('domain.users.repository');

            $user = $customerAS->getNewOrExistingCustomer($appointmentData['bookings'][0]['customer'], $result);

            if ($result->getResult() === CommandResult::RESULT_ERROR) {
                return null;
            }

            if ($save && !$user->getId()) {
                if (!($userId = $userRepository->add($user))) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setData(['emailError' => true]);

                    return null;
                }

                $user->setId(new Id($userId));
            }

            if ($user->getId()) {
                $appointmentData['bookings'][0]['customerId'] = $user->getId()->getValue();
            }
        }

        /** @var Service $service */
        $service = $serviceRepository->getProviderServiceWithExtras(
            $appointmentData['serviceId'],
            $appointmentData['providerId']
        );

        /** @var Appointment $existingAppointment */
        $existingAppointment = null;

        /** @var Collection $existingAppointments */
        $existingAppointments = $appointmentRepo->getFiltered([
            'dates'     => [$appointmentData['bookingStart'], $appointmentData['bookingStart']],
            'services'  => [$appointmentData['serviceId']],
            'providers' => [$appointmentData['providerId']]
        ]);

        if ($existingAppointments->length()) {
            $existingAppointment = $existingAppointments->getItem($existingAppointments->keys()[0]);
        }

        if ($existingAppointment) {
            /** @var Appointment $appointment */
            $appointment = unserialize(serialize($existingAppointment));
            $booking = CustomerBookingFactory::create($appointmentData['bookings'][0]);
            $booking->setAppointmentId($appointment->getId());
            $booking->setPrice($appointment->getService()->getPrice());
        } else {
            /** @var Appointment $appointment */
            $appointment = $appointmentAS->build($appointmentData, $service);

            /** @var CustomerBooking $booking */
            $booking = $appointment->getBookings()->getItem($appointment->getBookings()->keys()[0]);
        }

        $booking->setInfo(new Json(json_encode([
            'firstName' => $booking->getCustomer()->getFirstName()->getValue(),
            'lastName'  => $booking->getCustomer()->getLastName()->getValue(),
            'phone'     => $booking->getCustomer()->getPhone() ? $booking->getCustomer()->getPhone()->getValue() : '',
        ])));

        if ($coupon && $coupon->getUsed()->getValue() < $coupon->getLimit()->getValue()) {
            $booking->setCoupon($coupon);
        }

        if ($inspectTimeSlot) {
            $this->canBeBooked($result, $appointment, $booking);

            if ($result->getResult() === CommandResult::RESULT_ERROR) {
                return null;
            }
        }

        if ($save) {
            if ($existingAppointment) {
                $appointment->getBookings()->addItem($booking);
                $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);
                $appointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment($service, $bookingsCount);
                $appointment->setStatus(new BookingStatus($appointmentStatus));
                $appointmentStatusChanged =
                    $appointmentAS->isAppointmentStatusChanged($appointment, $existingAppointment);

                try {
                    $appointmentAS->update($existingAppointment, $appointment, $service, $appointmentData['payment']);
                } catch (QueryExecutionException $e) {
                    throw $e;
                }
            } else {
                try {
                    $appointmentAS->add($appointment, $service, $appointmentData['payment']);
                } catch (QueryExecutionException $e) {
                    throw $e;
                }
            }

            // Create WP user
            try {
                if ($userId && $user && $settingsService->getSetting('roles', 'automaticallyCreateCustomer')) {
                    /** @var UserApplicationService $userAS */
                    $userAS = $this->container->get('application.user.service');

                    $userAS->setWpUserIdForNewUser($user->getId()->getValue(), $user);
                }
            } catch (\Exception $e) {
            }
        }

        return [
            'service'                  => $service,
            'booking'                  => $booking,
            'appointment'              => $appointment,
            'appointmentStatusChanged' => $appointmentStatusChanged,
        ];
    }

    /**
     * @param CommandResult   $result
     * @param Appointment     $appointment
     * @param CustomerBooking $newBooking
     *
     * @return bool
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function canBeBooked($result, $appointment, $newBooking)
    {
        /** @var TimeSlotService $timeSlotService */
        $timeSlotService = $this->container->get('application.timeSlot.service');

        // if not new appointment, check if customer has already made booking
        if ($appointment->getId() !== null) {
            foreach ($appointment->getBookings()->keys() as $bookingKey) {
                /** @var CustomerBooking $booking */
                $booking = $appointment->getBookings()->getItem($bookingKey);

                if ($booking->getStatus()->getValue() !== BookingStatus::CANCELED &&
                    $newBooking->getCustomerId()->getValue() === $booking->getCustomerId()->getValue()) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setMessage(FrontendStrings::getCommonStrings()['customer_already_booked']);
                    $result->setData([
                        'customerAlreadyBooked' => true
                    ]);

                    return false;
                }
            }
        }

        $selectedExtras = [];

        foreach ($newBooking->getExtras()->keys() as $extraKey) {
            $selectedExtras[] = [
                'id'       => $newBooking->getExtras()->getItem($extraKey)->getExtraId()->getValue(),
                'quantity' => $newBooking->getExtras()->getItem($extraKey)->getQuantity()->getValue(),
            ];
        }

        if (!$timeSlotService->isSlotFree(
            $appointment->getServiceId()->getValue(),
            $appointment->getBookingStart()->getValue(),
            $appointment->getBookingEnd()->getValue(),
            $appointment->getProviderId()->getValue(),
            $selectedExtras,
            $newBooking->getPersons()->getValue(),
            true
        )) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['time_slot_unavailable']);
            $result->setData([
                'timeSlotUnavailable' => true
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param array $appointmentData
     *
     * @return array|null
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getAppointmentData($appointmentData)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        // Convert UTC slot to slot in TimeZone based on Settings
        if ($appointmentData['bookings'][0]['utcOffset'] !== null &&
            $settingsService->getSetting('general', 'showClientTimeZone')) {

            $appointmentData['bookingStart'] = DateTimeService::getCustomDateTimeFromUtc(
                $appointmentData['bookingStart']
            );
        }

        return $appointmentData;
    }
}
