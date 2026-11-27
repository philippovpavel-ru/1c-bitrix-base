<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

// Учебный стенд: почта не настроена, письма записываем в файл
EventManager::getInstance()->addEventHandler('main', 'OnBeforeMailSend', function (Event $event) {
    $mail = $event->getParameter(0);
    file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/mail.log',
        date('Y-m-d H:i:s') . "\nTo: " . $mail['TO'] . "\nSubject: " . $mail['SUBJECT'] . "\n\n" . $mail['BODY'] . "\n----\n",
        FILE_APPEND
    );
});