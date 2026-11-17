<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @var array|false $templateData
 * @global CMain $APPLICATION
 */

if (is_array($templateData) && $templateData['BROWSER_TITLE'] !== '')
{
    $APPLICATION->SetPageProperty('title', $templateData['BROWSER_TITLE']);
}