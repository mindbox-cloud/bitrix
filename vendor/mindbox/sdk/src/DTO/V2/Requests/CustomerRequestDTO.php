<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\V2\CustomerDTO;

/**
 * Class CustomerRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 * @property string                        $fullName
 * @property string                        $password
 * @property AreaRequestDTO                $area
 * @property SubscriptionRequestCollection $subscriptions
 * @property string                        $authenticationTicket
 * @property string                        $isAuthorized
 **/
class CustomerRequestDTO extends CustomerIdentityRequestDTO
{
    use CustomerDTO, CustomFieldRequestDTO;

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'area'          => AreaRequestDTO::class,
        'subscriptions' => SubscriptionRequestCollection::class,
        'discountCard'  => DiscountCardIdentityRequestDTO::class,
    ];

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->getField('fullName');
    }

    /**
     * @return AreaRequestDTO
     */
    public function getArea()
    {
        return $this->getField('area');
    }

    /**
     * @return SubscriptionRequestCollection
     */
    public function getSubscriptions()
    {
        return $this->getField('subscriptions');
    }

    /**
     * @return string
     */
    public function getAuthenticationTicket()
    {
        return $this->getField('authenticationTicket');
    }

    /**
     * @param mixed $authenticationTicket
     */
    public function setAuthenticationTicket($authenticationTicket)
    {
        $this->setField('authenticationTicket', $authenticationTicket);
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->setField('email', $email);
    }

    /**
     * @param mixed $phone
     */
    public function setMobilePhone($phone)
    {
        $this->setField('mobilePhone', $phone);
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->setField('lastName', $lastName);
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->setField('firstName', $firstName);
    }

    /**
     * @param mixed $middleName
     */
    public function setMiddleName($middleName)
    {
        $this->setField('middleName', $middleName);
    }

    /**
     * @param mixed $fullName
     */
    public function setFullName($fullName)
    {
        $this->setField('fullName', $fullName);
    }

    /**
     * @param mixed $birthDate
     */
    public function setBirthDate($birthDate)
    {
        $this->setField('birthDate', $birthDate);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->getField('password');
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->setField('password', $password);
    }

    /**
     * @param array|AreaRequestDTO $area
     */
    public function setArea($area)
    {
        $this->setField('area', $area);
    }

    /**
     * @param array|SubscriptionRequestCollection $subscriptions
     */
    public function setSubscriptions($subscriptions)
    {
        $this->setField('subscriptions', $subscriptions);
    }

    /**
     * @return DiscountCardIdentityRequestDTO
     */
    public function getDiscountCard()
    {
        return $this->getField('discountCard');
    }

    /**
     * @param array|DiscountCardIdentityRequestDTO $discountCard
     */
    public function setDiscountCard($discountCard)
    {
        $this->setField('discountCard', $discountCard);
    }

    /**
     * @return string
     */
    public function getIsAuthorized()
    {
        return $this->getField('isAuthorized');
    }

    /**
     * @param mixed $isAuthorized
     */
    public function setIsAuthorized($isAuthorized)
    {
        $this->setField('isAuthorized', $isAuthorized);
    }
}
