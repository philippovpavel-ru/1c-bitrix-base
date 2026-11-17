<?php

use Bitrix\Main\UserTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

class AcademyEventHostComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['USER_ID'] = (int)($arParams['USER_ID'] ?? 0);
        $arParams['SHOW_POSITION'] = ($arParams['SHOW_POSITION'] ?? 'Y') === 'N' ? 'N' : 'Y';
        $arParams['SHOW_PHOTO'] = ($arParams['SHOW_PHOTO'] ?? 'Y') === 'N' ? 'N' : 'Y';
        $arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 3600);

        return $arParams;
    }

    public function executeComponent()
    {
        if ($this->arParams['USER_ID'] <= 0)
        {
            return;
        }

        if ($this->startResultCache())
        {
            $host = $this->getHost($this->arParams['USER_ID']);
            if ($host === null)
            {
                $this->abortResultCache();

                return;
            }

            $this->arResult = $host;
            $this->includeComponentTemplate();
        }
    }

    private function getHost(int $userId): ?array
    {
        $user = UserTable::query()
            ->setSelect(['NAME', 'LAST_NAME', 'WORK_POSITION', 'PERSONAL_PHOTO'])
            ->where('ID', $userId)
            ->where('ACTIVE', 'Y')
            ->fetch()
        ;

        if (!$user)
        {
            return null;
        }

        return [
            'NAME' => trim($user['NAME'] . ' ' . $user['LAST_NAME']),
            'POSITION' => (string)$user['WORK_POSITION'],
            'PHOTO_SRC' => $user['PERSONAL_PHOTO'] ? (string)CFile::GetPath($user['PERSONAL_PHOTO']) : '',
        ];
    }
}