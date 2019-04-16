<?php

namespace AmeliaBooking\Application\Services\Bookable;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ProviderServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Gallery\GalleryRepository;

/**
 * Class BookableApplicationService
 *
 * @package AmeliaBooking\Application\Services\Booking
 */
class BookableApplicationService
{

    private $container;

    /**
     * TimeSlotService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Collection $categories
     * @param Collection $services
     *
     * @throws InvalidArgumentException
     */
    public function addServicesToCategories($categories, $services)
    {
        /** @var Category $category */
        foreach ($categories->getItems() as $category) {
            $category->setServiceList(new Collection());
        }

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            $categoryId = $service->getCategoryId()->getValue();

            $categories
                ->getItem($categoryId)
                ->getServiceList()
                ->addItem($service, $service->getId()->getValue());
        }
    }

    /**
     * @param Service    $service
     * @param Collection $providers
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageProvidersForServiceAdd($service, $providers)
    {
        /** @var ProviderServiceRepository $providerServiceRepo */
        $providerServiceRepo = $this->container->get('domain.bookable.service.providerService.repository');

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            $providerServiceRepo->add($service, $provider->getId()->getValue());
        }
    }

    /**
     * @param Service                   $service
     * @param Collection                $providers
     * @param ServiceRepository         $serviceRepository
     * @param ProviderServiceRepository $providerServiceRepo
     *
     * @throws QueryExecutionException
     */
    public function manageProvidersForServiceUpdate($service, $providers, $serviceRepository, $providerServiceRepo)
    {
        $providersIds = [];

        if ($providers !== null) {
            /** @var Provider $provider */
            foreach ($providers->getItems() as $provider) {
                $providersIds[] = $provider->getId()->getValue();
            }
        }

        if (!$providerServiceRepo->deleteAllNotInProvidersArrayForService(
            $providersIds,
            $service->getId()->getValue()
        )) {
            $serviceRepository->rollback();
        }

        if ($providers !== null) {
            $exitingProviders = $providerServiceRepo->getAllForService($service->getId()->getValue());
            $exitingProvidersIds = [];
            foreach ($exitingProviders as $exitingProvider) {
                $exitingProvidersIds[] = $exitingProvider['userId'];
            }
            foreach ($providers->getItems() as $provider) {
                if (!in_array($provider->getId()->getValue(), $exitingProvidersIds, false)) {
                    $providerServiceRepo->add($service, $provider->getId()->getValue());
                }
            }
        }
    }

    /**
     * @param Service $service
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageExtrasForServiceAdd($service)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        if ($service->getExtras() !== null) {
            $extras = $service->getExtras();
            foreach ($extras->getItems() as $extra) {
                /** @var Extra $extra */
                $extra->setServiceId(new Id($service->getId()->getValue()));

                if (!($extraId = $extraRepository->add($extra))) {
                    $serviceRepository->rollback();
                }

                $extra->setId(new Id($extraId));
            }
        }
    }

    /**
     * @param Service           $service
     * @param ServiceRepository $serviceRepository
     * @param ExtraRepository   $extraRepository
     *
     * @throws QueryExecutionException
     */
    public function manageExtrasForServiceUpdate($service, $serviceRepository, $extraRepository)
    {
        if ($service->getExtras() !== null) {
            $extras = $service->getExtras();
            foreach ($extras->getItems() as $extra) {
                /** @var Extra $extra */
                $extra->setServiceId(new Id($service->getId()->getValue()));
                if ($extra->getId() === null) {
                    if (!($extraId = $extraRepository->add($extra))) {
                        $serviceRepository->rollback();
                    }

                    $extra->setId(new Id($extraId));
                } else {
                    if (!$extraRepository->update($extra->getId()->getValue(), $extra)) {
                        $serviceRepository->rollback();
                    }
                }
            }
        }
    }

    /**
     * @param Service $service
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageGalleryForServiceAdd($service)
    {
        /** @var GalleryRepository $galleryRepository */
        $galleryRepository = $this->container->get('domain.galleries.repository');

        if ($service->getGallery() !== null) {
            $gallery = $service->getGallery();
            foreach ($gallery->getItems() as $image) {
                /** @var GalleryImage $image */
                $image->setEntityId(new Id($service->getId()->getValue()));

                if (!($imageId = $galleryRepository->add($image))) {
                    $galleryRepository->rollback();
                }

                $image->setId(new Id($imageId));
            }
        }
    }

    /**
     * @param Service           $service
     * @param ServiceRepository $serviceRepository
     * @param GalleryRepository $galleryServiceRepo
     *
     * @throws QueryExecutionException
     */
    public function manageGalleryForServiceUpdate($service, $serviceRepository, $galleryServiceRepo)
    {
        $imagesIds = [];
        $gallery = $service->getGallery();

        if ($gallery !== null) {
            /** @var GalleryImage $image */
            foreach ($gallery->getItems() as $image) {
                if ($image->getId()) {
                    $imagesIds[] = $image->getId()->getValue();
                }
            }
        }

        if (!$galleryServiceRepo->deleteAllNotInImagesArray(
            $imagesIds,
            $service->getId()->getValue(),
            Entities::SERVICE
        )) {
            $serviceRepository->rollback();
        }

        if ($gallery !== null) {
            /** @var GalleryImage $image */
            foreach ($gallery->getItems() as $image) {
                if (!$image->getId()) {
                    $galleryServiceRepo->add($image);
                } else {
                    $galleryServiceRepo->update($image->getId()->getValue(), $image);
                }
            }
        }
    }

    /**
     * @param Service $service
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageGalleryForServiceDelete($service)
    {
        /** @var GalleryRepository $galleryRepository */
        $galleryRepository = $this->container->get('domain.galleries.repository');

        if ($gallery = ($service->getGallery() !== null)) {
            foreach ((array)$gallery->getItems() as $image) {
                if (!$galleryRepository->delete($image->getId()->getValue())) {
                    $galleryRepository->rollback();
                }
            }
        }
    }

    /**
     * Accept two collection: services and providers
     * For each service function will add providers that are working on this service
     *
     * @param Service    $service
     * @param Collection $providers
     *
     * @return Collection
     *
     * @throws InvalidArgumentException
     */
    public function getServiceProviders($service, $providers)
    {
        $serviceProviders = new Collection();

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            /** @var Service $providerService */
            foreach ($provider->getServiceList()->getItems() as $providerService) {
                if ($providerService->getId()->getValue() === $service->getId()->getValue()) {
                    $serviceProviders->addItem($provider, $provider->getId()->getValue());
                }
            }
        }

        return $serviceProviders;
    }

    /**
     * Add 0 as duration for service time before or time after if it is null
     *
     * @param Service $service
     *
     * @throws InvalidArgumentException
     */
    public function checkServiceTimes($service)
    {
        if (!$service->getTimeBefore()) {
            $service->setTimeBefore(new Duration(0));
        }

        if (!$service->getTimeAfter()) {
            $service->setTimeAfter(new Duration(0));
        }
    }

    /**
     * Return collection of extras that are passed in $extraIds array for provided service
     *
     * @param array   $extraIds
     * @param Service $service
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function filterServiceExtras($extraIds, $service)
    {
        $extras = new Collection();

        foreach ((array)$service->getExtras()->keys() as $extraKey) {
            /** @var Extra $extra */
            $extra = $service->getExtras()->getItem($extraKey);

            if (in_array($extra->getId()->getValue(), $extraIds, false)) {
                if (!$extra->getDuration()) {
                    $extra->setDuration(new Duration(0));
                }

                $extras->addItem($extra, $extraKey);
            }
        }

        return $extras;
    }

    /**
     *
     * @param array $services
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getAppointmentsCountForServices($services)
    {
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var Collection $appointments */
        $appointments = $appointmentRepo->getFiltered(['services' => $services]);

        $now = DateTimeService::getNowDateTimeObject();

        $futureAppointments = 0;
        $pastAppointments = 0;

        foreach ((array)$appointments->keys() as $appointmentKey) {
            if ($appointments->getItem($appointmentKey)->getBookingStart()->getValue() >= $now) {
                $futureAppointments++;
            } else {
                $pastAppointments++;
            }
        }

        return [
            'futureAppointments' => $futureAppointments,
            'pastAppointments'   => $pastAppointments
        ];
    }
}
