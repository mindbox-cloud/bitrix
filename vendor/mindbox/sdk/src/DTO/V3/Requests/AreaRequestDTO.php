<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\V3\AreaDTO;

/**
 * Class AreaRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 */
class AreaRequestDTO extends AreaDTO
{
    use IdentityRequestDTO;

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->setField('name', $name);
    }
}
