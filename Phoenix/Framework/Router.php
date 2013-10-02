<?php

class Router {

    static $default_controller = 'Home';
    static $default_action = 'Index';

    static $request_route = '/';
    static $request_controller = '';
    static $request_action = '';
    static $request_params = array();

    static $_error = null;

    static $_registered = array();

    public static function RegisterCustomRouter($router)
    {
        Router::$_registered[] = $router;
    }

    public static function ControllerExists($controller)
    {
        return class_exists($controller, true);
    }

    public static function ActionExists($controller, $action)
    {
        if (!Router::ControllerExists($controller)) return null;
        $c = new $controller;
        return Router::GetActionName($c, $action) != null;
    }

    protected static function GetActionName($controller, $action)
    {
        $search = strtolower($action);
        $search_post = $search.'_post';
        $methods = get_class_methods($controller);
        if ($methods == null) return null;
        if (Post::IsPostBack()) {
            foreach ($methods as $method) {
                if (strtolower($method) == $search_post) {
                    return $method;
                }
            }
        }
        foreach ($methods as $method) {
            if ((strtolower($method) == $search)) {
                return $method;
            }
        }
        return null;
    }

    protected static function GetActionParams($controller, $action)
    {
        $method = new ReflectionMethod($controller, $action);
        return $method->getNumberOfRequiredParameters();
    }

    static function CreateUrl($controller, $action, $params = array())
    {
        $url = Phoenix::$base_url.$controller;
        if ($action !== null) $url .= '/' . $action;
        foreach ($params as $key => $value) {
            $url .= '/'.$value;
        }
        return $url;
    }

    static function Redirect($controller = null, $action = null, $params = array())
    {
        if ($controller == null) {
            $controller = Router::$request_controller;
            $action = Router::$request_action;
            $params = Router::$request_params;
        } else if ($action == null) {
            $action = Router::$default_action;
            $params = array();
        }
        $url = Router::CreateUrl($controller, $action, $params);
        header("Location: $url");
    }

    public function CanResolve($route)
    {
        return true;
    }

    /**
     * @param  $route
     * @return RouteParameters
     */
    public function ResolveRoute($route)
    {
        $replaced = str_replace('\\', '/', $route);
        $trimmed = trim($replaced, '/');

        $ret = new RouteParameters();
        $ret->route = $trimmed;
        $con = Router::$default_controller;
        $act = Router::$default_action;
        $args = array();

        $split = explode('/', $trimmed);
        $count = count($split);
        if ($trimmed == '') $count = 0;

        // Getting the controller
        if ($count > 0) {
            $con = $split[0];
        }

        $control = $con.'Controller';

        if (!Router::ControllerExists($control)) {
            Router::$_error = "Controller not found: $con.";
            return null;
        }

        $ret->controller = new $control;
        $ret->controller_name = substr(get_class($ret->controller), 0, -strlen('Controller'));

        // Getting the action
        if ($count > 1) {
            $act = $split[1];
        }

        $action = Router::GetActionName($ret->controller, $act);

        if ($action == null) {
            Router::$_error = "Action not found: $act.";
            return null;
        }

        $ret->action_name = preg_replace('/^(.*)_Post$/i', '\1', $action);
        $ret->action = $action;

        // Getting the args
        if ($count > 2) {
            $args = array_slice($split, 2);
        }

        $num_params = Router::GetActionParams($ret->controller, $ret->action);
        if ($num_params > count($args)) {
            Router::$_error = "Not enough parameters: Required $num_params, got " . count($args) . ".";
            return null;
        }

        $ret->params = $args;

        return $ret;
    }

    /**
     * Gets a controller instance for a specified route. The route format
     * is assumed to be: /Controller/Action/Param1/Param2/...<br />
     * The following routes are permitted:<br />
     * <ul>
     *   <li>Empty string (uses defaults)</li>
     *   <li>/ (uses defaults)</li>
     *   <li>/Controller/ (uses default action)</li>
     *   <li>/Controller/Action/ (no params)</li>
     *   <li>/Controller/Action/Param1/... (any number of params)</li>
     * </ul>
     * The only time a controller or action can be omitted is when
     * there are no parameters. A route such as /Controller/Param1 will not
     * work.
     *
     * @param string $route The route to resolve
     * @return RouteParameters The resolved route
     */
    static function Resolve($route)
    {
        // Loop through registered route handlers
        foreach (Router::$_registered as $rtr)
        {
            if ($rtr->CanResolve($route))
            {
                $rt = $rtr->ResolveRoute($route);
                Router::SetRouteVars($rt);
                return $rt;
            }
        }
        // Use default
        $def = new Router();
        $rt = $def->ResolveRoute($route);
        Router::SetRouteVars($rt);
        return $rt;
    }

    protected static function SetRouteVars($route)
    {
        if ($route == null) return;
        Router::$request_route = $route->route;
        Router::$request_controller = $route->controller_name;
        Router::$request_action = $route->action;
        Router::$request_params = $route->params;
    }

}

