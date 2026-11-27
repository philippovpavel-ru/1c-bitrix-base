<?php

use Academy\Events\RegistrationService;
use Academy\Events\RegistrationTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Проверка заявок');

Loader::requireModule('academy.events');

function showRegistrationResult(string $label, Bitrix\Main\Result $result): void
{
    echo '<p>' . HtmlFilter::encode($label) . ': ';

    if ($result->isSuccess())
    {
        echo 'заявка принята, номер ' . (int)$result->getId();
    }
    else
    {
        echo HtmlFilter::encode(implode(', ', $result->getErrorMessages()));
    }

    echo '</p>';
}

$userId = (int)CurrentUser::get()->getId();
$service = new RegistrationService();

showRegistrationResult(
    'Заявка на мероприятие 5',
    $service->register(5, $userId, 'event5@example.com', ['Анна Смирнова', 'Иван Иванов'])
);

showRegistrationResult(
    'Заявка на мероприятие 1',
    $service->register(1, $userId, 'event1@example.com', ['Олег Петров', 'Пётр Петров'])
);

$registrations = RegistrationTable::query()
    ->setSelect([
        'ID',
        'NAME',
        'EVENT_ID',
        'EVENT_NAME' => 'EVENT.NAME',
        'VENUE_CODE' => 'EVENT.VENUE.VALUE',
        'USER_COMPANY' => 'USER.UF_COMPANY',
    ])
    ->whereIn('EVENT_ID', [5, 1])
    ->setOrder(['ID' => 'ASC'])
    ->fetchAll();

$venueCodes = [];
foreach ($registrations as $registration)
{
    $venueCode = (string)($registration['VENUE_CODE'] ?? '');

    if ($venueCode !== '')
    {
        $venueCodes[] = $venueCode;
    }
}
$venueCodes = array_values(array_unique($venueCodes));

$venuesByCode = [];
if (!empty($venueCodes) && Loader::includeModule('highloadblock'))
{
    $block = HighloadBlockTable::getList([
        'select' => ['ID', 'NAME', 'TABLE_NAME'],
        'filter' => ['=NAME' => 'AcademyEventsVenue'],
    ])->fetch();

    if ($block)
    {
        $entity = HighloadBlockTable::compileEntity($block);
        $dataClass = $entity->getDataClass();
        $venues = $dataClass::query()
            ->setSelect(['ID', 'UF_NAME', 'UF_XML_ID'])
            ->whereIn('UF_XML_ID', $venueCodes)
            ->fetchAll();

        foreach ($venues as $venue)
        {
            $venuesByCode[(string)$venue['UF_XML_ID']] = $venue;
        }
    }
}
?>

<h2>Заявки мероприятий с площадкой</h2>
<?php if (empty($registrations)): ?>
    <p>Заявок нет</p>
<?php else: ?>
    <ul>
        <?php foreach ($registrations as $registration): ?>
            <?php
            $venueCode = (string)($registration['VENUE_CODE'] ?? '');

            if ($venueCode === '')
            {
                continue;
            }

            $eventName = (string)($registration['EVENT_NAME'] ?? '');
            $company = (string)($registration['USER_COMPANY'] ?? '');
            $venueName = $venuesByCode[$venueCode]['UF_NAME'] ?? $venueCode;
            ?>
            <li>
                № <?= (int)$registration['ID'] ?>
                — <?= HtmlFilter::encode((string)$registration['NAME']) ?>
                — компания: <?= HtmlFilter::encode((string)$company) ?>
                — мероприятие: <?= HtmlFilter::encode((string)$eventName) ?>
                — площадка: <?= HtmlFilter::encode((string)$venueName) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
