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
    protected $map;
    protected $pattern;
    protected $defaults;
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
     *   <li>params_as_string (boolean): If true, the parameters of the route will be assembled into a single string, separated by the '/' character.</li>
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
            'params_as_string' => false
        ), $options);
    }

    /**
     * @param $route
     * @return bool
     */
    public function CanResolve($route)
    {
        return true;
    }

    /**
     * @param $route
     * @return RouteParameters
     */
    public function ResolveRoute($route)
    {
        return null;
    }
}

class DefaultRouterPattern
{
    protected $map;
    protected $defaults;

    public $parts;

    function __construct($map, $defaults)
    {
        $this->map = $map;
        $this->defaults = $defaults;
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
        $count = count($this->parts);
        for ($i = 0; $i < $count; $i++) {
            $type = $this->parts[$i]['type'];
        }
    }
}