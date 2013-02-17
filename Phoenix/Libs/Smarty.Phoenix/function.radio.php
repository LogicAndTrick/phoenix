<?php

function smarty_function_radio($params, $template)
{
    $defaults = array(
        'checked' => false,
        'ignore_post' => false,
        'value' => null,
        'text' => $params['for'],
        'label_space' => ' ',
        'model' => null
    );
    $params = array_merge($defaults, $params);
    $htmlattr = array();
    $lhtmlattr = array();

    $htmlattr['type'] = 'radio';
    $htmlattr['name'] = $params['for'];
    $htmlattr['id'] = 'form_'.$params['for'].'_'.$params['value'];
    $htmlattr['value'] = $params['value'];

    $lhtmlattr['for'] = $htmlattr['id'];

    $model = $params['model'];
    if ($params['ignore_post'] === false && Post::IsPostBack() && Post::Get($params['for']) != null) {
        $params['checked'] = $htmlattr['value'] == Post::Get($params['for']);
    } else if ($model != null && ($model instanceof Model || $model instanceof CustomQueryRow)) {
        $params['checked'] = $htmlattr['value'] == $model->{$params['for']};
    }

    if ($params['checked']) {
        $htmlattr['checked'] = 'checked';
    }

    foreach ($params as $key => $value) {
        if (substr($key, 0, 11) == 'label_html_') {
            $lhtmlattr[str_ireplace('_', '-', substr($key, 11))] = $value;
        }
        else if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }
    
    $field = '<input';

    foreach ($htmlattr as $key => $value) {
        $field .= ' '.$key.'="'.$value.'"';
    }

    $field .= ' />' . $params['label_space'] . '<label';

    foreach ($lhtmlattr as $key => $value) {
        $field .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $field .= '>'.$params['text'].'</label>';

    return $field;
}
