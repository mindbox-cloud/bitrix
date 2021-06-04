<?php


namespace Mindbox;

use Bitrix\Main\Localization\Loc;

defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'mindbox.marketing');
/**
 * Class EventController
 * @package Mindbox
 */
class EventController
{
    /**
     * @var string
     */
    protected $bitrixEventCode = 'bitrixEventCode';
    /**
     * @var string
     */
    protected $notCompatibleCode = 'notCompatible';
    /**
     * @var string
     */
    protected $bitrixModuleCode = 'bitrixModuleId';
    /**
     * @var string
     */
    protected $langEventName = 'langEventName';

    protected $eventManager = null;

    /**
     * @var string
     */
    protected static $optionEventCode = 'ENABLE_EVENT_LIST';

    /**
     * EventController constructor.
     */
    public function __construct()
    {
        $this->eventManager = \Bitrix\Main\EventManager::getInstance();
    }

    /**
     * Статический метод. Возвращает код настройки списка событий
     * @return string
     */
    public static function getOptionEventCode()
    {
        return self::$optionEventCode;
    }

    /**
     * Метод получает все обработчики из класса Event
     * @return array
     */
    public function getModuleEvents()
    {
        $eventObject = new Event();
        $reflection = new \ReflectionClass($eventObject);
        $fullClassName = '\\' . $reflection->getName();
        $eventMethods = $reflection->getMethods();
        $eventList = [];

        if (!empty($eventMethods) && is_array($eventMethods)) {
            foreach ($eventMethods as $method) {
                $methodComments = $method->getDocComment();
                $methodDocsParams = $this->prepareDocsBlockParams($methodComments);

                if (!empty($methodDocsParams)
                    && array_key_exists($this->bitrixEventCode, $methodDocsParams)
                ) {
                    $eventList[$methodDocsParams[$this->bitrixEventCode]] = [
                        'bitrixModule' => $methodDocsParams[$this->bitrixModuleCode],
                        'bitrixEvent' => $methodDocsParams[$this->bitrixEventCode],
                        'notCompatible' => $methodDocsParams[$this->notCompatibleCode],
                        'method' => $method->getName(),
                        'class' => $fullClassName,
                        'name' => $this->getHumanEventName($methodDocsParams[$this->langEventName])
                    ];
                }
            }
        }

        return $eventList;
    }

    public function getHumanEventName($langCode)
    {
        $langName = Loc::getMessage($langCode);
        return (!empty($langName)) ? $langName : $langCode;
    }

    /**
     * Возвращает список обработчиков для страницы настроек.
     * @param string $lang
     * @return array
     */
    public static function getOptionEventList($lang = 'ru')
    {
        $return = [];
        $self = new EventController();
        $listEvents = $self->getModuleEvents();

        if (!empty($listEvents) && is_array($listEvents)) {
            foreach ($listEvents as $item) {
                $return[$item['bitrixEvent']] = $item['name'];
            }
        }

        return $return;
    }

    /**
     * Поиск всех зарегистрированных обработчиков
     * @return array
     */
    public function findEventHandlersAll()
    {
        $eventList = $this->getModuleEvents();
        $findRegisteredEvents = [];

        foreach ($eventList as $key => $item) {
            $find = $this->findEventHandlers($item['bitrixModule'], $item['bitrixEvent']);
            $findRegisteredEvents[$key] = $find;
        }

        return $findRegisteredEvents;
    }

