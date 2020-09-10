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

class CatalogTracking extends CBitrixComponent implements Controllerable
{
    private $actions = [
        'viewProduct',
        'viewCategory'
    ];

    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        if (!$this->loadModule()) {
            return;
        }

        $this->mindbox = Options::getConfig();
    }

    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }

    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }

    public function viewCategoryAction($id)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_CT_BAD_MODULE_SETTING'));
        }
        $ids = [
            Options::getModuleOption('EXTERNAL_SYSTEM') => $id
        ];

        $dto = new DTO([
            'viewProductCategory' => [
                'productCategory' => [
                    'ids' => $ids
                ]
            ]
        ]);

        try {
            $this->mindbox->getClientV3()
                ->prepareRequest('POST', Options::getOperationName('viewCategory'), $dto, '', [], false)
                ->sendRequest();
        } catch (MindboxClientException $e) {
            $lastResponse = $this->mindbox->getClientV3()->getLastResponse();

            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    public function viewProductAction($id)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_CT_BAD_MODULE_SETTING'));
        }
        $data = [
            'viewProduct' => [
                'product' => []
            ]
        ];

        $ids = [
            Options::getModuleOption('EXTERNAL_SYSTEM') => $id
        ];


        $data['viewProduct']['product'] = ['ids' => $ids];


        $dto = new DTO($data);

        try {
            $this->mindbox->getClientV3()
                ->prepareRequest('POST', Options::getOperationName('viewProduct'), $dto, '', [], false)
                ->sendRequest();
        } catch (MindboxClientException $e) {
            $lastResponse = $this->mindbox->getClientV3()->getLastResponse();

            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }


    private function loadModule()
    {
        try {
            if (!Loader::includeModule('qsoftm.mindbox')) {
                return false;
            }
        } catch (LoaderException $e) {
            return false;
        }

        return true;
    }
}