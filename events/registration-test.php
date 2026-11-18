<?php
use Academy\Events\RegistrationTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Проверка заявок');

Loader::requireModule('academy.events');

$result = RegistrationTable::add([
    'EVENT_ID' => 1,
    'NAME' => ' Анна Смирнова ',
    'EMAIL' => 'Anna@Example.com',
]);
$saved = $result->isSuccess() ? RegistrationTable::getByPrimary($result->getId())->fetch() : [];
?>
<?php if ($result->isSuccess()): ?>
    <p>Заявка принята, номер <?= (int)$result->getId() ?></p>
    <ul>
        <?php foreach ($saved as $field => $value): ?>
            <li><?= HtmlFilter::encode($field . ': ' . $value) ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <ul>
        <?php foreach ($result->getErrorMessages() as $message): ?>
            <li><?= HtmlFilter::encode($message) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>