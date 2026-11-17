<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

$arComponentDescription = [
    'NAME' => Loc::getMessage('ACADEMY_EVENT_HOST_NAME'),
    'DESCRIPTION' => Loc::getMessage('ACADEMY_EVENT_HOST_DESCRIPTION'),
    'PATH' => [
        'ID' => 'academy',
        'NAME' => Loc::getMessage('ACADEMY_EVENT_HOST_PATH'),
    ],
];