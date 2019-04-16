<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class CancelBookingRemotelyCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class CancelBookingRemotelyCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'token',
    ];

    /**
     * @param CancelBookingRemotelyCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws NotFoundException
     */
    public function handle(CancelBookingRemotelyCommand $command)
    {
        $this->checkMandatoryFields($command);

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $result = $bookingAS->updateStatus(
            (int)$command->getArg('id'),
            BookingStatus::CANCELED,
            $command->getField('token')
        );

        $notificationSettings = $settingsService->getCategorySettings('notifications');

        if ($notificationSettings['cancelSuccessUrl'] && $result->getResult() === CommandResult::RESULT_SUCCESS) {
            $result->setUrl($notificationSettings['cancelSuccessUrl']);
        } elseif ($notificationSettings['cancelErrorUrl'] && $result->getResult() === CommandResult::RESULT_ERROR) {
            $result->setUrl($notificationSettings['cancelErrorUrl']);
        } else {
            $result->setUrl('/');
        }

        return $result;
    }
}
