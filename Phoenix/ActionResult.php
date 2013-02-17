<?php

class ActionResult
{
    function Execute()
    {
        
    }
}

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

class ViewHierarchyResult extends ActionResult
{
    public $hierarchy;
    public $viewdata;

    function __construct($hierarchy, $viewdata)
    {
        $this->hierarchy = $hierarchy;
        $this->viewdata = $viewdata;
    }

    /**
     * Process a view hierachy and return the resulting content string
     * @param array $tree
     * @return string The content string
     */
    private function ProcessHierachy($tree)
    {
        $view = Templating::Create();
        $tname = Views::Find($tree[':view']);
        foreach ($tree as $name => $params) {
            if ($name == ':view') {
                continue;
            } else if (is_array($params) && array_key_exists(':view', $params)) {
                $view->assign($name, $this->ProcessHierachy($params));
            } else {
                $view->assign($name, $params);
            }
        }
        return $view->fetch($tname);
    }

    function Execute()
    {
        foreach ($this->hierarchy as $name => $params) {
            if (is_array($params) && array_key_exists(':view', $params)) {
                Templating::$page_data[$name] = $this->ProcessHierachy($params);
            } else {
                Templating::$page_data[$name] = $params;
            }
        }
        Templating::Render();
    }
}

class MultiViewResult extends ActionResult
{
    public $ntvma;
    public $viewdata;

    function  __construct($name_to_view_model_array, $viewdata)
    {
        $this->ntvma = $name_to_view_model_array;
        $this->viewdata = $viewdata;
    }

    function Execute()
    {
        Templating::SetPageData($this->viewdata);
        foreach ($this->ntvma as $name => $view_model) {
            Templating::SetPageData('model', $view_model['model']);
            $view = Templating::Create();
            Templating::$page_data[$name] = $view->fetch($view_model['view']);
            Templating::Render();
        }
    }
}

?>
