<?php

use Academy\Events\RegistrationService;
use Academy\Events\RegistrationTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Проверка заявок');

Loader::requireModule('academy.events');

// Сценарий практики: три заявки по четыре участника, отмена первой и ещё одна заявка.
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

$eventId = 2;
$userId = (int)CurrentUser::get()->getId();
$participants = ['Анна Смирнова', 'Олег Петров', 'Иван Иванов', 'Пётр Петров'];
$service = new RegistrationService();

$firstRegistrationId = null;

for ($i = 1; $i <= 3; $i++)
{
    $result = $service->register($eventId, $userId, 'anna@example.com', $participants);
    showRegistrationResult('Заявка ' . $i, $result);

    if ($result->isSuccess() && $firstRegistrationId === null)
    {
        $firstRegistrationId = (int)$result->getId();
    }
}

if ($firstRegistrationId !== null)
{
    $updateResult = RegistrationTable::update($firstRegistrationId, [
        'STATUS' => RegistrationTable::STATUS_CANCELLED,
    ]);

    echo '<p>Заявка № ' . $firstRegistrationId . ': ';
    echo $updateResult->isSuccess()
        ? 'статус изменен на CANCELLED'
        : HtmlFilter::encode(implode(', ', $updateResult->getErrorMessages()));
    echo '</p>';
}

$result = $service->register($eventId, $userId, 'anna@example.com', $participants);
showRegistrationResult('Новая заявка', $result);
?>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
