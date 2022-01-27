<?php


namespace Mindbox\DataBase;


use Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator,
    Bitrix\Main\Application,
    Bitrix\Main\Entity\Base;


/**
 * Class TransactionTable
 **/

class MindboxTransactionTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'mindbox_transaction';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'id',
                [
                    'autocomplete' => true,
                    'primary' => true,
                ]
            ),
            new IntegerField(
                'order_id',
                [
                    'required' => true,
                ]
            ),
            new StringField(
                'transaction',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'close',
                [
                    'default' => 0,
                ]
            ),
        ];
    }

    public function createTable()
    {
        if (!Application::getConnection()->isTableExists($this->getTableName())) {
            Base::getInstance(__CLASS__)->createDBTable();
        }
    }
}