<?php

namespace AmeliaBooking\Application\Services\User;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Schedule\Period;
use AmeliaBooking\Domain\Entity\Schedule\PeriodService;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDay;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriod;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriodService;
use AmeliaBooking\Domain\Factory\Location\ProviderLocationFactory;
use AmeliaBooking\Domain\Factory\Schedule\TimeOutFactory;
use AmeliaBooking\Domain\Repository\User\UserRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\TimeSlot\TimeSlotService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Entity\Schedule\TimeOut;
use AmeliaBooking\Domain\Entity\Schedule\WeekDay;
use AmeliaBooking\Domain\Entity\Schedule\DayOff;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ProviderServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Google\GoogleCalendarRepository;
use AmeliaBooking\Infrastructure\Repository\Location\ProviderLocationRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\DayOffRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\PeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\PeriodServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayPeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayPeriodServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\TimeOutRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\WeekDayRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;

/**
 * Class ProviderApplicationService
 *
 * @package AmeliaBooking\Application\Services\User
 */
class ProviderApplicationService
{
    private $container;

    /**
     * ProviderApplicationService constructor.
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
     * @param Provider $user
     *
     * @return boolean
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function add($user)
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var ProviderServiceRepository $providerServiceRepo */
        $providerServiceRepo = $this->container->get('domain.bookable.service.providerService.repository');

        /** @var ProviderLocationRepository $providerLocationRepo */
        $providerLocationRepo = $this->container->get('domain.bookable.service.providerLocation.repository');

        /** @var DayOffRepository $dayOffRepository */
        $dayOffRepository = $this->container->get('domain.schedule.dayOff.repository');

        /** @var WeekDayRepository $weekDayRepository */
        $weekDayRepository = $this->container->get('domain.schedule.weekDay.repository');

        /** @var TimeOutRepository $timeOutRepository */
        $timeOutRepository = $this->container->get('domain.schedule.timeOut.repository');

        /** @var PeriodRepository $periodRepository */
        $periodRepository = $this->container->get('domain.schedule.period.repository');

        /** @var PeriodServiceRepository $periodServiceRepository */
        $periodServiceRepository = $this->container->get('domain.schedule.period.service.repository');

        /** @var SpecialDayRepository $specialDayRepository */
        $specialDayRepository = $this->container->get('domain.schedule.specialDay.repository');

        /** @var SpecialDayPeriodRepository $specialDayPeriodRepository */
        $specialDayPeriodRepository = $this->container->get('domain.schedule.specialDay.period.repository');

        /** @var SpecialDayPeriodServiceRepository $specialDayPeriodServiceRepository */
        $specialDayPeriodServiceRepository = $this->container->get('domain.schedule.specialDay.period.service.repository');

        // add provider
        $userId = $userRepository->add($user);

        $user->setId(new Id($userId));


        if ($user->getLocationId()) {
            $providerLocation = ProviderLocationFactory::create([
                'userId'     => $userId,
                'locationId' => $user->getLocationId()->getValue()
            ]);

            $providerLocationRepo->add($providerLocation);
        }


        /**
         * Add provider services
         */
        foreach ((array)$user->getServiceList()->keys() as $key) {
            if (!($service = $user->getServiceList()->getItem($key)) instanceof Service) {
                throw new InvalidArgumentException('Unknown type');
            }

            $providerServiceRepo->add($service, $user->getId()->getValue());
        }


        // add provider day off
        foreach ((array)$user->getDayOffList()->keys() as $key) {
            if (!($providerDayOff = $user->getDayOffList()->getItem($key)) instanceof DayOff) {
                throw new InvalidArgumentException('Unknown type');
            }

            $providerDayOffId = $dayOffRepository->add($providerDayOff, $user->getId()->getValue());

            $providerDayOff->setId(new Id($providerDayOffId));
        }


