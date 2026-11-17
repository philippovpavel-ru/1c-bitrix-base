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
 * @var CBitrixComponentTemplate $this
 */
?>
<div class="events-list">
    <?php if (empty($arResult['ITEMS'])): ?>
        <p class="events-list__empty"><?= Loc::getMessage('EVENTS_LIST_EMPTY') ?></p>
    <?php endif; ?>

    <?php foreach ($arResult['ITEMS'] as $item): ?>
        <?php
        $this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_EDIT'));
        $this->AddDeleteAction($item['ID'], $item['DELETE_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_DELETE'));
        ?>
        <article class="events-list__item" id="<?= $this->GetEditAreaId($item['ID']) ?>">
            <h2 class="events-list__title">
                <a href="<?= $item['DETAIL_PAGE_URL'] ?>"><?= $item['NAME'] ?></a>
            </h2>
            <?php if ($item['EVENT_DATE_TEXT'] !== ''): ?>
                <p class="events-list__date"><?= HtmlFilter::encode($item['EVENT_DATE_TEXT']) ?></p>
            <?php endif; ?>
            <?php if (isset($item['DISPLAY_PROPERTIES']['FORMAT'])): ?>
                <p class="events-list__format"><?= $item['DISPLAY_PROPERTIES']['FORMAT']['DISPLAY_VALUE'] ?></p>
            <?php endif; ?>
            <div class="events-list__preview"><?= $item['PREVIEW_TEXT'] ?></div>
        </article>
    <?php endforeach; ?>

    <?php if ($arParams['DISPLAY_BOTTOM_PAGER']): ?>
        <?= $arResult['NAV_STRING'] ?>
    <?php endif; ?>
</div>