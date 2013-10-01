<?php

class Templating
{

    static function MakeDirs()
    {
        if (!file_exists(Phoenix::$app_dir.'/Cache/')) mkdir(Phoenix::$app_dir.'/Cache/');
        if (!file_exists(Phoenix::$app_dir.'/Cache/Configs/')) mkdir(Phoenix::$app_dir.'/Cache/Configs/');
        if (!file_exists(Phoenix::$app_dir.'/Cache/Compile/')) mkdir(Phoenix::$app_dir.'/Cache/Compile/');
        if (!file_exists(Phoenix::$app_dir.'/Cache/Cache/')) mkdir(Phoenix::$app_dir.'/Cache/Cache/');
    }
    /**
     * Create a Smarty instance with the required directories set up.
     * @return Smarty The created Smarty instance
     */
    static function Create()
    {
        Templating::MakeDirs();
        
        $smarty = new Smarty();
        $smarty->addTemplateDir(Phoenix::$app_dir.'/Views/');
        $smarty->addTemplateDir(Phoenix::$phoenix_dir.'/Views/');
        $smarty->setConfigDir(Phoenix::$app_dir.'/Cache/Configs/');
        $smarty->setCompileDir(Phoenix::$app_dir.'/Cache/Compile/');
        $smarty->setCacheDir(Phoenix::$app_dir.'/Cache/Cache/');
        $smarty->addPluginsDir(Phoenix::$phoenix_dir.'/Libs/Smarty.Phoenix');
        if (is_dir(Phoenix::$app_dir.'/Plugins'))
        {
            $smarty->addPluginsDir(Phoenix::$app_dir.'/Plugins');
        }
        $smarty->assign(Templating::$page_data);
        return $smarty;
    }

    /**
     * The name of the master page to use.
     * @var string
     */
    static $master_page = 'Shared/Master';

    /**
     * The master page data that will be rendered.
     * @var array
     */
    static $page_data = array();

    /**
     * Placeholder data set by {content} tags and retrieved by {placeholder} tags.
     * @var array
     */
    static $placeholder_data = array();
    
    /**
     * Sets the default page data before the request has been executed.
     */
    static function SetDefaults($request) {
        Templating::$page_data['page_title'] = $request == null ? '' : $request->controller_name . ' > ' . $request->action;
        Templating::$page_data['request_controller'] = $request == null ? '' : $request->controller_name;
        Templating::$page_data['request_action'] = $request == null ? '' : $request->action;
        Templating::$page_data['request_params'] = $request == null ? '' : $request->params;
    }

    static function SetPageTitle($title) {
        Templating::$page_data['page_title'] = $title;
    }

    /**
     * Adds one or more key/value pairs into the page data array
     * @param array|string $key If an array, all the key/value pairs in the array will be used. Otherwise, the value will be added with this key
     * @param object $value If key is not an array, this is the value that will be inserted into the page data
     * @return void
     */
    static function SetPageData($key, $value = null) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Templating::$page_data[$k] = $v;
            }
        } else {
            Templating::$page_data[$key] = $value;
        }
    }

    static function Fetch()
    {
        $master_page = Templating::$master_page;
        if (substr($master_page, 0, -4) != '.tpl') {
            $master_page .= '.tpl';
        }
        $master = Templating::Create();
        $master->assign(Templating::$page_data);
        Hooks::ExecuteRenderHooks($master);
        $master->assign('phoenix_debug', Phoenix::GetDebugInfo());
        return $master->fetch($master_page);
    }

    static function Render()
    {
        $master_page = Templating::$master_page;
        if (substr($master_page, 0, -4) != '.tpl') {
            $master_page .= '.tpl';
        }
        $master = Templating::Create();
        $master->assign(Templating::$page_data);
        Hooks::ExecuteRenderHooks($master);
        $master->assign('phoenix_debug', Phoenix::GetDebugInfo());
        $master->display($master_page);
    }
}

?>
