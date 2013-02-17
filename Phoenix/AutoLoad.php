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


function PhoenixTryLoadFile($file)
{
    $file = get_file_ci($file);
    if (file_exists($file)) {
        require_once $file;
        return true;
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
        // Check app controllers
        if (PhoenixTryLoadFile(Phoenix::$app_dir.'/Controllers/'.$controller_name)) return;
        // Check framework controllers
        if (PhoenixTryLoadFile(Phoenix::$phoenix_dir.'/Controllers/'.$controller_name)) return;
    }

    // If not a controller, probably a model
    if (PhoenixTryLoadFile(Phoenix::$app_dir.'/Models/'.$file_name)) return;

    // Otherwise, probably a framework file
    if (PhoenixTryLoadFile(Phoenix::$phoenix_dir.'/'.$file_name)) return;

    // None of the above, possibly a user defined helper?
    if (PhoenixTryLoadFile(Phoenix::$app_dir.'/Helpers/'.$file_name)) return;
    // We have a few system-level helpers as well
    if (PhoenixTryLoadFile(Phoenix::$phoenix_dir.'/Helpers/'.$file_name)) return;
}

spl_autoload_register('PhoenixAutoLoad');

?>
