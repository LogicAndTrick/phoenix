<?php

function smarty_function_label($params, $template)
{
    $defaults = array(
        'text' => $params['for']
    );
    $params = array_merge($defaults, $params);

    $htmlattr = array();

    $htmlattr['for'] = 'form_'.$params['for'];

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }

    $field = '<label';

    foreach ($htmlattr as $key => $value) {
        $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $field .= '>'.$params['text'].'</label>';

    return $field;
}
