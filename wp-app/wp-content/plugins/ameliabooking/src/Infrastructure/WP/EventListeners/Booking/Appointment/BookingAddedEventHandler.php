<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Services\Google\GoogleCalendarService;

/**
 * Class BookingAddedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class BookingAddedEventHandler
{
    /** @var string */
    const BOOKING_ADDED = 'bookingAdded';

    /**
     * @param CommandResult $commandResult
     * @param Container     $container
     *
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public static function handle($commandResult, $container)
    {
        /** @var GoogleCalendarService $googleCalendarService */
        $googleCalendarService = $container->get('infrastructure.google.calendar.service');
        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $container->get('application.emailNotification.service');
        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $container->get('application.smsNotification.service');
        /** @var SettingsService $settingsService */
        $settingsService = $container->get('domain.settings.service');

        $appointment = $commandResult->getData()[Entities::APPOINTMENT];
        $booking = $commandResult->getData()[Entities::BOOKING];
        $appointmentStatusChanged = $commandResult->getData()['appointmentStatusChanged'];

        if ($googleCalendarService) {
            try {
                $googleCalendarService->handleEvent(AppointmentFactory::create($appointment), self::BOOKING_ADDED);
            } catch (\Exception $e) {
            }
        }

        if ($appointmentStatusChanged === true) {
            $emailNotificationService->sendAppointmentStatusNotifications($appointment, true, true);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendAppointmentStatusNotifications($appointment, true, true);
            }
        }

        if ($appointmentStatusChanged !== true) {
            $emailNotificationService->sendBookingAddedNotifications($appointment, $booking, true);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendBookingAddedNotifications($appointment, $booking, true);
            }
        }
    }
}
