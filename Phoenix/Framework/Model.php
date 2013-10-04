<?php

class Model
{
    /**
     * @var string The name of the database table
     */
    public $table = '';

    /**
     * @var array The columns of the database table.
     * Can be aliased with 'Actual' => 'Alias'
     */
    public $columns = array();

    /**
     * @var array A list of aliases for dynamic aliasing
     */
    public $alias = array();

    /**
     * @var string The name of the primary key column in the table.
     * Use the aliased column name.
     */
    public $primaryKey = 'ID';

    /**
     * @var array Many-to-One relationships for this model
     * Format: 'ModelName' => array('ThisID' => 'ThatID')
     * Use the aliased column names.
     */
    public $one = array();

    /**
     * @var array One-to-Many relationships for this model
     * Format: 'ModelName' => array('ThisID' => 'ThatID')
     * Can also use ':Order', ':OrderDesc' to change the order of results.
     * Use the aliased column names.
     */
    public $many = array();

    /**
     * @var array Validators for this model
     * Format: 'Column' => array('validator' => array(**validator settings**))
     * See the validator documentation for available settings.
     * Use the aliased column names.
     */
    public $validation = array();

    /**
     * @var array Registered data mappers for this model
     * Format: 'Column' => 'mapper'
     * Each column can only have a maximum of one data mapper.
     * Use the aliased column names.
     */
    public $mappings = array();

    /**
     * @var array The DB name of the primary key
     */
    private $_dbpk;

    /**
     * @var array The actual data values for this model
     */
    private $_values;

    /**
     * @var bool True if this record is saved to the database, false otherwise
     */
    private $_new;

    /**
     * @var array The resolved validator classes
     */
    private $_validators;

    /**
     * @var array A cache for 'Get' and 'Find' lazy-loading methods
     */
    private $_getcache;

    /**
     * @param mixed $id
     */
    public function  __construct($id = false)
    {
        $this->_values = array();
        $this->_getcache['one'] = array();
        $this->_getcache['many'] = array();

        $temp = array();
        foreach ($this->columns as $db => $map) {
            if (!is_string($db)) $db = $map;
            $temp[$db] = $map;
        }

        $this->columns = $temp;
        foreach ($this->columns as $db => $map) {
            $this->_values[$map] = new ModelValue($db, $map);
            if ($map == $this->primaryKey) {
                $this->_dbpk = $db;
            }
        }

        foreach ($this->alias as $name => $alias) {
            if (array_search($alias, $this->columns)) {
                unset($this->alias[$name]);
            }
        }

        // Constructor
        if ($id !== false) {
            $this->_new = false;
            $val = Database::One($this->table, array("`{$this->_dbpk}` = :id"), array(':id' => $id), null, $this->GetTableFields());
            $this->SetValues($val);
        } else {
            $this->_new = true;
        }

        // Validators
        $this->_validators = array();
        foreach ($this->validation as $field => $params) {
            $this->_validators[$field] = array();
            foreach ($params as $val_name => $values) {
                if (is_numeric($val_name) || is_string($values)) {
                    $val_name = $values;
                    $values = array();
                }
                $this->_validators[$field][] = Validation::GetValidator($val_name, $values);
            }
        }
    }

    private function SetValues($val)
    {
        foreach ($this->columns as $v) {
            $value = $val[$v];
            if (array_key_exists($v, $this->mappings)) {
                $value = Model::MapFromDatabase($this->mappings[$v], $value);
            }
            $this->_values[$v]->Set($value);
            $this->_values[$v]->Reset();
        }
    }

    public function MapValue($column, $value)
    {
        if (array_key_exists($column, $this->mappings)) {
            return Model::MapToDatabase($this->mappings[$column], $value);
        }
        return $value;
    }

    public function GetTableFields($table_prefix = '', $result_prefix = '')
    {
        $table_prefix = strlen($table_prefix) == 0 ? '' : $table_prefix . '.';
        $result = array();
        foreach ($this->columns as $db => $map) {
            $result[] = "{$table_prefix}`{$db}` AS `{$result_prefix}{$map}`";
        }
        return implode(', ', $result);
    }

    public function Alias($name, $alias)
    {
        if (array_search($alias, $this->columns) === false && array_search($alias, $this->alias) === false) {
            $this->alias[$name] = $alias;
        }
    }

    public function GetDBName($alias)
    {
        return array_search($alias, $this->columns);
    }

    public function HasField($name) {
        return array_search($name, $this->columns) !== false || array_search($name, $this->alias) !== false;
    }

    public function IsValid($fields = null) {
        return count($this->GetErrors($fields)) == 0;
    }

