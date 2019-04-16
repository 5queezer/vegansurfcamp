<?php

namespace AmeliaBooking\Application\Services\Stats;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class StatsService
 *
 * @package AmeliaBooking\Application\Services\Stats
 */
class StatsService
{
    private $container;

    /**
     * StatsService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $statistics
     *
     * @param       $params
     *
     * @return array
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getStatisticsData($statistics, $params)
    {
        $data = [];

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var array $backendString */
        $backendString = BackendStrings::getDashboardStrings();

        /** @var Collection $appointments */
        $appointments = $appointmentRepo->getFiltered($params);

        foreach ($statistics as $statistic) {
            switch ($statistic) {
                case 'approvedAppointments':
                    $data[] = array_merge(
                        [
                            'name'    => $statistic,
                            'label'   => $backendString['approved_appointments'],
                            'tooltip' => $backendString['approved_appointments_tooltip']
                        ],
                        $this->getApprovedAppointments($appointments)
                    );

                    break;
                case 'pendingAppointments':
                    $data[] = array_merge(
                        [
                            'name'    => $statistic,
                            'label'   => $backendString['pending_appointments'],
                            'tooltip' => $backendString['pending_appointments_tooltip']
                        ],
                        $this->getPendingAppointments($appointments)
                    );

                    break;
                case 'averageBookings':
                    $data[] = array_merge(
                        [
                            'name'    => $statistic,
                            'label'   => $backendString['average_bookings'],
                            'tooltip' => $backendString['average_bookings_tooltip']
                        ],
                        $this->getAverageBookings($appointments, $params)
                    );

                    break;
                case 'revenue':
                    $data[] = array_merge(
                        [
                            'name'    => $statistic,
                            'label'   => $backendString['revenue'],
                            'tooltip' => $backendString['revenue_tooltip']
                        ],
                        $this->getRevenue($params)
                    );

                    break;
            }
        }

        return $data;
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getCustomersStats($params)
    {
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var array $returningCustomers */
        $returningCustomers = array_column($bookingRepository->getReturningCustomers($params), 'customerId');

        /** @var array $bookings */
        $bookings = array_column($bookingRepository->getFilteredDistinctCustomersIds($params), 'customerId');

        // Calculate number of customers in past period.
        // E.g. If in a date filter is selected current week, calculate it for past week.
        $dateFrom = DateTimeService::getCustomDateTimeObject($params['dates'][0]);
        $dateTo = DateTimeService::getCustomDateTimeObject($params['dates'][1]);

        $diff = (int)$dateTo->diff($dateFrom)->format('%a') + 1;

        $dateFrom->modify('-' . $diff . 'days');
        $dateTo->modify('-' . $diff . 'days');

        $paramsPast = ['dates' => [$dateFrom->format('Y-m-d H:i:s'), $dateTo->format('Y-m-d H:i:s')]];

        $bookingsPast = array_column($bookingRepository->getFilteredDistinctCustomersIds($paramsPast), 'customerId');
        $pastPeriodCount = count($bookingsPast);

        $returningCount = count(array_intersect($returningCustomers, $bookings));
        $newCount = count($bookings) - $returningCount;

        return [
            'newCustomersCount'        => $newCount,
            'returningCustomersCount'  => $returningCount,
            'totalPastPeriodCustomers' => $pastPeriodCount
        ];
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getEmployeesStats($params)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $appointments = $providerRepository->getAllNumberOfAppointments($params);

        $views = $providerRepository->getAllNumberOfViews($params);

        return array_values(array_replace_recursive($appointments, $views));
    }

    /**
     * @param $providerId
     *
     * @return bool
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     */
    public function addEmployeesViewsStats($providerId)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $providerRepository->beginTransaction();

        if (!$providerRepository->addViewStats($providerId)) {
            $providerRepository->rollback();

            return false;
        }

        return $providerRepository->commit();
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getServicesStats($params)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        $appointments = $serviceRepository->getAllNumberOfAppointments($params);

        $views = $serviceRepository->getAllNumberOfViews($params);

        return array_values(array_replace_recursive($appointments, $views));
    }

    /**
     * @param $serviceId
     *
     * @return bool
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     */
    public function addServicesViewsStats($serviceId)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        $serviceRepository->beginTransaction();

        if (!$serviceRepository->addViewStats($serviceId)) {
            $serviceRepository->rollback();

            return false;
        }

        return $serviceRepository->commit();
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getLocationsStats($params)
    {
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        $appointments = $locationRepository->getAllNumberOfAppointments($params);

        $views = $locationRepository->getAllNumberOfViews($params);

        return array_values(array_replace_recursive($appointments, $views));
    }

    /**
     * @param $locationId
     *
     * @return bool
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function addLocationsViewsStats($locationId)
    {
        /** @var LocationRepository $locationRepository */

        if ($locationId) {
            $locationRepository = $this->container->get('domain.locations.repository');
            $locationRepository->beginTransaction();
            if (!$locationRepository->addViewStats($locationId)) {
                $locationRepository->rollback();

                return false;
            }
            return $locationRepository->commit();
        }

        return false;
    }

    /**
     * @param Collection $appointments
     *
     * @return array
     */
    private function getApprovedAppointments($appointments)
    {
        $approvedAppointments = array_filter($appointments->toArray(), function ($appointment) {
            return $appointment['status'] === 'approved';
        });

        return [
            'value'    => count($approvedAppointments),
            'price'    => false,
            'redirect' => 'appointments',
            'params'   => ['status' => 'approved'],
            'image'    => 'check-circle-green'
        ];
    }

    /**
     * @param Collection $appointments
     *
     * @return array
     */
    private function getPendingAppointments($appointments)
    {
        $pendingAppointments = array_filter($appointments->toArray(), function ($appointment) {
            return $appointment['status'] === 'pending';
        });

        return [
            'value'    => count($pendingAppointments),
            'price'    => false,
            'redirect' => 'appointments',
            'params'   => ['status' => 'pending'],
            'image'    => 'loop'
        ];
    }

    /**
     * @param array      $params
     * @param Collection $appointments
     *
     * @return array
     */
    private function getAverageBookings($appointments, $params)
    {
        $startDate = DateTimeService::getCustomDateTimeObject($params['dates'][0]);
        $endDate = DateTimeService::getCustomDateTimeObject($params['dates'][1]);

        $numberOfDays = (int)$endDate->diff($startDate)->format('%a') + 1;

        return [
            'value'    => round($appointments->length() / $numberOfDays, 2),
            'price'    => false,
            'redirect' => 'appointments',
            'params'   => [],
            'image'    => 'timelapse'
        ];
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function getRevenue($params)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        $profit = 0;

        $paidAppointments = $paymentRepository->getFiltered(array_merge($params, ['status' => 'paid']));

        foreach ($paidAppointments as $paidAppointment) {
            $profit += $paidAppointment['amount'];
        }

        return [
            'value'    => $profit,
            'price'    => true,
            'redirect' => 'finance',
            'params'   => ['status' => 'paid'],
            'image'    => 'graph'
        ];
    }
}
