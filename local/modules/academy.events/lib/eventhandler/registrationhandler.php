<?php

namespace Academy\Events\EventHandler;

use Academy\Events\RegistrationTable;
use Bitrix\Main\Event;
use Bitrix\Main\Mail\Event as MailEvent;

class RegistrationHandler
{
    public static function onAfterRegistrationAdd(Event $event): void
    {
        $registration = RegistrationTable::getByPrimary((int)$event->getParameter('ID'), [
            'select' => ['ID', 'EMAIL', 'SEATS', 'EVENT_NAME' => 'EVENT.NAME'],
        ])->fetch();

        if (!$registration)
        {
            return;
        }

        MailEvent::send([
            'EVENT_NAME' => 'ACADEMY_EVENTS_REGISTRATION',
            'LID' => 's1',
            'C_FIELDS' => [
                'EMAIL_TO' => $registration['EMAIL'],
                'REGISTRATION_ID' => $registration['ID'],
                'EVENT_NAME' => $registration['EVENT_NAME'],
                'SEATS' => $registration['SEATS'],
            ],
        ]);
    }
}