    public function GetErrors($fields = null) {
        if ($fields == null) {
            $fields = array_values($this->columns);
        } else if (is_string($fields)) {
            $nf = array();
            $nf[] = $fields;
            $fields = $nf;
        }
        $errors = array();
        foreach ($fields as $field) {
            if (isset($this->_validators[$field])) {
                foreach ($this->_validators[$field] as $vd) {
                    if ($vd == null) {
                        continue;
                    }
                    if (!$vd->Validate($this, $field, $this->$field)) {
                        $errors[$field][] = $vd->GetMessage();
                    }
                }
            }
        }
        return array_merge($errors, $this->Validate());
    }

    public function  __get($name) {
        $alias = array_search($name, $this->alias);
        if ($alias !== false) {
            $name = $alias;
        }
        if (array_key_exists($name, $this->_values)) {
            return $this->_values[$name]->Get();
        }
        return null;
    }

    public function  __set($name,  $value) {
        $alias = array_search($name, $this->alias);
        if ($alias !== false) {
            $name = $alias;
        }
        if (array_key_exists($name, $this->_values)) {
            $this->_values[$name]->Set($value);
        }
    }

    public function Find($class) {
        $obj = new $class();
        $order = null;
        $limit = null;
        $params = array();
        $where = array();
        if (isset($this->_getcache['many'][$class])) {
            return $this->_getcache['many'][$class];
        }
        $rel = $this->many[$class];
        $counter = 1;
        foreach ($rel as $tk => $ok) {
            if ($tk == ':Order') $order = "`$ok` ASC";
            else if ($tk == ':OrderDesc') $order = "`$ok` DESC";
            else if ($tk == ':JoinParams') $where[] = $ok;
            else if ($tk == ':Limit') $limit = $ok;
            else {
                $ok = $obj->GetDbName($ok);
                $param_name = ':jp'.$counter;
                $counter++;
                $where[] = "`$ok` = $param_name";
                $params[$param_name] = $this->$tk;
            }
        }
        $values = Database::All($obj->table, $where, $params, $order, $limit, $obj->GetTableFields());
        $result = array();
        foreach ($values as $val) {
            $res = new $class();
            $res->_new = false;
            $res->SetValues($val);
            $result[] = $res;
        }
        $this->_getcache['many'][$class] = $result;
        return $result;
    }

    public function Get($class) {
        $obj = new $class();
        $where = array();
        $params = array();
        $order = null;
        if (isset($this->_getcache['one'][$class])) {
            return $this->_getcache['one'][$class];
        }
        $rel = $this->one[$class];
        $counter = 1;
        foreach ($rel as $tk => $ok) {
            if ($tk == ':JoinParams') $where[] = $ok;
            else {
                $ok = $obj->GetDbName($ok);
                $param_name = ':jp'.$counter;
                $counter++;
                $where[] = "`$ok` = $param_name";
                $params[$param_name] = $this->$tk;
            }
        }
        $val = Database::One($obj->table, $where, $params, $order, $obj->GetTableFields());
        $res = new $class();
        $res->_new = false;
        if ($val !== false) {
            $res->SetValues($val);
        }
        $this->_getcache['one'][$class] = $res;
        return $res;
    }

    public function Copy() {
        $class = get_called_class();
        $copy = new $class;
        foreach ($this->_values as $n => $d) {
            $copy->_values[$n]->Set($d->Get());
        }
        $copy->_values[$copy->primaryKey]->Set(null);
        $copy->_values[$copy->primaryKey]->Reset();
        return $copy;
    }

    public function Save() {
        $insert = $this->_new;
        $insert ? $this->BeforeInsert() : $this->BeforeUpdate();

        $updated = array();
        foreach ($this->_values as $d) {
            if ($d->HasChanged()) {
                $updated[$d->GetDbName()] = $d->Get();
            }
        }

        if (!$insert && count($updated) == 0) {
            return;
        }

        if ($this->_new) {
            $ins = Database::Insert($this->table, $updated);
            $this->_values[$this->primaryKey]->Set($ins);
            $this->_new = false;
        } else {
            Database::Update($this->table, array("`{$this->_dbpk}` = :id"), array(':id' => $this->{$this->primaryKey}), $updated);
        }
        foreach ($this->_values as $d) {
            $d->Reset();
        }
        $insert ? $this->AfterInsert() : $this->AfterUpdate();
    }

    public function Delete() {
        $this->BeforeDelete();
        Database::Delete($this->table, array("{$this->_dbpk} = :id"), array(':id' => $this->{$this->primaryKey}));
        $this->_values[$this->primaryKey]->Set(null);
        $this->_values[$this->primaryKey]->Reset();
        $this->_new = true;
        $this->AfterDelete();
    }

    public function ToArray($columns = array())
    {
        $result = array();
        foreach ($this->_values as $d) {
            if (count($columns) == 0 || array_search($d->GetName(), $columns) !== false) {
                $result[$d->GetName()] = $d->Get();
            }
        }
        return $result;
    }