        // add provider week day / time out
        foreach ((array)$user->getWeekDayList()->keys() as $weekDayKey) {
            // add day work hours
            /** @var WeekDay $weekDay */
            if (!($weekDay = $user->getWeekDayList()->getItem($weekDayKey)) instanceof WeekDay) {
                throw new InvalidArgumentException('Unknown type');
            }

            $weekDayId = $weekDayRepository->add($weekDay, $user->getId()->getValue());

            $weekDay->setId(new Id($weekDayId));


            // add day time out values
            foreach ((array)$weekDay->getTimeOutList()->keys() as $timeOutKey) {
                /** @var TimeOut $timeOut */
                if (!($timeOut = $weekDay->getTimeOutList()->getItem($timeOutKey)) instanceof TimeOut) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $timeOutId = $timeOutRepository->add($timeOut, $weekDayId);

                $timeOut->setId(new Id($timeOutId));
            }


            // add day period values
            foreach ((array)$weekDay->getPeriodList()->keys() as $periodKey) {
                /** @var Period $period */
                if (!($period = $weekDay->getPeriodList()->getItem($periodKey)) instanceof Period) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $periodId = $periodRepository->add($period, $weekDay->getId()->getValue());

                foreach ((array)$period->getPeriodServiceList()->keys() as $periodServiceKey) {
                    /** @var PeriodService $periodService */
                    $periodService = $period->getPeriodServiceList()->getItem($periodServiceKey);

                    $periodServiceRepository->add($periodService, $periodId);
                }
            }
        }

        foreach ((array)$user->getSpecialDayList()->keys() as $specialDayKey) {
            // add special day work hours
            /** @var SpecialDay $specialDay */
            if (!($specialDay = $user->getSpecialDayList()->getItem($specialDayKey)) instanceof SpecialDay) {
                throw new InvalidArgumentException('Unknown type');
            }

            $specialDayId = $specialDayRepository->add($specialDay, $user->getId()->getValue());

            $specialDay->setId(new Id($specialDayId));

            // add special day period values
            foreach ((array)$specialDay->getPeriodList()->keys() as $periodKey) {
                /** @var SpecialDayPeriod $period */
                if (!($period = $specialDay->getPeriodList()->getItem($periodKey)) instanceof SpecialDayPeriod) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $periodId = $specialDayPeriodRepository->add($period, $specialDay->getId()->getValue());

                foreach ((array)$period->getPeriodServiceList()->keys() as $periodServiceKey) {
                    /** @var SpecialDayPeriodService $periodService */
                    $periodService = $period->getPeriodServiceList()->getItem($periodServiceKey);

                    $specialDayPeriodServiceRepository->add($periodService, $periodId);
                }
            }
        }

