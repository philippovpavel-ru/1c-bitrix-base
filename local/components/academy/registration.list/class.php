<?php

use Academy\Events\RegistrationTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UI\PageNavigation;

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
                'select' => ['ID', 'EVENT_ID', 'STATUS', 'CREATED_AT'],
                'filter' => $filter,
                'order' => ['CREATED_AT' => 'DESC', 'ID' => 'DESC'],
                'offset' => $navigation->getOffset(),
                'limit' => $navigation->getLimit(),
            ])->fetchCollection();
            $registrations->fill(['PARTICIPANTS', 'MASTERCLASSES']);

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
        }

        $this->includeComponentTemplate();
    }
}