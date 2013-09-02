<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL ^ E_NOTICE);

define('TOEPRINT_ROOT_PATH', realpath(dirname(__FILE__) . '/../../'));
define('TOEPRINT_ROOT_URL', '/');
define('TOEPRINT_INC_PATH', TOEPRINT_ROOT_PATH . '/inc');
define('TOEPRINT_LIB_PATH',TOEPRINT_ROOT_PATH.'/src/lib');
define('TOEPRINT_VIEW_PATH',TOEPRINT_ROOT_PATH.'/views');
define('TOEPRINT_VIEW_URL',TOEPRINT_ROOT_URL.'/views');
define('TOEPRINT_SCRIPT_URL',TOEPRINT_ROOT_URL.'/scripts');
define('TOEPRINT_AUTOGLOBAL',true);

require_once(TOEPRINT_ROOT_PATH . '/src/lib/toeprint/toeprint.php');
require_once(TOEPRINT_ROOT_PATH . '/src/lib/toeprint/mvc/toeprint.mvc.php');

try {
    $config = tp::config(dirname(__FILE__) . '/config.json');
    $app = new toeprint_MVCApp($config);



    $app->route();
} catch(Exception $e) { echo $e->getMessage(); }