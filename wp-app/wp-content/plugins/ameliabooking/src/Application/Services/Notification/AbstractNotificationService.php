<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\String\NotificationStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationLogRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;
use AmeliaBooking\Infrastructure\Services\Notification\MailgunService;
use AmeliaBooking\Infrastructure\Services\Notification\PHPMailService;
use AmeliaBooking\Infrastructure\Services\Notification\SMTPService;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class AbstractNotificationService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
abstract class AbstractNotificationService
{
    /** @var Container */
    protected $container;

    /** @var string */
    protected $type;

    /**
     * ProviderApplicationService constructor.
     *
     * @param Container $container
     * @param string    $type
     */
    public function __construct(Container $container, $type)
    {
        $this->container = $container;
        $this->type = $type;
    }

    /**
     * @param array        $appointmentArray
     * @param Notification $notification
     * @param bool         $logNotification
     * @param null         $bookingKey
     *
     * @return mixed
     */
    abstract public function sendNotification(
        $appointmentArray,
        $notification,
        $logNotification,
        $bookingKey = null
    );


    /**
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \Exception
     */
    abstract public function sendBirthdayGreetingNotifications();

    /**z
     *
     * @param string $name
     * @param string $type
     *
     * @return mixed
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getByNameAndType($name, $type)
    {
        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');

        return $notificationRepo->getByNameAndType($name, $type);
    }

    /**
     * @param array $appointmentArray
     * @param bool  $forcedStatusChange - True when appointment status is changed to 'pending' because minimum capacity
     * condition is not satisfied
     * @param bool  $logNotification
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function sendAppointmentStatusNotifications($appointmentArray, $forcedStatusChange, $logNotification)
    {
        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        // Notify provider
        /** @var Notification $providerNotification */
        $providerNotification =
            $this->getByNameAndType("provider_appointment_{$appointmentArray['status']}", $this->type);

        if ($providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
            $this->sendNotification(
                $appointmentArray,
                $providerNotification,
                $logNotification
            );
        }

