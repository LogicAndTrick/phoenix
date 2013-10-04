<?php

function smarty_function_action($params, $template)
{
    $defaults = array(
        'action' => null,
        'controller' => Phoenix::$request->controller
    );
    $params = array_merge($defaults, $params);

    $urlparams = array();
    foreach ($params as $key => $value) {
        if ($value === null || $key == 'controller' || $key == 'action') continue;
        $urlparams[$key] = $value;
    }

    $url = Router::CreateUrl($params['controller'], $params['action'], $urlparams);

    return str_ireplace('%2F', '/', rawurlencode($url));

}