        return $userId;
    }

    /**
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function update($oldUser, $newUser)
    {
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        // update provider
        $userRepository->update($oldUser->getId()->getValue(), $newUser);

        $this->updateProviderLocations($oldUser, $newUser);
        $this->updateProviderServices($newUser);
        $this->updateProviderDaysOff($oldUser, $newUser);
        $this->updateProviderWorkDays($oldUser, $newUser);
        $this->updateProviderSpecialDays($oldUser, $newUser);

        if ($newUser->getGoogleCalendar() && $newUser->getGoogleCalendar()->getId()) {
            $this->updateProviderGoogleCalendar($newUser);
        }

        return true;
    }

    /**
     * Update provider week day / time out
     *
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function updateProviderWorkDays($oldUser, $newUser)
    {
        /** @var WeekDayRepository $weekDayRepository */
        $weekDayRepository = $this->container->get('domain.schedule.weekDay.repository');

        /** @var TimeOutRepository $timeOutRepository */
        $timeOutRepository = $this->container->get('domain.schedule.timeOut.repository');

        /** @var PeriodRepository $periodRepository */
        $periodRepository = $this->container->get('domain.schedule.period.repository');

        /** @var PeriodServiceRepository $periodServiceRepository */
        $periodServiceRepository = $this->container->get('domain.schedule.period.service.repository');

        $existingWeekDayIds = [];

        foreach ((array)$newUser->getWeekDayList()->keys() as $newUserWeekDayKey) {
            // add day work hours
            /** @var WeekDay $newWeekDay */
            $newWeekDay = $newUser->getWeekDayList()->getItem($newUserWeekDayKey);

            // update week day if ID exist
            if ($newWeekDay->getId() && $newWeekDay->getId()->getValue()) {
                $weekDayRepository->update($newWeekDay, $newWeekDay->getId()->getValue());
            }

            // add week day off if ID does not exist
            if (!$newWeekDay->getId()) {
                $newWeekDayId = $weekDayRepository->add($newWeekDay, $newUser->getId()->getValue());

                $newWeekDay->setId(new Id($newWeekDayId));
            }

            $existingWeekDayIds[] = $newWeekDay->getId()->getValue();

            $existingTimeOutIds[$newWeekDay->getId()->getValue()] = [];

            $existingPeriodIds[$newWeekDay->getId()->getValue()] = [];

            $existingPeriodServicesIds[$newWeekDay->getId()->getValue()] = [];

            // add day time out values
            foreach ((array)$newWeekDay->getTimeOutList()->keys() as $newTimeOutKey) {
                /** @var TimeOut $newTimeOut */
                if (!($newTimeOut = $newWeekDay->getTimeOutList()->getItem($newTimeOutKey)) instanceof TimeOut) {
                    throw new InvalidArgumentException('Unknown type');
                }

                // update week day time out if ID exist
                if ($newTimeOut->getId() && $newTimeOut->getId()->getValue()) {
                    $timeOutRepository->update($newTimeOut, $newTimeOut->getId()->getValue());
                }

                // add week day time out if ID does not exist
                if (!$newTimeOut->getId()) {
                    $newTimeOutId = $timeOutRepository->add($newTimeOut, $newWeekDay->getId()->getValue());

                    $newTimeOut->setId(new Id($newTimeOutId));
                }

                $existingTimeOutIds[$newWeekDay->getId()->getValue()][] = $newTimeOut->getId()->getValue();
            }

            // add day period values
            foreach ((array)$newWeekDay->getPeriodList()->keys() as $newPeriodKey) {
                /** @var Period $newPeriod */
                if (!($newPeriod = $newWeekDay->getPeriodList()->getItem($newPeriodKey)) instanceof Period) {
                    throw new InvalidArgumentException('Unknown type');
                }

                // update week day period if ID exist
                if ($newPeriod->getId() && $newPeriod->getId()->getValue()) {
                    $periodRepository->update($newPeriod, $newPeriod->getId()->getValue());

                    $existingPeriodServicesIds[$newWeekDay->getId()->getValue()][$newPeriod->getId()->getValue()] = [];

                    foreach ((array)$newPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                        /** @var PeriodService $periodService */
                        $periodService = $newPeriod->getPeriodServiceList()->getItem($periodServiceKey);

                        if (!$periodService->getId()) {
                            $periodServiceId = $periodServiceRepository->add(
                                $periodService, $newPeriod->getId()->getValue()
                            );

                            $periodService->setId(new Id($periodServiceId));
                        }

                        $existingPeriodServicesIds[$newWeekDay->getId()->getValue()][$newPeriod->getId()->getValue()][]
                            = $periodService->getId()->getValue();
                    }
                }

                // add week day period if ID does not exist
                if (!$newPeriod->getId()) {
                    $newPeriodId = $periodRepository->add($newPeriod, $newWeekDay->getId()->getValue());

                    $newPeriod->setId(new Id($newPeriodId));

                    foreach ((array)$newPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                        /** @var PeriodService $periodService */
                        $periodService = $newPeriod->getPeriodServiceList()->getItem($periodServiceKey);

                        $periodServiceRepository->add($periodService, $newPeriodId);
                    }
                }

                $existingPeriodIds[$newWeekDay->getId()->getValue()][] = $newPeriod->getId()->getValue();
            }
        }

        // delete week day time out and period if not exist in new week day time out list and period list
        foreach ((array)$oldUser->getWeekDayList()->keys() as $oldUserKey) {
            /** @var WeekDay $oldWeekDay */
            if (!($oldWeekDay = $oldUser->getWeekDayList()->getItem($oldUserKey)) instanceof WeekDay) {
                throw new InvalidArgumentException('Unknown type');
            }

            $oldWeekDayId = $oldWeekDay->getId()->getValue();

            if (!in_array($oldWeekDayId, $existingWeekDayIds, true)) {
                $weekDayRepository->delete($oldWeekDayId);
            }

            foreach ((array)$oldWeekDay->getTimeOutList()->keys() as $oldTimeOutKey) {
                if (!($oldTimeOut = $oldWeekDay->getTimeOutList()->getItem($oldTimeOutKey)) instanceof TimeOut) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $oldTimeOutId = $oldTimeOut->getId()->getValue();

                if (isset($existingTimeOutIds[$oldWeekDayId]) &&
                    !in_array($oldTimeOutId, $existingTimeOutIds[$oldWeekDayId], true)) {
                    $timeOutRepository->delete($oldTimeOutId);
                }
            }

            foreach ((array)$oldWeekDay->getPeriodList()->keys() as $oldPeriodKey) {
                if (!($oldPeriod = $oldWeekDay->getPeriodList()->getItem($oldPeriodKey)) instanceof Period) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $oldPeriodId = $oldPeriod->getId()->getValue();

                if (isset($existingPeriodIds[$oldWeekDayId]) &&
                    !in_array($oldPeriodId, $existingPeriodIds[$oldWeekDayId], true)) {
                    $periodRepository->delete($oldPeriodId);
                }

                foreach ((array)$oldPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                    $oldPeriodServiceId = $oldPeriod->getPeriodServiceList()
                        ->getItem($periodServiceKey)->getId()->getValue();

                    if (isset($existingPeriodServicesIds[$oldWeekDayId][$oldPeriodId]) &&
                        !in_array($oldPeriodServiceId, $existingPeriodServicesIds[$oldWeekDayId][$oldPeriodId], true)) {
                        $periodServiceRepository->delete($oldPeriodServiceId);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Update provider special day
     *
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function updateProviderSpecialDays($oldUser, $newUser)
    {
        /** @var SpecialDayRepository $specialDayRepository */
        $specialDayRepository = $this->container->get('domain.schedule.specialDay.repository');

        /** @var SpecialDayPeriodRepository $specialDayPeriodRepository */
        $specialDayPeriodRepository = $this->container->get('domain.schedule.specialDay.period.repository');

        /** @var SpecialDayPeriodServiceRepository $specialDayPeriodServiceRepository */
        $specialDayPeriodServiceRepository = $this->container->get('domain.schedule.specialDay.period.service.repository');

        $existingSpecialDayIds = [];

        foreach ((array)$newUser->getSpecialDayList()->keys() as $newUserSpecialDayKey) {
            // add special day work hours
            /** @var SpecialDay $newSpecialDay */
            $newSpecialDay = $newUser->getSpecialDayList()->getItem($newUserSpecialDayKey);

            // update special day if ID exist
            if ($newSpecialDay->getId() && $newSpecialDay->getId()->getValue()) {
                $specialDayRepository->update($newSpecialDay, $newSpecialDay->getId()->getValue());
            }

            // add special day if ID does not exist
            if (!$newSpecialDay->getId()) {
                $newSpecialDayId = $specialDayRepository->add($newSpecialDay, $newUser->getId()->getValue());

                $newSpecialDay->setId(new Id($newSpecialDayId));
            }

            $existingSpecialDayIds[] = $newSpecialDay->getId()->getValue();

            $existingSpecialDayPeriodIds[$newSpecialDay->getId()->getValue()] = [];

            $existingSpecialDayPeriodServicesIds[$newSpecialDay->getId()->getValue()] = [];

            // add day period values
            foreach ((array)$newSpecialDay->getPeriodList()->keys() as $newPeriodKey) {
                /** @var SpecialDayPeriod $newPeriod */
                if (!($newPeriod = $newSpecialDay->getPeriodList()->getItem($newPeriodKey)) instanceof SpecialDayPeriod) {
                    throw new InvalidArgumentException('Unknown type');
                }

                // update special day period if ID exist
                if ($newPeriod->getId() && $newPeriod->getId()->getValue()) {
                    $specialDayPeriodRepository->update($newPeriod, $newPeriod->getId()->getValue());

                    $existingSpecialDayPeriodServicesIds
                    [$newSpecialDay->getId()->getValue()]
                    [$newPeriod->getId()->getValue()] = [];

                    foreach ((array)$newPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                        /** @var SpecialDayPeriodService $periodService */
                        $periodService = $newPeriod->getPeriodServiceList()->getItem($periodServiceKey);

                        if (!$periodService->getId()) {
                            $periodServiceId = $specialDayPeriodServiceRepository->add(
                                $periodService, $newPeriod->getId()->getValue()
                            );

                            $periodService->setId(new Id($periodServiceId));
                        }

                        $existingSpecialDayPeriodServicesIds
                        [$newSpecialDay->getId()->getValue()]
                        [$newPeriod->getId()->getValue()][] = $periodService->getId()->getValue();
                    }
                }

                // add special day period if ID does not exist
                if (!$newPeriod->getId()) {
                    $newPeriodId = $specialDayPeriodRepository->add($newPeriod, $newSpecialDay->getId()->getValue());

                    $newPeriod->setId(new Id($newPeriodId));

                    foreach ((array)$newPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                        /** @var SpecialDayPeriodService $periodService */
                        $periodService = $newPeriod->getPeriodServiceList()->getItem($periodServiceKey);

                        $specialDayPeriodServiceRepository->add($periodService, $newPeriodId);
                    }
                }

                $existingSpecialDayPeriodIds[$newSpecialDay->getId()->getValue()][] = $newPeriod->getId()->getValue();
            }
        }

        // delete week day time out and period if not exist in new week day time out list and period list
        foreach ((array)$oldUser->getSpecialDayList()->keys() as $oldUserKey) {
            /** @var SpecialDay $oldSpecialDay */
            if (!($oldSpecialDay = $oldUser->getSpecialDayList()->getItem($oldUserKey)) instanceof SpecialDay) {
                throw new InvalidArgumentException('Unknown type');
            }

            $oldSpecialDayId = $oldSpecialDay->getId()->getValue();

            if (!in_array($oldSpecialDayId, $existingSpecialDayIds, true)) {
                $specialDayRepository->delete($oldSpecialDayId);
            }

            foreach ((array)$oldSpecialDay->getPeriodList()->keys() as $oldPeriodKey) {
                if (!($oldPeriod = $oldSpecialDay->getPeriodList()->getItem($oldPeriodKey)) instanceof SpecialDayPeriod) {
                    throw new InvalidArgumentException('Unknown type');
                }

                $oldPeriodId = $oldPeriod->getId()->getValue();

                if (isset($existingSpecialDayPeriodIds[$oldSpecialDayId]) &&
                    !in_array($oldPeriodId, $existingSpecialDayPeriodIds[$oldSpecialDayId], true)) {
                    $specialDayPeriodRepository->delete($oldPeriodId);
                }

                foreach ((array)$oldPeriod->getPeriodServiceList()->keys() as $periodServiceKey) {
                    $oldPeriodServiceId = $oldPeriod->getPeriodServiceList()
                        ->getItem($periodServiceKey)->getId()->getValue();

                    if (isset($existingSpecialDayPeriodServicesIds[$oldSpecialDayId][$oldPeriodId]) &&
                        !in_array(
                            $oldPeriodServiceId,
                            $existingSpecialDayPeriodServicesIds[$oldSpecialDayId][$oldPeriodId],
                            true)
                    ) {
                        $specialDayPeriodServiceRepository->delete($oldPeriodServiceId);
                    }
                }
            }
        }

        return true;
    }


    /**
     * @param array $providers
     * @param bool  $companyDayOff
     *
     * @return array
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageProvidersActivity($providers, $companyDayOff)
    {

        if ($companyDayOff === false) {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = $this->container->get('domain.users.providers.repository');
            /** @var AppointmentRepository $appointmentRepo */
            $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

            $availableProviders = $providerRepository->getAvailable((int)date('w'));
            $onBreakProviders = $providerRepository->getOnBreak((int)date('w'));
            $onVacationProviders = $providerRepository->getOnVacation();
            $busyProviders = $appointmentRepo->getCurrentAppointments();
            $specialDayProviders = $providerRepository->getOnSpecialDay();

            foreach ($providers as &$provider) {
                if (array_key_exists($provider['id'], $availableProviders)) {
                    $provider['activity'] = 'available';
                } else {
                    $provider['activity'] = 'away';
                }

                if (array_key_exists($provider['id'], $onBreakProviders)) {
                    $provider['activity'] = 'break';
                }

                if (array_key_exists($provider['id'], $specialDayProviders)) {
                    $provider['activity'] = $specialDayProviders[$provider['id']]['available'] ? 'available' : 'away';
                }

                if (array_key_exists($provider['id'], $busyProviders)) {
                    $provider['activity'] = 'busy';
                }

                if (array_key_exists($provider['id'], $onVacationProviders)) {
                    $provider['activity'] = 'dayoff';
                }
            }
        } else {
            foreach ($providers as &$provider) {
                $provider['activity'] = 'dayoff';
            }
        }

        return $providers;
    }

    /**
     * @param $companyDaysOff
     *
     * @return bool
     */
    public function checkIfTodayIsCompanyDayOff($companyDaysOff)
    {
        $currentDate = DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0);

        $dayOff = false;
        foreach ((array)$companyDaysOff as $companyDayOff) {
            if ($currentDate >= DateTimeService::getCustomDateTimeObject($companyDayOff['startDate']) &&
                $currentDate <= DateTimeService::getCustomDateTimeObject($companyDayOff['endDate'])) {
                $dayOff = true;
                break;
            }
        }

        return $dayOff;
    }

    /**
     * @param array $providers
     *
     * @return array
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function removeAllExceptCurrentUser($providers)
    {
        /** @var Provider $currentUser */
        $currentUser = $this->container->get('logged.in.user');

        if ($currentUser !== null &&
            $currentUser->getType() === 'provider' &&
            !$this->container->getPermissionsService()->currentUserCanReadOthers(Entities::APPOINTMENTS)
        ) {
            if ($currentUser->getId() === null) {
                return [];
            }

            $currentUserId = $currentUser->getId()->getValue();
            foreach ($providers as $key => $provider) {
                if ($provider['id'] !== $currentUserId) {
                    unset($providers[$key]);
                }
            }
        }

        return array_values($providers);
    }

    /**
     * Add appointments to provider's appointments list
     *
     * @param Collection $providers
     * @param Collection $appointments
     *
     * @throws InvalidArgumentException
     */
    public function addAppointmentsToAppointmentList($providers, $appointments)
    {
        foreach ($appointments->keys() as $appointmentKey) {
            $appointment = $appointments->getItem($appointmentKey);

            foreach ($providers->keys() as $providerKey) {
                $provider = $providers->getItem($providerKey);

                if ($appointment->getProviderId()->getValue() === $provider->getId()->getValue()) {
                    $provider->getAppointmentList()->addItem($appointment);
                    break;
                }
            }
        }
    }

    /**
     * @param Provider $newUser
     *
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function updateProviderGoogleCalendar($newUser)
    {
        /** @var GoogleCalendarRepository $googleCalendarRepository */
        $googleCalendarRepository = $this->container->get('domain.google.calendar.repository');

        $googleCalendarRepository->update(
            $newUser->getGoogleCalendar(),
            $newUser->getGoogleCalendar()->getId()->getValue()
        );
    }

    /**
     * Update provider locations
     *
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    private function updateProviderLocations($oldUser, $newUser)
    {
        /** @var ProviderLocationRepository $providerLocationRepo */
        $providerLocationRepo = $this->container->get('domain.bookable.service.providerLocation.repository');

        if ($oldUser->getLocationId() && $newUser->getLocationId()) {
            $providerLocation = ProviderLocationFactory::create([
                'userId'     => $newUser->getId()->getValue(),
                'locationId' => $newUser->getLocationId()->getValue()
            ]);

            $providerLocationRepo->update($providerLocation);
        } elseif ($newUser->getLocationId()) {
            $providerLocation = ProviderLocationFactory::create([
                'userId'     => $newUser->getId()->getValue(),
                'locationId' => $newUser->getLocationId()->getValue()
            ]);

            $providerLocationRepo->add($providerLocation);
        } elseif ($oldUser->getLocationId()) {
            $providerLocationRepo->delete($oldUser->getId()->getValue());
        }

        return true;
    }

    /**
     * Update provider services
     *
     * @param Provider $newUser
     *
     * @return boolean
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function updateProviderServices($newUser)
    {
        /** @var ProviderServiceRepository $providerServiceRepo */
        $providerServiceRepo = $this->container->get('domain.bookable.service.providerService.repository');

        $servicesIds = [];
        $services = $newUser->getServiceList();

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            $servicesIds[] = $service->getId()->getValue();
        }

        $providerServiceRepo->deleteAllNotInServicesArrayForProvider($servicesIds, $newUser->getId()->getValue());

        $existingServices = $providerServiceRepo->getAllForProvider($newUser->getId()->getValue());

        $existingServicesIds = [];

        foreach ($existingServices as $existingService) {
            $existingServicesIds[] = $existingService['serviceId'];
        }

        foreach ($services->getItems() as $service) {
            if (!in_array($service->getId()->getValue(), $existingServicesIds, false)) {
                $providerServiceRepo->add($service, $newUser->getId()->getValue());
            } else {
                foreach ($existingServices as $providerService) {
                    if ($providerService['serviceId'] === $service->getId()->getValue()) {
                        $providerServiceRepo->update($service, $providerService['id']);
                        break;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Update provider days off
     *
     * @param Provider $oldUser
     * @param Provider $newUser
     *
     * @return boolean
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function updateProviderDaysOff($oldUser, $newUser)
    {
        /** @var DayOffRepository $dayOffRepository */
        $dayOffRepository = $this->container->get('domain.schedule.dayOff.repository');

        $existingDayOffIds = [];

        foreach ((array)$newUser->getDayOffList()->keys() as $newUserKey) {
            $newDayOff = $newUser->getDayOffList()->getItem($newUserKey);

            // update day off if ID exist
            if ($newDayOff->getId() && $newDayOff->getId()->getValue()) {
                $dayOffRepository->update($newDayOff, $newDayOff->getId()->getValue());
            }

            // add new day off if ID does not exist
            if ($newDayOff->getId() === null || $newDayOff->getId()->getValue() === 0) {
                $newDayOffId = $dayOffRepository->add($newDayOff, $newUser->getId()->getValue());

                $newDayOff->setId(new Id($newDayOffId));
            }

            $existingDayOffIds[] = $newDayOff->getId()->getValue();
        }

        // delete day off if not exist in new day off list
        foreach ((array)$oldUser->getDayOffList()->keys() as $oldUserKey) {
            $oldDayOff = $oldUser->getDayOffList()->getItem($oldUserKey);

            if (!in_array($oldDayOff->getId()->getValue(), $existingDayOffIds, true)) {
                $dayOffRepository->delete($oldDayOff->getId()->getValue());
            }
        }

        return true;
    }

    /**
     * set provider schedule by service
     *
     * @param Provider $employee
     * @param int      $serviceId
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function setServicePeriodSchedule(Provider $employee, $serviceId)
    {
        /** @var TimeSlotService $timeSlotService */
        $timeSlotService = $this->container->get('domain.timeSlot.service');

        $newWeekDaysTimeOuts = [];

        foreach ($employee->getWeekDayList()->keys() as $weekDayKey) {
            /** @var WeekDay $weekDay */
            $weekDay = $employee->getWeekDayList()->getItem($weekDayKey);

            $availableIntervals = [];
            $minStartPeriod = null;
            $maxEndPeriod = null;

            foreach ((array)$weekDay->getPeriodList()->keys() as $periodKey) {
                /** @var Period $period */
                $period = $weekDay->getPeriodList()->getItem($periodKey);

                $hasPeriodService = false;

                foreach ((array)$period->getPeriodServiceList()->keys() as $periodServiceKey) {
                    /** @var PeriodService $periodService */
                    $periodService = $period->getPeriodServiceList()->getItem($periodServiceKey);

                    // get available intervals by service
                    if ($periodService->getServiceId()->getValue() === $serviceId) {
                        $hasPeriodService = true;
                    }
                }

                if ($hasPeriodService || $period->getPeriodServiceList()->length() === 0) {
                    $startPeriod = $timeSlotService->getSeconds(
                        $period->getStartTime()->getValue()->format('H:i:s')
                    );

                    $endPeriod = $timeSlotService->getSeconds(
                        $period->getEndTime()->getValue()->format('H:i:s') === '00:00:00' ? '24:00:00' :
                            $period->getEndTime()->getValue()->format('H:i:s')
                    );

                    if ($minStartPeriod === null || $startPeriod < $minStartPeriod) {
                        $minStartPeriod = $startPeriod;
                    }

                    if ($maxEndPeriod === null || $endPeriod > $maxEndPeriod) {
                        $maxEndPeriod = $endPeriod;
                    }

                    $availableIntervals[$startPeriod] = [
                        $startPeriod,
                        $endPeriod
                    ];
                }
            }

            // get unavailable intervals
            $unavailableIntervals = $availableIntervals ? $timeSlotService->getFreeIntervals(
                $timeSlotService->mergeOverlappedIntervals($availableIntervals),
                $minStartPeriod ?: $timeSlotService->getSeconds($weekDay->getStartTime()->getValue()->format('H:i:s')),
                $maxEndPeriod ?: $timeSlotService->getSeconds(
                    $weekDay->getEndTime()->getValue()->format('H:i:s') === '00:00:00' ? '24:00:00' :
                        $weekDay->getEndTime()->getValue()->format('H:i:s')
                )
            ) : [];

            foreach ($weekDay->getTimeOutList()->keys() as $timeOutKey) {
                /** @var TimeOut $timeOut */
                $timeOut = $weekDay->getTimeOutList()->getItem($timeOutKey);

                $timeOutStartTime = $timeSlotService->getSeconds($timeOut->getStartTime()->getValue()->format('H:i:s'));
                $timeOutEndTime = $timeSlotService->getSeconds($timeOut->getEndTime()->getValue()->format('H:i:s'));

                if (array_key_exists($timeOutStartTime, $unavailableIntervals) &&
                    $timeOutEndTime < $unavailableIntervals[$timeOutStartTime][1]) {
                    continue;
                }

                $unavailableIntervals[$timeOutStartTime] = [
                    $timeOutStartTime,
                    $timeOutEndTime
                ];
            }

            if ($availableIntervals) {
                $newWeekDaysTimeOuts[$weekDayKey] = [
                    'startTime' => $minStartPeriod ?: $timeSlotService->getSeconds(
                        $weekDay->getStartTime()->getValue()->format('H:i:s')
                    ),
                    'endTime'   => $maxEndPeriod ?: $timeSlotService->getSeconds(
                        $weekDay->getEndTime()->getValue()->format('H:i:s') === '00:00:00' ? '24:00:00' :
                            $weekDay->getEndTime()->getValue()->format('H:i:s')
                    ),
                    'intervals' => $unavailableIntervals ? $timeSlotService->mergeOverlappedIntervals($unavailableIntervals) : []
                ];
            }
        }

        foreach ($employee->getWeekDayList()->keys() as $weekDayKey) {
            /** @var WeekDay $weekDay */
            $weekDay = $employee->getWeekDayList()->getItem($weekDayKey);

            // skip if week day exist but periods don't (employees saved with older version)
            if ($weekDay->getPeriodList()->length()) {
                // remove week day if periods don't have service
                if (isset($newWeekDaysTimeOuts[$weekDayKey])) {
                    $weekDay->setStartTime(
                        new DateTimeValue(
                            \DateTime::createFromFormat('H:i:s',
                                sprintf('%02d', floor($newWeekDaysTimeOuts[$weekDayKey]['startTime'] / 3600)) . ':'
                                . sprintf('%02d', floor(($newWeekDaysTimeOuts[$weekDayKey]['startTime'] / 60) % 60)) . ':00'
                            )
                        )
                    );

                    $weekDay->setEndTime(
                        new DateTimeValue(
                            \DateTime::createFromFormat('H:i:s',
                                sprintf('%02d', floor($newWeekDaysTimeOuts[$weekDayKey]['endTime'] / 3600)) . ':'
                                . sprintf('%02d', floor(($newWeekDaysTimeOuts[$weekDayKey]['endTime'] / 60) % 60)) . ':00'
                            )
                        )
                    );

                    $weekDay->setTimeOutList(new Collection());

                    foreach ((array)$newWeekDaysTimeOuts[$weekDayKey]['intervals'] as $interval) {
                        $weekDay->getTimeOutList()->addItem(
                            TimeOutFactory::create(
                                [
                                    'startTime' => sprintf('%02d', floor($interval[0] / 3600)) . ':'
                                        . sprintf('%02d', floor(($interval[0] / 60) % 60)) . ':00',
                                    'endTime'   => sprintf('%02d', floor($interval[1] / 3600)) . ':'
                                        . sprintf('%02d', floor(($interval[1] / 60) % 60)) . ':00'
                                ]
                            )
                        );
                    }
                } else {
                    $employee->getWeekDayList()->deleteItem($weekDayKey);
                }
            }
        }
    }
}
