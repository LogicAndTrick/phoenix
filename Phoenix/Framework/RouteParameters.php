<?php

/**
 * The result of a fully resolved route.
 */
class RouteParameters {

    /**
     * The route that has been resolved
     * @var string
     */
    public $route;

    /**
     * The name of the controller
     * @var string
     */
    public $controller;
    
    /**
     * The action as passed into the route
     * @var string
     */
    public $action;

    /**
     * The controller instance
     * @var Controller
     */
    public $controller_instance;

    /**
     * The action as a function name in the controller
     * @var string
     */
    public $action_function;
    
    /**
     * The parameters
     * @var array
     */
    public $params = array();

    function Execute()
    {
        $class = $this->controller . 'Controller';
        $this->controller_instance = new $class;

        $action = $this->action;
        if (Post::IsPostBack()) {
            if (method_exists($this->controller_instance, $action.'_Post')) {
                $action = $action.'_Post';
            }
        }
        $this->action_function = $action;

        $this->controller_instance->BeforeExecute();
        $result = call_user_func_array(array($this->controller_instance, $action), $this->params);
        $this->controller_instance->AfterExecute();
        return $result;
    }

    function GetRouteString()
    {
        return trim($this->controller . '/' . $this->action . '/' . implode('/', $this->params), '/');
    }
}

?>