    /**
     * Поиск зарегистрированного обработчика модуля
     * @param $moduleId
     * @param $eventId
     * @return false|mixed
     */
    public function findEventHandlers($moduleId, $eventId)
    {
        $return = false;
        $eventHandlers = $this->eventManager->findEventHandlers($moduleId, $eventId);

        if (!empty($eventHandlers) && is_array($eventHandlers)) {
            foreach ($eventHandlers as $handler) {
                if ($handler['TO_MODULE_ID'] === ADMIN_MODULE_NAME) {
                    $return = $handler;
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * Метод получает данные их комментариев к методу
     * @param $stirng
     * @return array
     */
    protected function prepareDocsBlockParams($stirng)
    {
        $return = [];

        if (preg_match_all('/@(\w+)\s+(.*)\r?\n/m', $stirng, $matches)) {
            $return = array_combine($matches[1], $matches[2]);
        }

        if (!empty($return)) {
            foreach ($return as &$item) {
                $item = str_replace(["\r\n", "\r", "\n", PHP_EOL], '', trim($item));
            }
        }

        return $return;
    }

    /**
     * Регистрация обработчика
     * @param $params
     */
    protected function registerEventHandler($params)
    {
        $method = 'registerEventHandlerCompatible';

        if ($params['notCompatible']) {
            $method = 'registerEventHandler';
        }

        $this->eventManager->{$method}(
            $params['bitrixModule'],
            $params['bitrixEvent'],
            ADMIN_MODULE_NAME,
            $params['class'],
            $params['method'],
            1000
        );
    }

    /**
     * Удаление обработчика
     * @param $params
     */
    protected function unRegisterEventHandler($params)
    {
        $this->eventManager->unRegisterEventHandler(
            $params['bitrixModule'],
            $params['bitrixEvent'],
            ADMIN_MODULE_NAME,
            $params['class'],
            $params['method']
        );
    }

    /**
     * @return string[]
     */
    protected function getEventControllerHandlerData()
    {
        return [
            'bitrixModule' => 'main',
            'bitrixEvent' => 'OnAfterSetOption_' . self::getOptionEventCode(),
            'class' => '\Mindbox\EventController',
            'method' => 'onAfterSetOption'
        ];
    }

    protected function getCartRulesHandlerData()
    {
        return [
            'bitrixModule' => 'sale',
            'bitrixEvent' => 'OnCondSaleActionsControlBuildList',
            'class' => '\Mindbox\ExtensionCartRulesActions',
            'method' => 'GetControlDescr'
        ];
    }

    protected function installCartRulesHandler()
    {
        $this->registerEventHandler($this->getCartRulesHandlerData());
    }

    protected function unInstallCartRulesHandler()
    {
        $this->unRegisterEventHandler($this->getCartRulesHandlerData());
    }

    /**
     * Регистрация обработчика, ответствененного за изменения активности обработчиков
     */
    protected function installEventControllerHandler()
    {
        $this->registerEventHandler($this->getEventControllerHandlerData());
    }

    /**
     * Удаление обработчика, ответствененного за изменения активности обработчиков
     */
    protected function unInstallEventControllerHandler()
    {
        $this->unRegisterEventHandler($this->getEventControllerHandlerData());
    }

    /**
     * Обработка статуса обработчика. Вызывается при изменении списка на странице настроек.
     * @param array $activeEventList
     */
    protected function handle($activeEventList = [])
    {
        $allRegisteredEvent = $this->findEventHandlersAll();
        $eventList = $this->getModuleEvents();

        foreach ($allRegisteredEvent as $eventCode => $value) {
            $moduleEventData = $eventList[$eventCode];
            if (!empty($moduleEventData)) {
                if (!in_array($eventCode, $activeEventList) && $value !== false) {
                    $this->unRegisterEventHandler($moduleEventData);
                } elseif (in_array($eventCode, $activeEventList) && $value === false) {
                    $this->registerEventHandler($moduleEventData);
                }
            }
        }
    }

    /**
     *  Регистрация всех обработчиков при установке модуля
     */
    public function installEvents()
    {

        $eventList = $this->getModuleEvents();

        foreach ($eventList as $eventCode => $item) {
            $this->registerEventHandler($item);
        }

        $bitrixEventList = array_keys($eventList);

        if (!empty($bitrixEventList) && is_array($bitrixEventList)) {
            $strValue = implode(',', $bitrixEventList);
            $this->setOptionValue($strValue);
        }

        $this->installEventControllerHandler();
        $this->installCartRulesHandler();
    }

    /**
     *  Удаление всех обработчиков при удалении модуля
     */
    public function unInstallEvents()
    {
        $dataBaseEventList = $this->getAllRegisteredEvents();

        foreach ($dataBaseEventList as $item) {
            $eventFields = [
                'bitrixModule' => $item['FROM_MODULE_ID'],
                'bitrixEvent' => $item['MESSAGE_ID'],
                'class' => $item['TO_CLASS'],
                'method' => $item['TO_METHOD']
            ];
            $this->unRegisterEventHandler($eventFields);
        }

        $this->unInstallEventControllerHandler();
        $this->unInstallCartRulesHandler();
        $this->setOptionValue('');
    }

    /**
     * Устанавливает значение настройке после регистрации основных обработчиков
     * @param $stringValue
     */
    public function setOptionValue($stringValue)
    {
        \COption::SetOptionString(ADMIN_MODULE_NAME, self::getOptionEventCode(), $stringValue);
    }

    /**
     * Метод регистрируется для события OnAfterSetOption_ENABLE_EVENT_LIST.
     * Изменения списка обработчиков.
     * @param $value
     */
    public function onAfterSetOption($value)
    {
        $arEventOptions = explode(',', $value);
        $self = new EventController();
        $self->handle($arEventOptions);
    }

    /**
     * Метод возвщает списко всех записей модуля из таблицы b_module_to_module
     * @return array
     */
    protected function getAllRegisteredEvents()
    {
        $adminModuleName = ADMIN_MODULE_NAME;
        $return = [];

        if (!empty($adminModuleName)) {

            $getActiveModuleEvents = \Mindbox\DataBase\ModuleToModuleTable::getList(
                [
                    'filter' => [
                        'TO_MODULE_ID' => $adminModuleName
                    ],
                ]
            );

            while ($row = $getActiveModuleEvents->fetch()) {
                if ($row['TO_MODULE_ID'] === $adminModuleName) {
                    $return[] = $row;
                }
            }

        }

        return $return;
    }


    /**
     * Метод проводит ревизию зарегистрированных событий с таблице b_module_to_module и событий из класса Event
     * Если событие есть в базе, но нет в классе - удаляем запись из базы
     */
    public function revisionHandlers()
    {
        $dataBaseEventList = $this->getAllRegisteredEvents();
        $declareEventList = $this->getModuleEvents();

        if (!empty($dataBaseEventList) && is_array($dataBaseEventList)) {
            foreach ($dataBaseEventList as $item) {

                if ($item['TO_CLASS'] === '\Mindbox\Event') {

                    $exist = false;

                    foreach ($declareEventList as $declareEvent) {
                        if ($item['TO_METHOD'] === $declareEvent['method'] && $item['MESSAGE_ID'] === $declareEvent['bitrixEvent']) {
                            $exist = true;
                            break;
                        }
                    }

                    if ($exist === false) {
                        $eventFields = [
                            'bitrixModule' => $item['FROM_MODULE_ID'],
                            'bitrixEvent' => $item['MESSAGE_ID'],
                            'class' => $item['TO_CLASS'],
                            'method' => $item['TO_METHOD']
                        ];

                        $this->unRegisterEventHandler($eventFields);
                    }
                }
            }
        }

        $this->revisionEventListOptionValue();
    }

    protected function revisionEventListOptionValue()
    {
        $regEventHandlers = [];
        $getRegEventList = $this->getAllRegisteredEvents();

        foreach ($getRegEventList as $item) {
            if ($item['TO_CLASS'] === '\Mindbox\Event') {
                $regEventHandlers[] = $item['MESSAGE_ID'];
            }
        }

        if (!empty($regEventHandlers)) {
            $this->setOptionValue(implode(',', $regEventHandlers));
        }
    }
}
