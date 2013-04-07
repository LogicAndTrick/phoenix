<?php

class Validation
{
    public static $register = array();
    public static $errors = array();
    public static $recaptcha_public = null;
    public static $recaptcha_private = null;
    public static $recaptcha_error = null;

    static function ValidateRecaptcha() {
        if ($_POST["recaptcha_response_field"] !== null) {
            $resp = recaptcha_check_answer (Validation::$recaptcha_private, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
            if ($resp->is_valid) {
                return true;
            } else {
                Validation::$recaptcha_error = $resp->error;
                Validation::AddError('Captcha', 'Incorrect code! Please try again.');
                return false;
            }
        }
        return false;
    }

    static function AddError($name, $error) {
        Validation::$errors[$name][] = $error;
    }

    static function AddErrors($errors, $prefix = '') {
        foreach ($errors as $name => $errs) {
            foreach ($errs as $err) {
                Validation::$errors[$prefix.$name][] = $err;
            }
        }
    }

    static function HasErrors($name = null) {
        if ($name == null) {
            foreach (Validation::$errors as $name => $errors) {
                if (count($errors) > 0) {
                    return true;
                }
            }
            return false;
        }
        return (array_key_exists($name, Validation::$errors) && count(Validation::$errors[$name]) > 0);
    }

    static function GetFirstError($name = null) {
        if ($name == null) {
            foreach (Validation::$errors as $g) {
                foreach ($g as $err) return $err;
            }
        }
        else if (array_key_exists($name, Validation::$errors) && count(Validation::$errors[$name]) > 0) {
            return Validation::$errors[$name][0];
        }
        return null;
    }

    static function GetAllErrors($name = null) {
        if ($name == null) {
            return Validation::$errors;
        }
        if (array_key_exists($name, Validation::$errors)) {
            return Validation::$errors[$name];
        }
        return array();
    }

    static function Validate($model, $prefix = '', $fields = null) {
        Validation::AddErrors($model->GetErrors($fields), $prefix);
        return !Validation::HasErrors();
    }

    static function RegisterValidator($refname, $classname) {
        Validation::$register[$refname] = $classname;
    }

    static function GetValidator($refname, $params) {
        $classname = Validation::$register[$refname];
        if (!class_exists($classname)) {
            return null;
        }
        $obj = new $classname();
        foreach ($params as $name => $value) {
            $obj->$name = $value;
        }
        return $obj;
    }
}

Validation::RegisterValidator('required', 'RequiredValidation');
Validation::RegisterValidator('stringrange', 'StringRangeValidation');
Validation::RegisterValidator('range', 'RangeValidation');
Validation::RegisterValidator('regex', 'RegexValidation');
Validation::RegisterValidator('datatype', 'DataTypeValidation');
Validation::RegisterValidator('urlsafe', 'UrlSafeValidation');
Validation::RegisterValidator('oneof', 'OneOfValidation');
Validation::RegisterValidator('email', 'EmailValidation');

Validation::RegisterValidator('db-unique', 'DbUniqueValidation');

class ValidationMethod
{
    public $message;
    
    function Validate($model, $field, $value) {
        // Virtual
    }

    function GetMessage() {
        // Virtual
    }
}

class RequiredValidation extends ValidationMethod
{
    function Validate($model, $field, $value) {
        return $value !== null && $value !== '';
    }

    function GetMessage() {
        if ($this->message != null) {
            return $this->message;
        }
        return 'This field is required.';
    }
}

class StringRangeValidation extends ValidationMethod
{
    public $min;
    public $max;

    function  __construct() {
        $this->min = 0;
        $this->max = 0;
    }

    function Validate($model, $field, $value) {
        if ($value == null) {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        $len = strlen($value);
        if ($this->max > 0 && $len > $this->max) {
            return false;
        }
        return !($len < $this->min);
    }

    function GetMessage() {
        if ($this->message != null) {
            return $this->message;
        }
        $msg = 'This field must be ';
        if ($this->max > 0) {
            $msg .= "between {$this->min} and {$this->max}";
        } else {
            $msg .= "greater than {$this->min}";
        }
        $msg .= ' characters long.';
        return $msg;
    }
}

class RangeValidation extends ValidationMethod
{
    public $min;
    public $max;

    function  __construct() {
        $this->min = 0;
        $this->max = 0;
    }

    function Validate($model, $field, $value) {
        if ($value == null) {
            return true;
        }
        if (!is_numeric($value)) {
            return false;
        }
        if ($this->max > 0 && $value > $this->max) {
            return false;
        }
        return !($value < $this->min);
    }

    function GetMessage() {
        if ($this->message != null) {
            return $this->message;
        }
        $msg = 'This field must be ';
        if ($this->max > 0) {
            $msg .= "between {$this->min} and {$this->max}";
        } else {
            $msg .= "greater than {$this->min}";
        }
        $msg .= '.';
        return $msg;
    }
}

class RegexValidation extends ValidationMethod
{
    public $pattern;

