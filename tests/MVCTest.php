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

        $this->assertEquals(0, $this->ba->getBalance());
        $this->ba->depositMoney(1);
        $this->assertEquals(1, $this->ba->getBalance());
        $this->ba->withdrawMoney(1);
        $this->assertEquals(0, $this->ba->getBalance());

    }
}

?>