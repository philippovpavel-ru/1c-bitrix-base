<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Афиша');
$APPLICATION->SetPageProperty('title', 'Афиша мероприятий — ближайшие события');
$APPLICATION->SetPageProperty('description', 'Расписание ближайших мероприятий и запись участников');
?>

<p class="events-lead"><a href="/events/online/">Только онлайн мероприятия</a></p>

<?php
$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"afisha", 
	[
		"IBLOCK_TYPE" => "events",
		"IBLOCK_ID" => "1",
		"NEWS_COUNT" => "10",
		"SORT_BY1" => "PROPERTY_EVENT_DATE",
		"SORT_ORDER1" => "ASC",
		"PROPERTY_CODE" => [
			0 => "FORMAT",
			1 => "EVENT_DATE",
			2 => "SEATS_LIMIT",
		],
		"DETAIL_URL" => "/events/detail.php?CODE=#ELEMENT_CODE#",
		"SET_TITLE" => "N",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"CACHE_GROUPS" => "Y"
	],
	false
);
?>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>