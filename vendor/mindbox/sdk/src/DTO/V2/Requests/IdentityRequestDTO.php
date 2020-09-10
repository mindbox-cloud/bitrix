<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\V2\IdentityDTO;

/**
 * Trait IdentityRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 */
trait IdentityRequestDTO
{
    use IdentityDTO;

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setId($name, $value)
    {
        $ids        = is_array($this->getIds()) ? $this->getIds() : [];
        $ids[$name] = $value;
        $this->setIds($ids);
    }

    /**
     * @param array $ids
     */
    public function setIds($ids)
    {
        $this->setField('ids', $ids);
    }
}
