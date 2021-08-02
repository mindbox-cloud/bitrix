<?php


namespace Mindbox\Installer;

use Bitrix\Main\Loader;
use Mindbox\Helper;


class OrderPropertiesInstaller
{
    const PROPERTIES_GROUP_NAME = 'Mindbox';
    const PROPERTY_BONUS = 'MINDBOX_BONUS';
    const PROPERTY_PROMO_CODE = 'MINDBOX_PROMO_CODE';

    public function __construct()
    {
        Loader::IncludeModule('sale');
    }

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


    public function install()
    {
        $getPersonTypeList = $this->getSitePersonType();
        $getPropertiesGroup = $this->getPropertiesGroupList();


        // обрабатываем группы свойств, если группы нет - добавим
        foreach ($getPersonTypeList as $personItem) {
            if (!array_key_exists($personItem['ID'], $getPropertiesGroup)) {
                $addGroup = $this->addPropertyGroup($personItem['ID']);

                if ((int)$addGroup > 0) {
                    $getPropertiesGroup[$personItem['ID']] = \CSaleOrderPropsGroup::GetByID($addGroup);
                }
            }
        }


    }

    public function dev()
    {
        $test = $this->install();
        var_dump($test);
    }


}