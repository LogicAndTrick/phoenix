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
     * @return string The full view location
     */
    static function Find($vname)
    {
        $name = $vname;
        if ($name == null || strlen($name) == 0) {
            $name = Phoenix::$request->action;
            $vname = '[null]';
        }
        $dirs = array();
        $vd = '/Views/';
        $cd = Phoenix::$request->controller_name.'/';
        $sd = 'Shared/';
        
        $count = 0;
        
        $dirs[$count++] = array('prepend' => $cd, 'search' => Phoenix::$app_dir.$vd.$cd);
        if (strstr($name, '/') !== false) {
            $dirs[$count++] = array('prepend' => '', 'search' => Phoenix::$app_dir.$vd);
        }
        $dirs[$count++] = array('prepend' => $sd, 'search' => Phoenix::$app_dir.$vd.$sd);
        
        $dirs[$count++] = array('prepend' => $cd, 'search' => Phoenix::$phoenix_dir.$vd.$cd);
        if (strstr($name, '/') !== false) {
            $dirs[$count++] = array('prepend' => '', 'search' => Phoenix::$phoenix_dir.$vd);
        }
        $dirs[$count++] = array('prepend' => $sd, 'search' => Phoenix::$phoenix_dir.$vd.$sd);
        
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
        $msg = "Unable to locate view for this request. The requested view was: $vname.\n";
        $msg .= "Directories searched:\n";
        foreach ($dirs as $dir) {
           $msg .= "&nbsp;&nbsp;* {$dir}\n";
        }
        throw new Exception($msg);
    }
}

?>
