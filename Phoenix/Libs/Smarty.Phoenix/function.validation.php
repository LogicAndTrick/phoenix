<?php

function smarty_function_validation($params, $template)
{
    $defaults = array(
        'html_class' => 'validation-error',
        'text' => Validation::GetFirstError($params['for'])
    );
    $params = array_merge($defaults, $params);

    $htmlattr = array();

    $for = $params['for'];
    
    if (!Validation::HasErrors($for))
    {
        return null;
    }

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }

    $field = '<span';

    foreach ($htmlattr as $key => $value) {
        $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $field .= '>'.$params['text'].'</span>';

    return $field;
}
