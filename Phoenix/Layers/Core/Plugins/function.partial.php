<?php

function smarty_function_partial($params, $template)
{
    $defaults = array(
        'model' => null
    );
    $params = array_merge($defaults, $params);

    $view = Templating::Create();
    $view->assign(Phoenix::$request->controller->viewData);
    foreach ($params as $name => $value) {
        if ($name == 'view') {
            continue;
        }
        $view->assign($name, $value);
    }

    $params['view'] = Views::Find($params['view']);

    return $view->fetch($params['view']);
}
