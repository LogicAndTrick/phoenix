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
     * The controller
     * @var Controller
     */
    public $controller;

    /**
     * The name of the controller
     * @var string
     */
    public $controller_name;
    
    /**
     * The action as a function name in the controller
     * @var string
     */
    public $action;

    /**
     * The action as passed into the route
     * @var string
     */
    public $action_name;
    
    /**
     * The parameters
     * @var array
     */
    public $params = array();

    function Execute()
    {
        $action = $this->action;
        if (Post::IsPostBack()) {
            if (method_exists($this->controller, $action.'_Post')) {
                $action = $action.'_Post';
            }
        }
        $this->controller->BeforeExecute();
        $result = call_user_func_array(array($this->controller, $action), $this->params);
        $this->controller->AfterExecute();
        return $result;
    }

    function GetRouteString()
    {
        return trim($this->action_name . '/' . $this->controller_name . '/' . implode('/', $this->params), '/');
    }
}

?>
