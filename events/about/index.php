<?php
use Academy\Events\Settings;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('О мероприятиях');
$APPLICATION->SetPageProperty('slogan', 'Узнайте, как устроены наши события');

$organizerEmail = Loader::includeModule('academy.events') ? Settings::getOrganizerEmail() : '';
$showEmail = Loader::includeModule('academy.events') ? Settings::isOrganizerEmailVisible() : false;
?>
<p class="events-lead">Как проходят мероприятия и как на них записаться.</p>
<?php if ($showEmail && $organizerEmail !== ''): ?>
    <p>
        Вопросы о мероприятиях:
        <a href="mailto:<?= HtmlFilter::encode($organizerEmail) ?>"><?= HtmlFilter::encode($organizerEmail) ?></a>
    </p>
<?php endif; ?>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>