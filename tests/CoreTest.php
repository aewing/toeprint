<?php
require_once("../src/lib/toeprint/toeprint.php");
require_once("../src/lib/toeprint/mvc/toeprint.mvc.php");
class CoreTest extends PHPUnit_Framework_TestCase
{
    public function setUp() { }
    public function testRouter()
    {
        $router = tp::router();
        $router->register('/test/*', array($this, '_routeAsterisk'));
        $router->register('/test/1', array($this, '_routeNumeric'));
        $router->register('/test', array($this, '_routeNull'));

        $result = $router->route(array('test', 'asdf'));
        assert(($result == '*'));

        $result = $router->route(array('test', '1'));
        assert(($result == 1));

        $result = $router->route(array('test'));
        assert(($result == null));
    }
    public function _routeAsterisk() {
        return '*';
    }
    public function _routeNumeric() {
        return 1;
    }
    public function _routeNull() {
        return null;
    }
}


?>