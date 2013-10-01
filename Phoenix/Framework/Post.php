<?php

class Post
{
    public static function IsPostBack() {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    public static function IsAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
               && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Get a postback variable
     * @param string $name
     * @param object $default Value to return if the variable doesn't exist
     * @return object The post variable, or the default if it doesn't exist.
     */
    public static function Get($name, $default = null) {
        if (isset($_POST[$name]) && $_POST[$name] !== null) {
            return $_POST[$name];
        } else {
            return $default;
        }
    }

    /**
     * Bind a class from the post back.
     * @param object $class The class object or class name
     * @param string $prefix Optional prefix for the post variable names
     * @param array $vars Optional list of fields to bind. Default will bind all fields.
     * @return Model
     */
    public static function Bind($class, $prefix = '', $vars = array())
    {
        if (!$class instanceof Model) {
            $class = new $class();
        }
        foreach ($class->columns as $db => $map) {
            if (count($vars) != 0 && array_search($db, $vars) === false && array_search($map, $vars) === false) continue;
            $dbval = Post::Get($prefix.$db);
            $mapval = Post::Get($prefix.$map);
            if ($dbval !== null) {
                $class->$map = $class->MapValue($map, $dbval);
            } else if ($mapval !== null) {
                $class->$map = $class->MapValue($map, $mapval);
            }
        }
        return $class;
    }

    public static function File($name) {
        return new PostedFile($name);
    }
}

class SelectListItem
{
    public $ID;
    public $Name;

    public function __construct($id, $name)
    {
        $this->ID = $id;
        $this->Name = $name;
    }

    public static function BuildFromArray($array)
    {
        $ret = array();
        foreach ($array as $k => $v) {
            $ret[] = new SelectListItem($k, $v);
        }
        return $ret;
    }
}

class PostedFile
{
    public static $error_messages = array(
        UPLOAD_ERR_INI_SIZE   => "The file exceeded the maximum file size limit.",
        UPLOAD_ERR_FORM_SIZE  => "The file exceeded the maximum file size limit.",
        UPLOAD_ERR_PARTIAL    => "The file failed to upload, please try again.",
        UPLOAD_ERR_NO_FILE    => "You must select a file to upload.",
        UPLOAD_ERR_NO_TMP_DIR => "The file failed to upload, please try again.",
        UPLOAD_ERR_CANT_WRITE => "The file failed to upload, please try again.",
        UPLOAD_ERR_EXTENSION  => "The file failed to upload, please try again."
    );
    private $_name;
    private $_info;
    private $_validated;

    public function __construct($name)
    {
        $this->_validated = false;
        $this->_name = $name;
        if (isset($_FILES[$name]) && is_array($_FILES[$name])) {
            $this->_info = $_FILES[$name];
        } else {
            $this->_info = array('error' => 4);
        }
    }

    /**
     * Get the file's extension without the leading '.'
     */
    public function GetExtension()
    {
        $name = $this->_info['name'];
        $ext = explode('.', $name);
        return $ext[count($ext) - 1];
    }

    public function GetFileSize()
    {
        return $this->_info['size'];
    }

    public function Validate($required = true, $max_size = 0, $allowed_types = array(), $allowed_extensions = array())
    {
        $err = $this->_info['error'];
        if ($err !== 0 && ($err !== 4 || $required !== false)) {
            Validation::AddError($this->_name, PostedFile::$error_messages[$err]);
            return false;
        }
        if ($err === 4) {
            $this->_validated = true;
            return true;
        }
        $size = $this->_info['size'];
        if ($max_size > 0 && $size > $max_size) {
            Validation::AddError($this->_name, 'The file exceeded the maximum file size limit.');
            return false;
        }
        $name = $this->_info['name'];
        if (count($allowed_extensions) > 0) {
            $ext = strtolower($this->GetExtension());
            if (array_search($ext, $allowed_extensions) === false) {
                Validation::AddError($this->_name, 'This file extension is not allowed. Allowed extensions are: '. implode(', ', $allowed_extensions));
                return false;
            }
        }
        $type = $this->_info['type'];
        if (count($allowed_types) > 0) {
            if (array_search($type, $allowed_types) === false) {
                Validation::AddError($this->_name, 'This file type is not allowed.');
                return false;
            }
        }
        $this->_validated = true;
        return true;
    }

    public function Exists()
    {
        return $this->_validated === true && $this->_info['error'] != 4;
    }

    public function GetTempFile()
    {
        return $this->_info['tmp_name'];
    }

    public function Save($location, $overwrite = true)
    {
        if ($this->_validated !== true || $this->_info['error'] == 4) {
            return null;
        }
        if (is_dir($location)) {
            $location = rtrim($location, '/') . '/' . $this->_info['name'];
        }
        if (file_exists($location)) {
            if ($overwrite !== true) {
                return null;
            } else {
                unlink($location);
            }
        }
        $res = move_uploaded_file($this->_info['tmp_name'], $location);
        return $res === true ? $location : null;
    }

    public static function MakeSafeFileName($string, $allow_spaces = false, $invalid_chars = null)
    {
        if ($invalid_chars === null) {
            $invalid_chars = array('[', ']', '/', '\\', '=', '+', '<', '>', ':', ';', '"', ',', '*', '|', '?');
        }
        foreach ($invalid_chars as $char) {
            $string = str_ireplace($char, '', $string);
        }
        if (!$allow_spaces) {
            $string = str_ireplace(' ', '_', $string);
        }
        return $string;
    }
}

?>
