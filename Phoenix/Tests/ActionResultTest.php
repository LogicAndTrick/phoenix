<?php

require_once dirname(__FILE__)."/../Phoenix.php";
Phoenix::AddLayer("Test", dirname(__FILE__));

class MockHeaders {
    public $headers = array();
    public function AddHeader($header) { $this->headers[] = $header; }
}

class ActionResultTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Headers::$impl = new MockHeaders();
    }

    public static function tearDownAfterClass()
    {
        Headers::$impl = null;
    }

    public function setUp()
    {
        Headers::$impl->headers = array();
    }

    public function testEmpty()
    {
        // Coverage!
        $a = new ActionResult();
        $a->Execute();
        $this->assertTrue(true);
    }

    public function testContent()
    {
        $this->expectOutputString('Test');
        $a = new ContentResult("Test");
        $a->Execute();
    }

    public function testJson()
    {
        $this->expectOutputString('{"a":"b"}');
        $a = new JsonResult(array('a'=>'b'));
        $a->Execute();
        $this->assertEquals(array("Content-Type: application/json"), Headers::$impl->headers);
    }

    public function testRedirectToAction()
    {
        $a = new RedirectToActionResult('Index', 'Home', array(1));
        $a->Execute();
        $this->assertEquals(array("Location: Home/Index/1"), Headers::$impl->headers);
    }

    public function testRedirectToRoute()
    {
        Phoenix::$base_url = 'Base';
        $a = new RedirectToRouteResult('/Home/Index/1');
        $a->Execute();
        $this->assertEquals(array("Location: Base/Home/Index/1"), Headers::$impl->headers);
        Phoenix::$base_url = '';
    }

    public function testRedirectToUrl()
    {
        $a = new RedirectToUrlResult('http://example.com');
        $a->Execute();
        $this->assertEquals(array("Location: http://example.com"), Headers::$impl->headers);
    }

    public function testView()
    {
        Phoenix::$request = new RouteParameters();
        Phoenix::$request->controller = 'Home';
        Phoenix::$request->action = 'ShowView';
        $this->expectOutputString('Master: View');
        $a = new ViewResult('content', Views::Find('Home/ShowView'), 'View', array(), true);
        $a->Execute();
    }

    public function testRender()
    {
        Phoenix::$request = new RouteParameters();
        Phoenix::$request->controller = 'Home';
        Phoenix::$request->action = 'Index';
        $this->expectOutputString('View');
        $a = new RenderResult(Views::Find('Home/ShowView'), 'View', array());
        $a->Execute();
    }
}
 