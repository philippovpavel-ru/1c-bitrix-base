<?php

use Academy\Events\RegistrationTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Highloadblock\HighloadBlockTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

class AcademyRegistrationListComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        if (!Loader::includeModule('academy.events'))
        {
            return;
        }

        $userId = (int)CurrentUser::get()->getId();
        $this->arResult['AUTHORIZED'] = $userId > 0;

        if ($userId > 0)
        {
            // Get registrations for the current user
            $filter = ['=USER_ID' => $userId];

            // Pagination
            $navigation = new PageNavigation('registrations');
            $navigation
                ->setPageSize(5)
                ->setRecordCount(RegistrationTable::getCount($filter))
                ->initFromUri();

            // Get registrations with pagination
            $registrations = RegistrationTable::getList([
                'select' => ['ID', 'EVENT_ID', 'STATUS', 'CREATED_AT', 'EVENT', 'EVENT.VENUE'],
                'filter' => $filter,
                'order' => ['CREATED_AT' => 'DESC', 'ID' => 'DESC'],
                'offset' => $navigation->getOffset(),
                'limit' => $navigation->getLimit(),
            ])->fetchCollection();
            $registrations->fill(['PARTICIPANTS', 'MASTERCLASSES']);

            $venueCodes = [];
            foreach ($registrations as $registration)
            {
                $venueCode = $registration->getEvent()?->get('VENUE')?->getValue();
                if ($venueCode !== null && $venueCode !== '')
                {
                    $venueCodes[] = (string)$venueCode;
                }
            }
            $venueCodes = array_values(array_unique($venueCodes));

            $venuesByCode = [];
            if (!empty($venueCodes) && Loader::includeModule('highloadblock'))
            {
                $block = HighloadBlockTable::getList([
                    'select' => ['ID', 'NAME', 'TABLE_NAME'],
                    'filter' => ['=NAME' => 'AcademyEventsVenue'],
                ])->fetch();

                if ($block)
                {
                    $entity = HighloadBlockTable::compileEntity($block);
                    $dataClass = $entity->getDataClass();
                    $venues = $dataClass::query()
                        ->setSelect(['ID', 'UF_NAME', 'UF_XML_ID', 'UF_CAPACITY'])
                        ->whereIn('UF_XML_ID', $venueCodes)
                        ->fetchAll();

                    foreach ($venues as $venue)
                    {
                        $venuesByCode[(string)$venue['UF_XML_ID']] = $venue;
                    }
                }
            }

            $venuesByEventId = [];
            foreach ($registrations as $registration)
            {
                $eventId = (int)$registration->getEventId();
                $venueCode = $registration->getEvent()?->get('VENUE')?->getValue();
                if ($venueCode !== null)
                {
                    $venueCode = (string)$venueCode;
                    if (isset($venuesByCode[$venueCode]))
                    {
                        $venuesByEventId[$eventId] = $venuesByCode[$venueCode];
                    }
                }
            }

            // Get statistics for the current user
            $stats = RegistrationTable::query()
                ->setSelect([
                    'STATUS',
                    new ExpressionField('CNT', 'COUNT(*)'),
                ])
                ->setFilter($filter)
                ->setGroup(['STATUS'])
                ->fetchAll();

            // Fill arResult with data
            $this->arResult['NAVIGATION'] = $navigation;
            $this->arResult['REGISTRATIONS'] = $registrations;
            $this->arResult['STATS'] = $stats;
            $this->arResult['VENUES'] = $venuesByEventId;
        }

        $this->includeComponentTemplate();
    }
}