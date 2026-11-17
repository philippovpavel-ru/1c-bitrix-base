<?php
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @var array $arResult
 */

$arResult['EVENT_DATE_TEXT'] = '';
$arResult['EVENT_HOST_NAME'] = '';
$arResult['EVENT_HOST_URL'] = '';

// получаем текстовое представление даты события
$rawDate = (string)($arResult['PROPERTIES']['EVENT_DATE']['~VALUE'] ?? '');
if ($rawDate !== '')
{
    try
    {
        $date = DateTime::createFromUserTime($rawDate);
        $arResult['EVENT_DATE_TEXT'] = FormatDate('j F Y, H:i', $date->getTimestamp());
    }
    catch (ObjectException $e)
    {
        // дата в неожиданном формате — не выводим ее
    }
}

// получаем имя ведущего события
$userId = (int)($arResult['PROPERTIES']['HOST']['VALUE'] ?? 0);
if ($userId > 0)
{
    $user = UserTable::query()
        ->setSelect(['NAME', 'LAST_NAME'])
        ->where('ID', $userId)
        ->where('ACTIVE', 'Y')
        ->fetch()
    ;

    if ($user)
    {
        $arResult['EVENT_HOST_NAME'] = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
        $arResult['EVENT_HOST_URL'] = '/events/hosts/#host-' . $userId;
    }
}