<?php

function smarty_function_field($params, $template)
{
    $defaults = array(
        'type' => stristr($params['for'], 'password') !== false ? 'password' : 'text',
        'ignore_post' => false,
        'disabled' => false,
        'model' => null
    );
    $params = array_merge($defaults, $params);

    if (strtolower($params['type']) == 'openid') {
        $params['type'] = 'text';
        $params['html_class'] = trim($params['html_class'] . ' openid-input');
        if (!array_key_exists('for', $params))
        {
            $params['for'] = 'openid';
        }
    }
    $htmlattr = array();

    if ($params['disabled'] === true) {
        $htmlattr['disabled'] = 'disabled';
    }

    $htmlattr['type'] = $params['type'];
    $htmlattr['name'] = $params['for'];
    $htmlattr['id'] = 'form_'.$params['for'];

    $model = $params['model'];
    if ($params['ignore_post'] === false && Post::IsPostBack() && Post::Get($params['for']) != null) {
        $htmlattr['value'] = Post::Get($params['for']);
    } else if ($model != null && ($model instanceof Model || $model instanceof CustomQueryRow)) {
        $htmlattr['value'] = $model->{$params['for']};
    } else if (isset($params['value']) && $params['value'] != null) {
        $htmlattr['value'] = $params['value'];
    }

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }

    if (Validation::HasErrors($params['for'])) {
        $htmlattr['class'] = trim((isset($htmlattr['class']) ? $htmlattr['class'] : '') . ' validation-error-field');
    }
    
    $field = '<input';

    foreach ($htmlattr as $key => $value) {
        $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $field .= ' />';

    return $field;
}
