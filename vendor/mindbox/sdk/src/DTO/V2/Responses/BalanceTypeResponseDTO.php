<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\V2\BalanceTypeDTO;

/**
 * Class BalanceTypeResponseDTO
 *
 * @package Mindbox\DTO
 * @property string $name
 */
class BalanceTypeResponseDTO extends BalanceTypeDTO
{
    /**
     * @return string
     */
    public function getName()
    {
        return $this->getField('name');
    }
}
