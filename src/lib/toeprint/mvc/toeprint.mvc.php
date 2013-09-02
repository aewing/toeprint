<?php
/**
 * Toeprint Framework v0.1a - ( http://toeprint.phenocode.com/ )
 * Copyright (C) 2013 Drew Ewing
 * Unless explicitly acquired and licensed from Licensor under another license, the contents of this file are subject
 * to the Reciprocal Public License ("RPL") Version 1.5, or subsequent versions as allowed by the RPL, and You may not
 * copy or use this file in either source code or executable form, except in compliance with the terms and conditions
 * of the RPL. All software distributed under the RPL is provided strictly on an "AS IS" basis, WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESS OR IMPLIED, AND LICENSOR HEREBY DISCLAIMS ALL SUCH WARRANTIES, INCLUDING WITHOUT LIMITATION,
 * ANY WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, QUIET ENJOYMENT, OR NON-INFRINGEMENT.
 * See the RPL for specific language governing rights and limitations under the RPL.
 * For more details on this particular license see http://toeprint.phenocode.com/license
 * For more information on the RPL license please see http://en.wikipedia.org/wiki/Reciprocal_Public_License
 */

class toeprint_MVCApp extends toeprint_App {
    private $modules = array();
    private $controllers = array();
    private $models = array();
    private $views = array();
    private $routes = array();
    public $config = array();
    public $activeRoute = false;
    /**
     * @var toeprint_Layout
     */
    public $layout = false;
    public $mobileStatus = false;
    public $navItems = array();
    public function __construct($config=false) {
        parent::__construct($config);
        $md = tp::mobile();
        if($md->isTablet()) {
            $this->mobileStatus = 2;
        } elseif($md->isMobile()) {
            $this->mobileStatus = 1;
        }
    }
    public function registerController($name, $routes, $controller) {
        $this->routes[$name] = array();
        foreach($routes as $route => $action) {
            $this->routes[$name][$route] = array($controller, $action);
        }
    }
    public function registerModule($name, $config) {
        $name = strtolower(tp::slug($name));
        $controllers = isset($config->controllers) ? $config->controllers : array();
        unset($config->controllers);
        $this->modules[$name] = $config;
        foreach($controllers as $controller => $routes) {
            $cname = $name . '_' . $controller;
            $this->modules[$name]->routes[$controller] = array();
            foreach($routes as $route => $action) {
                $this->routes[$cname][$route] = array($cname, $action);
            }
        }
    }
    public function registerNavItems($items) {
        $this->navItems = array_merge_recursive($this->navItems, $items);
    }
    public function route() {
        $this->router->reset();
        foreach($this->routes as $controllerName => $routes) {
            foreach($routes as $route => $controller) {
                list($class, $action) = $controller;
                $app = $this;
                $this->router->register($route, function($params, $request) use ($action, $class, &$app) {
                    $obj = new $class($app);
                    return $obj->route($action, $params);
                });
            }
        }
        return $this->router->route(function($winner, &$result) {
            $this->activeRoute = $winner;
            $this->layout->assign('activeRoute', $winner);
        });
    }
    public function getRoute($which) {
        foreach($this->routes as $controller => $routes) {
            foreach($routes as $route => $controller) {
                if($route == $which) return $controller;
            }
        }
        return false;
    }
    public function render() {
        $content = $this->route();
        if($this->layout) {
            $this->layout->assign('pageContent', $content);
            $this->layout->widget('navigation', array('items' => $this->navItems));
            echo $this->layout->render();
        } else {
            echo $content;
        }
    }
}
/**
 * Toeprint Model Class.
 * Used in MVC settings to provide data to the toeprint Controller.
 * For more details visit http://toeprint.phenocode.com/docs/mvc
 * @package    toeprint
 * @subpackage MVC
 */
