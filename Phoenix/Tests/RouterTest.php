<?php

include "../Framework/RouteParameters.php";
include "../Framework/Router.php";

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultRouterPattern_Initialise()
    {
        $map = "{controller}/{action}/{*}";
        $pattern = new DefaultRouterPattern($map, array());

        $this->assertEquals("regex", $pattern->parts[0]['type']);
        $this->assertEquals("controller", $pattern->parts[0]['name']);
        $this->assertEquals(false, $pattern->parts[0]['match']);

        $this->assertEquals("text", $pattern->parts[1]['type']);
        $this->assertEquals("/", $pattern->parts[1]['text']);
        $this->assertEquals(false, $pattern->parts[1]['match']);

        $this->assertEquals("regex", $pattern->parts[2]['type']);
        $this->assertEquals("action", $pattern->parts[2]['name']);
        $this->assertEquals(true, $pattern->parts[2]['match']);

        $this->assertEquals("text", $pattern->parts[3]['type']);
        $this->assertEquals("/", $pattern->parts[3]['text']);
        $this->assertEquals(false, $pattern->parts[3]['match']);

        $this->assertEquals("regex", $pattern->parts[4]['type']);
        $this->assertEquals("*", $pattern->parts[4]['name']);
        $this->assertEquals(true, $pattern->parts[4]['match']);
    }

    public function testDefaultRouterPattern_Match()
    {
        $map = "{controller}/{action}/{*}";
        $pattern = new DefaultRouterPattern($map, array());
        $match = $pattern->Match("Home/Index/1/2/3");

        $this->assertNotNull($match);
        $this->assertEquals("Home", $match['controller']);
        $this->assertEquals("Index", $match['action']);
        $this->assertEquals("1/2/3", $match['*']);
        $this->assertEquals(array(), $match['*params']);

        $map = "{controller}/{*action}/{*id}/{*page}/{*}";
        $pattern = new DefaultRouterPattern($map, array('action' => 'Index'));
        $match = $pattern->Match("Home/Index/1/2/3");

        $this->assertNotNull($match);
        $this->assertEquals("Home", $match['controller']);
        $this->assertEquals("Index", $match['action']);
        $this->assertEquals("1", $match['id']);
        $this->assertEquals("2", $match['page']);
        $this->assertEquals("3", $match['*']);
        $this->assertEquals(array("1", "2"), $match['*params']);

        $match = $pattern->Match("Home");

        $this->assertNotNull($match);
        $this->assertEquals("Home", $match['controller']);
        $this->assertEquals("Index", $match['action']);
        $this->assertArrayNotHasKey('id', $match);
        $this->assertArrayNotHasKey('page', $match);
        $this->assertArrayNotHasKey('id', $match);
        $this->assertEmpty($match['*params']);
    }

    private function match($map, $defaults, $options, $route, $controller, $action, $params)
    {
        $router = DefaultRouter::Create($map, $defaults, $options);
        $this->assertTrue($router->CanResolve($route));

        $rp = $router->ResolveRoute($route);
        $this->assertNotNull($rp);

        $this->assertEquals($controller, $rp->controller_name);
        $this->assertEquals($action, $rp->action_name);
        $this->assertEquals($params, $rp->params);
    }

    private function notMatch($map, $defaults, $route)
    {
        $router = DefaultRouter::Create($map, $defaults, array());
        $this->assertFalse($router->CanResolve($route));
    }

    public function testBasicRouter_ControllerAction()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Default');
        $options = array();

        // Controller and action required
        $this->match("{controller}/{action}/{*}", $defaults, $options, "Home/Index",       "Home", "Index", array());
        $this->match("{controller}/{action}/{*}", $defaults, $options, "Home/Index/1",     "Home", "Index", array(1));
        $this->match("{controller}/{action}/{*}", $defaults, $options, "Home/Index/1/2",   "Home", "Index", array(1, 2));
        $this->match("{controller}/{action}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array(1, 2, 3));

        $this->notMatch("{controller}/{action}/{*}", $defaults, "");
        $this->notMatch("{controller}/{action}/{*}", $defaults, "Home");
    }

    public function testBasicRouter_Controller()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Default');
        $options = array();

        // Controller required
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home",             "Home", "Default", array());
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home/Index",       "Home", "Index",   array());
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home/Index/1",     "Home", "Index",   array(1));
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home/Index/1/2",   "Home", "Index",   array(1, 2));
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index",   array(1, 2, 3));

        $this->notMatch("{controller}/{action}/{*}", $defaults, "");
    }

    public function testBasicRouter_Nothing()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Default');
        $options = array();

        // Nothing required
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "",                 "Default", "Default", array());
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home",             "Home",    "Default", array());
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home/Index",       "Home",    "Index",   array());
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home/Index/1",     "Home",    "Index",   array(1));
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home/Index/1/2",   "Home",    "Index",   array(1, 2));
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home",    "Index",   array(1, 2, 3));
    }

    public function testBasicRouter_ControllerActionParameter1()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Default');
        $options = array();

        // Controller, action, and one parameter required
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1",     "Home", "Index", array(1));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2",   "Home", "Index", array(1, 2));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array(1, 2, 3));

        $this->notMatch("{controller}/{action}/{id}/{*}", $defaults, "");
        $this->notMatch("{controller}/{action}/{id}/{*}", $defaults, "Home");
        $this->notMatch("{controller}/{action}/{id}/{*}", $defaults, "Home/Index");
    }

    public function testBasicRouter_ControllerActionParameter2()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Default');
        $options = array();

        // Controller, action, and two parameters required
        $this->match("{controller}/{action}/{id}/{page}/{*}", $defaults, $options, "Home/Index/1/2",   "Home", "Index", array(1, 2));
        $this->match("{controller}/{action}/{id}/{page}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array(1, 2, 3));

        $this->notMatch("{controller}/{action}/{id}/{page}/{*}", $defaults, "");
        $this->notMatch("{controller}/{action}/{id}/{page}/{*}", $defaults, "Home");
        $this->notMatch("{controller}/{action}/{id}/{page}/{*}", $defaults, "Home/Index");
        $this->notMatch("{controller}/{action}/{id}/{page}/{*}", $defaults, "Home/Index/1");
    }

    public function testRouterNoCatchAll()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Default');
        $options = array();

        // Nothing
        $this->match("{controller}/{action}", $defaults, $options, "Home/Index", "Home", "Index", array());

        $this->notMatch("{controller}/{action}", $defaults, "Home/Index/1");
        $this->notMatch("{controller}/{action}", $defaults, "Home/Index/1/2");
        $this->notMatch("{controller}/{action}", $defaults, "Home/Index/1/2/3");

        // One param
        $this->match("{controller}/{action}/{id}", $defaults, $options, "Home/Index/1", "Home", "Index", array(1));

        $this->notMatch("{controller}/{action}/{id}", $defaults, "Home/Index");
        $this->notMatch("{controller}/{action}/{id}", $defaults, "Home/Index/1/2");
        $this->notMatch("{controller}/{action}/{id}", $defaults, "Home/Index/1/2/3");

        // Optional params
        $this->match("{controller}/{action}/{*id}/{*page}", $defaults, $options, "Home/Index",     "Home", "Index", array());
        $this->match("{controller}/{action}/{*id}/{*page}", $defaults, $options, "Home/Index/1",   "Home", "Index", array(1));
        $this->match("{controller}/{action}/{*id}/{*page}", $defaults, $options, "Home/Index/1/2", "Home", "Index", array(1, 2));

        $this->notMatch("{controller}/{action}/{*id}/{*page}", $defaults, "Home");
        $this->notMatch("{controller}/{action}/{*id}/{*page}", $defaults, "Home/Index/1/2/3");
    }

    public function testNonSlashyRouter()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Default');
        $options = array();

        // Here's an interesting one - the separator isn't a slash. What will happen??
        $this->match("{controller}-{action}/{*}", $defaults, $options, "Home-Index",       "Home", "Index", array());
        $this->match("{controller}-{action}/{*}", $defaults, $options, "Home-Index/1",     "Home", "Index", array(1));
        $this->match("{controller}-{action}/{*}", $defaults, $options, "Home-Index/1/2",   "Home", "Index", array(1, 2));
        $this->match("{controller}-{action}/{*}", $defaults, $options, "Home-Index/1/2/3", "Home", "Index", array(1, 2, 3));

        $this->notMatch("{controller}-{action}/{*}", $defaults, "");
        $this->notMatch("{controller}-{action}/{*}", $defaults, "Home");
        $this->notMatch("{controller}-{action}/{*}", $defaults, "Home-");
        $this->notMatch("{controller}-{action}/{*}", $defaults, "-Index");
        $this->notMatch("{controller}-{action}/{*}", $defaults, "Home/Index");
    }

    public function testFixedTextRouter()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Default');
        $options = array();

        // Hard-code "Home" and use the default controller instead
        $this->match("Home/{action}/{*}", $defaults, $options, "Home/Index",       "Default", "Index", array());
        $this->match("Home/{action}/{*}", $defaults, $options, "Home/Index/1",     "Default", "Index", array(1));
        $this->match("Home/{action}/{*}", $defaults, $options, "Home/Index/1/2",   "Default", "Index", array(1, 2));
        $this->match("Home/{action}/{*}", $defaults, $options, "Home/Index/1/2/3", "Default", "Index", array(1, 2, 3));

        $this->notMatch("Home/{action}/{*}", $defaults, "");
        $this->notMatch("Home/{action}/{*}", $defaults, "Home");
        $this->notMatch("Home/{action}/{*}", $defaults, "Default/Index");
    }
}
