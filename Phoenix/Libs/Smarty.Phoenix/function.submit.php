<?php

function smarty_function_submit($params, $template)
{
    $defaults = array(
        'text' => isset($params['value']) ? $params['value'] : 'Submit',
        'html_type' => 'submit',
        'disabled' => false,
        'html_value' => null,
        'button' => false
    );
    $params = array_merge($defaults, $params);

    if ($params['html_value'] == null) $params['html_value'] = $params['text'];

    $htmlattr = array();

    if ($params['disabled'] === true) {
        $htmlattr['disabled'] = 'disabled';
    }

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }

    $field = '';

    if ($params['button'])
    {
        $field .= '<button';

        foreach ($htmlattr as $key => $value) {
            if ($key == 'value') continue;
            $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
        }

        $field .= '>' . $htmlattr['value'] . '</button>';
    }
    else
    {
        $field .= '<input';

        foreach ($htmlattr as $key => $value) {
            $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
        }

        $field .= ' />';
    }

    return $field;
}
