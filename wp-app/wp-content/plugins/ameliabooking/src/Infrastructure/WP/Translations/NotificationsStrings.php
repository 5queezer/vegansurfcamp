<?php

namespace AmeliaBooking\Infrastructure\WP\Translations;

/**
 * Class NotificationsStrings
 *
 * @package AmeliaBooking\Infrastructure\WP\Translations
 *
 * @SuppressWarnings(ExcessiveMethodLength)
 */
class NotificationsStrings
{
    /**
     * Array of default customer's notifications that are not time based
     *
     * @return array
     */
    public static function getCustomerNonTimeBasedEmailNotifications()
    {
        return [
            [
                'name'       => 'customer_appointment_approved',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => '%service_name% Appointment Approved',
                'content'    =>
                    'Dear <strong>%customer_full_name%</strong>,<br><br>You have successfully scheduled
                     <strong>%service_name%</strong> appointment with <strong>%employee_full_name%</strong>. We are 
                     waiting you at <strong>%location_address% </strong>on <strong>%appointment_date_time%</strong>.
                     <br><br>Thank you for choosing our company,<br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'customer_appointment_pending',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => '%service_name% Appointment Pending',
                'content'    =>
                    'Dear <strong>%customer_full_name%</strong>,<br><br>The <strong>%service_name%</strong> appointment 
                     with <strong>%employee_full_name%</strong> at <strong>%location_address%</strong>, scheduled for
                     <strong>%appointment_date_time%</strong> is waiting for a confirmation.<br><br>Thank you for 
                     choosing our company,<br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'customer_appointment_rejected',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => '%service_name% Appointment Rejected',
                'content'    =>
                    'Dear <strong>%customer_full_name%</strong>,<br><br>Your <strong>%service_name%</strong> 
                     appointment, scheduled on <strong>%appointment_date_time%</strong> at <strong>%location_address%
                     </strong>has been rejected.<br><br>Thank you for choosing our company,
                     <br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'customer_appointment_canceled',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => '%service_name% Appointment Canceled',
                'content'    =>
                    'Dear <strong>%customer_full_name%</strong>,<br><br>Your <strong>%service_name%</strong> 
                     appointment, scheduled on <strong>%appointment_date_time%</strong> at <strong>%location_address%
                     </strong>has been canceled.<br><br>Thank you for choosing our company,
                     <br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'customer_appointment_rescheduled',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => '%service_name% Appointment Rescheduled',
                'content'    =>
                    'Dear <strong>%customer_full_name%</strong>,<br><br>The details for your 
                     <strong>%service_name%</strong> appointment with <strong>%employee_full_name%</strong> at 
                     <strong>%location_name%</strong> has been changed. The appointment is now set for 
                     <strong>%appointment_date%</strong> at <strong>%appointment_start_time%</strong>.<br><br>
                     Thank you for choosing our company,<br><strong>%company_name%</strong>'
            ]
        ];
    }

    /**
     * Array of default customer's notifications that are time based (require cron job)
     *
     * @return array
     */
    public static function getCustomerTimeBasedEmailNotifications()
    {
        return [
            [
                'name'       => 'customer_appointment_next_day_reminder',
                'type'       => 'email',
                'time'       => '"17:00:00"',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => '%service_name% Appointment Reminder',
                'content'    =>
                    'Dear <strong>%customer_full_name%</strong>,<br><br>We would like to remind you that you have 
                     <strong>%service_name%</strong> appointment tomorrow at <strong>%appointment_start_time%</strong>.
                     We are waiting you at <strong>%location_name%</strong>.<br><br>Thank you for 
                     choosing our company,<br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'customer_appointment_follow_up',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 1800,
                'sendTo'     => 'customer',
                'subject'    => '%service_name% Appointment Follow Up',
                'content'    =>
                    'Dear <strong>%customer_full_name%</strong>,<br><br>Thank you once again for choosing our company. 
                     We hope you were satisfied with your <strong>%service_name%</strong>.<br><br>We look forward to 
                     seeing you again soon,<br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'customer_birthday_greeting',
                'type'       => 'email',
                'time'       => '"17:00:00"',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => 'Happy Birthday',
                'content'    =>
                    'Dear <strong>%customer_full_name%</strong>,<br><br>Happy birthday!<br>We wish you all the best.
                    <br><br>Thank you for choosing our company,<br><strong>%company_name%</strong>'
            ]
        ];
    }


