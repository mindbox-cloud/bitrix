<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\V3\CustomFieldDTO;
use Mindbox\DTO\V3\CustomerDTO;

/**
 * Class CustomerResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property string                         $processingStatus
 * @property string                         $sex
 * @property string                         $isEmailInvalid
 * @property string                         $isEmailConfirmed
 * @property string                         $pendingEmail
 * @property string                         $isMobilePhoneInvalid
 * @property string                         $isMobilePhoneConfirmed
 * @property string                         $pendingMobilePhone
 * @property AreaResponseDTO                $area
 * @property SubscriptionResponseCollection $subscriptions
 * @property string                         $changeDateTimeUtc
 * @property string                         $status
 * @property string                         $ianaTimeZone
 * @property string                         $timeZoneSource
 **/
class CustomerResponseDTO extends CustomerIdentityResponseDTO
{
    use CustomerDTO, CustomFieldDTO;

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'area'          => AreaResponseDTO::class,
        'subscriptions' => SubscriptionResponseCollection::class,
    ];

    /**
     * @return string
     */
    public function getProcessingStatus()
    {
        return $this->getField('processingStatus');
    }

    /**
     * @return string
     */
    public function getSex()
    {
        return $this->getField('sex');
    }

    /**
     * @return AreaResponseDTO
     */
    public function getArea()
    {
        return $this->getField('area');
    }

    /**
     * @return SubscriptionResponseCollection
     */
    public function getSubscriptions()
    {
        return $this->getField('subscriptions');
    }

    /**
     * @return string
     */
    public function getIsEmailInvalid()
    {
        return $this->getField('isEmailInvalid');
    }

    /**
     * @return string
     */
    public function getIsEmailConfirmed()
    {
        return $this->getField('isEmailConfirmed');
    }

    /**
     * @return string
     */
    public function getPendingEmail()
    {
        return $this->getField('pendingEmail');
    }

    /**
     * @return string
     */
    public function getIsMobilePhoneInvalid()
    {
        return $this->getField('isMobilePhoneInvalid');
    }

    /**
     * @return string
     */
    public function getIsMobilePhoneConfirmed()
    {
        return $this->getField('isMobilePhoneConfirmed');
    }

    /**
     * @return string
     */
    public function getPendingMobilePhone()
    {
        return $this->getField('pendingMobilePhone');
    }

    /**
     * @return string
     */
    public function getChangeDateTimeUtc()
    {
        return $this->getField('changeDateTimeUtc');
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->getField('status');
    }

    /**
     * @return string
     */
    public function getIanaTimeZone()
    {
        return $this->getField('ianaTimeZone');
    }

    /**
     * @return string
     */
    public function getTimeZoneSource()
    {
        return $this->getField('timeZoneSource');
    }
}
