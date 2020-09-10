<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\V3\CustomerIdentityDTO;

/**
 * Class CustomerIdentityResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
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
