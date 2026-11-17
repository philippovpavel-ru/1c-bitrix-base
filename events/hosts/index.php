<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle("Наши ведущие");
?><p class="events-lead">
	 Кто ведет мероприятия и к кому обращаться с вопросами по программе.
</p>
 <?$APPLICATION->IncludeComponent(
	"academy:event.host",
	"",
	Array(
		"CACHE_TIME" => 3600,
		"CACHE_TYPE" => "A",
		"SHOW_PHOTO" => "Y",
		"SHOW_POSITION" => "Y",
		"USER_ID" => 1
	)
);?>
<?$APPLICATION->IncludeComponent(
	"academy:event.host",
	"compact",
	Array(
		"CACHE_TIME" => "3600",
		"CACHE_TYPE" => "A",
		"SHOW_PHOTO" => "N",
		"SHOW_POSITION" => "N",
		"USER_ID" => "2"
	)
);?><?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>