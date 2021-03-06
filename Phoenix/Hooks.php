<?php

class Hooks {
    private static $_route = array();
    private static $_request = array();
    private static $_result = array();
    private static $_render = array();

    public static function RegisterRouteHook($hook)
    {
        Hooks::$_route[] = $hook;
    }

    public static function RegisterRequestHook($hook)
    {
        Hooks::$_request[] = $hook;
    }

    public static function RegisterResultHook($hook)
    {
        Hooks::$_result[] = $hook;
    }

    public static function RegisterRenderHook($hook)
    {
        Hooks::$_render[] = $hook;
    }

    public static function ExecuteRouteHooks($route)
    {
        foreach (Hooks::$_route as $hook)
        {
            $hook->Execute($route);
        }
    }

    public static function ExecuteRequestHooks($request)
    {
        foreach (Hooks::$_request as $hook)
        {
            $hook->Execute($request);
        }
    }

    public static function ExecuteResultHooks($result)
    {
        foreach (Hooks::$_result as $hook)
        {
            $hook->Execute($result);
        }
    }

    public static function ExecuteRenderHooks($result)
    {
        foreach (Hooks::$_render as $hook)
        {
            $hook->Execute($result);
        }
    }
}

Hooks::RegisterRequestHook(new CheckErrorsRequestHook());
Hooks::RegisterRequestHook(new SetTemplatingDefaultsRequestHook());

class Hook {
    function Execute($item)
    {

    }
}

class CheckErrorsRequestHook extends Hook {
    function Execute($request)
    {
        $error = null;
        if ($request == null) {
            Phoenix::$request = new RouteParameters();
            $error = array(Router::$_error);
        }
        if (Authentication::IsCurrentUserBanned())
        {
            if (Phoenix::$request->controller_name != Authentication::$ban_controller
                || Phoenix::$request->action_name != Authentication::$ban_action)
            {
                $cname = Authentication::$ban_controller . 'Controller';
                Phoenix::$request->action = Authentication::$ban_action;
                Phoenix::$request->action_name = Authentication::$ban_action;
                Phoenix::$request->controller_name = Authentication::$ban_controller;
                Phoenix::$request->params = array();
                Phoenix::$request->controller = new $cname();
                return;
            }
        }
        if (!Authorisation::Check($request)) {
            $error = array("You do not have permission to access this page.");
        }
        if ($error != null) {
            $cname = Phoenix::$error_controller . 'Controller';
            Phoenix::$request->action = Phoenix::$error_action;
            Phoenix::$request->action_name = Phoenix::$error_action;
            Phoenix::$request->controller_name = Phoenix::$error_controller;
            Phoenix::$request->params = $error;
            Phoenix::$request->controller = new $cname();
        }
    }
}

class SetTemplatingDefaultsRequestHook extends Hook {
    function Execute($request)
    {
        Templating::SetDefaults($request);
    }
}

?>