<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'USER_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('ACADEMY_EVENT_HOST_PARAM_USER_ID'),
            'TYPE' => 'STRING',
        ],
        'SHOW_POSITION' => [
            'PARENT' => 'VISUAL',
            'NAME' => Loc::getMessage('ACADEMY_EVENT_HOST_PARAM_SHOW_POSITION'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 3600,
        ],
        'SHOW_PHOTO' => [
            'PARENT' => 'VISUAL',
            'NAME' => Loc::getMessage('ACADEMY_EVENT_HOST_PARAM_SHOW_PHOTO'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ]
    ],
];