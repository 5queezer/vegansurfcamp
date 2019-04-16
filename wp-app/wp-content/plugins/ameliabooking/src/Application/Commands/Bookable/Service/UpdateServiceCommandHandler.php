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
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ProviderServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Gallery\GalleryRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;

/**
 * Class UpdateServiceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class UpdateServiceCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'categoryId',
        'duration',
        'maxCapacity',
        'minCapacity',
        'name',
        'price',
        'applyGlobally',
        'providers'
    ];

    /**
     * @param UpdateServiceCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateServiceCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to update service.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $service = ServiceFactory::create($command->getFields());
        if (!$service instanceof Service) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to update service.');

            return $result;
        }

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');
        /** @var ProviderServiceRepository $providerServiceRepo */
        $providerServiceRepo = $this->container->get('domain.bookable.service.providerService.repository');
        /** @var GalleryRepository $galleryRepository */
        $galleryRepository = $this->container->get('domain.galleries.repository');
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var BookableApplicationService $bookableService */
        $bookableService = $this->container->get('application.bookable.service');

        $serviceRepository->beginTransaction();

        $serviceId = $command->getArg('id');

        if ($command->getField('applyGlobally') &&
            !$providerServiceRepo->updateServiceForAllProviders($service, $serviceId)) {
            $serviceRepository->rollback();
        }

        if (!$serviceRepository->update($serviceId, $service)) {
            $serviceRepository->rollback();
        }

        $providers = $command->getField('providers') ?
            $providerRepository->getFiltered(['providers' => $command->getField('providers')], 0) : new Collection();

        $bookableService->manageProvidersForServiceUpdate(
            $service,
            $providers,
            $serviceRepository,
            $providerServiceRepo
        );
        $bookableService->manageExtrasForServiceUpdate($service, $serviceRepository, $extraRepository);
        $bookableService->manageGalleryForServiceUpdate($service, $serviceRepository, $galleryRepository);

        $serviceRepository->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated service.');
        $result->setData(
            [
                Entities::SERVICE => $service->toArray(),
            ]
        );

        return $result;
    }
}
