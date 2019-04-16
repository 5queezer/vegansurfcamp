<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Notification;

use AmeliaBooking\Domain\Services\Notification\AbstractMailService;
use AmeliaBooking\Domain\Services\Notification\MailServiceInterface;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class PHPMailService
 */
class PHPMailService extends AbstractMailService implements MailServiceInterface
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param      $to
     * @param      $subject
     * @param      $body
     * @param bool $attachment
     * @param bool $bcc
     *
     * @return mixed|void
     * @throws Exception
     * @SuppressWarnings(PHPMD)
     */
    public function send($to, $subject, $body, $attachment = false, $bcc = false)
    {
        $mail = new PHPMailer;

        try {
            //Recipients
            $mail->setFrom($this->from, $this->fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($this->from);
            if ($bcc) {
                $mail->addBCC($bcc);
            }

            //Attachments
            if ($attachment) {
                $mail->addAttachment($attachment);
            }

            //Content
            $mail->CharSet = 'UTF-8';
            $mail->isHTML();
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
