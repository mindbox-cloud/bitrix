<?php


namespace Mindbox\DTO\V3;

/**
 * Trait CustomFieldDTO
 *
 * @package Mindbox\DTO\V3
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
