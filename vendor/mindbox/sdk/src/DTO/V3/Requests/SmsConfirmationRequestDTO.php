<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTO;

/**
 * Class SmsConfirmationRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property string $code
 **/
class SmsConfirmationRequestDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'smsConfirmation';

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->getField('code');
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->setField('code', $code);
    }
}
