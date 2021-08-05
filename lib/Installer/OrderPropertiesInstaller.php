<?php


namespace Mindbox\Installer;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Mindbox\Helper;


/**
 * Класс ответственный за установку и удаление групп и свойств заказа,
 * которые модуль использует для обработки промокодов и бонусов в программе лояльности
 * Class OrderPropertiesInstaller
 * @package Mindbox\Installer
 */
class OrderPropertiesInstaller
{
    /**
     * Название свойства группы
     */
    const PROPERTIES_GROUP_NAME = 'Mindbox';
    /**
     * Код свойства для хранения бонусов Mindbox
     */
    const PROPERTY_BONUS = 'MINDBOX_BONUS';
    /**
     *  Код свойства для хранения промокода Mindbox
     */
    const PROPERTY_PROMO_CODE = 'MINDBOX_PROMO_CODE';

    /**
     * OrderPropertiesInstaller constructor.
     */
    public function __construct()
    {
        Loader::IncludeModule('sale');
    }

    /**
     * Не вычисляемые поля свойства. Используются при создании свойств
     * @param $propertyCode
     * @return array
     */
    protected function getInstallProperiesConfig($propertyCode)
    {
        $config = [
            self::PROPERTY_PROMO_CODE => [
                'NAME' => Loc::getMessage('ORDER_PROMO_CODE_PROPERTY_NAME'),
                'TYPE' => 'TEXT',
                'CODE' => self::PROPERTY_PROMO_CODE,
                'REQUIED' => 'N',
                'UTIL' => 'Y'
            ],
            self::PROPERTY_BONUS => [
                'NAME' => Loc::getMessage('ORDER_BONUS_PROPERTY_NAME'),
                'TYPE' => 'TEXT',
                'CODE' => self::PROPERTY_BONUS,
                'REQUIED' => 'N',
                'UTIL' => 'Y'
            ],
        ];

        return $config[$propertyCode];
    }

    /**
     * Массив допустимых свойств при установке и удалении
     * @return string[]
     */
    protected function getMindboxProperiesCodes()
    {
        return [self::PROPERTY_BONUS, self::PROPERTY_PROMO_CODE];
    }

    /**
     *  Метод запускающий установку. Проверяет существование необходимых групп свойств и свойств.
     *  Если элементы отсутствуют, то создает их.
     */
    public function install()
    {
        $getPersonTypeList = $this->getSitePersonType();
        $getPropertiesGroup = $this->getPropertiesGroupList();

        foreach ($getPersonTypeList as $personItem) {
            if (!array_key_exists($personItem['ID'], $getPropertiesGroup)) {
                $addGroup = $this->addPropertyGroup($personItem['ID']);

                if ((int)$addGroup > 0) {
                    $getPropertiesGroup[$personItem['ID']] = \CSaleOrderPropsGroup::GetByID($addGroup);
                }
            }
        }

        $personTypesIds = array_keys($getPersonTypeList);
        $mindboxPropertiesCodes = $this->getMindboxProperiesCodes();
        $propertiesList  = $this->getPropertiesList();

        if (!empty($personTypesIds)) {
            foreach ($personTypesIds as $personTypesId) {
                foreach ($mindboxPropertiesCodes as $propCode) {
                    $needInstall = true;

                    foreach ($propertiesList as $propertyItem) {
                        if ($propertyItem['PERSON_TYPE_ID'] == $personTypesId && $propertyItem['CODE'] === $propCode) {
                            $needInstall = false;
                        }
                    }

                    if ($needInstall) {
                        $addFields = [
                            'CODE' => $propCode,
                            'PERSON_TYPE_ID' => $personTypesId,
                        ];

                        $selectPropGroup = $getPropertiesGroup[$personTypesId]['ID'];

                        if (!empty($selectPropGroup)) {
                            $addFields['PROPS_GROUP_ID'] = $selectPropGroup;
                        }

                        $this->addProperty($addFields);
                    }
                }
            }
        }
    }

    /**
     *  Удаление групп свойств и свойств
     */
    public function uninstall()
    {
        $this->deletePropertiesGroups();
        $this->deleteProperties();
    }

    /**
     * Получение списка всех типов плательщика для всех активных сайтов на проекте
     * @return array
     */
    protected function getSitePersonType(): array
    {
        $return = [];

        $projectActiveSites = Helper::getSiteList(true);
        $getPersonType = \CSalePersonType::GetList([], ['LID' => $projectActiveSites, 'ACTIVE' => 'Y']);

        while ($item = $getPersonType->Fetch()) {
            $return[$item['ID']] = $item;
        }

        return $return;
    }

    /**
     * Получение списка групп свойств Mindbox
     * @return array
     */
    protected function getPropertiesGroupList()
    {
        $return = [];
        $getGroups = \CSaleOrderPropsGroup::GetList([], [
            'NAME' => self::PROPERTIES_GROUP_NAME
        ]);

        while ($item = $getGroups->Fetch()) {
            $return[$item['PERSON_TYPE_ID']] = $item;
        }

        return $return;
    }

    /**
     * Добавление новой группы с именем self::PROPERTIES_GROUP_NAME
     * @param $personType
     * @return false
     */
    protected function addPropertyGroup($personType)
    {
        $return = false;

        if (!empty($personType)) {
            $return = \CSaleOrderPropsGroup::Add([
                'PERSON_TYPE_ID' => $personType,
                'NAME' => self::PROPERTIES_GROUP_NAME
            ]);
        }

        return $return;
    }

    /**
     *  Удаление всех групп свойств с имененем self::PROPERTIES_GROUP_NAME
     */
    protected function deletePropertiesGroups()
    {
        $list = $this->getPropertiesGroupList();

        if (!empty($list) && is_array($list)) {
            foreach ($list as $item) {
                \CSaleOrderPropsGroup::Delete($item['ID']);
            }
        }
    }

    /**
     * Получение списка свойств Mindbox. Фильтр строится на основе массива getMindboxProperiesCodes()
     * @return array
     */
    protected function getPropertiesList()
    {
        $propsCodes = $this->getMindboxProperiesCodes();
        $return = [];

        if (!empty($propsCodes)) {
            $getProperty = \CSaleOrderProps::GetList([], [
                'CODE' => $propsCodes,
            ]);

            while ($propData = $getProperty->Fetch()) {
                $return[$propData['ID']] = $propData;
            }
        }

        return $return;
    }

    /**
     * Добавление нового свойства
     * @param $fields
     */
    protected function addProperty($fields)
    {
        $addPropertyFields = $this->getInstallProperiesConfig($fields['CODE']);

        if ($addPropertyFields) {
            $addPropertyFields = array_merge($addPropertyFields, $fields);
        }

        $result = \CSaleOrderProps::Add($addPropertyFields);
        var_dump($result);
    }

    /**
     *  Удаление свойств
     */
    protected function deleteProperties()
    {
        $list = $this->getPropertiesList();

        if (!empty($list) && is_array($list)) {
            foreach ($list as $item) {
                \CSaleOrderProps::Delete($item['ID']);
            }
        }
    }
}