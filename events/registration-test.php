<?php

use Academy\Events\RegistrationService;
use Bitrix\Iblock\Elements\ElementEventsTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Проверка заявок');

Loader::requireModule('academy.events');

// Сценарий практики урока 11: две заявки по четыре участника на мероприятие
// с лимитом 6, затем заявки на неактивное и на несуществующее мероприятие.
// Запускайте страницу на мероприятии без заявок.
function showRegistrationResult(string $label, Result $result): void
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
$participants = ['Анна Смирнова', 'Олег Петров', 'Иван Иванов', 'Пётр Петров'];
$service = new RegistrationService();

// Мероприятие с лимитом 6 — на нем проверяем нехватку мест.
$limitEvent = ElementEventsTable::query()
    ->setSelect(['ID', 'NAME', 'SEATS_LIMIT'])
    ->where('ACTIVE', true)
    ->where('SEATS_LIMIT.VALUE', 6)
    ->setOrder(['EVENT_DATE.VALUE' => 'ASC'])
    ->fetchObject()
;

// Неактивное мероприятие — на нем проверяем условие ACTIVE.
$inactiveEvent = ElementEventsTable::query()
    ->setSelect(['ID', 'NAME'])
    ->where('ACTIVE', false)
    ->setOrder(['ID' => 'ASC'])
    ->fetchObject()
;

if ($limitEvent)
{
    $limitEventId = (int)$limitEvent->getId();
    echo '<p>Мероприятие с лимитом 6: ' . HtmlFilter::encode((string)$limitEvent->getName())
        . ' (ID ' . $limitEventId . ')</p>';

    for ($i = 1; $i <= 2; $i++)
    {
        showRegistrationResult(
            'Заявка ' . $i . ' на 4 места',
            $service->register($limitEventId, $userId, 'anna@example.com', $participants)
        );
    }
}
else
{
    echo '<p>Не найдено активное мероприятие с лимитом 6</p>';
}

if ($inactiveEvent)
{
    $inactiveEventId = (int)$inactiveEvent->getId();
    echo '<p>Неактивное мероприятие: ' . HtmlFilter::encode((string)$inactiveEvent->getName())
        . ' (ID ' . $inactiveEventId . ')</p>';

    showRegistrationResult(
        'Заявка на неактивное мероприятие',
        $service->register($inactiveEventId, $userId, 'anna@example.com', $participants)
    );
}
else
{
    echo '<p>Не найдено неактивное мероприятие</p>';
}

showRegistrationResult(
    'Заявка на мероприятие 999999',
    $service->register(999999, $userId, 'anna@example.com', $participants)
);

// Активные мероприятия с заполненным лимитом, ближайшие сверху.
$events = ElementEventsTable::query()
    ->setSelect(['ID', 'NAME', 'SEATS_LIMIT', 'EVENT_DATE'])
    ->where('ACTIVE', true)
    ->where('SEATS_LIMIT.VALUE', '>', 0)
    ->setOrder(['EVENT_DATE.VALUE' => 'ASC'])
    ->fetchCollection()
;
?>

<h2>Активные мероприятия с лимитом мест</h2>
<?php if ($events->isEmpty()): ?>
    <p>Активных мероприятий с заполненным лимитом нет</p>
<?php else: ?>
    <ul>
        <?php foreach ($events as $event): ?>
            <li>
                <?= HtmlFilter::encode((string)$event->getName()) ?>
                — лимит <?= (int)$event->get('SEATS_LIMIT')?->getValue() ?>
                (ID <?= (int)$event->getId() ?>)
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
