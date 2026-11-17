<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @var array $arParams
 * @var array $arResult
 */
?>
<span id="host-<?= (int)$arParams['USER_ID'] ?>">
    <?= HtmlFilter::encode($arResult['NAME']) ?></div>
</span>