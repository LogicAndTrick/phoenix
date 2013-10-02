<?php

class Controller {

    public $authorise;
    public $viewData;

    function BeforeExecute()
    {
        
    }

    function AfterExecute()
    {
        
    }

    /**
     * Return a result containing content only. Content will
     * be echoed to the screen with no other processing.
     * @param string $content
     * @return ContentResult
     */
    function Content($content)
    {
        $ar = new ContentResult($content);
        return $ar;
    }

    /**
     * Return a result containing a serialised JSON object. The JSON will
     * be echoed to the screen and the content-type will be set to application/json.
     * @param string $obj
     * @return JsonResult
     */
    function Json($obj)
    {
        $ar = new JsonResult($obj);
        return $ar;
    }

    /**
     * Returns a result that redirects to an action in a controller.
     * @param string $action The action to redirect to
     * @param string $controller The controller to redirect to (null for the current controller)
     * @param array $params The parameters to pass to the redirected route
     * @return RedirectToActionResult
     */
    function RedirectToAction($action, $controller = null, $params = array())
    {
        if ($controller == null) {
            $controller = Phoenix::$request->controller;
        }
        if (!is_array($params)) {
            $params = array($params);
        }
        $ar = new RedirectToActionResult($action, $controller, $params);
        return $ar;
    }

    /**
     * Returns a result that redirects to a specified route.
     * @param string $route The route (as a string) to redirect to
     * @return RedirectToRouteResult
     */
    function RedirectToRoute($route)
    {
        $ar = new RedirectToRouteResult($route);
        return $ar;
    }

    /**
     * Returns a result that redirects to a URL.
     * @param string $url The URL to redirect to
     * @return RedirectToUrlResult
     */
    function RedirectToUrl($url)
    {
        $ar = new RedirectToUrlResult($url);
        return $ar;
    }

    /**
     * Return a result containing a view. Content will be
     * processed with Smarty before being output.
     * @param object $model The model to use
     * @param string $view The name of the view to use
     * @param string $name The name of the content placeholder in the master page
     * @param boolean $cache True to cache the results of the result
     * @return ViewResult
     */
    function View($model = null, $view = null, $name = 'content', $cache = true)
    {
        $view = Views::Find($view);
        $ar = new ViewResult($name, $view, $model, $this->viewData, $cache);
        return $ar;
    }

    /**
     * Return a result containing a render command. The supplied
     * view will be rendered directly, no master page will be used.
     * @param object $model The model to use
     * @param string $view The name of the view to use
     * @return RenderResult
     */
    function Render($model = null, $view = null)
    {
        $view = Views::Find($view);
        $ar = new RenderResult($view, $model, $this->viewData);
        return $ar;
    }

    function ViewHierarchy($hierarchy)
    {
        $ar = new ViewHierarchyResult($hierarchy, $this->viewData);
        return $ar;
    }

    function MultiView($array)
    {
        foreach ($array as $name => $vm) {
            $array[$name]['view'] = Views::Find($array[$name]['view']);
        }
        $ar = new MultiViewResult($array, $this->viewData);
        return $ar;
    }
}

?>
