<?php

function get_file_ci($filename) {
    if (file_exists($filename)) return $filename;
    $dir = dirname($filename);
    foreach(glob($dir . '/*') as $file) {
        if (strtolower($file) == strtolower($filename)) {
            return $file;
        }
    }
    return $filename;
}


function PhoenixTryLoadFile($filename)
{
    $c = count(Phoenix::$_layers);
    for ($i = $c - 1; $i >= 0; $i--) {
        $file = get_file_ci(rtrim(Phoenix::$_layers[$i]['dir'], '/\\') . DS . ltrim($filename,'/\\'));
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
}

function PhoenixAutoLoad($class_name)
{
    $file_name = $class_name.'.php';

    // Check for a controller request
    if (strlen($class_name) > 10 && substr($class_name, -10) == 'Controller')
    {
        // Extract the name from the controller
        $controller_name = substr($class_name, 0, -10).'.php';
        // Try and load it
        if (PhoenixTryLoadFile('Controllers/'.$controller_name)) return;
    }

    // If not a controller, probably a model or helper
    if (PhoenixTryLoadFile('Models/'.$file_name)) return;
    if (PhoenixTryLoadFile('Helpers/'.$file_name)) return;

    // If we're still here, try a framework file as a final option
    $file = get_file_ci(Phoenix::$phoenix_dir.'/Framework/'.$file_name);
    if (file_exists($file)) {
        require_once $file;
        return;
    }
}

spl_autoload_register('PhoenixAutoLoad');
