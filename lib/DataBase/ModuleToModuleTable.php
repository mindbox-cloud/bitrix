<?php


namespace Mindbox\DataBase;


use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator;


/**
 * Class ToModuleTable
 *
 * Fields:
 *
 *  ID int mandatory
 *  TIMESTAMP_X datetime optional
 *  SORT int optional default 100
 *  FROM_MODULE_ID string(50) mandatory
 *  MESSAGE_ID string(255) mandatory
 *  TO_MODULE_ID string(50) mandatory
 *  TO_PATH string(255) optional
 *  TO_CLASS string(255) optional
 *  TO_METHOD string(255) optional
 *  TO_METHOD_ARG string(255) optional
 *  VERSION int optional
 *  UNIQUE_ID string(32) mandatory
 *
 * @package Bitrix\Module
 **/

class ModuleToModuleTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_module_to_module';
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
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_ID_FIELD')
                ]
            ),
            new DatetimeField(
                'TIMESTAMP_X',
                [
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_TIMESTAMP_X_FIELD')
                ]
            ),
            new IntegerField(
                'SORT',
                [
                    'default' => 100,
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_SORT_FIELD')
                ]
            ),
            new StringField(
                'FROM_MODULE_ID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateFromModuleId'],
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_FROM_MODULE_ID_FIELD')
                ]
            ),
            new StringField(
                'MESSAGE_ID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateMessageId'],
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_MESSAGE_ID_FIELD')
                ]
            ),
            new StringField(
                'TO_MODULE_ID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateToModuleId'],
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_TO_MODULE_ID_FIELD')
                ]
            ),
            new StringField(
                'TO_PATH',
                [
                    'validation' => [__CLASS__, 'validateToPath'],
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_TO_PATH_FIELD')
                ]
            ),
            new StringField(
                'TO_CLASS',
                [
                    'validation' => [__CLASS__, 'validateToClass'],
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_TO_CLASS_FIELD')
                ]
            ),
            new StringField(
                'TO_METHOD',
                [
                    'validation' => [__CLASS__, 'validateToMethod'],
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_TO_METHOD_FIELD')
                ]
            ),
            new StringField(
                'TO_METHOD_ARG',
                [
                    'validation' => [__CLASS__, 'validateToMethodArg'],
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_TO_METHOD_ARG_FIELD')
                ]
            ),
            new IntegerField(
                'VERSION',
                [
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_VERSION_FIELD')
                ]
            ),
           /* new StringField(
                'UNIQUE_ID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateUniqueId'],
                    'title' => Loc::getMessage('TO_MODULE_ENTITY_UNIQUE_ID_FIELD')
                ]
            ),*/
        ];
    }

    /**
     * Returns validators for FROM_MODULE_ID field.
     *
     * @return array
     */
    public static function validateFromModuleId()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for MESSAGE_ID field.
     *
     * @return array
     */
    public static function validateMessageId()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for TO_MODULE_ID field.
     *
     * @return array
     */
    public static function validateToModuleId()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for TO_PATH field.
     *
     * @return array
     */
    public static function validateToPath()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for TO_CLASS field.
     *
     * @return array
     */
    public static function validateToClass()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for TO_METHOD field.
     *
     * @return array
     */
    public static function validateToMethod()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for TO_METHOD_ARG field.
     *
     * @return array
     */
    public static function validateToMethodArg()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for UNIQUE_ID field.
     *
     * @return array
     */
    public static function validateUniqueId()
    {
        return [
            new LengthValidator(null, 32),
        ];
    }
}