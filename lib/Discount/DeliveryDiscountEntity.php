<?php

namespace Mindbox\Discount;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;

class DeliveryDiscountEntity
{
    const HL_NAME = 'MindboxDelivery';

    /** @var null|\Bitrix\Main\Entity\Base */
    protected $entity = null;

    public function __construct()
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new SystemException('Module highloadblock not loader');
        }

        $this->setEntity();
    }

    protected function setEntity()
    {
        if ($this->entity === null) {
            $hlblock = HighloadBlockTable::getList(['filter' => ['=NAME' => self::HL_NAME], 'limit' => 1])->fetch();

            if ($hlblock) {
                $this->entity = HighloadBlockTable::compileEntity($hlblock);
            } else {
                throw new ObjectNotFoundException("Object DeliveryDiscountEntity not found");
            }
        }

        return $this->entity;
    }

    public function getDataClass()
    {
        return $this->entity->getDataClass();
    }

    public function query()
    {
        return  new \Bitrix\Main\Entity\Query($this->entity);
    }

    public function add(array $arFields)
    {
        return $this->getDataClass()::add($arFields);
    }

    public function update(int $id, array $arFields)
    {
        return $this->getDataClass()::update($id, $arFields);
    }

    public function getRowByFilter(array $filter)
    {
        if (empty(array_values($filter))) {
            return false;
        }

        $iterator = $this->getDataClass()::getList([
                'filter' => $filter,
                'limit' => 1,
                'select' => ['*']
        ]);

        return $iterator->fetch();
    }

    public function deleteByFilter(array $filter)
    {
        if (empty(array_values($filter))) {
            return;
        }

        $iterator = $this->getDataClass()::getList([
                'filter' => $filter,
                'select' => ['ID']
        ]);

        while ($item = $iterator->fetch()) {
            $this->getDataClass()::delete($item['ID']);
        }
    }
}
