<?php

use Bitrix\Main\Text\HtmlFilter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */
?>
<?php if (!$arResult['AUTHORIZED']): ?>
    <p>Войдите, чтобы увидеть заявки</p>
<?php elseif (empty($arResult['STATS'])): ?>
    <p>Заявок пока нет</p>
<?php else: ?>
    <div class="academy-registration-stats">
        <?php foreach ($arResult['STATS'] as $stat): ?>
            <span class="events-status events-status--<?= HtmlFilter::encode(strtolower($stat['STATUS'])) ?>"><?= HtmlFilter::encode($stat['STATUS']) ?> - <?= (int)$stat['CNT'] ?></span>
        <?php endforeach; ?>
    </div>
    <?php foreach ($arResult['REGISTRATIONS'] as $registration): ?>
        <div class="academy-registration">
            <p>
                Заявка № <?= (int)$registration->getId() ?> - <?= HtmlFilter::encode($registration->getEvent()->getName()) ?>
                <span class="events-status events-status--<?= HtmlFilter::encode(strtolower($registration->getStatus())) ?>"><?= HtmlFilter::encode($registration->getStatus()) ?></span>
            </p>
            <?php if (isset($arResult['VENUES'][(int)$registration->getEventId()])): ?>
                <p>Площадка: <?= HtmlFilter::encode((string)$arResult['VENUES'][(int)$registration->getEventId()]['UF_NAME']) ?></p>
            <?php endif; ?>
            <ul>
                <?php foreach ($registration->getParticipants() as $participant): ?>
                    <li><?= HtmlFilter::encode($participant->getName()) ?></li>
                <?php endforeach; ?>
            </ul>
            <p>
                <?php foreach ($registration->getMasterclasses() as $masterclass): ?>
                    <?= HtmlFilter::encode($masterclass->getTitle()) ?><br>
                <?php endforeach; ?>
            </p>
        </div>
    <?php endforeach; ?>
    <?php
    $APPLICATION->IncludeComponent('bitrix:main.pagenavigation', '', [
        'NAV_OBJECT' => $arResult['NAVIGATION'],
        'SEF_MODE' => 'N',
    ]);
    ?>
<?php endif; ?>