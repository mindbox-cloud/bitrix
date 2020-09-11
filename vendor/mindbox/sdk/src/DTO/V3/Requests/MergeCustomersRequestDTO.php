<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTO;

/**
 * Class MergeCustomersRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property CustomerIdentityRequestCollection $customersToMerge
 * @property CustomerIdentityRequestDTO        $resultingCustomer
 **/
class MergeCustomersRequestDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'customersToMerge'  => CustomerIdentityRequestCollection::class,
        'resultingCustomer' => CustomerIdentityRequestDTO::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'operation';

    /**
     * @return CustomerIdentityRequestCollection
     */
    public function getCustomersToMerge()
    {
        return $this->getField('customersToMerge');
    }

    /**
     * @param array|CustomerIdentityRequestCollection $customersToMerge
     */
    public function setCustomersToMerge($customersToMerge)
    {
        $this->setField('customersToMerge', $customersToMerge);
    }

    /**
     * @return CustomerIdentityRequestDTO
     */
    public function getResultingCustomer()
    {
        return $this->getField('resultingCustomer');
    }

    /**
     * @param array|CustomerIdentityRequestDTO $resultingCustomer
     */
    public function setResultingCustomer($resultingCustomer)
    {
        $this->setField('resultingCustomer', $resultingCustomer);
    }
}
