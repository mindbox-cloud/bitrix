<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;

/**
 * Class ActionTemplateResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property string $systemName
 * @property string $name
 **/
class ActionTemplateResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $name = 'actionTemplate';

    /**
     * @return string
     */
    public function getSystemName()
    {
        return $this->getField('systemName');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getField('name');
    }
}