    /**
     * Array of default employee's notifications that are not time based
     *
     * @return array
     */
    public static function getProviderNonTimeBasedEmailNotifications()
    {
        return [
            [
                'name'       => 'provider_appointment_approved',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => '%service_name% Appointment Approved',
                'content'    =>
                    'Hi <strong>%employee_full_name%</strong>,<br><br>You have one confirmed 
                     <strong>%service_name%</strong> appointment at <strong>%location_name%</strong> on 
                     <strong>%appointment_date%</strong> at <strong>%appointment_start_time%</strong>. The appointment 
                     is added to your schedule.<br><br>Thank you,<br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'provider_appointment_pending',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => '%service_name% Appointment Pending',
                'content'    =>
                    'Hi <strong>%employee_full_name%</strong>,<br><br>You have new appointment 
                     in <strong>%service_name%</strong>. The appointment is waiting for a confirmation.<br><br>Thank 
                     you,<br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'provider_appointment_rejected',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => '%service_name% Appointment Rejected',
                'content'    =>
                    'Hi <strong>%employee_full_name%</strong>,<br><br>Your <strong>%service_name%</strong> appointment 
                     at <strong>%location_name%</strong>, scheduled for <strong>%appointment_date%</strong> at  
                     <strong>%appointment_start_time%</strong> has been rejected.
                     <br><br>Thank you,<br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'provider_appointment_canceled',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => '%service_name% Appointment Canceled',
                'content'    =>
                    'Hi <strong>%employee_full_name%</strong>,<br><br>Your <strong>%service_name%</strong> appointment,
                     scheduled on <strong>%appointment_date%</strong>, at <strong>%location_name%</strong> has been 
                     canceled.<br><br>Thank you,<br><strong>%company_name%</strong>'
            ],
            [
                'name'       => 'provider_appointment_rescheduled',
                'type'       => 'email',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => '%service_name% Appointment Rescheduled',
                'content'    =>
                    'Hi <strong>%employee_full_name%</strong>,<br><br>The details for your 
                     <strong>%service_name%</strong> appointment at <strong>%location_name%</strong> has been changed. 
                     The appointment is now set for <strong>%appointment_date%</strong> at 
                     <strong>%appointment_start_time%</strong>.<br><br>Thank you,<br><strong>%company_name%</strong>'
            ]
        ];
    }

    /**
     * Array of default providers's notifications that are time based (require cron job)
     *
     * @return array
     */
    public static function getProviderTimeBasedEmailNotifications()
    {
        return [
            [
                'name'       => 'provider_appointment_next_day_reminder',
                'type'       => 'email',
                'time'       => '"17:00:00"',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => '%service_name% Appointment Reminder',
                'content'    =>
                    'Dear <strong>%employee_full_name%</strong>,<br><br>We would like to remind you that you have 
                     <strong>%service_name%</strong> appointment tomorrow at <strong>%appointment_start_time%</strong>
                     at <strong>%location_name%</strong>.<br><br>Thank you, 
                     <br><strong>%company_name%</strong>'
            ]
        ];
    }

    /**
     * Array of default customer's notifications that are not time based
     *
     * @return array
     */
    public static function getCustomerNonTimeBasedSMSNotifications()
    {
        return [
            [
                'name'       => 'customer_appointment_approved',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %customer_full_name%,

You have successfully scheduled %service_name% appointment with %employee_full_name%. We are waiting you at %location_address% on %appointment_date_time%.

Thank you for choosing our company,
%company_name%'
            ],
            [
                'name'       => 'customer_appointment_pending',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %customer_full_name%, 
                    
The %service_name% appointment with %employee_full_name% at %location_address%, scheduled for %appointment_date_time% is waiting for a confirmation.
                    
Thank you for choosing our company,
%company_name%'
            ],
            [
                'name'       => 'customer_appointment_rejected',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %customer_full_name%,
                    
Your %service_name% appointment, scheduled on %appointment_date_time% at %location_address% has been rejected.
                    
Thank you for choosing our company,
%company_name%'
            ],
            [
                'name'       => 'customer_appointment_canceled',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %customer_full_name%,
                    
Your %service_name% appointment, scheduled on %appointment_date_time% at %location_address% has been canceled. 
                    
Thank you for choosing our company,
%company_name%'
            ],
            [
                'name'       => 'customer_appointment_rescheduled',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %customer_full_name%,
                    
The details for your %service_name% appointment with %employee_full_name% at %location_name% has been changed. The appointment is now set for %appointment_date% at %appointment_start_time%.
                    
Thank you for choosing our company,
%company_name%'
            ]
        ];
    }

