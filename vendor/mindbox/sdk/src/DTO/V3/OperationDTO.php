<?php


namespace Mindbox\DTO\V3;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\AddProductToListRequestDTO;
use Mindbox\DTO\V3\Requests\CustomerIdentityRequestDTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\PageRequestDTO;
use Mindbox\DTO\V3\Requests\ProductListItemRequestCollection;
use Mindbox\DTO\V3\Requests\RemoveProductFromListRequestDTO;
use Mindbox\DTO\V3\Requests\SetProductCountInListRequestDTO;
use Mindbox\DTO\V3\Requests\SmsConfirmationRequestDTO;

/**
 * Class OperationDTO
 *
 * @package Mindbox\DTO\V3
 * @property CustomerRequestDTO               $customer
 * @property string                           $authentificationCode
 * @property SmsConfirmationRequestDTO        $smsConfirmation
 * @property PageRequestDTO                   $page
 * @property ProductListItemRequestCollection $productList
 * @property AddProductToListRequestDTO       $addProductToList
 * @property RemoveProductFromListRequestDTO  $removeProductFromList
 * @property SetProductCountInListRequestDTO  $setProductCountInList
 **/
class OperationDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'customer'              => CustomerRequestDTO::class,
        'smsConfirmation'       => SmsConfirmationRequestDTO::class,
        'page'                  => PageRequestDTO::class,
        'productList'           => ProductListItemRequestCollection::class,
        'addProductToList'      => AddProductToListRequestDTO::class,
        'removeProductFromList' => RemoveProductFromListRequestDTO::class,
        'setProductCountInList' => SetProductCountInListRequestDTO::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'operation';

    /**
     * @return CustomerRequestDTO
     */
    public function getCustomer()
    {
        return $this->getField('customer');
    }

    /**
     * @param array|CustomerIdentityRequestDTO $customer
     */
    public function setCustomer($customer)
    {
        $this->setField('customer', $customer);
    }

    /**
     * @return string
     */
    public function getAuthentificationCode()
    {
        return $this->getField('authentificationCode');
    }

    /**
     * @param mixed $code
     */
    public function setAuthentificationCode($code)
    {
        $this->setField('authentificationCode', $code);
    }

    /**
     * @return SmsConfirmationRequestDTO
     */
    public function getSmsConfirmation()
    {
        return $this->getField('smsConfirmation');
    }

    /**
     * @param array|SmsConfirmationRequestDTO $smsConfirmation
     */
    public function setSmsConfirmation($smsConfirmation)
    {
        $this->setField('smsConfirmation', $smsConfirmation);
    }

    /**
     * @return PageRequestDTO
     */
    public function getPage()
    {
        return $this->getField('page');
    }

    /**
     * @param array|PageRequestDTO $page
     */
    public function setPage($page)
    {
        $this->setField('page', $page);
    }

    /**
     * @return ProductListItemRequestCollection
     */
    public function getProductList()
    {
        return $this->getField('productList');
    }

    /**
     * @param array|ProductListItemRequestCollection $productList
     */
    public function setProductList($productList)
    {
        $this->setField('productList', $productList);
    }

    /**
     * @return AddProductToListRequestDTO
     */
    public function getAddProductToList()
    {
        return $this->getField('addProductToList');
    }

    /**
     * @param array|AddProductToListRequestDTO $addProductToList
     */
    public function setAddProductToList($addProductToList)
    {
        $this->setField('addProductToList', $addProductToList);
    }

    /**
     * @return RemoveProductFromListRequestDTO
     */
    public function getRemoveProductFromList()
    {
        return $this->getField('removeProductFromList');
    }

    /**
     * @param array|RemoveProductFromListRequestDTO $removeProductFromList
     */
    public function setRemoveProductFromList($removeProductFromList)
    {
        $this->setField('removeProductFromList', $removeProductFromList);
    }

    /**
     * @return SetProductCountInListRequestDTO
     */
    public function getSetProductCountInList()
    {
        return $this->getField('setProductCountInList');
    }

    /**
     * @param array|SetProductCountInListRequestDTO $setProductCountInList
     */
    public function setSetProductCountInList($setProductCountInList)
    {
        $this->setField('setProductCountInList', $setProductCountInList);
    }
}
