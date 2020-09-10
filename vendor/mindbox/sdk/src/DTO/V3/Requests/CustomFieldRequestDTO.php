<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\V3\CustomFieldDTO;

/**
 * Trait CustomFieldRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 */
trait CustomFieldRequestDTO
{
    use CustomFieldDTO;

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function setCustomField($name, $value)
    {
        $fields        = is_array($this->getCustomFields()) ? $this->getCustomFields() : [];
        $fields[$name] = $value;
        $this->setCustomFields($fields);
    }

    /**
     * @param array $fields
     */
    public function setCustomFields($fields)
    {
        $this->setField('customFields', $fields);
    }
}
