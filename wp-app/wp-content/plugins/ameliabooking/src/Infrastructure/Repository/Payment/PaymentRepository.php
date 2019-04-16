<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Payment;

use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Domain\Repository\Payment\PaymentRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;

/**
 * Class PaymentRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Payment
 */
class PaymentRepository extends AbstractRepository implements PaymentRepositoryInterface
{
    /** @var string */
    protected $appointmentsTable;

    /** @var string */
    protected $bookingsTable;

    /** @var string */
    protected $servicesTable;

    /** @var string */
    protected $usersTable;


    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $appointmentsTable
     * @param string     $bookingsTable
     * @param string     $servicesTable
     * @param string     $usersTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $appointmentsTable,
        $bookingsTable,
        $servicesTable,
        $usersTable
    ) {
        parent::__construct($connection, $table);

        $this->appointmentsTable = $appointmentsTable;
        $this->bookingsTable = $bookingsTable;
        $this->servicesTable = $servicesTable;
        $this->usersTable = $usersTable;
    }

    const FACTORY = PaymentFactory::class;

    /**
     * @param Payment $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customerBookingId' => $data['customerBookingId'],
            ':amount'            => $data['amount'],
            ':dateTime'          => DateTimeService::getCustomDateTimeInUtc($data['dateTime']),
            ':status'            => $data['status'],
            ':gateway'           => $data['gateway'],
            ':gatewayTitle'      => $data['gatewayTitle'],
            ':data'              => $data['data'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO
                {$this->table} 
                (
                `customerBookingId`, `amount`, `dateTime`, `status`, `gateway`, `gatewayTitle`, `data`
                ) VALUES (
                :customerBookingId, :amount, :dateTime, :status, :gateway, :gatewayTitle, :data
                )"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param int     $id
     * @param Payment $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customerBookingId' => $data['customerBookingId'],
            ':amount'            => $data['amount'],
            ':dateTime'          => DateTimeService::getCustomDateTimeInUtc($data['dateTime']),
            ':status'            => $data['status'],
            ':gateway'           => $data['gateway'],
            ':gatewayTitle'      => $data['gatewayTitle'],
            ':data'              => $data['data'],
            ':id'                => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `customerBookingId` = :customerBookingId,
                `amount`            = :amount,
                `dateTime`          = :dateTime,
                `status`            = :status,
                `gateway`           = :gateway,
                `gatewayTitle`      = :gatewayTitle,
                `data`              = :data
                WHERE
                id = :id"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        return $response;
    }

    /**
     * @param int $customerBookingId
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function deleteByBookingId($customerBookingId)
    {
        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table}
                WHERE customerBookingId = :customerBookingId"
            );

            $statement->bindParam(':customerBookingId', $customerBookingId);

            return $statement->execute();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param array $criteria
     * @param int   $itemsPerPage
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getFiltered($criteria, $itemsPerPage = null)
    {
        try {
            $params = [];
            $where = [];

            $limit = '';
            if ($itemsPerPage) {
                $params[':startingLimit'] = ($criteria['page'] - 1) * $itemsPerPage;
                $params[':itemsPerPage'] = $itemsPerPage;

                $limit = 'LIMIT :startingLimit, :itemsPerPage';
            }

            if ($criteria['dates']) {
                $where[] = "(DATE_FORMAT(p.dateTime, '%Y-%m-%d %H:%i:%s') BETWEEN :paymentFrom AND :paymentTo)";
                $params[':paymentFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                $params[':paymentTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            }

            if (!empty($criteria['customerId'])) {
                $params[':customerId'] = $criteria['customerId'];

                $where[] = 'cb.customerId = :customerId';
            }

            if (!empty($criteria['providerId'])) {
                $params[':providerId'] = $criteria['providerId'];

                $where[] = 'a.providerId = :providerId';
            }

            if (!empty($criteria['services'])) {
                $queryServices = [];

                foreach ((array)$criteria['services'] as $index => $value) {
                    $param = ':service' . $index;
                    $queryServices[] = $param;
                    $params[$param] = $value;
                }

                $where[] = 'a.serviceId IN (' . implode(', ', $queryServices) . ')';
            }

            if (!empty($criteria['status'])) {
                $params[':status'] = $criteria['status'];

                $where[] = 'p.status = :status';
            }

            $where = $where ? ' AND ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare(
                "SELECT
                    p.id AS id,
                    p.customerBookingId AS customerBookingId,
                    p.amount AS amount,
                    p.dateTime AS dateTime,
                    p.status AS status,
                    p.gateway AS gateway,
                    p.gatewayTitle AS gatewayTitle,
                    a.providerId AS providerId,
                    cb.customerId AS customerId,
                    a.serviceId AS serviceId,
                    a.id AS appointmentId,
                    a.bookingStart AS bookingStart,
                    s.name AS serviceName,
                    cu.firstName AS customerFirstName,
                    cu.lastName AS customerLastName,
                    cu.email AS customerEmail,
                    pu.firstName AS providerFirstName,
                    pu.lastName AS providerLastName,
                    pu.email AS providerEmail
                FROM {$this->table} p
                INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
                INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
                INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
                INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId
                INNER JOIN {$this->usersTable} pu ON pu.id = a.providerId
                WHERE 1=1 $where
                ORDER BY p.dateTime
                $limit"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        foreach ($rows as &$row) {
            $row['dateTime'] = DateTimeService::getCustomDateTimeFromUtc($row['dateTime']);
            $row['bookingStart'] = DateTimeService::getCustomDateTimeFromUtc($row['bookingStart']);
            $row['id'] = (int)$row['id'];
            $row['customerBookingId'] = (int)$row['customerBookingId'];
            $row['amount'] = (float)$row['amount'];
            $row['providerId'] = (int)$row['providerId'];
            $row['customerId'] = (int)$row['customerId'];
            $row['serviceId'] = (int)$row['serviceId'];
            $row['appointmentId'] = (int)$row['appointmentId'];

        }

        return $rows;
    }

    /**
     * @param array $criteria
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function getCount($criteria)
    {
        try {
            $params = [];

            $where = [];

            if (!empty($criteria['dates'])) {
                $where[] = "(DATE_FORMAT(p.dateTime, '%Y-%m-%d %H:%i:%s') BETWEEN :paymentFrom AND :paymentTo)";
                $params[':paymentFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                $params[':paymentTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            }

            if (!empty($criteria['customerId'])) {
                $params[':customerId'] = $criteria['customerId'];

                $where[] = 'cb.customerId = :customerId';
            }

            if (!empty($criteria['providerId'])) {
                $params[':providerId'] = $criteria['providerId'];

                $where[] = 'a.providerId = :providerId';
            }

            if (!empty($criteria['services'])) {
                $queryServices = [];

                foreach ((array)$criteria['services'] as $index => $value) {
                    $param = ':service' . $index;
                    $queryServices[] = $param;
                    $params[$param] = $value;
                }

                $where[] = 'a.serviceId IN (' . implode(', ', $queryServices) . ')';
            }

            if (!empty($criteria['status'])) {
                $params[':status'] = $criteria['status'];

                $where[] = 'p.status = :status';
            }

            $where = $where ? ' AND ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare(
                "SELECT COUNT(*) AS count
                    FROM {$this->table} p
                    INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
                    INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
                    INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
                    WHERE 1 = 1 $where"
            );

            $statement->execute($params);

            $row = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $row;
    }

    /**
     * @param int $status
     */
    public function findByStatus($status)
    {
        // TODO: Implement findByStatus() method.
    }
}
