<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class PlaceholderResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string                        $id
 * @property ContentItemResponseCollection $content
 **/
class PlaceholderResponseDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'content' => ContentItemResponseCollection::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'placeHolder';

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getField('id');
    }

    /**
     * @return ContentItemResponseCollection
     */
    public function getContent()
    {
        return $this->getField('content');
    }
}
