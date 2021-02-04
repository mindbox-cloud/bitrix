<?php
/**
 * Created by @copyright QSOFT.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

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
            return Loader::includeModule('mindbox.marketing');
        } catch (LoaderException $e) {
            return false;
        }
    }
}