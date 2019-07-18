<?php

namespace Mindbox;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

class QueueTable extends Entity\DataManager
{
    public static function start()
    {
        self::execute();

        return '\Mindbox\QueueTable::start();';
    }

    public static function push(MindboxRequest $request)
    {
        if(isset($request)) {
            self::add(['REQUEST_DATA' => serialize($request)]);
        }
    }

    public static function execute()
    {
        $mindbox = Options::getConfig(true);
        if (!$mindbox) {
            return;
        }

        foreach (self::getPendingTasks() as $task) {
            $request = unserialize($task['REQUEST_DATA']);
            if (!$request instanceof MindboxRequest) {
                continue;
            }

            try {
                $client = $mindbox->getClient($request->getApiVersion());

                $client->setRequest($request)->sendRequest();
                $status = 'Y';
            } catch (Exceptions\MindboxClientErrorException $e) {
                $status = 'F';
            } catch (Exceptions\MindboxUnavailableException $e) {
                $status = 'N';
            } catch (Exceptions\MindboxClientException $e) {
                $status = 'N';
            }

            self::update($task['ID'],
                [
                    'STATUS_EXEC' => $status,
                    'DATE_EXEC' => DateTime::createFromTimestamp(time())
                ]
            );
        }
    }

    private static function getPendingTasks()
    {
        $dbTasks = self::getList([
            'filter' => ['STATUS_EXEC' => 'N']
        ]);

        $pending = [];

        while ($task = $dbTasks->fetch()) {
            $pending[] = $task;
        }

        return $pending;
    }

    public static function getTableName()
    {
        return 'mindbox_queue';
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            new Entity\TextField('REQUEST_DATA'),
            new Entity\DatetimeField('DATE_INSERT', [
                'default_value' => DateTime::createFromTimestamp(time())
            ]),
            new Entity\DatetimeField('DATE_EXEC'),
            new Entity\EnumField('STATUS_EXEC', [
                'values' => array('N', 'Y', 'F'),
                'default_value' => 'N'

            ])
        ];
    }

}