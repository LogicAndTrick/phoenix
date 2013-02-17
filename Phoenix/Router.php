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

    protected function CanResolve($route)
    {
        return true;
    }

    /**
     * @param  $route
     * @return RouteParameters
     */
    protected function ResolveRoute($route)
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
     *   <li>{controller}/{!action}/{*} (this will catch routes where the value in the !action field is not a valid action)</li>
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

    protected function CanResolve($route)
    {
        return $this->pattern->Match($route) != null;
    }

    protected function ResolveRoute($route)
    {
        $match = $this->pattern->Match($route);

        $ret = new RouteParameters();
        $ret->route = trim($route, "\\/ ");
        $con = array_key_exists('controller', $match) ? $match['controller'] : $this->defaults['controller'];
        $act = array_key_exists('action', $match) ? $match['action'] : $this->defaults['action'];
        $args = array_key_exists('params', $this->defaults) ? $this->defaults['params'] : array();

        $control = $con.'Controller';

        if (!Router::ControllerExists($control)) {
            Router::$_error = "Controller not found: $con.";
            return null;
        }

        $ret->controller = new $control;
        $ret->controller_name = substr(get_class($ret->controller), 0, -strlen('Controller'));

        $action = Router::GetActionName($ret->controller, $act);

        if ($action == null) {
            Router::$_error = "Action not found: $act.";
            return null;
        }

        $ret->action_name = preg_replace('/^(.*)_Post$/i', '\1', $action);
        $ret->action = $action;

        foreach ($match as $name => $value) {
            if ($name == 'controller' || $name == 'action') continue;

            if ($name == '*') foreach (explode('/', $value) as $split) $args[] = $split;
            else $args[] = $value;
        }

        if ($this->options['params_as_string'] === true) {
            $p = implode('/', $args);
            $args = array($p);
        }

        $num_params = Router::GetActionParams($ret->controller, $ret->action);
        if ($num_params > count($args)) {
            Router::$_error = "Not enough parameters: Required $num_params, got " . count($args) . ".";
            return null;
        }

        $ret->params = $args;

        return $ret;
    }
}

class DefaultRouterPattern
{
    private $map;
    private $regex_groups;
    private $defaults;
    private $match_cache;

    function __construct($map, $defaults)
    {
        $this->map = $map;
        $this->regex_groups = array();
        $this->defaults = $defaults;
        $this->match_cache = array();

        // Map syntax (e.g): {controller}/{action}/{*}
        // Reserved names: {controller}, {action}, {*}
        // Match only actions/controllers that do not exist: {!controller}, {!action}
        $map = trim($map, "\\/ ");
        $check_controller = strstr($map, '{controller}');
        if ($check_controller === false && !array_key_exists('controller', $defaults)) {
            trigger_error('Badly formed route: ' . $map . ' - {controller} must be in the route, or set in the defaults.', E_USER_NOTICE);
            return null;
        }
        $check_action = strstr($map, '{action}');
        if ($check_action === false && !array_key_exists('action', $defaults)) {
            trigger_error('Badly formed route: ' . $map . ' - {action} must be in the route, or set in the defaults.', E_USER_NOTICE);
            return null;
        }
        // $check_catch_all = strstr($map, '{*}', true); // Requires PHP >= 5.3
        $check_catch_all = substr($map, 0, strpos($map, '{*}'));
        if ($check_catch_all !== false && strlen($check_catch_all) != strlen($map) - 3) {
            trigger_error("Badly formed route: " . $map . " - the catch-all {*} must be at the end of the route.", E_USER_NOTICE);
            return null;
        }
        $groups = explode('/', $map);
        $count = 0;
        $regex = '';
        $r_groups = array();
        foreach ($groups as $group) {
            $offset = 0;
            if (strlen($regex) != 0) $regex .= '/';
            while (preg_match('/\{([^}]*)\}/', $group, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                $count++;
                $index = $matches[0][1];
                $length = strlen($matches[0][0]);
                $name = $matches[1][0];
                $regex .= substr($group, $offset, $index - $offset) . ($name == '*' ? "(.*)" : "([^\\\\/]*?)");

                if (strstr($name, '*') === false) {
                    $this->regex_groups = array();
                }

                $actual_name = $name == '*' ? '*' : str_replace('*', '', $name);
                $group_index = $count;

                $r_groups[$group_index] = $actual_name;
                $offset = $index + $length;
            }
            if ($offset < strlen($group)) $regex .= substr($group, $offset);
            if ($offset == 0) $this->regex_groups = array();
            $this->regex_groups[$regex] = $r_groups;
        }
    }

    /**
     * @param $route
     * @return array
     */
    function Match($route)
    {
        $route = trim($route, "\\/ ");
        if (!array_key_exists($route, $this->match_cache)) {
            foreach ($this->regex_groups as $regex => $groups) {
                if (preg_match("%^$regex$%", $route, $result)) {
                    $values = $this->defaults;
                    foreach ($groups as $index => $name) {
                        $actual_name = $name;
                        if ($name == '!action') $actual_name = 'action';
                        if ($name == '!controller') $actual_name = 'controller';
                        $default = array_key_exists($actual_name, $this->defaults) ? $this->defaults[$actual_name] : null;
                        $val = trim($result[$index]);
                        if ($val == null || strlen($val) == 0) $val = $default;
                        $values[$name] = $val;
                    }

                    $nc = array_key_exists('!controller', $values);
                    $na = array_key_exists('!action', $values);

                    $controller = ($nc ? $values['!controller'] : $values['controller']) . 'Controller';
                    $action = $na ? $values['!action'] : $values['action'];

                    $ce = Router::ControllerExists($controller);
                    $ae = Router::ActionExists($controller, $action);

                    if ($ce ? $nc : !$nc) continue;
                    if ($ae ? $na : !$na) continue;

                    $this->match_cache[$route] = $values;
                    break;
                }
            }
        }

        return array_key_exists($route, $this->match_cache) ? $this->match_cache[$route] : null;
    }
}

// These are for backwards compatibility only, do not use

/**
 * @deprecated
 */
class PageActionRouter extends DefaultRouter {
    function __construct($controller, $action = 'Page', $array_params = false) {
        parent::__construct(
            $controller.'/{!action}/{*}',
            array(
                'controller' => $controller,
                'action' => $action
            ), array(
                'params_as_string' => !$array_params
            ));
    }
}

/**
 * @deprecated
 */
class UnknownControllerRouter extends DefaultRouter {
    function __construct($controller, $action, $array_params = false) {
        parent::__construct(
            '{!controller}/{*}',
            array(
                'controller' => $controller,
                'action' => $action
            ), array(
                'params_as_string' => !$array_params
            ));
    }
}

/**
 * @deprecated
 */
class SkippingRouter extends DefaultRouter {
    function __construct($defaults)
    {
        $con = isset($this->defaults['Controller']);
        $act = isset($this->defaults['Action']);
        $def = array();
        if ($con) $def['controller'] = $defaults['Controller'];
        if ($act) $def['action'] = $defaults['Action'];
        parent::__construct(
            trim(($con ? '' : '{controller}') . '/' . ($act ? '' : '{action}') . '/{*}', '/'),
            $def
        );
    }
}

?>
