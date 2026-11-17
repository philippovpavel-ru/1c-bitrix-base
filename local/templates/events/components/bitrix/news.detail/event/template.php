<?php
use Bitrix\Main\Text\HtmlFilter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @var array $arResult
 */

$props = $arResult['DISPLAY_PROPERTIES'];
$templateData = [
    'BROWSER_TITLE' => $arResult['EVENT_DATE_TEXT'] !== ''
        ? $arResult['NAME'] . ' — ' . HtmlFilter::encode($arResult['EVENT_DATE_TEXT'])
        : $arResult['NAME'],
];
?>
<article class="event-card">
    <dl class="event-card__facts">
        <?php if ($arResult['EVENT_DATE_TEXT'] !== ''): ?>
            <dt>Дата и время</dt>
            <dd><?= HtmlFilter::encode($arResult['EVENT_DATE_TEXT']) ?></dd>
        <?php endif; ?>

        <?php if ($arResult['EVENT_HOST_NAME'] !== ''): ?>
            <dt>Ведущий</dt>
            <dd>
                <a href="<?= HtmlFilter::encode($arResult['EVENT_HOST_URL']) ?>">
                    <?= HtmlFilter::encode($arResult['EVENT_HOST_NAME']) ?>
                </a>
            </dd>
        <?php endif; ?>

        <?php foreach (['FORMAT', 'SEATS_LIMIT'] as $code): ?>
            <?php if (isset($props[$code])): ?>
                <dt><?= $props[$code]['NAME'] ?></dt>
                <dd><?= $props[$code]['DISPLAY_VALUE'] ?></dd>
            <?php endif; ?>
        <?php endforeach; ?>
    </dl>
    <div class="event-card__text"><?= $arResult['DETAIL_TEXT'] ?></div>
    <p><a href="<?= $arResult['LIST_PAGE_URL'] ?>">Вся афиша</a></p>
</article>