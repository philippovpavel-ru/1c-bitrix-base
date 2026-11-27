<?php

namespace Academy\Events\Agent;

class ReminderAgent
{
    public static function run(): string
    {
        // найти заявки на завтрашние мероприятия и отправить напоминания

        return '\\' . __METHOD__ . '();';
    }
}