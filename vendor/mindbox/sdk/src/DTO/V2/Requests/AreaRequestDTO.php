<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\V2\AreaDTO;

/**
 * Class AreaRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
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
