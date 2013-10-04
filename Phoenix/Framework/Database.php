<?php

class DbUtil
{
    static function GetConnectionString($type, $host, $database)
    {
        if ($type == 'mysql') {
            return 'mysql:host='.$host.';dbname='.$database;
        } else if ($type == 'sqlite') {
            return 'sqlite:'.$database;
        }
    }

    static function GetConnection($type, $host, $database, $username, $password)
    {
        return new PDO(
            DbUtil::GetConnectionString($type, $host, $database),
            $username,
            $password
        );
    }
}

class Database
{
    public static $type;
    public static $username;
    public static $password;
    public static $host;
    public static $database;
    public static $_enabled = false;
    public static $_loggers = array();

    /**
     * The database connection
     * @var PDO
     */
    public static $_connection;
    
    public static function Enable()
    {
        Database::$_enabled = true;
        Database::$_connection = DbUtil::GetConnection(
            Database::$type,
            Database::$host,
            Database::$database,
            Database::$username,
            Database::$password
        );
    }

    public static function AddLogger($logger)
    {
        Database::$_loggers[] = $logger;
    }

    private static function Execute($stmt, $params)
    {
        foreach (Database::$_loggers as $logger) {
            $logger->Log($stmt->queryString, $params);
        }
        return $stmt->execute($params);
    }

    private static function AssembleSelect($table, $where = array(), $order = null, $limit = null, $fields = '*')
    {
        $sql = "SELECT $fields FROM $table WHERE 1 = 1";
        foreach ($where as $cond) {
            $sql .= " AND $cond";
        }
        if ($order != null) {
            $sql .= " ORDER BY $order";
        }
        if ($limit != null) {
            $sql .= " LIMIT $limit";
        }
        return Database::$_connection->prepare($sql);
    }

    public static function Count($table, $where = array(), $params = array())
    {
        $stmt = Database::AssembleSelect($table, $where, null, null, 'count(*) AS Count');
        $result = array('Count' => 0);
        if (Database::Execute($stmt, $params)) {
            $result = $stmt->fetch();
        }
        $stmt->closeCursor();
        return $result['Count'];
    }

    public static function All($table, $where = array(), $params = array(), $order = null, $limit = null, $fields = '*')
    {
        $stmt = Database::AssembleSelect($table, $where, $order, $limit, $fields);
        $result = array();
        if (Database::Execute($stmt, $params)) {
            $result = $stmt->fetchAll();
        }
        $stmt->closeCursor();
        return $result;
    }

    public static function One($table, $where = array(), $params = array(), $order = null, $fields = '*')
    {
        $stmt = Database::AssembleSelect($table, $where, $order, null, $fields);
        $result = array();
        if (Database::Execute($stmt, $params)) {
            $result = $stmt->fetch();
        }
        $stmt->closeCursor();
        return $result;
    }

    public static function NonQuery($sql, $params = array())
    {
        $stmt = Database::$_connection->prepare($sql);
        return Database::Execute($stmt, $params);
    }

    public static function QueryAll($sql, $params = array())
    {
        $stmt = Database::$_connection->prepare($sql);
        $result = array();
        if (Database::Execute($stmt, $params)) {
            $result = $stmt->fetchAll();
        }
        $stmt->closeCursor();
        return $result;
    }

    public static function QueryOne($sql, $params = array())
    {
        $stmt = Database::$_connection->prepare($sql);
        $result = array();
        if (Database::Execute($stmt, $params)) {
            $result = $stmt->fetch();
        }
        $stmt->closeCursor();
        return $result;
    }

    public static function Insert($table, $values)
    {
        $params = array();
        $counter = 1;
        foreach ($values as $field => $value) {
            $pname = ':param'.$counter;
            $counter++;
            $params[$pname] = $value;
        }

        $cols = array_keys($values);
        foreach ($cols as $k => $v)
        {
            $cols[$k] = "`$v`";
        }
        $sql = "INSERT INTO $table (";
        $sql .= implode(',', $cols);
        $sql .= ') VALUES (';
        $sql .= implode(',', array_keys($params));
        $sql .= ')';
        
        $stmt = Database::$_connection->prepare($sql);
        if (Database::Execute($stmt, $params)) {
            return Database::$_connection->lastInsertId();
        }
        return -1;
    }

