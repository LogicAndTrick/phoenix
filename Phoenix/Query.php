<?php

/**
 * An easy-to-use wrapper around the Model querying functions.
 * Methods can be chained like so: <br>
 * $result = Query::Create('User')->Where('ID', '>', 1)->OrderBy('ID', 'DESC')->All();
 */
class Query
{
    private $_model;
    private $_model_instance;
    private $_params;
    private $_where;
    private $_order;
    private $_limit;

    function  __construct($class) {
        $this->_model = $class;
        $this->_model_instance = new $class();
        $this->_params = array();
        $this->_where = array();
        $this->_order = array();
        $this->_limit = null;
    }

    /**
     * Create a new query for a model
     * @param string The class to query on
     * @return Query A new query instance
     */
    public static function Create($class) {
        return new Query($class);
    }

    private function GetDbName($name) {
        $db = $this->_model_instance->GetDBName($name);
        return $db ? $db : $name;
    }

    /**
     * Add a where clause to the query
     * @param string $field The name of the field
     * @param string $op The operator to use
     * @param object $value The value to match against
     * @return Query Self
     */
    public function Where($field, $op, $value) {
        $field = $this->GetDbName($field);
        $pname = ':param'.(count($this->_params)+1);
        $this->_where[] = "`$field` $op $pname";
        $this->_params[$pname] = $value;
        return $this;
    }

    /**
     * Add an order to the query
     * @param string $field The name of the field
     * @param string $dir The direction to sort in (ASC or DESC)
     * @return Query self
     */
    public function OrderBy($field, $dir = 'ASC') {
        $field = $this->GetDbName($field);
        $this->_order[] = "`$field` $dir";
        return $this;
    }

    /**
     * Add a limit to the query
     * @param int $limit The number of rows to take
     * @param int $offset The number of rows to skip (default zero)
     * @return Query self
     */
    public function Limit($limit, $offset = 0) {
        $this->_limit = '';
        if ($offset > 0) {
            $this->_limit .= "$offset,";
        }
        $this->_limit .= $limit;
        return $this;
    }

    public function Count() {
        return Model::Count($this->_model, $this->_where, $this->_params);
    }

    /**
     * Execute the query and return a single result
     * @return Model The result of the query
     */
    public function One() {
        $order = null;
        if (count($this->_order) > 0) {
            $order = implode(',', $this->_order);
        }
        $results = Model::Search($this->_model, $this->_where, $this->_params, $order, 1);
        if (count($results) > 0) return $results[0];
        else return new $this->_model();
    }

    /**
     * Execute the query and return all results
     * @return array The result of the query
     */
    public function All() {
        $order = null;
        if (count($this->_order) > 0) {
            $order = implode(',', $this->_order);
        }
        return Model::Search($this->_model, $this->_where, $this->_params, $order, $this->_limit);
    }

    /**
     * Remember to only use this function when using an ordered query!
     * @param int $items_per_page The number of items to display per page
     * @param int $current_page The current page. The first index is 1. This can be equal to 'last' to fetch the last page
     * @return object Items = the database items for this page,
     *                FirstPage = first page number,
     *                CurrentPage = current page number,
     *                LastPage = last page number,
     *                NumItems = total number of items,
     *                NumPages = total number of pages,
     *                ItemsPerPage = number of items per page
     */
    public function Paginate($items_per_page, $current_page) {
        // Get the total item count and find out the number of pages
        $count = Model::Count($this->_model, $this->_where, $this->_params);
        $info = Query::GetPaginationInfo($items_per_page, $current_page, $count);
        $this->Limit($items_per_page, $info['QueryOffset']);
        return Query::CreatePaginationModel($this->All(), $info);
    }

    public static function CreatePaginationModel($items, $pagination_info)
    {
        return new CustomQueryRow(array(
            'Items' => $items,
            'NumItemsOnPage' => count($items),
            'FirstPage' => 1,
            'CurrentPage' => $pagination_info['CurrentPage'],
            'LastPage' => $pagination_info['TotalPages'],
            'NumItems' => $pagination_info['TotalItems'],
            'NumPages' => $pagination_info['TotalPages'],
            'ItemsPerPage' => $pagination_info['ItemsPerPage']
        ));
    }

    public static function GetPaginationInfo($items_per_page, $current_page, $item_count)
    {
        $total_pages = ceil($item_count / $items_per_page);
        // Find the current page
        $current = $current_page;
        if (strtolower($current) == 'first') $current = 1;
        else if (strtolower($current) == 'last') $current = $total_pages;
        else if (!is_numeric($current)) $current = 1;
        else if ($current < 1) $current = 1;
        else if ($current > $total_pages) $current = $total_pages;
        // Calculate the required offset/limit values
        $offset = ($current - 1) * $items_per_page;
        return array(
            'ItemsPerPage' => $items_per_page,
            'CurrentPage' => $current,
            'QueryOffset' => $offset,
            'TotalItems' => $item_count,
            'TotalPages' => $total_pages
        );
    }
}

class CustomQuery
{
    public static function Query($sql, $params = array())
    {
        $values = Database::QueryAll($sql, $params);
        $rows = array();
        foreach ($values as $val) {
            $rows[] = new CustomQueryRow($val);
        }
        return $rows;
    }

    public static function Paginated($items_per_page, $current_page, $num_items, $sql, $params = array())
    {
        $info = Query::GetPaginationInfo($items_per_page, $current_page, $num_items);
        $o = $info['QueryOffset'];
        $l = $info['ItemsPerPage'];
        $sql = str_ireplace('{limit}', "LIMIT $o, $l", $sql);
        return Query::CreatePaginationModel(CustomQuery::Query($sql, $params), $info);
    }
}

class CustomQueryRow
{
    private $_values;

    function __construct($values)
    {
        $this->_values = $values;
    }

    function __get($name)
    {
        return $this->_values[$name];
    }

    function __set($name, $value)
    {
        $this->_values[$name] = $value;
    }
}

?>
