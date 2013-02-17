<?php

function smarty_function_textarea($params, $template)
{
    $defaults = array(
        'rows' => 10,
        'cols' => 60,
        'ignore_post' => false,
        'disabled' => false,
        'model' => null
    );
    $params = array_merge($defaults, $params);

    $htmlattr = array();

    if ($params['disabled'] === true) {
        $htmlattr['disabled'] = 'disabled';
    }

    $htmlattr['rows'] = $params['rows'];
    $htmlattr['cols'] = $params['cols'];
    $htmlattr['name'] = $params['for'];
    $htmlattr['id'] = 'form_'.$params['for'];
    $htmlattr['class'] = '';

    $model = $params['model'];
    $textvalue = '';
    if ($params['ignore_post'] === false && Post::IsPostBack() && Post::Get($params['for']) != null) {
        $textvalue = Post::Get($params['for']);
    } else if ($model != null && ($model instanceof Model || $model instanceof CustomQueryRow)) {
        $textvalue = $model->{$params['for']};
    } else if (isset($params['value']) && $params['value'] != null) {
        $textvalue = $params['value'];
    }

    $textvalue = htmlspecialchars($textvalue);

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }

    if (Validation::HasErrors($params['for']))
    {
        $htmlattr['class'] = trim($htmlattr['class'] . ' validation-error-field');
    }
    
    $field = '<textarea';

    foreach ($htmlattr as $key => $value) {
        $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $field .= '>' . $textvalue . '</textarea>';

    return $field;
}

