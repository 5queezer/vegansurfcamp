<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Stats;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Stats\StatsService;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class GetStatsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Stats
 */
class GetStatsCommandHandler extends CommandHandler
{
    /**
     * @param GetStatsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function handle(GetStatsCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::DASHBOARD)) {
            throw new AccessDeniedException('You are not allowed to read coupons.');
        }

        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var StatsService $statsAS */
        $statsAS = $this->container->get('application.stats.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $params = $command->getField('params');

        if ($params['dates']) {
            $params['dates'][0] .= ' 00:00:00';
            $params['dates'][1] .= ' 23:59:59';
        }

        // Statistic
        $statistics = $statsAS->getStatisticsData(
            ['approvedAppointments', 'pendingAppointments', 'averageBookings', 'revenue'],
            $params
        );

        // Charts
        $customersStats = $statsAS->getCustomersStats($params);

        $employeesStats = $statsAS->getEmployeesStats($params);

        $servicesStats = $statsAS->getServicesStats($params);

        $locationsStats = $statsAS->getLocationsStats($params);

        // Today Appointments
        $todayAppointments = $appointmentRepo->getFiltered(['dates' => [
            DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            DateTimeService::getNowDateTimeObject()->setTime(23, 23, 59)->format('Y-m-d H:i:s')]
        ]);

        if (!$todayAppointments instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get appointments');

            return $result;
        }

        // Get general settings
        $generalSettings = $settingsDS->getCategorySettings('general');

        // Get current date time object
        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $todayAppointmentsArr = [];

        foreach ($todayAppointments->keys() as $appointmentKey) {
            /** @var Appointment $appointment */
            $appointment = $todayAppointments->getItem($appointmentKey);

            $minimumCancelTime = DateTimeService::getCustomDateTimeObject(
                $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
            )->modify("-{$generalSettings['minimumTimeRequirementPriorToCanceling']} seconds");

            $todayAppointmentsArr[] = array_merge(
                $appointment->toArray(),
                [
                    'cancelable' => $currentDateTime <= $minimumCancelTime,
                    'past'       => $currentDateTime >= $appointment->getBookingStart()->getValue()
                ]
            );
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved coupons.');
        $result->setData(
            [
                'stats'                => $statistics,
                'employeesStats'       => $employeesStats,
                'servicesStats'        => $servicesStats,
                'locationsStats'       => $locationsStats,
                'customersStats'       => $customersStats,
                Entities::APPOINTMENTS => $todayAppointmentsArr
            ]
        );

        return $result;
    }
}
