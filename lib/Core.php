<?php

namespace Mindbox;

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