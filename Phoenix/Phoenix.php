<?php

/// PHOENIX MVC
/// VERSION 0.3
/// http://logic-and-trick.com

// Third party libs
include 'Libs/Smarty/Smarty.class.php';
include 'Libs/LightOpenID/LightOpenID.php';
include 'Libs/Recaptcha/recaptchalib.php';

// Phoenix
include 'Framework/AutoLoad.php';
include 'Framework/ExceptionHandler.php';
include 'Framework/Headers.php';

include 'Framework/Database.php';
include 'Framework/Model.php';
include 'Framework/Query.php';
include 'Framework/Validation.php';

include 'Framework/Post.php';
include 'Framework/Authentication.php';
include 'Framework/Authorisation.php';

include 'Framework/Router.php';
include 'Framework/RouteParameters.php';
include 'Framework/Hooks.php';

include 'Framework/Controller.php';
include 'Framework/ActionResult.php';

include 'Framework/Views.php';
include 'Framework/Templating.php';

class Phoenix {
    /**
     * The directory holding the Phoenix.php file.
     * Used internally, do not change.
     * @var string
     */
    static $phoenix_dir;

    /**
     * The directory holding the application's file structure.
     * This is the directory containing the 'Controllers', 'Views',
     * and 'Models' directories.
     * @var string
     */
    static $app_dir;

    /**
     * The url containing the index.php script. Used for linking
     * in the template functions. Ends in a slash.
     * @var string
     */
    static $base_url;

    /**
     * The route parameters associated with the current request.
     * @var RouteParameters
     */
    static $request;

    /**
     * The action result returned after the current request has executed.
     * @var ActionResult
     */
    static $result;

    /**
     * Set to true to enable debugging output.
     * @var bool
     */
    static $debug;

    /**
     * Limit the debug output to a specific username.
     * @var string
     */
    static $debug_user;

    /**
     * Phoenix's internal database logger.
     * @var MemoryLogger
     */
    private static $_dblog;

    /**
     * The error controller (without the 'Controller' suffix). Default is 'Error'.
     * @var string
     */
    static $error_controller = 'Error';

    /**
     * The error action. Default is 'Index'.
     * @var string
     */
    static $error_action = 'Index';

    /**
     * The registered layers in the application
     * @var array
     */
    static $_layers = array();

    /**
     * Initialise the framework. Called internally, do not use.
     */
    static function Init()
    {
        Phoenix::$phoenix_dir = dirname(__FILE__);
        Phoenix::$app_dir = Phoenix::$phoenix_dir . '/../App';
        Phoenix::$debug = false;
        Phoenix::$_dblog = new MemoryLogger();
        Database::AddLogger(Phoenix::$_dblog);
        Phoenix::AddLayer('Framework', Phoenix::$phoenix_dir . DS . 'Layers' . DS . 'Core');
    }

    /**
     * Add a layer to the runtime.
     * @param $path string The file path of the layer to add.
     */
    static function AddLayer($name, $path)
    {
        if (array_search($path, Phoenix::$_layers) !== false) return;
        Phoenix::$_layers[] = array('name' => $name, 'dir' => $path);
    }

    /**
     * Run the framework
     */
    static function Run()
    {
        Phoenix::AddLayer('App', Phoenix::$app_dir);

        $route = array_key_exists('phoenix_route', $_GET) ? $_GET['phoenix_route'] : '';
        Hooks::ExecuteRouteHooks($route);

        Phoenix::$request = Router::Resolve($route);
        Hooks::ExecuteRequestHooks(Phoenix::$request);

        Phoenix::$result = Phoenix::$request->Execute();
        Hooks::ExecuteResultHooks(Phoenix::$result);

        Phoenix::$result->Execute();
    }

    public static function GetDebugInfo()
    {
        if (Phoenix::$debug && (Phoenix::$debug_user == null || Phoenix::$debug_user == Authentication::GetUserName())) {
            $debug = Templating::Create();
            $debug->assign('queries', Phoenix::$_dblog->queries);
            return $debug->fetch(Views::Find('Debug/Debug'));
        } else {
            return null;
        }
    }
}

// Init the framework before any variables are set, because
// this sets some defaults.
Phoenix::Init();

?>