    public static function BulkInsert_MySQL($table, $cols, $values)
    {
        $limit = 25;
        $insert = "INSERT INTO $table (" . implode(',', $cols) . ') VALUES ';
        $params = array();
        $rows = 0;
        $counter = 1;
        $sqls = array();
        foreach ($values as $row) {
            $ins = array();
            foreach ($row as $d) {
                $pname = ':param'.$counter;
                $counter++;
                $params[$pname] = $d;
                $ins[] = $pname;
            }
            $sqls[] = '(' . implode(',', $ins) . ')';
            $rows++;
            if ($rows >= $limit) {
                Database::NonQuery($insert . implode(',', $sqls), $params);
                $sqls = array();
                $params = array();
                $rows = 0;
                $counter = 1;
            }
        }
        if ($rows > 0) {
            Database::NonQuery($insert . implode(',', $sqls), $params);
        }
    }

    public static function Update($table, $where = array(), $params = array(), $values = array())
    {
        $sql = "UPDATE $table SET";
        $counter = 1;
        foreach ($values as $field => $value) {
            if ($counter > 1) {
                $sql .= ",";
            }
            $pname = ':param'.$counter;
            $counter++;
            $params[$pname] = $value;
            $sql .= " `$field` = $pname";
        }
        $sql .= ' WHERE 1 = 1';
        foreach ($where as $cond) {
            $sql .= " AND $cond";
        }
        $stmt = Database::$_connection->prepare($sql);
        if (Database::Execute($stmt, $params)) {
            return $stmt->rowCount();
        }
        return 0;
    }

    public static function Delete($table, $where = array(), $params = array())
    {
        $sql = "DELETE FROM $table";
        $sql .= ' WHERE 1 = 1';
        foreach ($where as $cond) {
            $sql .= " AND $cond";
        }
        $stmt = Database::$_connection->prepare($sql);
        if (Database::Execute($stmt, $params)) {
            return $stmt->rowCount();
        }
        return 0;
    }
}

class DatabaseLogger
{
    function Log($sql, $params)
    {
        // virtual
    }
}

class MemoryLogger extends DatabaseLogger
{
    public $queries;

    function Log($sql, $params)
    {
        $this->queries[] = array(
            'query' => $sql,
            'params' => $params
        );
    }
}

class DbLogger extends DatabaseLogger
{
    public $table;
    public $field_sql;
    public $field_params;
    public $field_time;
    public $field_user;

    function Log($sql, $params)
    {
        $pstr = '';
        foreach ($params as $k => $v) {
            $pstr .= $k.' = '.$v."\n";
        }
        $pstr = trim($pstr);

        $sql = "INSERT INTO {$this->table} ";
        $sql .= "({$this->field_sql}, {$this->field_params}, {$this->field_time}, {$this->field_user})";
        $sql .= ' VALUES (:sql, :params, :time, :user)';
        $stmt = Database::$_connection->prepare($sql);
        $params = array(
            ':sql' => $sql,
            ':params' => $pstr,
            ':time' => time(),
            ':user' => Authentication::GetUserID()
        );
        $stmt->execute($params);
    }
}

class EchoLogger extends DatabaseLogger
{
    public $printstacktrace = false;

    function Log($sql, $params)
    {
        echo '<div style="border: 1px solid black; padding: 5px;">'.$sql.'<br>';
        foreach ($params as $k => $v) {
            echo '-- '.$k.' = '.$v.'<br>';
        }
        if ($this->printstacktrace) {
            ob_start();
            debug_print_backtrace();
            $trace = ob_get_contents();
            ob_end_clean();
            echo str_replace("\n", "<br>\n", $trace);
        }
        echo '</div>';
    }
}
