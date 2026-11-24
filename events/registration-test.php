<?php
use Academy\Events\MasterclassTable;
use Academy\Events\ParticipantTable;
use Academy\Events\RegistrationTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Проверка заявок');

Loader::requireModule('academy.events');

$masterclasses = [];
foreach (['Работа с ORM', 'Шаблоны компонентов'] as $title)
{
    $masterclassId = MasterclassTable::add(['TITLE' => $title])->getId();
    $masterclasses[] = MasterclassTable::getByPrimary($masterclassId)->fetchObject();
}

for ($i = 1; $i <= 7; $i++)
{
    $result = RegistrationTable::add([
        'EVENT_ID' => 1,
        'USER_ID' => CurrentUser::get()->getId(),
        'NAME' => 'Анна Смирнова',
        'EMAIL' => 'anna@example.com',
        'SEATS' => 2,
    ]);
    if (!$result->isSuccess())
    {
        echo HtmlFilter::encode(implode(', ', $result->getErrorMessages()));
        break;
    }

    ParticipantTable::add(['REGISTRATION_ID' => $result->getId(), 'NAME' => 'Анна Смирнова']);
    ParticipantTable::add(['REGISTRATION_ID' => $result->getId(), 'NAME' => 'Игорь Белов']);

    $registration = RegistrationTable::getByPrimary($result->getId())->fetchObject();
    $registration->addToMasterclasses($masterclasses[$i % 2]);
    if ($i > 5) {
        $registration->setStatus(RegistrationTable::STATUS_CONFIRMED);
    }
    $registration->save();

    echo 'Заявка № ' . (int)$result->getId() . '<br>';
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>