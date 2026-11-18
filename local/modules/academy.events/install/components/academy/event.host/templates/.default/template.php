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
<div class="academy-event-host" id="host-<?= (int)$arParams['USER_ID'] ?>">
    <?php if ( $arParams['SHOW_PHOTO'] === 'Y' && $arResult['PHOTO_SRC'] !== ''): ?>
        <img
            class="academy-event-host__photo"
            src="<?= HtmlFilter::encode($arResult['PHOTO_SRC']) ?>"
            alt="<?= HtmlFilter::encode($arResult['NAME']) ?>"
        >
    <?php endif; ?>

    <div class="academy-event-host__label"><?= Loc::getMessage('ACADEMY_EVENT_HOST_LABEL') ?></div>
    <div class="academy-event-host__name"><?= HtmlFilter::encode($arResult['NAME']) ?></div>
    <?php if ($arParams['SHOW_POSITION'] === 'Y' && $arResult['POSITION'] !== ''): ?>
        <div class="academy-event-host__position"><?= HtmlFilter::encode($arResult['POSITION']) ?></div>
    <?php endif; ?>
</div>