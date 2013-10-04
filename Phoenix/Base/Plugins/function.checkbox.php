<?php

function smarty_function_checkbox($params, $template)
{
    $defaults = array(
        'checked' => false,
        'disabled' => false,
        'model' => null
    );
    $params = array_merge($defaults, $params);
    
    $htmlattr = array();

    if ($params['disabled'] === true) {
        $htmlattr['disabled'] = 'disabled';
    }

    $htmlattr['type'] = 'checkbox';
    $htmlattr['name'] = $params['for'];
    $htmlattr['id'] = 'form_'.$params['for'];

    $checked = false;
    $model = $params['model'];
    if ($model != null && ($model instanceof Model || $model instanceof CustomQueryRow)) {
        $checked = $model->{$params['for']};
    } else if (isset($params['checked']) && $params['checked'] != null) {
        $checked = $params['checked'];
    } else if (Post::IsPostBack() && Post::Get($params['for']) != null) {
        $checked = Post::Get($params['for']);
    }

    $checked = ($checked === true || $checked == 'true' || $checked == 'on' || (is_numeric($checked) && $checked > 0));
    if ($checked) {
        $htmlattr['checked'] = 'checked';
    }

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }
    $htmlattr['value'] = 1;

    $field = '<input name="'.$htmlattr['name'].'" value="0" type="hidden" />';
    $field .= '<input';

    foreach ($htmlattr as $key => $value) {
        $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $field .= ' />';

    return $field;
}
