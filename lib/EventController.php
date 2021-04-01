<?php


namespace Mindbox;


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
    protected $bitrixModuleCode = 'bitrixModuleId';
    /**
     * @var string
     */
    protected $russianNameCode = 'optionNameRu';
    /**
     * @var string
     */
    protected $englishNameCode = 'optionNameEn';
    /**
     * @var null
     */
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

                if (
                    !empty($methodDocsParams)
                    && array_key_exists($this->bitrixEventCode, $methodDocsParams)
                ) {
                      $eventList[$methodDocsParams[$this->bitrixEventCode]] = [
                          'bitrixModule' => $methodDocsParams[$this->bitrixModuleCode],
                          'bitrixEvent' => $methodDocsParams[$this->bitrixEventCode],
                          'method' => $method->getName(),
                          'class' => $fullClassName,
                          'name_ru' => $methodDocsParams[$this->russianNameCode],
                          'name_en' => $methodDocsParams[$this->russianNameCode],
                      ];
                }

            }
        }

        return $eventList;
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
                $return[$item['bitrixEvent']] = ($lang === 'en') ? $item['name_en'] : $item['name_ru'];
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
     * Обработка док-блока. Метод вытаскивает данные из параметров в комментарии.
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
        $this->eventManager->registerEventHandlerCompatible(
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

    /**
     * Регистрация обработчика, ответствененного за изменения активности обработчиков
     */
    public function installEventControllerHandler()
    {
        $this->registerEventHandler($this->getEventControllerHandlerData());
    }

    /**
     * Удаление обработчика, ответствененного за изменения активности обработчиков
     */
    public function unInstallEventControllerHandler()
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
                }
                elseif (in_array($eventCode, $activeEventList) && $value === false) {
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
            $this->setOptionAfterRegisterHandlers($strValue);
        }

        $this->installEventControllerHandler();
    }

    /**
     *  Удаление всех обработчиков при удалении модуля
     */
    public function unInstallEvents()
    {
        $eventList = $this->getModuleEvents();

        foreach ($eventList as $item) {
            $this->unRegisterEventHandler($item);
        }

        $this->unInstallEventControllerHandler();
        $this->setOptionAfterRegisterHandlers('');
    }

    /**
     * Устанавливает значение настройке после регистрации основных обработчиков
     * @param $stringValue
     */
    public function setOptionAfterRegisterHandlers($stringValue)
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
}