<?php


namespace Mindbox;


class EventController
{
    protected $bitrixEventCode = 'bitrixEventCode';
    protected $bitrixModuleCode = 'bitrixModuleId';
    protected $russianNameCode = 'optionNameRu';
    protected $englishNameCode = 'optionNameEn';
    protected $eventManager = null;

    public function __construct()
    {
        $this->eventManager = \Bitrix\Main\EventManager::getInstance();
    }

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

    public function findEventHandlers($moduleId, $eventId)
    {
        $return = false;
        $eventHandlers = $this->eventManager->findEventHandlers($moduleId, $eventId);

        if (!empty($eventHandlers) && is_array($eventHandlers)) {
            foreach ($eventHandlers as $handler) {
                if ($handler['TO_MODULE_ID'] === 'mindbox.marketing') {
                    $return = $handler;
                    break;
                }
            }
        }

        return $return;
    }

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

    protected function registerEventHandler($params)
    {
        $this->eventManager->registerEventHandlerCompatible(
            $params['bitrixModule'],
            $params['bitrixEvent'],
            'mindbox.marketing',
            $params['class'],
            $params['method'],
            1000
        );
    }

    protected function unRegisterEventHandler($params)
    {
        $this->eventManager->unRegisterEventHandler(
            $params['bitrixModule'],
            $params['bitrixEvent'],
            'mindbox.marketing',
            $params['class'],
            $params['method']
        );
    }

    public function handle($activeEventList = [])
    {
        $allRegisteredEvent = $this->findEventHandlersAll();
        $eventList = $this->getModuleEvents();

        foreach ($allRegisteredEvent as $eventCode => $value) {
            $moduleEventData = $eventList[$eventCode];
            if (!empty($moduleEventData)) {
                if (!in_array($eventCode, $activeEventList) && $value !== false) {
                    // деактивируем

                    $this->unRegisterEventHandler($moduleEventData);
                }
                elseif (in_array($eventCode, $activeEventList) && $value === false) {
                    // активируем

                    $this->registerEventHandler($moduleEventData);
                }
            }
        }
    }

    public function installEvents()
    {
        $eventList = $this->getModuleEvents();
        foreach ($eventList as $item) {
            $this->registerEventHandler($item);
        }
    }

    public function onAfterSetOption($value)
    {
        $arEventOptions = explode(',', $value);
        $self = new EventController();
        $self->handle($arEventOptions);
    }
}