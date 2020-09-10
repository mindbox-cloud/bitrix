<?php

namespace Mindbox\Responses;

use Mindbox\DTO\V3\Responses\SmsConfirmationResponseDTO;
use Mindbox\MindboxResponse;

/**
 * Класс, расширяющий стандартный класс ответа от Mindbox и используемый в стандартном запросе на подтверждение
 * телефона.
 * Class MindboxSmsConfirmationResponse
 *
 * @package Mindbox
 */
class MindboxSmsConfirmationResponse extends MindboxResponse
{
    /**
     * Возвращает объект результата подтверждения телефона, если такой присутствует в ответе.
     *
     * @return SmsConfirmationResponseDTO|null
     */
    public function getSmsConfirmation()
    {
        return $this->getResult()->getSmsConfirmation();
    }
}
