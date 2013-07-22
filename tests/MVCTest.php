<?php
require_once("../src/lib/toeprint/toeprint.php");
require_once("../src/lib/toeprint/mvc/toeprint.mvc.php");
class MVCTest extends PHPUnit_Framework_TestCase
{
    private $mvc;
    public function setUp() {
        $this->mvc = new toeprint_MVCApp();
    }
    public function testMVCApp()
    {
        $this->mvc = new toeprint_MVCApp();
        $this->mvc->pdo();
    }
}


?>