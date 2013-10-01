<?php

/**
 * Base class for all action results. Contains a single method: Execute()
 */
class ActionResult
{
    function Execute()
    {
        
    }
}

/**
 * Echos an object and completes execution.
 */
class ContentResult extends ActionResult
{

    public $content;

    function  __construct($content)
    {
        $this->content = $content;
    }

    function Execute()
    {
        echo $this->content;
    }
}

/**
 * Echos an object as a JSON string and completes execution. Sets the Content-Type to application/json.
 */
class JsonResult extends ActionResult
{

    public $obj;

    function  __construct($obj)
    {
        $this->obj = $obj;
    }

    function Execute()
    {
        header('Content-Type: application/json');
        echo json_encode($this->obj);
    }
}

/**
 * Redirects to a controller action with a 302 redirect.
 */
class RedirectToActionResult extends ActionResult
{
    public $action;
    public $controller;
    public $params;

    function  __construct($act, $con, $arg) {
        $this->action = $act;
        $this->controller = $con;
        $this->params = $arg;
    }

    function Execute()
    {
        $url = Router::CreateUrl($this->controller, $this->action, $this->params);
        header("Location: $url");
        exit();
    }
}

/**
 * Redirects to a route with a 302 redirect.
 */
class RedirectToRouteResult extends ActionResult
{
    public $route;

    function  __construct($rt) {
        $this->route = $rt;
    }

    function Execute()
    {
        $url = trim(Phoenix::$base_url, '/') . '/' . trim($this->route, '/');
        header("Location: $url");
        exit();
    }
}

/**
 * Redirects to a url with a 302 redirect.
 */
class RedirectToUrlResult extends ActionResult
{
    public $url;

    function  __construct($rt) {
        $this->url = $rt;
    }

    function Execute()
    {
        $url = $this->url;
        header("Location: $url");
        exit();
    }
}

/**
 * The main action result. Uses templating to render a view in the master page.
 * The view is passed a model and additional viewdata from the controller.
 * Optionally, fetches the result from the cache.
 */
class ViewResult extends ActionResult
{
    public $name;
    public $view;
    public $model;
    public $viewdata;
    public $cache;

    function  __construct($name, $view, $model, $viewdata, $cache)
    {
        $this->name = $name;
        $this->view = $view;
        $this->model = $model;
        $this->viewdata = $viewdata;
        $this->cache = $cache;
    }

    function Execute()
    {
        Templating::SetPageData('model', $this->model);
        Templating::SetPageData($this->viewdata);
        $view = Templating::Create();
        Templating::$page_data[$this->name] = $this->cache !== false
                ? Cache::FetchView(Phoenix::$request->route, $view, $this->view)
                : $view->fetch($this->view);
        Templating::Render();
    }
}

/**
 * Same as the view result, but the result is rendered with no master page.
 */
class RenderResult extends ActionResult
{
    public $view;
    public $model;
    public $viewdata;

    function  __construct($view, $model, $viewdata)
    {
        $this->view = $view;
        $this->model = $model;
        $this->viewdata = $viewdata;
    }

    function Execute()
    {
        Templating::SetPageData('model', $this->model);
        Templating::SetPageData($this->viewdata);
        $view = Templating::Create();
        $view->display($this->view);
    }
}

?>
