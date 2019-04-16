<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Factory\Notification\NotificationFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;

/**
 * Class UpdateNotificationCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class UpdateNotificationCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'subject',
        'content'
    ];

    /**
     * @param UpdateNotificationCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateNotificationCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to update notification');
        }

        $notificationId = (int)$command->getArg('id');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');

        $currentNotification = $notificationRepo->getById($notificationId);

        $content = str_replace(
            ['<br>', '<br/>', '</p><p>', '<p>', '</p>'],
            ['', '', '<br>', '', ''],
            $command->getField('content')
        );

        $notification = NotificationFactory::create([
            'name'       => $currentNotification->getName()->getValue(),
            'status'     => $currentNotification->getStatus()->getValue(),
            'type'       => $currentNotification->getType()->getValue(),
            'time'       => $command->getField('time'),
            'timeBefore' => $command->getField('timeBefore'),
            'timeAfter'  => $command->getField('timeAfter'),
            'sendTo'     => $currentNotification->getSendTo()->getValue(),
            'subject'    => $command->getField('subject'),
            'content'    => $content
        ]);

        if (!$notification instanceof Notification) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update notification entity.');

            return $result;
        }

        if ($notificationRepo->update($notificationId, $notification)) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated notification.');
            $result->setData([
                Entities::NOTIFICATION => $notification->toArray()
            ]);
        }

        return $result;
    }
}
