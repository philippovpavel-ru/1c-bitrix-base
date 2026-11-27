<?php

namespace Academy\Events\Agent;

use Academy\Events\RegistrationTable;
use Bitrix\Iblock\Elements\ElementEventsTable;
use Bitrix\Main\Mail\Event as MailEvent;

class ReminderAgent
{
    public static function run(): string
    {
        $tomorrowStart = (new \DateTimeImmutable('today'))->modify('+1 day')->format('Y-m-d H:i:s');
        $dayAfterTomorrowStart = (new \DateTimeImmutable('today'))->modify('+2 days')->format('Y-m-d H:i:s');

        $events = ElementEventsTable::query()
            ->setSelect(['ID'])
            ->where('ACTIVE', true)
            ->where('EVENT_DATE.VALUE', '>=', $tomorrowStart)
            ->where('EVENT_DATE.VALUE', '<', $dayAfterTomorrowStart)
            ->fetchAll()
        ;

        $eventIds = array_map('intval', array_column($events, 'ID'));
        if (empty($eventIds))
        {
            return '\\' . __METHOD__ . '();';
        }

        $registrations = RegistrationTable::query()
            ->setSelect([
                'ID',
                'EMAIL',
                'EVENT_NAME' => 'EVENT.NAME',
                'EVENT_DATE' => 'EVENT.EVENT_DATE.VALUE',
            ])
            ->whereIn('EVENT_ID', $eventIds)
            ->where('STATUS', '<>', RegistrationTable::STATUS_CANCELLED)
            ->fetchAll()
        ;

        foreach ($registrations as $registration)
        {
            MailEvent::send([
                'EVENT_NAME' => 'ACADEMY_EVENTS_REMINDER',
                'LID' => 's1',
                'C_FIELDS' => [
                    'EMAIL_TO' => $registration['EMAIL'],
                    'EVENT_NAME' => $registration['EVENT_NAME'],
                    'EVENT_DATE' => $registration['EVENT_DATE'],
                ],
            ]);
        }

        return '\\' . __METHOD__ . '();';
    }
}