class toeprint_Model{
    /**
     * Model source table
     * @var string
     */
    private $table = false;
    /**
     *  Class applied to fetched objects
     * @var class
     */
    private $fetchclass = false;
    /**
     * Database handle
     * @var toeprint_PDO
     */
    private $handle = false;
    /**
     * Toeprint Model Object
     * @param      $handle
     * @param      $table
     * @param bool $fetchclass
     */
    function __construct($table,$handle=false,$fetchclass = false){
        $this->handle = $handle ? $handle : tp::pdo();
        $this->table = $table ? $table : $this->table;
        $this->fetchclass = $fetchclass?$fetchclass:"toeprint_PDO_Result";
    }
    /**
     * Fetch a row from the model
     * @param mixed $what   Which column(s) to fetch (array of column names or SQL string)
     * @param mixed $where  SQL WHERE clause, data pair, or 1 for all rows
     * @param bool  $single Select a single row? True/False
     * @param bool  $join   SQL JOIN clause, or false if none
     * @param bool  $limit  SQL LIMIT clause, or false if none
     * @param bool  $order  SQL ORDER clause, or false if none
     * @return mixed Fetched ResultSet object (or Row object for single)
     */
    function fetch($what=false,$where = 1,$single = false,$join = false,$limit = false,$order = false){
        if(!$what) $what = '*';
        if(!$this->handle) { throw new Exception("Invalid database handler"); }
        $result = $this->handle->fetch($this->table,$what,$where,$single,$join,$limit,$order,$this->fetchclass);
        if($single) {
            return $this->sanitize($result, false);
        } else {
            if($result) $result = $result->toArray();
            $clean = array();
            foreach($result as $offset => $row) {
                $clean[$offset] = $this->sanitize($row, false);
            }
            $result = $clean;
        }
        return $result;
    }
    /**
     * Update a model row or resultset
     * @param $where The row or resultset object, or a SQL WHERE clause
     * @param $what  A data pair or SQL UPDATE clause
     * @return bool True on success, false on failure
     */
    function update($where,$what){
        $what = $this->sanitize($what);
        if($where instanceof toeprint_PDO_ResultSet || $where instanceof toeprint_PDO_Result){
            return $where->update($what);
        } else{
            return $this->handle->update($this->table,$what,$where);
        }
    }
    /**
     * Insert a model row
     * @param $data The row data
     * @return bool True on success, false on failure
     */
    function insert($data){
        return $this->handle->insert($this->table,$this->sanitize($data));
    }
    /**
     * Delete a model row
     * @param $where The row or resultset object, or a SQL WHERE clause
     * @return bool True on success, false on failure
     */
    function delete($where){
        return $this->handle->delete($this->table,$where);
    }
    public function form($template, $action, $row=false) {
        $fields = $this->map();
        $formElements = array();
        foreach($fields as $fieldName => $field) {
            $formElements[$fieldName] = tpui::formField($field);
        }
        return tpui::form($template, $action, $formElements);
    }

    /**
     * Sanitize model elements
     * @param $row
     * @param bool $incoming
     */
    public function sanitize($row, $incoming=true) {
        if(is_object($row)) $row = $row->toArray();
        foreach($this->map() as $var => $data) {
            if(isset($row[$var])) {
                $row_res = array($row[$var], $row, $var, $incoming);
                if(isset($data['sanitize']) && is_callable($data['sanitize'])) {
                    // Check for model "soft-hooks"
                    $row[$var] = call_user_func_array($data['sanitize'], $row_res);
                } else {
                    // Check for field type sanitize hook
                    $row[$var] = tp::hook('crud_sanitize[' . $data['type'] . ']', $row_res, $row[$var]);
                }
            }
        }
        return $row;
    }

    /**
     * Validate model elements
     * @param $row
     * @param bool $incoming
     */
    public function validate($row, $incoming=true) {
        foreach($this->map() as $var => $data) {
            if(isset($row[$var])) {
                if(isset($data['validate']) && is_callable($data['validate'])) {
                    // Check for model "soft-hooks"
                    call_user_func_array($data['validate'], array($row[$var], $row, $var, $incoming));
                } else {
                    // Check for field type validate hook
                    $row[$var] = tp::hook('crud_validate[' . $data['type'] . ']', array($row[$var], $row, $var, $incoming), $row[$var]);
                }
            }
        }
        return $row;
    }
}
/**
 * Toeprint Controller Class.
 * Used in MVC settings to provide content to the toeprint Router.
 * For more details visit http://toeprint.phenocode.com/docs/mvc
 * @package    toeprint
 * @subpackage MVC
 */
