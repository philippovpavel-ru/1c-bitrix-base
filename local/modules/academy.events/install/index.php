<?php

use Academy\Events\RegistrationTable;
use Academy\Events\ParticipantTable;
use Academy\Events\MasterclassTable;
use Academy\Events\RegistrationMasterclassTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\TypeTable;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class academy_events extends CModule
{
    public $MODULE_ID = 'academy.events';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Academy';
    public $PARTNER_URI = 'https://example.com';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('ACADEMY_EVENTS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('ACADEMY_EVENTS_MODULE_DESCRIPTION');
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallFiles();
    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        $connection = Application::getConnection();
        foreach ($this->getEntityClasses() as $class)
        {
            $entity = $class::getEntity();
            if (!$connection->isTableExists($entity->getDBTableName()))
            {
                $entity->createDbTable();
            }
        }

        $this->installIblock();

        return true;
    }

    public function UnInstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        $connection = Application::getConnection();
        foreach ($this->getEntityClasses() as $class)
        {
            $tableName = $class::getEntity()->getDBTableName();
            if ($connection->isTableExists($tableName))
            {
                $connection->dropTable($tableName);
            }
        }

        Option::delete($this->MODULE_ID);

        return true;
    }

    private function getEntityClasses(): array
    {
        return [
            RegistrationTable::class,
            ParticipantTable::class,
            MasterclassTable::class,
            RegistrationMasterclassTable::class,
        ];
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );

        return true;
    }

    public function UnInstallFiles()
    {
        Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/local/components/academy/event.host');
        Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/local/components/academy/registration.list');

        return true;
    }

    private function installIblock(): void
    {
        Loader::includeModule('iblock');

        if (!TypeTable::getByPrimary('events')->fetch())
        {
            $type = new CIBlockType();
            $typeAdded = $type->Add([
                'ID' => 'events',
                'SECTIONS' => 'Y',
                'LANG' => [
                    'ru' => [
                        'NAME' => 'Мероприятия',
                        'SECTION_NAME' => 'Раздел',
                        'ELEMENT_NAME' => 'Мероприятие',
                    ],
                ],
            ]);
            if (!$typeAdded)
            {
                throw new SystemException($type->LAST_ERROR);
            }
        }

        $iblock = IblockTable::getList([
            'select' => ['ID', 'API_CODE'],
            'filter' => ['=IBLOCK_TYPE_ID' => 'events', '=CODE' => 'events'],
        ])->fetch();

        if ($iblock)
        {
            if ($iblock['API_CODE'] !== 'Events')
            {
                (new CIBlock())->Update($iblock['ID'], ['API_CODE' => 'Events']);
            }

            return;
        }

        $iblockApi = new CIBlock();
        $iblockId = $iblockApi->Add([
            'IBLOCK_TYPE_ID' => 'events',
            'NAME' => 'Мероприятия',
            'CODE' => 'events',
            'API_CODE' => 'Events',
            'ACTIVE' => 'Y',
            'LID' => ['s1'],
            'GROUP_ID' => [2 => 'R'],
        ]);
        if (!$iblockId)
        {
            throw new SystemException($iblockApi->LAST_ERROR);
        }

        $properties = [
            ['NAME' => 'Дата и время', 'CODE' => 'EVENT_DATE', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'DateTime'],
            ['NAME' => 'Лимит мест', 'CODE' => 'SEATS_LIMIT', 'PROPERTY_TYPE' => 'N'],
            ['NAME' => 'Ведущий', 'CODE' => 'HOST', 'PROPERTY_TYPE' => 'N', 'USER_TYPE' => 'UserID'],
            ['NAME' => 'Формат', 'CODE' => 'FORMAT', 'PROPERTY_TYPE' => 'L', 'VALUES' => [
                ['VALUE' => 'Онлайн', 'XML_ID' => 'online', 'SORT' => 10],
                ['VALUE' => 'Офлайн', 'XML_ID' => 'offline', 'SORT' => 20],
            ]],
        ];
        foreach ($properties as $property)
        {
            $propertyApi = new CIBlockProperty();
            if (!$propertyApi->Add(['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'] + $property))
            {
                throw new SystemException($propertyApi->LAST_ERROR);
            }
        }
    }
}