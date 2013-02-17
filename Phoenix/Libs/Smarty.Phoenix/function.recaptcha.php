<?php

function smarty_function_recaptcha($params, $template)
{
    $defaults = array(
        'public' => Validation::$recaptcha_public,
        'error' => Validation::$recaptcha_error,
        'ssl' => false
    );
    $params = array_merge($defaults, $params);
    
    return recaptcha_get_html($params['public'], $params['error'], $params['ssl']);
}