    function  __construct() {
        $this->pattern = '';
    }

    function Validate($model, $field, $value) {
        if ($value == null) {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        return preg_match($this->pattern, $value) > 0;
    }

    function GetMessage() {
        if ($this->message != null) {
            return $this->message;
        }
        return 'This field is not valid.';
    }
}

class UrlSafeValidation extends RegexValidation
{
    function __construct() {
        $this->pattern = '/^[a-z0-9_-]*$/i';
        $this->message = 'This field must be URL-safe (alphanumeric, hyphens, and underscores only)';
    }
}

class OneOfValidation extends ValidationMethod
{
    public $values;

    function  __construct() {
        $this->values = array();
    }

    function Validate($model, $field, $value) {
        if ($value === null || $value == '') return true;
        return array_search($value, $this->values) !== false;
    }

    function GetMessage() {
        if ($this->message != null) {
            return $this->message;
        }
        return 'This field must be one of the following: '.implode(', ', $this->values);
    }
}

class EmailValidation extends ValidationMethod
{
    function Validate($model, $field, $value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    function GetMessage() {
        if ($this->message != null) {
            return $this->message;
        }
        return 'This field must be a valid email address.';
    }
}

class DataTypeValidation extends ValidationMethod
{
    public $type;
    public $pattern;

    function  __construct() {
        $this->type = 'none';
        $this->pattern = '';
    }

    function Validate($model, $field, $value) {
        // If the field is null, it's valid (this is
        // the responsibility of the required validation method)
        if ($value == null) {
            return true;
        }
        switch ($this->type) {
            case 'int':
            case 'timestamp':
                return is_numeric($value) && (int)$value == $value;
            case 'number':
            case 'decimal':
            case 'float':
            case 'double':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'date':
            case 'datetime':
                return ($value instanceof DateTime)
                        || ($this->pattern != '' && DateTime::createFromFormat($this->pattern, $value) !== false)
                        || DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false
                        || DateTime::createFromFormat('Y-m-d', $value) !== false;
            case 'time':
                return ($value instanceof DateTime)
                        || ($this->pattern != '' && DateTime::createFromFormat($this->pattern, $value) !== false)
                        || DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false
                        || DateTime::createFromFormat('H:i:s', $value) !== false;
            case 'bool':
            case 'bit':
                return is_bool($value);
        }
        return true;
    }

    function GetMessage() {
        if ($this->message != null) {
            return $this->message;
        }
        $msg = 'This field must be a valid ';
        switch ($this->type) {
            case 'int':
                $msg .= 'integer';
                break;
            case 'timestamp':
                $msg .= 'unix timestamp';
                break;
            case 'number':
            case 'decimal':
            case 'float':
            case 'double':
                $msg .= 'number';
                break;
            case 'string':
                $msg .= 'string';
                break;
            case 'date':
                $msg .= 'date';
                break;
            case 'datetime':
                $msg .= 'date/time';
                break;
            case 'time':
                $msg .= 'time';
                break;
            case 'bool':
            case 'bit':
                $msg .= 'boolean value (1 or 0)';
                break;
        }
        $msg .= '.';
        return $msg;
    }
}

class DbUniqueValidation extends ValidationMethod
{
    public $fields;
    public $case_sensitive;

    function  __construct() {
        $this->fields = array();
        $this->case_sensitive = true;
    }

    function Validate($model, $field, $value) {
        if ($value == null) {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        $params = array();
        $sql = "SELECT count(*) AS Count FROM {$model->table} WHERE ";

        // If primary key is not null, exclude itself from the result set
        if ($model->{$model->primaryKey} !== null)
        {
            $params[':pkparam'] = $model->{$model->primaryKey};
            $sql .= "`" . $model->GetDBName($model->primaryKey)."` != :pkparam AND ";
        }

        // Set the field parameter value to check. Case-insensitive conversion converts to upper-case
        if ($this->case_sensitive === false)
        {
            $params[':fieldparam'] = strtoupper($value);
            $sql .= 'UPPER(`' . $model->GetDBName($field) . '`) = :fieldparam';
        }
        else
        {
            $params[':fieldparam'] = $value;
            $sql .= '`' . $model->GetDBName($field) . '` = :fieldparam';
        }

        // Assign all the additional fields specified
        $pcount = 1;
        $flds = is_array($this->fields) ? $this->fields : array($this->fields);
        foreach ($flds as $fname)
        {
            $sql .= " AND `" . $model->GetDBName($fname) . "` = :param{$pcount}";
            $params[":param{$pcount}"] = $model->$fname;
            $pcount++;
        }
        $result = CustomQuery::Query($sql, $params);
        $count = $result[0]->Count;
        return $count == 0;
    }

    function GetMessage() {
        if ($this->message != null) {
            return $this->message;
        }
        $flds = is_array($this->fields) ? $this->fields : array($this->fields);
        return 'This field must be unique in the database'
            . (count($flds) > 0 ? ' for fields: '.implode(', ', $flds) : '')
            . '.';
    }
}

?>
