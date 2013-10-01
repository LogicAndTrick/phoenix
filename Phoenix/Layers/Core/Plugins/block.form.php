<?php

function smarty_block_form($params, $content, $template, &$repeat)
{
    if ($repeat) // First call, opening tag, $content = NULL
    {
        $con = Router::$request_controller;
        $act = Router::$request_action;
        $pms = Router::$request_params;
        $mtd = 'post';
        if (array_key_exists('controller', $params)) {
            $con = $params['controller'];
            $act = Router::$default_action;
            $pms = array();
        }
        if (array_key_exists('action', $params)) {
            $act = $params['action'];
            $pms = array();
        }
        if (array_key_exists('method', $params)) {
            $mtd = $params['method'];
        }

        $upl = false;
        if (array_key_exists('upload', $params)) {
            $upl = $params['upload'] === true;
        }

        $htmlattr = array();
        $urlparams = array();
        foreach ($params as $key => $value) {
            if ($key == 'controller' || $key == 'action' || $key == 'method' || $key == 'upload') continue;
            if (substr($key, 0, 5) == 'html_') {
                $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
            } else {
                $urlparams[$key] = $value;
            }
        }

        if (count($urlparams) > 0) {
            $pms = $urlparams;
        }

        $url = Phoenix::$base_url.$con.'/'.$act;
        foreach ($pms as $key => $value) {
            $url .= '/'.$value;
        }

        $url = str_ireplace('%2F', '/', rawurlencode($url));

        $ret = sprintf('<form action="%s" method="%s"', $url, $mtd);
        if ($upl) {
            $ret .= ' enctype="multipart/form-data"';
        }
        foreach ($htmlattr as $key => $value) {
            $ret .= ' '.$key.'="'.$value.'"';
        }
        $ret .= '>';
    }
    else // Second call, closing tag, $content = parsed output of block content
    {
        $ret = $content . '</form>';
    }
    return $ret;
}