        // Notify customers
        if ($appointmentArray['notifyParticipants']) {

            /** @var Notification $customerNotification */
            $customerNotification = $this->getByNameAndType(
                "customer_appointment_{$appointmentArray['status']}",
                $this->type
            );

            if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                // If appointment status is changed to 'pending' because minimum capacity condition is not satisfied,
                // return all 'approved' bookings and send them notification that appointment is now 'pending'.
                if ($forcedStatusChange === true) {
                    $appointmentArray['bookings'] = $bookingAS->filterApprovedBookings($appointmentArray['bookings']);
                }

                // Notify each customer from customer bookings
                foreach (array_keys($appointmentArray['bookings']) as $bookingKey) {
                    $this->sendNotification(
                        $appointmentArray,
                        $customerNotification,
                        $logNotification,
                        $bookingKey
                    );
                }
            }
        }


    }

    /**
     * @param array $appointmentArray
     * @param array $bookingsArray
     * @param bool  $forcedStatusChange
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function sendAppointmentEditedNotifications($appointmentArray, $bookingsArray, $forcedStatusChange)
    {
        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        // Notify customers
        if ($appointmentArray['notifyParticipants']) {
            // If appointment status is 'pending', remove all 'approved' bookings because they can't receive
            // notification that booking is 'approved' until appointment status is changed to 'approved'
            if ($appointmentArray['status'] === 'pending') {
                $bookingsArray = $bookingAS->removeBookingsByStatuses($bookingsArray, ['approved']);
            }

            // If appointment status is changed, because minimum capacity condition is satisfied or not,
            // remove all 'approved' bookings because notification is already sent to them.
            if ($forcedStatusChange === true) {
                $bookingsArray = $bookingAS->removeBookingsByStatuses($bookingsArray, ['approved']);
            }

            $appointmentArray['bookings'] = $bookingsArray;

            foreach (array_keys($appointmentArray['bookings']) as $bookingKey) {
                /** @var Notification $customerNotification */
                $customerNotification =
                    $this->getByNameAndType(
                        "customer_appointment_{$appointmentArray['bookings'][$bookingKey]['status']}",
                        $this->type
                    );

                if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                    $this->sendNotification(
                        $appointmentArray,
                        $customerNotification,
                        true,
                        $bookingKey
                    );
                }
            }
        }
    }

    /**
     * @param $appointmentArray
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function sendAppointmentRescheduleNotifications($appointmentArray)
    {
        // Notify customers
        if ($appointmentArray['notifyParticipants']) {

            /** @var Notification $customerNotification */
            $customerNotification = $this->getByNameAndType('customer_appointment_rescheduled', $this->type);

            if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                // Notify each customer from customer bookings
                foreach (array_keys($appointmentArray['bookings']) as $bookingKey) {
                    $this->sendNotification(
                        $appointmentArray,
                        $customerNotification,
                        true,
                        $bookingKey
                    );
                }
            }
        }

        // Notify provider
        /** @var Notification $providerNotification */
        $providerNotification = $this->getByNameAndType('provider_appointment_rescheduled', $this->type);

        if ($providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
            $this->sendNotification(
                $appointmentArray,
                $providerNotification,
                true
            );
        }
    }

    /**
     * @param array $appointmentArray
     * @param array $bookingArray
     * @param bool  $logNotification
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function sendBookingAddedNotifications($appointmentArray, $bookingArray, $logNotification)
    {
        /** @var Notification $customerNotification */
        $customerNotification =
            $this->getByNameAndType("customer_appointment_{$appointmentArray['status']}", $this->type);

        if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
            // Notify customer that scheduled the appointment
            $this->sendNotification(
                $appointmentArray,
                $customerNotification,
                $logNotification,
                array_search($bookingArray['id'], array_column($appointmentArray['bookings'], 'id'), true)
            );
        }

        // Notify provider
        /** @var Notification $providerNotification */
        $providerNotification =
            $this->getByNameAndType("provider_appointment_{$appointmentArray['status']}", $this->type);

        if ($providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
            $this->sendNotification(
                $appointmentArray,
                $providerNotification,
                $logNotification
            );
        }
    }

    /**
     * Notify the customer when he change his booking status.
     *
     * @param $appointmentArray
     * @param $bookingArray
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function sendCancelBookingNotification($appointmentArray, $bookingArray)
    {
        // Notify customers
        if ($appointmentArray['notifyParticipants']) {

            /** @var Notification $customerNotification */
            $customerNotification =
                $this->getByNameAndType("customer_appointment_{$bookingArray['status']}", $this->type);

            if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                // Notify customer
                $bookingKey = array_search(
                    $bookingArray['id'],
                    array_column($appointmentArray['bookings'], 'id'),
                    true
                );

                $this->sendNotification(
                    $appointmentArray,
                    $customerNotification,
                    true,
                    $bookingKey
                );
            }
        }
    }

    /**
     * Returns an array of next day reminder notifications that have to be sent to customers with cron
     *
     * @return void
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function sendAppointmentNextDayReminderNotifications()
    {
        /** @var NotificationLogRepository $notificationLogRepo */
        $notificationLogRepo = $this->container->get('domain.notificationLog.repository');

        /** @var Notification $customerNotification */
        $customerNotification = $this->getByNameAndType('customer_appointment_next_day_reminder', $this->type);

        // Check if notification is enabled and it is time to send notification
        if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
            DateTimeService::getNowDateTimeObject() >=
            DateTimeService::getCustomDateTimeObject($customerNotification->getTime()->getValue())
        ) {
            $appointments = $notificationLogRepo->getCustomersNextDayAppointments($this->type);

            $this->sendBookingsNotifications($customerNotification, $appointments);
        }

        /** @var Notification $providerNotification */
        $providerNotification = $this->getByNameAndType('provider_appointment_next_day_reminder', $this->type);

        // Check if notification is enabled and it is time to send notification
        if ($providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
            DateTimeService::getNowDateTimeObject() >=
            DateTimeService::getCustomDateTimeObject($providerNotification->getTime()->getValue())
        ) {
            $appointments = $notificationLogRepo->getProvidersNextDayAppointments($this->type);

            foreach ((array)$appointments->toArray() as $appointmentArray) {
                $this->sendNotification(
                    $appointmentArray,
                    $providerNotification,
                    true
                );
            }
        }
    }

    /**
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function sendAppointmentFollowUpNotifications()
    {
        /** @var Notification $notification */
        $notification = $this->getByNameAndType('customer_appointment_follow_up', $this->type);

        if ($notification->getStatus()->getValue() === NotificationStatus::ENABLED) {
            /** @var NotificationLogRepository $notificationLogRepo */
            $notificationLogRepo = $this->container->get('domain.notificationLog.repository');

            $appointments = $notificationLogRepo->getFollowUpAppointments($notification);

            $this->sendBookingsNotifications($notification, $appointments);
        }
    }

    /**
     * @param $appointmentArray
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function sendLimitReachedNotification($appointmentArray)
    {
        /** @var PHPMailService|SMTPService|MailgunService $mailService */
        $mailService = $this->container->get('infrastructure.mail.service');

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var Appointment $appointment */
        $appointment = AppointmentFactory::create($appointmentArray);

        $mailService->send(
            $userRepository->getById($appointment->getProviderId()->getValue())->getEmail()->getValue(),
            BackendStrings::getAppointmentStrings()['notify_free_subject'],
            BackendStrings::getAppointmentStrings()['notify_free_body']
        );
    }

    /**
     * Send passed notification for all passed bookings and save log in the database
     *
     * @param Notification $notification
     * @param Collection   $appointments
     */
    private function sendBookingsNotifications($notification, $appointments)
    {
        /** @var array $appointmentArray */
        foreach ((array)$appointments->toArray() as $appointmentArray) {
            // Notify each customer from customer bookings
            foreach (array_keys($appointmentArray['bookings']) as $bookingKey) {
                $this->sendNotification(
                    $appointmentArray,
                    $notification,
                    true,
                    $bookingKey
                );
            }
        }
    }
}
