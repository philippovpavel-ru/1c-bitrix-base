<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Мои заявки');

$APPLICATION->IncludeComponent(
    'academy:registration.list',
    '',
    []
);
?>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>