<?php

namespace Academy\Events;

use Bitrix\Main\Application;
use Bitrix\Main\Result;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Iblock\Elements\ElementEventsTable;

class RegistrationService
{
    public function register(int $eventId, int $userId, string $email, array $participantNames): Result
    {
        $connection = Application::getConnection();
        $lockName = 'academy_events_registration_' . $eventId;

        if (!$connection->lock($lockName, 5))
        {
            return (new Result())->addError(new Error('Попробуйте записаться еще раз'));
        }

        try
        {
            $event = ElementEventsTable::query()
                ->setSelect(['ID', 'SEATS_LIMIT'])
                ->where('ID', $eventId)
                ->where('ACTIVE', true)
                ->fetchObject()
            ;
            if (!$event)
            {
                return (new Result())->addError(new Error('Мероприятие не найдено'));
            }

            $taken = (int)$connection->queryScalar(new SqlExpression(
                'SELECT SUM(SEATS) FROM ?# WHERE EVENT_ID = ?i AND STATUS <> ?s',
                RegistrationTable::getTableName(),
                $eventId,
                RegistrationTable::STATUS_CANCELLED
            ));
            $free = (int)$event->get('SEATS_LIMIT')?->getValue() - $taken;

            if ($free < count($participantNames))
            {
                return (new Result())->addError(new Error('Свободных мест: ' . max($free, 0)));
            }

            $connection->startTransaction();
            try
            {
                $result = RegistrationTable::add([
                    'EVENT_ID' => $eventId,
                    'USER_ID' => $userId,
                    'NAME' => (string)reset($participantNames),
                    'EMAIL' => $email,
                    'SEATS' => count($participantNames),
                ]);
                if (!$result->isSuccess())
                {
                    $connection->rollbackTransaction();

                    return $result;
                }

                foreach ($participantNames as $name)
                {
                    $participantResult = ParticipantTable::add([
                        'REGISTRATION_ID' => $result->getId(),
                        'NAME' => (string)$name,
                    ]);
                    if (!$participantResult->isSuccess())
                    {
                        $connection->rollbackTransaction();

                        return $participantResult;
                    }
                }

                $connection->commitTransaction();

                (new Event('academy.events', 'OnAfterRegistrationAdd', [
                    'ID' => $result->getId(),
                ]))->send();

                return $result;
            }
            catch (\Throwable $exception)
            {
                $connection->rollbackTransaction();
                throw $exception;
            }
        }
        finally
        {
            $connection->unlock($lockName);
        }
    }
}