<?php

function smarty_function_validationsummary($params, $template)
{
    $htmlattr = array();

    if (!Validation::HasErrors())
    {
        return null;
    }

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }

    $summary = '<p class="validation-summary"';

    foreach ($htmlattr as $key => $value) {
        $summary .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $summary .= ">\nErrors were found in the data. See below for details:\n</p>\n<ul class=\"validation-summary\"";

    foreach ($htmlattr as $key => $value) {
        $summary .= ' '.$key.'="'.htmlspecialchars($value).'"';
    }

    $summary .= ">\n";

    foreach (Validation::GetAllErrors() as $name => $errors) {
        foreach ($errors as $e) {
            $error = htmlspecialchars($e);
            $summary .= "<li>$error</li>\n";
        }
    }

    $summary .= "</ul>";

    return $summary;
}

