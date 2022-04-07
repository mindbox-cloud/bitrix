<?php

namespace Mindbox;

use Bitrix\Main\Config\Option;

trait Core
{
    /**
     * @return Mindbox
     */
    public static function mindbox()
    {
        return Options::getConfig();
    }
}