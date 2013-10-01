<?php

function smarty_function_placeholder($params, $template)
{
    if (!array_key_exists('name', $params) || !array_key_exists($params['name'], Templating::$placeholder_data)) {
        return '';
    }
    return Templating::$placeholder_data[$params['name']];
}
