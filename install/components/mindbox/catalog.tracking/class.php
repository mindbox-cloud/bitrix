<?php
/**
 * Created by @copyright QSOFT.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\DTO\DTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Options;
use Mindbox\Ajax;
use Mindbox\QueueTable;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class CatalogTracking extends CBitrixComponent
{
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        if (!$this->loadModule()) {
            return;
        }
    }

    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }

    private function loadModule()
    {
        try {
            if (!Loader::includeModule('mindbox.marketing')) {
                return false;
            }
        } catch (LoaderException $e) {
            return false;
        }

        return true;
    }
}