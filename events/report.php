<?php

use Academy\Events\RegistrationTable;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Отчет по заявкам');

Loader::requireModule('academy.events');

if (!CurrentUser::get()->isAdmin())
{
    echo '<p>Отчет доступен только администратору</p>';
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';

    return;
}

$request = Context::getCurrent()->getRequest();
$status = (string)$request->getQuery('status');
$allowed = [
    RegistrationTable::STATUS_NEW,
    RegistrationTable::STATUS_CONFIRMED,
    RegistrationTable::STATUS_CANCELLED,
];

if (!in_array($status, $allowed, true))
{
    $status = RegistrationTable::STATUS_CONFIRMED;
}

$connection = Application::getConnection();
$tracker = $connection->startTracker(true);

$sql = new SqlExpression(
    'SELECT EVENT_ID, COUNT(*) AS CNT, SUM(SEATS) AS SEATS_TOTAL FROM ?#'
    . ' WHERE STATUS = ?s GROUP BY EVENT_ID ORDER BY EVENT_ID',
    RegistrationTable::getTableName(),
    $status
);

$rows = $connection->query($sql)->fetchAll();
?>
<p>Статус: <strong><?= HtmlFilter::encode($status) ?></strong></p>

<div class="events-table-wrap">
    <table>
        <thead>
            <tr>
                <th>Мероприятие</th>
                <th>Заявок</th>
                <th>Мест</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="3">Заявок нет</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>Мероприятие #<?= (int)$row['EVENT_ID'] ?></td>
                    <td><?= (int)$row['CNT'] ?></td>
                    <td><?= (int)$row['SEATS_TOTAL'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php foreach ($tracker->getQueries() as $query): ?>
    <p>
        <code><?= HtmlFilter::encode($query->getSql()) ?></code>
        — <?= round($query->getTime(), 4) ?> с
    </p>
<?php endforeach; ?>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
