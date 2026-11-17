<?php
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @var array $arResult
 */

$arResult['EVENT_DATE_TEXT'] = '';

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