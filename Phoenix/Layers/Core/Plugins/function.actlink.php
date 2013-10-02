<?php

function smarty_function_actlink($params, $template)
{
    $defaults = array(
        'text' => 'Link',
        'action' => null,
        'controller' => Phoenix::$request->controller,
        'img' => null,
        'alt' => null,
        'url' => null
    );
    $params = array_merge($defaults, $params);

    $htmlattr = array();
    $imgattr = array();
    $urlparams = array();
    foreach ($params as $key => $value) {
        if ($value === null || $key == 'controller' || $key == 'action' || $key == 'text' || $key == 'img' || $key == 'alt') continue;
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        } else if (substr($key, 0, 4) == 'img_') {
            $imgattr[substr($key, 4)] = $value;
        } else {
            $urlparams[$key] = $value;
        }
    }

    if ($params['img'] !== null) {
        $imgattr['src'] = $params['img'];
        $imgattr['alt'] = $params['alt'] == null ? $params['text'] : $params['alt'];
        $img = '<img';
        foreach ($imgattr as $key => $value) {
            $img .= ' '.$key.'="'.$value.'"';
        }
        $img .= '>';
        $params['text'] = $img;
    }

    $url = $params['url'];
    if ($url === null) {
        $url = Router::CreateUrl($params['controller'], $params['action'], $urlparams);
    }

    $htmlattr['href'] = str_ireplace('%2F', '/', rawurlencode($url));

    $link = '<a';

    foreach ($htmlattr as $key => $value) {
        $link .= ' '.$key.'="'.$value.'"';
    }

    $link .= '>'.$params['text'].'</a>';

    return $link;

}

?>