    protected function BeforeInsert()
    {
        // Virtual
    }

    protected function AfterInsert()
    {
        // Virtual
    }

    protected function BeforeUpdate()
    {
        // Virtual
    }

    protected function AfterUpdate()
    {
        // Virtual
    }

    protected function BeforeDelete()
    {
        // Virtual
    }

    protected function AfterDelete()
    {
        // Virtual
    }

    protected function Validate()
    {
        // Virtual
        return array();
    }

    // Static

    // Query

    public static function Count($class, $where = array(), $params = array()) {
        $obj = new $class();
        return Database::Count($obj->table, $where, $params);
    }

    public static function Search($class, $where = array(), $params = array(), $order = null, $limit = null) {
        $obj = new $class();
        $values = Database::All($obj->table, $where, $params, $order, $limit, $obj->GetTableFields());
        $result = array();
        foreach ($values as $val) {
            $res = new $class();
            $res->_new = false;
            $res->SetValues($val);
            $result[] = $res;
        }
        return $result;
    }

    // Data mappers
    private static $_data_mappers = array();

    static function RegisterDataMapper($ref_name, $class_name)
    {
        if (class_exists($class_name)) {
            Model::$_data_mappers[$ref_name] = new $class_name();
        }
    }

    static function GetMapped($to_db, $ref_name, $data)
    {
        $config_data = null;
        if (is_array($ref_name)) {
            $nm = array_keys($ref_name); $nm = $nm[0];
            $config_data = $ref_name[$nm];
            $ref_name = $nm;
        }

        $obj = Model::$_data_mappers[$ref_name];
        if ($obj == null) {
            return $data;
        }

        return $to_db
                ? $obj->MapToDatabase($data, $config_data)
                : $obj->MapFromDatabase($data, $config_data);
    }

    static function MapToDatabase($ref_name, $data)
    {
        return Model::GetMapped(true, $ref_name, $data);
    }

    static function MapFromDatabase($ref_name, $data)
    {
        return Model::GetMapped(false, $ref_name, $data);
    }
}

class ModelValue
{
    private $_db_name;
    private $_name;
    private $_initial_value;
    private $_current_value;
    private $_has_changed;

    function __construct($db_name, $name, $value = null)
    {
        $this->_db_name = $db_name;
        $this->_name = $name;
        $this->_initial_value = $value;
        $this->_current_value = $value;
        $this->_has_changed = false;
    }

    public function HasChanged()
    {
        return $this->_has_changed;
    }

    public function GetDbName()
    {
        return $this->_db_name;
    }

    public function GetName()
    {
        return $this->_name;
    }

    public function Set($value)
    {
        if ($this->_current_value !== $value) {
            $this->_current_value = $value;
        }
        $this->_has_changed = $this->_current_value !== $this->_initial_value;
    }

    public function Get()
    {
        return $this->_current_value;
    }

    public function Revert()
    {
        $this->_current_value = $this->_initial_value;
        $this->_has_changed = false;
    }

    public function Reset()
    {
        $this->_initial_value = $this->_current_value;
        $this->_has_changed = false;
    }

    public function Copy()
    {
        $n = new ModelValue($this->_db_name, $this->_name);
        $n->_initial_value = $this->_initial_value;
        $n->_current_value = $this->_current_value;
        $n->_has_changed = $this->_has_changed;
        return $n;
    }
}

Model::RegisterDataMapper('bool', 'BooleanDataMapper');
Model::RegisterDataMapper('date', 'DateTimeDataMapper');

class ModelDataMapper
{
    function MapToDatabase($data, $config_data = null)
    {
        // Virtual
    }

    function MapFromDatabase($data, $config_data = null)
    {
        // Virtual
    }
}

class BooleanDataMapper extends ModelDataMapper
{
    function MapToDatabase($data, $config_data = null)
    {
        return ($data === true || $data == 'true' || $data == 'on' || (is_numeric($data) && $data > 0)) ? 1 : 0;
    }

    function MapFromDatabase($data, $config_data = null)
    {
        return $data > 0;
    }
}

class DateTimeDataMapper extends ModelDataMapper
{
    function MapToDatabase($data, $config_data = null)
    {
        if ($data === null || !is_string($data)) return null;
        $format = 'Y-m-d';
        if (is_string($config_data)) $format = $config_data;
        if (is_array($config_data) && $config_data['format'] !== null) $format = $config_data['format'];
        $dt = DateTime::createFromFormat($format, $data);
        if ($dt === false) {
            $time = strtotime($data);
            if ($time === false) return null;
            return gmdate('Y-m-d H:i:s', $time);
        }
        return $dt->format('Y-m-d H:i:s');
    }

    function MapFromDatabase($data, $config_data = null)
    {
        if ($data === null || !is_string($data)) return null;
        return gmdate('Y-m-d H:i:s', strtotime($data));
    }
}
