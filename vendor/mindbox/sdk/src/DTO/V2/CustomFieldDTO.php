<?php


namespace Mindbox\DTO\V2;

/**
 * Trait CustomFieldDTO
 *
 * @package Mindbox\DTO\V2
 */
trait CustomFieldDTO
{
    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getCustomField($name)
    {
        $fields = $this->getCustomFields();

        return !empty($fields[$name]) ? $fields[$name] : null;
    }

    /**
     * @return mixed
     */
    public function getCustomFields()
    {
        return $this->getField('customFields');
    }
}