class DefaultRouter extends Router
{
    /**
     * @var string The router map
     */
    protected $map;

    /**
     * @var DefaultRouterPattern The router pattern
     */
    protected $pattern;

    /**
     * @var array The default route parameter values
     */
    protected $defaults;

    /**
     * @var array The route options
     */
    protected $options;

    /**
     * @param string $map Allows patterns in the following forms:
     * <ul>
     *   <li>{*controller}/{*action}/{*} (behaviour of the default route, the * field must always be on the end)</li>
     *   <li>{controller}/{*} (action and controller must have default values if they are excluded)</li>
     *   <li>{controller}/(action}/{*id}/{*page} (this will match routes with 0, 1, or 2 arguments on the end. both id and page are optional}</li>
     *   <li>{controller}/{action}/{id}/{*page} (page is optional, but id is not)</li>
     *   <li>{controller}/{action}/{id}/{*} (id is a single parameter, * catches all remaining parameters and is always optional)</li>
     * </ul>
     * @param array $defaults The default values of this pattern. If excluded for optional fields, the value passed will be null.
     * @param array $options Allowed options:
     * <ul>
     *   <li>string_catchall (boolean:false): If true, the elements in the catch-all parameter of the route will be assembled into a single string</li>
     *   <li>string_params (boolean:false): If true, the parameters of the route will be assembled into a single string. Includes the catch-all, so this overrides the string_catchall setting.</li>
     *   <li>catchall_separator (string:'/'): The string to split the catch-all parameter by when array-ifying it [e.g. '1/2/3' -> array(1,2,3)]</li>
     *   <li>string_separator (string:'/'): The string to join the string parameters with when string-ifying them [e.g. array(1,2,3) -> '1/2/3']</li>
     * </ul>
     * @return DefaultRouter
     */
    static function Create($map, $defaults, $options = array()) {
        return new DefaultRouter($map, $defaults, $options);
    }

    function __construct($map, $defaults, $options = array()) {
        $this->map = $map;
        $this->defaults = $defaults;
        $this->pattern = new DefaultRouterPattern($this->map, $this->defaults);
        $this->options = array_merge(array(
            'string_catchall' => false,
            'string_params' => false,
            'catchall_separator' => '/',
            'string_separator' => '/'
        ), $options);
        if (strlen($this->options['catchall_separator']) == 0) throw new Exception("The catchall_separator value cannot be empty.");
    }

    /**
     * @param $route
     * @return bool
     */
    public function CanResolve($route)
    {
        $match = $this->pattern->Match($route);
        return $match != null;
    }

    /**
     * @param $route
     * @return RouteParameters
     */
    public function ResolveRoute($route)
    {
        $match = $this->pattern->Match($route);
        if ($match == null) return null;
        $action = $match['action'];
        $controller = $match['controller'];
        $params = $match['*params'];
        $catchall = $match['*'];
        if ($catchall != null) {
            $catchall = explode($this->options['catchall_separator'], $catchall);
            // TODO if ($this->options[''])
        }
    }
}

class DefaultRouterPattern
{
    protected $map;
    protected $defaults;

    protected $_matchCache;

    /**
     * @var array
     */
    public $parts;

    /**
     * @param string $map Supports patterns in the following forms:
     * <ul>
     *   <li>{*controller}/{*action}/{*} (behaviour of the default route, the * field must always be on the end)</li>
     *   <li>{controller}/{*} (action and controller must have default values if they are excluded)</li>
     *   <li>{controller}/(action}/{*id}/{*page} (this will match routes with 0, 1, or 2 arguments on the end. both id and page are optional}</li>
     *   <li>{controller}/{action}/{id}/{*page} (page is optional, but id is not)</li>
     *   <li>{controller}/{action}/{id}/{*} (id is a single parameter, * catches all remaining parameters and is always optional)</li>
     * </ul>
     * @param array $defaults The default values for the named variables in the map.
     * `action` and `controller` must be mandatory in the route or have defaults specified.
     */
    function __construct($map, $defaults)
    {
        $this->map = $map;
        $this->defaults = $defaults;
        $this->_matchCache = array();
        $this->parts = array();
        $this->ExtractParts(); // Splits by type and extracts the contents
        $this->ProcessParts(); // Adds metadata to the parts
    }

