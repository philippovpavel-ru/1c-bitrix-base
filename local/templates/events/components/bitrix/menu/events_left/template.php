<?php
use Bitrix\Main\Text\HtmlFilter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @var array $arResult
 */

if (empty($arResult))
{
    return;
}
?>
<nav class="events-sidemenu">
    <ul class="events-sidemenu__list">
        <?php foreach ($arResult as $item): ?>
            <li class="events-sidemenu__item<?= $item['SELECTED'] ? ' events-sidemenu__item--active' : '' ?>">
                <a href="<?= HtmlFilter::encode($item['LINK']) ?>"><?= HtmlFilter::encode($item['TEXT']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>