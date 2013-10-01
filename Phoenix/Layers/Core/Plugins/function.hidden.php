<?php

function smarty_function_hidden($params, $template)
{
    $defaults = array(
        'model' => null
    );
    $params = array_merge($defaults, $params);

    $htmlattr = array();

    $htmlattr['type'] = 'hidden';
    $htmlattr['name'] = $params['for'];

    $model = $params['model'];
    if ($model != null && ($model instanceof Model || $model instanceof CustomQueryRow)) {
        $htmlattr['value'] = $model->{$params['for']};
    } else if (isset($params['value']) && $params['value'] != null) {
        $htmlattr['value'] = $params['value'];
    }

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }
    
    $field = '<input';

    foreach ($htmlattr as $key => $value) {
        $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $field .= ' />';

    return $field;
}