    /**
     * Array of default customer's notifications that are time based (require cron job)
     *
     * @return array
     */
    public static function getCustomerTimeBasedSMSNotifications()
    {
        return [
            [
                'name'       => 'customer_appointment_next_day_reminder',
                'type'       => 'sms',
                'time'       => '"17:00:00"',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %customer_full_name%,
                    
We would like to remind you that you have %service_name% appointment tomorrow at %appointment_start_time%. We are waiting you at %location_name%.
                    
Thank you for choosing our company,
%company_name%'
            ],
            [
                'name'       => 'customer_appointment_follow_up',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 1800,
                'sendTo'     => 'customer',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %customer_full_name%,
                    
Thank you once again for choosing our company. We hope you were satisfied with your %service_name%.
                     
We look forward to seeing you again soon,
%company_name%'
            ],
            [
                'name'       => 'customer_birthday_greeting',
                'type'       => 'sms',
                'time'       => '"17:00:00"',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'customer',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %customer_full_name%,
                    
Happy birthday! We wish you all the best. 
                    
Thank you for choosing our company,
%company_name%'
            ]
        ];
    }


    /**
     * Array of default employee's notifications that are not time based
     *
     * @return array
     */
    public static function getProviderNonTimeBasedSMSNotifications()
    {
        return [
            [
                'name'       => 'provider_appointment_approved',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => 'NULL',
                'content'    =>
                    'Hi %employee_full_name%,
                    
You have one confirmed %service_name% appointment at %location_name% on %appointment_date% at %appointment_start_time%. The appointment is added to your schedule.
                    
Thank you,
%company_name%'
            ],
            [
                'name'       => 'provider_appointment_pending',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => 'NULL',
                'content'    =>
                    'Hi %employee_full_name%,
                    
You have new appointment in %service_name%. The appointment is waiting for a confirmation.
                    
Thank you,
%company_name%'
            ],
            [
                'name'       => 'provider_appointment_rejected',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => 'NULL',
                'content'    =>
                    'Hi %employee_full_name%,
                    
Your %service_name% appointment at %location_name%, scheduled for %appointment_date% at %appointment_start_time% has been rejected. 
                    
Thank you,
%company_name%'
            ],
            [
                'name'       => 'provider_appointment_canceled',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => 'NULL',
                'content'    =>
                    'Hi %employee_full_name%,
                    
Your %service_name% appointment, scheduled on %appointment_date%, at %location_name% has been canceled.
                    
Thank you,
%company_name%'
            ],
            [
                'name'       => 'provider_appointment_rescheduled',
                'type'       => 'sms',
                'time'       => 'NULL',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => 'NULL',
                'content'    =>
                    'Hi %employee_full_name%,
                    
The details for your %service_name% appointment at %location_name% has been changed. The appointment is now set for %appointment_date% at %appointment_start_time%.
                    
Thank you,
%company_name%'
            ]
        ];
    }

    /**
     * Array of default providers's notifications that are time based (require cron job)
     *
     * @return array
     */
    public static function getProviderTimeBasedSMSNotifications()
    {
        return [
            [
                'name'       => 'provider_appointment_next_day_reminder',
                'type'       => 'sms',
                'time'       => '"17:00:00"',
                'timeBefore' => 'NULL',
                'timeAfter'  => 'NULL',
                'sendTo'     => 'provider',
                'subject'    => 'NULL',
                'content'    =>
                    'Dear %employee_full_name%, 
                    
We would like to remind you that you have %service_name% appointment tomorrow at %appointment_start_time% at %location_name%.
                    
Thank you, 
%company_name%'
            ]
        ];
    }
}
