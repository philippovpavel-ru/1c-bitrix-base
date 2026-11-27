<?php

namespace Academy\Events\EventHandler;

use Academy\Events\RegistrationTable;

class UserHandler
{
    public static function onUserDelete($userId): void
    {
        $registrations = RegistrationTable::query()
            ->setSelect(['ID'])
            ->where('USER_ID', (int)$userId)
            ->fetchCollection()
        ;

        foreach ($registrations as $registration)
        {
            $registration->setStatus(RegistrationTable::STATUS_CANCELLED);
            $registration->save();
        }
    }
}