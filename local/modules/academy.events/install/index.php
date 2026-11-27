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
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Main\EventManager;
use Academy\Events\EventHandler\RegistrationHandler;
use Academy\Events\EventHandler\UserHandler;

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

    // Инициализация модуля, установка его версии, имени и описания
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('ACADEMY_EVENTS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('ACADEMY_EVENTS_MODULE_DESCRIPTION');
    }

    // Устанавливаем модуль, его таблицы, пользовательские поля и файлы
    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallEvents();
    }

    // Удаляем модуль, его таблицы, пользовательские поля и файлы
    public function DoUninstall()
    {
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    // Создаем таблицы сущностей модуля, инфоблок "Мероприятия" и его свойства, пользовательские поля
    public function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        // Создаем таблицы сущностей модуля
        $connection = Application::getConnection();
        foreach ($this->getEntityClasses() as $class)
        {
            $entity = $class::getEntity();
            if (!$connection->isTableExists($entity->getDBTableName()))
            {
                $entity->createDbTable();
            }
        }

        // Добавляем пользовательское поле UF_COMPANY для пользователей и UF_MANAGER_NOTE для регистрации
        $this->addUserField('USER', 'UF_COMPANY', 'string', Loc::getMessage('ACADEMY_EVENTS_MODULE_USER_UF_COMPANY'));
        $this->addUserField(RegistrationTable::getUfId(), 'UF_MANAGER_NOTE', 'string', Loc::getMessage('ACADEMY_EVENTS_MODULE_REGISTRATION_UF_MANAGER_NOTE'));

        $iblockId = $this->installIblock();
        $venueTable = $this->installVenues();
        $this->installVenueProperty($iblockId, $venueTable);

        EventManager::getInstance()->registerEventHandler(
            'academy.events',
            'OnAfterRegistrationAdd',
            $this->MODULE_ID,
            RegistrationHandler::class,
            'onAfterRegistrationAdd'
        );

        EventManager::getInstance()->registerEventHandlerCompatible(
            'main',
            'OnUserDelete',
            $this->MODULE_ID,
            UserHandler::class,
            'onUserDelete'
        );

        CAgent::AddAgent(
            '\Academy\Events\Agent\ReminderAgent::run();',
            $this->MODULE_ID,
            'N',
            86400
        );

        return true;
    }

    // Удаляем таблицы сущностей модуля, пользовательские поля и настройки модуля
    public function UnInstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        // Удаляем таблицы сущностей модуля
        $connection = Application::getConnection();
        foreach ($this->getEntityClasses() as $class)
        {
            $tableName = $class::getEntity()->getDBTableName();
            if ($connection->isTableExists($tableName))
            {
                $connection->dropTable($tableName);
            }
        }

        // Удаляем пользовательское поле UF_MANAGER_NOTE из таблицы регистрации
        (new CUserTypeEntity())->DropEntity(RegistrationTable::getUfId());

        // Удаляем настройки модуля
        Option::delete($this->MODULE_ID);

        EventManager::getInstance()->unRegisterEventHandler(
            'academy.events',
            'OnAfterRegistrationAdd',
            $this->MODULE_ID,
            RegistrationHandler::class,
            'onAfterRegistrationAdd'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnUserDelete',
            $this->MODULE_ID,
            UserHandler::class,
            'onUserDelete'
        );

        return true;
    }

    // Возвращает массив классов сущностей модуля
    private function getEntityClasses(): array
    {
        return [
            RegistrationTable::class,
            ParticipantTable::class,
            MasterclassTable::class,
            RegistrationMasterclassTable::class,
        ];
    }

    // Копируем файлы компонентов модуля
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

    // Удаляем файлы компонентов модуля
    public function UnInstallFiles()
    {
        Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/local/components/academy/event.host');
        Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/local/components/academy/registration.list');

        return true;
    }

    // Создаем инфоблок "Мероприятия" и его свойства
    private function installIblock(): int
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

            return (int)$iblock['ID'];
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

        return (int)$iblockId;
    }

    // Добавляем пользовательское поле для сущности
    private function addUserField(string $entityId, string $fieldName, string $type, string $label, bool $mandatory = false): void
    {
        $exists = CUserTypeEntity::GetList([], ['ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldName])->Fetch();
        if ($exists)
        {
            return;
        }

        $fieldId = (new CUserTypeEntity())->Add([
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName,
            'USER_TYPE_ID' => $type,
            'MANDATORY' => $mandatory ? 'Y' : 'N',
            'EDIT_FORM_LABEL' => ['ru' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label],
        ]);

        if (!$fieldId)
        {
            global $APPLICATION;
            $exception = $APPLICATION->GetException();
            throw new SystemException($exception ? $exception->GetString() : 'Не удалось создать поле ' . $fieldName);
        }
    }

    // Создаем таблицу для хранения мест проведения мероприятий
    private function installVenues(): string
    {
        Loader::includeModule('highloadblock');

        $block = HighloadBlockTable::getList([
            'select' => ['ID', 'TABLE_NAME'],
            'filter' => ['=NAME' => 'AcademyEventsVenue'],
        ])->fetch();

        if (!$block)
        {
            $result = HighloadBlockTable::add([
                'NAME' => 'AcademyEventsVenue',
                'TABLE_NAME' => 'academy_events_venue',
            ]);
            if (!$result->isSuccess())
            {
                throw new SystemException(implode('; ', $result->getErrorMessages()));
            }
            $block = ['ID' => $result->getId(), 'TABLE_NAME' => 'academy_events_venue'];
        }

        $entityId = HighloadBlockTable::compileEntityId($block['ID']);
        $this->addUserField($entityId, 'UF_NAME', 'string', Loc::getMessage('ACADEMY_EVENTS_VENUE_UF_NAME'), true);
        $this->addUserField($entityId, 'UF_XML_ID', 'string', Loc::getMessage('ACADEMY_EVENTS_VENUE_UF_XML_ID'), true);
        $this->addUserField($entityId, 'UF_CAPACITY', 'integer', Loc::getMessage('ACADEMY_EVENTS_VENUE_UF_CAPACITY'), true);

        $blockLangName = (string)Loc::getMessage('ACADEMY_EVENTS_VENUE_HL_NAME');
        if ($blockLangName !== '')
        {
            $blockLang = HighloadBlockLangTable::getList([
                'filter' => ['=ID' => (int)$block['ID'], '=LID' => 'ru'],
            ])->fetch();

            if (!$blockLang)
            {
                $langResult = HighloadBlockLangTable::add([
                    'ID' => (int)$block['ID'],
                    'LID' => 'ru',
                    'NAME' => $blockLangName,
                ]);
                if (!$langResult->isSuccess())
                {
                    throw new SystemException(implode('; ', $langResult->getErrorMessages()));
                }
            }
        }

        return $block['TABLE_NAME'];
    }

    private function installVenueProperty(int $iblockId, string $tableName): void
    {
        $exists = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'VENUE'])->Fetch();
        if ($exists)
        {
            return;
        }

        $property = new CIBlockProperty();
        $propertyAdded = $property->Add([
            'IBLOCK_ID' => $iblockId,
            'NAME' => 'Площадка',
            'CODE' => 'VENUE',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'directory',
            'USER_TYPE_SETTINGS' => ['TABLE_NAME' => $tableName],
        ]);
        if (!$propertyAdded)
        {
            throw new SystemException($property->LAST_ERROR);
        }
    }

    public function InstallEvents()
    {
        $type = new CEventType();
        $type->Add([
            'LID' => 'ru',
            'EVENT_NAME' => 'ACADEMY_EVENTS_REGISTRATION',
            'NAME' => 'Заявка на мероприятие принята',
            'DESCRIPTION' => "#EMAIL_TO# — email участника\n#REGISTRATION_ID# — номер заявки\n#EVENT_NAME# — мероприятие\n#SEATS# — количество мест",
        ]);

        $message = new CEventMessage();
        $message->Add([
            'ACTIVE' => 'Y',
            'EVENT_NAME' => 'ACADEMY_EVENTS_REGISTRATION',
            'LID' => ['s1'],
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#',
            'SUBJECT' => 'Заявка № #REGISTRATION_ID# принята',
            'BODY_TYPE' => 'text',
            'MESSAGE' => "Вы записаны на мероприятие «#EVENT_NAME#».\nКоличество мест: #SEATS#.",
        ]);

        return true;
    }

    public function UnInstallEvents()
    {
        $by = 'id';
        $order = 'asc';
        $messages = CEventMessage::GetList($by, $order, ['TYPE_ID' => 'ACADEMY_EVENTS_REGISTRATION']);
        while ($message = $messages->Fetch())
        {
            CEventMessage::Delete($message['ID']);
        }
        CEventType::Delete('ACADEMY_EVENTS_REGISTRATION');

        return true;
    }
}