<?php

function smarty_function_resolve($params, $template)
{
    $defaults = array(
        'path' => ''
    );
    $params = array_merge($defaults, $params);

    

    return rtrim(Phoenix::$base_url, '/') . '/' . ltrim($params['path'], '/');
}

?>
