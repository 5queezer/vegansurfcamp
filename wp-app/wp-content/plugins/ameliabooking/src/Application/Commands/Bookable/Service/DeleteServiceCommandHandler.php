<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;

/**
 * Class DeleteServiceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class DeleteServiceCommandHandler extends CommandHandler
{
    /**
     * @param DeleteServiceCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(DeleteServiceCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanDelete(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to delete services.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->getContainer()->get('application.bookable.service');

        $appointmentsCount = $bookableAS->getAppointmentsCountForServices([$command->getArg('id')]);

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        if ($appointmentsCount['futureAppointments']) {
            $result->setResult(CommandResult::RESULT_CONFLICT);
            $result->setMessage('Could not delete service.');
            $result->setData([]);

            return $result;
        }

        if (!$serviceRepository->delete($command->getArg('id'))) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete service.');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted service.');
        $result->setData([]);

        return $result;
    }
}
