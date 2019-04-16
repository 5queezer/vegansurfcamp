<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class CancelBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class CancelBookingCommandHandler extends CommandHandler
{
    /**
     * @param CancelBookingCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws NotFoundException
     */
    public function handle(CancelBookingCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWriteStatus(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to update booking status');
        }

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        $result = $bookingAS->updateStatus((int)$command->getArg('id'), BookingStatus::CANCELED, null);

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            throw new AccessDeniedException('Appointment can\'t be canceled');
        }

        return $result;
    }
}
