<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\V2\CustomerIdentityDTO;

/**
 * Class CustomerIdentityResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string $processingStatus
 */
class CustomerIdentityResponseDTO extends CustomerIdentityDTO
{
    /**
     * @return string
     */
    public function getProcessingStatus()
    {
        return $this->getField('processingStatus');
    }
}