    private function ExtractParts()
    {
        preg_match_all('/\{(.*?)\}/m', $this->map, $result, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
        $g0 = $result[0];
        $g1 = $result[1];
        $index = 0;
        for ($i = 0; $i < count($g0); $i++) {
            $end = $g0[$i][1];
            if ($end > $index) {
                $substr = substr($this->map, $index, $end - $index);
                $this->parts[] = array('type' => 'text', 'text' => $substr);
            }
            $label = $g1[$i][0];
            $this->parts[] = array('type' => 'regex', 'text' => $label);
            $index = $end + strlen($g0[$i][0]);
        }
        if (strlen($this->map) > $index) {
            $substr = substr($this->map, $index);
            $this->parts[] = array('type' => 'text', 'text' => $substr);
        }
    }

    private function ProcessParts()
    {
        $controllerFound = array_key_exists('controller', $this->defaults);
        $actionFound = array_key_exists('action', $this->defaults);

        $group = 1;
        $count = count($this->parts);
        $lastMandatory = -1;
        $firstOptional = -1;
        for ($i = 0; $i < $count; $i++) {
            $next = $i < $count - 1 ? $this->parts[$i+1] : null;
            $type = $this->parts[$i]['type'];
            $text = $this->parts[$i]['text'];

            if ($type == 'regex')
            {
                // Get the splitting character (defaults to '/', but uses the first char in the next string if it exists)
                $exclude = '/';
                if ($next != null) {
                    if ($next['type'] == 'regex') throw new Exception("Route variables must be separated by at least one character.");
                    $exclude = substr($next['text'], 0, 1);
                }

                // Check if this variable is optional or not
                $optional = substr($text, 0, 1) == '*';
                if ($firstOptional >= 0 && !$optional) {
                    throw new Exception("Mandatory route variables cannot come before optional variables.");
                }
                if ($optional && $firstOptional < 0) $firstOptional = $i;
                if (!$optional) $lastMandatory = $i;

                // Check if this variable is the catch-all
                $catchAll = $text == '*';
                if ($catchAll && $next != null) {
                    throw new Exception("The catch-all variable must be at the very end of the route.");
                }
                $name = $catchAll ? '*' : trim($text, '*');

                // Create the regex pattern
                $pattern = $catchAll ? "(.*)" : "([^".$exclude."]*)";

                // Assign the variables
                $this->parts[$i]['name'] = $name;
                $this->parts[$i]['pattern'] = $pattern;
                $this->parts[$i]['capturing'] = true;
                $this->parts[$i]['group'] = $group;
                $this->parts[$i]['catch-all'] = $catchAll;
                $this->parts[$i]['default'] = array_key_exists($name, $this->defaults) ? $this->defaults[$name] : null;

                if ($name == 'action' && !$optional) $actionFound = true;
                if ($name == 'controller' && !$optional) $controllerFound = true;

                $group++;
            }
            else if ($type == 'text')
            {
                $this->parts[$i]['pattern'] = $text;
                $this->parts[$i]['capturing'] = false;
            }
        }

        if (!$controllerFound) throw new Exception("The route controller must be mandatory in the route or have a default value.");
        if (!$actionFound) throw new Exception("The route action must be mandatory in the route or have a default value.");

        // Assemble the matching regexes
        $regex = '';
        for ($i = 0; $i < $count; $i++) {
            $type = $this->parts[$i]['type'];

            // Assign some final metadata
            $optional = $i > $lastMandatory;
            $this->parts[$i]['optional'] = $optional;

            // The last mandatory regex and all optional regexes get a match
            $match = $i >= $lastMandatory && $type == 'regex';
            $this->parts[$i]['match'] = $match;

            // Append the pattern regardless
            $regex .= $this->parts[$i]['pattern'];

            if ($match) {
                // Assemble a regex that can go straight into preg_match
                $this->parts[$i]['regex'] = '%^' . $regex . '$%';
            }
        }
    }

    public function Match($route)
    {
        $route = trim($route, '/');
        if (!array_key_exists($route, $this->_matchCache))
        {
            // Iterate all the matching parts
            $count = count($this->parts);
            for ($i = 0; $i < $count; $i++)
            {
                if (!$this->parts[$i]['match']) continue;
                if (preg_match($this->parts[$i]['regex'], $route, $result))
                {
                    // Put the parsed match into the cache
                    $this->_matchCache[$route] = $this->ParseMatch($result);
                    break; // We're done here
                }
            }
            if (!array_key_exists($route, $this->_matchCache))
            {
                // No match, persist the non-result
                $this->_matchCache[$route] = null;
            }
        }
        // Result is persisted by now, carry on.
        return $this->_matchCache[$route];
    }

    private function ParseMatch($match)
    {
        // $result = { controller, action, *params, [named parameters], * }
        // All parameters aside from action, controller, and * go into *params (for ordering)
        // * always goes on the end so we're safe there.
        $result = array(
            'controller' => '',
            'action' => '',
            '*' => null,
            '*params' => array()
        );
        $count = count($this->parts);
        $groupCount = count($match);

        // Iterate the capturing groups
        for ($i = 0; $i < $count; $i++) {
            if (!$this->parts[$i]['capturing']) continue;
            $name = $this->parts[$i]['name'];
            $group = $this->parts[$i]['group'];
            $default = $this->parts[$i]['default'];

            // Default if not matched
            $value = $group < $groupCount ? trim($match[$group]) : $default;
            if ($value === null) $value = '';

            // Don't insert 'nothing' results
            if ($value === null || strlen($value) == 0) continue;

            // Populate the result array as required
            if ($name != 'controller' && $name != 'action' && $name != '*') $result['*params'][] = $value;
            $result[$name] = $value;
        }
        return $result;
    }
}