<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Афиша');
$APPLICATION->SetPageProperty('title', 'Афиша мероприятий — ближайшие события');
$APPLICATION->SetPageProperty('description', 'Расписание ближайших мероприятий и запись участников');
?>
<p class="events-lead">Ближайшие мероприятия и запись участников.</p>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>