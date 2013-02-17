<?php

function smarty_function_submit($params, $template)
{
    $defaults = array(
        'html_value' => isset($params['value']) ? $params['value'] : 'Submit',
        'html_type' => 'submit',
        'disabled' => false
    );
    $params = array_merge($defaults, $params);

    $htmlattr = array();

    if ($params['disabled'] === true) {
        $htmlattr['disabled'] = 'disabled';
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
