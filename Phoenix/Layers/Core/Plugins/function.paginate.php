<?php

/**
 * @param  $params
 * @param Smarty $template
 * @param  $template
 * @return string
 */
function smarty_function_paginate($params, $template)
{
    $defaults = array(
        'model' => null,
        'current_page' => null,
        'total_pages' => null,
        'link_num' => 10,
        'jumps' => true,
        'format_spacer' => " \n",
        'format_params' => '{page}',
        'format_url' => null,
        'format_jump_first' => '&lt;&lt;',
        'format_jump_last' => '&gt;&gt;',
        'format_current' => '<strong>[{page}]</strong>',
        'format_number' => '<a href="{url}">{page}</a>',
        'wrap_element' => 'div',
        'wrap_class' => 'pagination',
        'list' => false,
        'list_class' => null,
        'list_active_class' => 'active'
    );
    $params = array_merge($defaults, $params);
    if ($params['model'] != null) {
        $params['current_page'] = $params['model']->CurrentPage;
        $params['total_pages'] = $params['model']->NumPages;
    }
    if ($params['format_url'] == null) {
        if (!is_array($params['format_params'])) $params['format_params'] = array($params['format_params']);
        $params['format_url'] = Router::CreateUrl(Phoenix::$request->controller, Phoenix::$request->action, $params['format_params']);
    }

    // These vars can be defined in the viewdata
    if ($params['current_page'] === null) $params['current_page'] = $template->getVariable('current_page')->value;
    if ($params['total_pages'] === null) $params['total_pages'] = $template->getVariable('total_pages')->value;

    $cur = $params['current_page'];
    $fst = 1;
    $lst = $params['total_pages'];
    $spc = $params['format_spacer'];
    $num = $params['link_num'] / 2;
    $jmp = $params['jumps'];
    $url = $params['format_url'];
    $wrp = $params['wrap_element'];
    $wrc = $params['wrap_class'];
    $lus = $params['list'];
    $lcl = $params['list_class'];
    $lac = $params['list_active_class'];

    $lower = max($fst, $cur - $num);
    $upper = min($lst, $cur + $num);
    $jump_first = true;
    $jump_last = true;
    $leftover = ($num * 2) - ($upper - $lower);
    if ($lower == $fst) {
        $upper = min($lst, $upper + $leftover);
        $jump_first = false;
    }
    if ($upper == $lst) {
        $lower = max($fst, $lower - $leftover);
        $jump_last = false;
    }

    $ret = '';
    if ($lus) {
        $ret .= '<ul'.($lcl==null?'':' class="'.$lcl.'"').'>';
    }
    if ($jmp && $jump_first) {
        if ($lus) $ret .= '<li>';
        $ret .= '<a href="' . str_ireplace('{page}', $fst, $url) . '">' . $params['format_jump_first'] . '</a>' . $spc;
        if ($lus) $ret .= '</li>';
    }
    for ($i = $lower; $i <= $upper; $i++) {
        if ($lus) $ret .= '<li'.($i==$cur&&$lac!=null?' class="'.$lac.'"':'').'>';
        $format = $params['format_number'];
        if ($i == $cur) $format = $params['format_current'];
        $text = str_ireplace('{url}', $url, $format);
        $text = str_ireplace('{page}', $i, $text);
        if ($i == $lower && $jump_first) $ret .= '<span>...</span>' . $spc;
        $ret .= $text . $spc;
        if ($i == $upper && $jump_last) $ret .= '<span>...</span>' . $spc;
        if ($lus) $ret .= '</li>';
    }
    if ($jmp && $jump_last) {
        if ($lus) $ret .= '<li>';
        $ret .= '<a href="' . str_ireplace('{page}', $lst, $url) . '">' . $params['format_jump_last'] . '</a>';
        if ($lus) $ret .= '</li>';
    }
    if ($lus) {
        $ret .= '</ul>';
    }
    $ret = trim($ret);
    if ($wrp != null) {
        $cls = $wrc == null ? '' : ' class="' . $wrc . '"';
        $ret = '<' . $wrp . $cls . '>' . $ret . '</' . $wrp . '>';
    }
    return trim($ret);
}

?>
