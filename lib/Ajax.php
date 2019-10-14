<?php
/**
 * Created by @copyright QSOFT.
 */

namespace Mindbox;

class Ajax
{

    public static function configureActions($actions)
    {
        $actionConfig = [];
        foreach ($actions as $action) {
            $actionConfig[$action] = ['prefilters' => []];
        }
        return $actionConfig;
    }

    public static function errorResponse($error)
    {
        $response['type'] = 'error';
        if(is_subclass_of($error, \Exception::class)) {
            $response['message'] = $error->getMessage();
        } else {
            $response['message'] = $error;
        }

        return $response;
    }

    public static function loadParams($componentName)
    {
        $params = [];

        if(isset($_SESSION[$componentName]) && is_array($_SESSION[$componentName])) {
            $params = $_SESSION[$componentName];
        }

        return $params;
    }

}