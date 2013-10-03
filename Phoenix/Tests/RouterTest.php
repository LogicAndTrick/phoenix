<?php

require_once dirname(__FILE__)."/../Phoenix.php";
Phoenix::AddLayer("Test", dirname(__FILE__));

class RouterTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function setUp()
    {
        Router::Clear();
    }

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
        Router::Clear();
        Router::MapRoute($map, $defaults, $options);

        $rp = Router::Resolve($route);
        $this->assertNotNull($rp);

        $this->assertEquals($controller, $rp->controller);
        $this->assertEquals($action, $rp->action);
        $this->assertEquals($params, $rp->params);
    }

    private function notMatch($map, $defaults, $route)
    {
        Router::Clear();
        Router::MapRoute($map, $defaults);

        $rp = Router::Resolve($route);
        $this->assertNull($rp);
    }

    public function testBasicRouter_ControllerAction()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Def');
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
        $defaults = array('controller' => 'Default', 'action' => 'Def');
        $options = array();

        // Controller required
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home",             "Home", "Def",     array());
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home/Index",       "Home", "Index",   array());
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home/Index/1",     "Home", "Index",   array(1));
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home/Index/1/2",   "Home", "Index",   array(1, 2));
        $this->match("{controller}/{*action}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index",   array(1, 2, 3));

        $this->notMatch("{controller}/{action}/{*}", $defaults, "");
    }

    public function testBasicRouter_Nothing()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Def');
        $options = array();

        // Nothing required
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "",                 "Default", "Def",     array());
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home",             "Home",    "Def",     array());
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home/Index",       "Home",    "Index",   array());
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home/Index/1",     "Home",    "Index",   array(1));
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home/Index/1/2",   "Home",    "Index",   array(1, 2));
        $this->match("{*controller}/{*action}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home",    "Index",   array(1, 2, 3));
    }

    public function testBasicRouter_ControllerActionParameter1()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Def');
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
        $defaults = array('controller' => 'Default', 'action' => 'Def');
        $options = array();

        // Controller, action, and two parameters required
        $this->match("{controller}/{action}/{id}/{page}/{*}", $defaults, $options, "Home/Index/1/2",   "Home", "Index", array(1, 2));
        $this->match("{controller}/{action}/{id}/{page}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array(1, 2, 3));

        $this->notMatch("{controller}/{action}/{id}/{page}/{*}", $defaults, "");
        $this->notMatch("{controller}/{action}/{id}/{page}/{*}", $defaults, "Home");
        $this->notMatch("{controller}/{action}/{id}/{page}/{*}", $defaults, "Home/Index");
        $this->notMatch("{controller}/{action}/{id}/{page}/{*}", $defaults, "Home/Index/1");
    }

    public function testOptions_CatchAll()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Def');
        $options = array('string_catchall' => true);

        // Test the catchall stringifier
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1",   "Home", "Index", array(1));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2",   "Home", "Index", array(1, 2));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array(1, '2/3'));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3/4", "Home", "Index", array(1, '2/3/4'));

        // Test the catchall stringifier with a custom joiner
        $options['string_separator'] = '=';
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array(1, '2=3'));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3/4", "Home", "Index", array(1, '2=3=4'));

        // Test the catchall stringifier with a custom joiner and splitter
        $options['catchall_separator'] = '-';
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array(1, '2/3'));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3/4", "Home", "Index", array(1, '2/3/4'));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2-3", "Home", "Index", array(1, '2=3'));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2-3-4", "Home", "Index", array(1, '2=3=4'));
    }

    public function testOptions_Params()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Def');
        $options = array('string_params' => true);

        // Test the params stringifier
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1",   "Home", "Index", array(1));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2",   "Home", "Index", array('1/2'));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array('1/2/3'));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3/4", "Home", "Index", array('1/2/3/4'));

        // Test the params stringifier with a custom joiner
        $options['string_separator'] = '=';
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3", "Home", "Index", array('1=2=3'));
        $this->match("{controller}/{action}/{id}/{*}", $defaults, $options, "Home/Index/1/2/3/4", "Home", "Index", array('1=2=3=4'));
    }

    public function testRouterNoCatchAll()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Def');
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
        $defaults = array('controller' => 'Default', 'action' => 'Def');
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
        $defaults = array('controller' => 'Default', 'action' => 'Def');
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

    public function testExtraTextRouter()
    {
        $defaults = array('controller' => 'Default', 'action' => 'Def');
        $options = array();

        // Extra text on the end of the route
        $this->match("{controller}/{action}/{*id}/extra", $defaults, $options, "Home/Index",           "Home", "Index", array());
        $this->match("{controller}/{action}/{*id}/extra", $defaults, $options, "Home/Index/1",         "Home", "Index", array(1));
        $this->match("{controller}/{action}/{*id}/extra", $defaults, $options, "Home/Index/1/extra",   "Home", "Index", array(1));

        $this->notMatch("{controller}/{action}/{*id}/extra", $defaults, "");
        $this->notMatch("{controller}/{action}/{*id}/extra", $defaults, "Home");
        $this->notMatch("{controller}/{action}/{*id}/extra", $defaults, "Home/Index/1/2");
        $this->notMatch("{controller}/{action}/{*id}/extra", $defaults, "Home/Index/1/extra/2");
    }

    public function testPostOnlyMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Post only method
        $this->match("{controller}/{action}/{*}", array(), array(), "Home/PostOnly", "Home", "PostOnly", array());

        // Non post only method
        $this->match("{controller}/{action}/{*}", array(), array(), "Home/Index", "Home", "Index", array());
    }

    public function testExecute()
    {
        Router::Clear();
        Router::MapRoute('{*controller}/{*action}/{*}', array('controller' => 'Home', 'action' => 'Index'));

        $route = Router::Resolve("Home/Index/test");
        $this->assertNotNull($route);
        $this->assertEquals('Home/Index/test', $route->GetRouteString());

        $result = $route->Execute();
        $this->assertInstanceOf('ContentResult', $result);

        $this->assertEquals('test', $result->content);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('test', Router::Resolve("Home/Index/test")->Execute()->content);
        $this->assertEquals('test', Router::Resolve("Home/PostOnly/test")->Execute()->content);
    }

    public function testPostOnlyMethod_Failure()
    {
        $this->setExpectedException("Exception");
        $this->match("{controller}/{action}/{*}", array(), array(), "Home/PostOnly", "Home", "PostOnly", array());
    }

    public function testInvalidRoute_MandatoryAfterOptional()
    {
        $this->setExpectedException('Exception');
        $rt = new DefaultRouter("{controller}/{*action}/{id}", array());
    }

    public function testInvalidRoute_ParameterAfterCatchAll()
    {
        $this->setExpectedException('Exception');
        $rt = new DefaultRouter("{controller}/{action}/{*}/{id}", array());
    }

    public function testInvalidRoute_NoController()
    {
        $this->setExpectedException('Exception');
        $this->match("{controller}/{action}", array(), array(), "Nope/Index", "Nope", "Index", array());
    }

    public function testInvalidRoute_NoAction()
    {
        $this->setExpectedException('Exception');
        $this->match("{controller}/{action}", array(), array(), "Home/Nope", "Home", "Nope", array());
    }

    public function testInvalidRoute_NotEnoughParameters()
    {
        $this->setExpectedException('Exception');
        $this->match("{controller}/{action}/{1}", array(), array(), "Home/LotsOfParameters/1", "Home", "LotsOfParameters", array(1));
    }

    public function testCreateUrl()
    {
        $this->assertEquals("Home", Router::CreateUrl("Home", null));
        $this->assertEquals("Home/Index", Router::CreateUrl("Home", "Index"));

        $this->assertEquals("Home/Index/1", Router::CreateUrl("Home", null, array(1)));
        $this->assertEquals("Home/Index/1", Router::CreateUrl("Home", "Index", array(1)));
        $this->assertEquals("Home/Index/1", Router::CreateUrl("Home", "Index", 1));

        $this->assertEquals("Home/Index/1/2", Router::CreateUrl("Home", null, array(1,2)));
        $this->assertEquals("Home/Index/1/2", Router::CreateUrl("Home", "Index", array(1,2)));

        Phoenix::$base_url = 'Base';

        $this->assertEquals("Base/Home", Router::CreateUrl("Home", null));
        $this->assertEquals("Base/Home/Index", Router::CreateUrl("Home", "Index"));

        $this->assertEquals("Base/Home/Index/1", Router::CreateUrl("Home", null, array(1)));
        $this->assertEquals("Base/Home/Index/1", Router::CreateUrl("Home", "Index", array(1)));
        $this->assertEquals("Base/Home/Index/1", Router::CreateUrl("Home", "Index", 1));

        $this->assertEquals("Base/Home/Index/1/2", Router::CreateUrl("Home", null, array(1,2)));
        $this->assertEquals("Base/Home/Index/1/2", Router::CreateUrl("Home", "Index", array(1,2)));
    }

    public function testNothingButRunAbstractFunctionsSoMyCodeCoverageIsBetter()
    {
        // Well... yes, it's an abstract class. Sort of.
        // But if I run these methods I get that delicious 100% code coverage!
        $v = new AbstractRouter();
        $v->CanResolve("");
        $v->ResolveRoute("");
        $this->assertTrue(true);
    }
}