class toeprint_Controller{
    /**
     * Toeprint Controller name
     * @var string
     */
    protected $name = false;
    /**
     * Path to the Toeprint Controller view directory
     * @var string
     */
    protected $viewpath = false;
    /**
     * Toeprint Model (optional)
     * @var toeprint_Model
     */
    protected $model = false;
    /**
     * Toeprint App (optional)
     * @var toeprint_MVCApp
     */
    protected $app = false;
    /**
     * Toeprint Controller Object
     * @param toeprint_MVCApp $app  Toeprint MVC Application
     */
    public function __construct(&$app){$this->app=$app;}
    /**
     * Activate routing for the controller
     * @param $action
     * @param $params
     * @return mixed
     */
    public function route($action,$params){
        if(preg_match('/\$[0-9]/i', $action)) {
            $offset = (str_replace('$','',$action)-1);
            $action = isset($params[$offset]) ? $params[$offset] : 'index';
            if($offset == 0) array_shift($params);
        }
        $method = $action.'Route';
        if(method_exists($this,$method)){
            return $this->$method($params);
        } else{
            return $this->indexRoute($params);
        }
    }
    /**
     * Get the path for a view file based on action name
     * @param $action Action name
     * @return string Path to view file, or false if view doesn't exist
     */
    public function getView($action){
        $path = $this->getViewPath($action);
        return new toeprint_View($this,$action,$path);
    }
    /**
     * Get the path for a view file based on action name
     * @param $action Action name
     * @return string Path to view file, or false if view doesn't exist
     */
    public function getViewPath($action){
        $filename = $this->path.'/'.$action.'.phtml';;
        return file_exists($filename)?$filename:false;
    }
    /**
     * Default route method
     * @param array $params Route parameters
     * @return string Text/HTML content
     */
    public function indexRoute($params){
        return '<div class="alert alert-error">404</div>';
    }
}
/**
 * Toeprint View Class.
 * Used in MVC settings to provide a template interface to the Toeprint Controller.
 * For more details visit http://toeprint.phenocode.com/docs/mvc
 * @package    toeprint
 * @subpackage MVC
 */
class toeprint_View extends toeprint_Template{
    /**
     * Toeprint View Object
     * @param toeprint_Controller $controller The toeprint_Controller object for this view
     * @param string              $action     The action being called
     * @param string              $path       The path to the template file
     * @throws Exception
     */
    function __construct($controller,$action,$path = ''){
        $this->controller = $controller;
        $this->action = $action;
        if(! $path) $path = $this->controller->getViewPath($action);
        if(! $path) throw new Exception("Unable to locate view template at '".$path."'");
        return parent::__construct($path);
    }
}
class toeprint_Module {
    public $name = false;
    public $routes = false;
    function __construct($name, $routes) {
        $this->name = $name;
        $this->routes = $routes;
        $this->addRoutes($routes);
    }
    function addRoutes($routes) {
        foreach($routes as $name => $route) {
            $this->routes[$name] = $route;
        }
    }
}
/*
 * Attempt to autoload model and controllers for MVC app instances
 */
function __autoload($class_name) {
    if(stristr($class_name, '_')) {
        $parts = explode('_', $class_name);
        $class_name = array_pop($parts);
        $folder = '/modules/' . strtolower(implode('/', $parts) . '/');
    } else {
        $folder = '/';
    }
    if(stristr($class_name, 'Controller') && file_exists(TOEPRINT_ROOT_PATH . $folder .  'controllers/' . $class_name . '.php')) {
        require_once(TOEPRINT_ROOT_PATH . $folder . 'controllers/' . $class_name . '.php');
    } elseif(stristr($class_name, 'Model') && file_exists(TOEPRINT_ROOT_PATH . $folder . 'models/' .  $class_name . '.php')) {
        require_once(TOEPRINT_ROOT_PATH . $folder . 'models/' . $class_name . '.php');
    }
}