<?php
require_once("../src/lib/toeprint/toeprint.php");
require_once("../src/lib/toeprint/mvc/toeprint.mvc.php");
class CoreTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    public function testOther()
    {
        // Test Single Slug
        $this->assertEquals('test-this-slug', tp::slug('test this slug'));
        // Test Array of Slugs
        $slugmap = array(
            'slug-test-1' => 'Slug Test 1!',
            'slug-test-2' => '(Slug test #2)',
            'slug-test-3' => 'Slug.Test.3'
        );
        $slugs = tp::slug($slugmap);
        foreach($slugmap as $cslug => $title) {
            $slug = array_shift($slugs);
            $this->assertEquals($slug, $cslug);
        }

        // Test Request
        $this->assertFalse(tp::request());

        // Test mobile, tablet
        $mobile = tp::mobile();
        $this->assertFalse(tp::isMobile());
        $this->assertFalse(tp::isTablet());
        $this->assertTrue(tp::isDesktop());
    }

    public function testRouter()
    {
        $router = tp::router();
        $router->register('/test/*', array($this, '_routeAsterisk'));
        $router->register('/test/1', array($this, '_routeNumeric'));
        $router->register('/test', array($this, '_routeNull'));

        $result = false;
        try {
            $result = $router->route(false, array('test', 'asdf'));
        } catch (Exception $e) {
        }
        assert(($result == '*'));

        $result = false;
        try {
            $result = $router->route(false, array('test', '1'));
        } catch (Exception $e) {
        }
        assert(($result == 1));

        $result = false;
        try {
            $result = $router->route(false, array('test'));
        } catch (Exception $e) {
        }
        assert(($result == null));
    }

    public function testPDO()
    {
        $pdo = tp::pdo('mysql', 'localhost', 'toeprint_test', 'toeprint_test', 'toeprint_test');
        $rows = $pdo->fetch('test');
        $this->assertInstanceOf('toeprint_PDO_ResultSet', $rows);
        $this->assertCount(3, $rows->results());
    }

    public function testTemplate()
    {
        $template = tp::template(dirname(__FILE__) . '/resources/template.phtml', array('val' => true));
        $json = json_decode($template->render(true),true);
        $this->assertTrue($json['test']);
    }

    public function _routeAsterisk()
    {
        return '*';
    }

    public function _routeNumeric()
    {
        return 1;
    }

    public function _routeNull()
    {
        return null;
    }
}


?>