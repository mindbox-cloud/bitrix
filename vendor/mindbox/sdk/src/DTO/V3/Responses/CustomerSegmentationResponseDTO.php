<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;

/**
 * Class CustomerSegmentationResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property SegmentationResponseDTO $segmentation
 * @property SegmentResponseDTO      $segment
 **/
class CustomerSegmentationResponseDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'segmentation' => SegmentationResponseDTO::class,
        'segment'      => SegmentResponseDTO::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'customerSegmentation';

    /**
     * @return SegmentationResponseDTO
     */
    public function getSegmentation()
    {
        return $this->getField('segmentation');
    }

    /**
     * @return SegmentResponseDTO
     */
    public function getSegment()
    {
        return $this->getField('segment');
    }
}
