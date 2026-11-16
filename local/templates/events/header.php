<?php
use Bitrix\Main\Page\Asset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @global CMain $APPLICATION
 */

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . '/css/events.css');
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . '/js/events.js');
Asset::getInstance()->addString('<meta name="viewport" content="width=device-width, initial-scale=1">');
?>
<!DOCTYPE html>
<html lang="<?= LANGUAGE_ID ?>">
<head>
    <?php $APPLICATION->ShowHead(); ?>
    <title><?php $APPLICATION->ShowTitle(); ?></title>
</head>
<body>
<?php $APPLICATION->ShowPanel(); ?>
<header class="events-header">
    <a class="events-header__logo" href="<?= SITE_DIR ?>events/">Мероприятия</a>
    <p class="events-header__slogan"><?php $APPLICATION->ShowProperty('slogan'); ?></p>

    <?php $APPLICATION->IncludeComponent(
        'bitrix:menu',
        'events_top',
        [
            'ROOT_MENU_TYPE' => 'top',
            'MAX_LEVEL' => '1',
            'USE_EXT' => 'N',
            'MENU_CACHE_TYPE' => 'A',
            'MENU_CACHE_TIME' => '3600',
            'MENU_CACHE_USE_GROUPS' => 'Y',
            'MENU_CACHE_GET_VARS' => [],
        ]
    ); ?>
</header>
<main class="events-main">
    <?php $APPLICATION->IncludeComponent(
        'bitrix:menu',
        'events_left',
        [
            'ROOT_MENU_TYPE' => 'left',
            'MAX_LEVEL' => '1',
            'USE_EXT' => 'N',
            'MENU_CACHE_TYPE' => 'N',
            'MENU_CACHE_TIME' => '3600',
            'MENU_CACHE_USE_GROUPS' => 'Y',
            'MENU_CACHE_GET_VARS' => [],
        ]
    ); ?>
    <h1><?php $APPLICATION->ShowTitle(false); ?></h1>