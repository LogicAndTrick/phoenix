<?php

class Views
{
    /**
     * Searches for a view in the following order:
     * <ul>
     * <li>(App)/Views/[Controller]/[Name].tpl</li>
     * <li>(App)/Views/Shared/[Name].tpl</li>
     * <li>(Phoenix)/Views/[Controller]/[Name].tpl</li>
     * <li>(Phoenix)/Views/Shared/[Name].tpl</li>
     * </ul>
     * The name can also contain at most one forward slash (/), to
     * define a specific folder to look in. For example, a name like
     * 'Home/Index' would not search in the shared folders. For the same
     * request, a name such as 'Forums/Index' will force a different
     * controller's directory to be searched. Will throw an exception if the
     * view cannot be found.
     * @param string $name The name of the view to find
     * @throws Exception
     * @return string The full view location
     */
    static function Find($name)
    {
        if ($name == null || strlen($name) == 0) {
            $name = Phoenix::$request->action;
        }
        $dirs = array();
        $vd = '/Views/';
        $cd = Phoenix::$request->controller.'/';
        $sd = 'Shared/';
        
        $count = 0;

        $c = count(Phoenix::$_layers);
        for ($i = $c - 1; $i >= 0; $i--) {
            // Search the view subdirectory first
            $dirs[$count++] = array('prepend' => $cd, 'search' => Phoenix::$_layers[$i]['dir'].$vd.$cd, 'err' => '['.Phoenix::$_layers[$i]['name'].']'.$vd.$cd);

            // If there's a / in the view name, search the views parent directory
            if (strstr($name, '/') !== false) $dirs[$count++] = array('prepend' => '', 'search' => Phoenix::$_layers[$i]['dir'].$vd, 'err' => '['.Phoenix::$_layers[$i]['name'].']'.$vd);

            // Search the shared subdirectory last
            $dirs[$count++] = array('prepend' => $sd, 'search' => Phoenix::$_layers[$i]['dir'].$vd.$sd, 'err' => '['.Phoenix::$_layers[$i]['name'].']'.$vd.$sd);
        }

        if (substr($name, 0, -4) != '.tpl') {
            $name .= '.tpl';
        }
        for ($i = 0; $i < count($dirs); $i++) {
            $dir = $dirs[$i]['search'];
            $prep = $dirs[$i]['prepend'];
            $file = $dir.$name;
            if (file_exists($file)) {
                return $prep.$name;
            }
        }

        $msg = "Unable to locate view for this request. The requested view was: $name.\n";
        $msg .= "Directories searched:\n";
        foreach ($dirs as $dir) {
           $msg .= "&nbsp;&nbsp;* {$dir['err']}\n";
        }
        throw new Exception($msg);
    }
}

?>
