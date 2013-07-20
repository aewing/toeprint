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
    private $controllers = array();
    private $models = array();
    private $views = array();
    private $routes = array();
    public $config = array();
    public $layout = false;
    public $mobileStatus = false;
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
        foreach($routes as $route => $action) {
            $this->routes[$route] = array($controller, $action);
        }
    }
    public function route() {
        $this->router->reset();
        foreach($this->routes as $route => $controller) {
            list($class, $action) = $controller;
            $app = $this;
            $this->router->register($route, function($params, $request) use ($action, $class, &$app) {
                $obj = new $class;
                return $obj->route($app, $action, $params);
            });
        }
        return $this->router->route();
    }
    public function getRoute($route) {
        isset($this->routes[$route]) ? $this->routes[$route] : false;
    }
    public function render() {
        $content = $this->route();
        if($this->layout) {
            $this->layout->assign('pageContent', $content);
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
    function __construct($handle,$table,$fetchclass = false){
        $this->handle = $handle;
        $this->table = $table;
        $this->fetchclass = $fetchclass?$fetchclass:toeprint_DBObject;
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
    function fetch($what,$where = 1,$single = false,$join = false,$limit = false,$order = false){
        return $this->handle->fetch($this->table,$what,$where,$single,$join,$limit,$order,$this->fetchclass);
    }
    /**
     * Update a model row or resultset
     * @param $where The row or resultset object, or a SQL WHERE clause
     * @param $what  A data pair or SQL UPDATE clause
     * @return bool True on success, false on failure
     */
    function update($where,$what){
        if($where instanceof toeprint_PDO_ResultSet || $where instanceof toeprint_PDO_Result){
            return $where->update($what);
        } else{
            return $this->handle->update($this->table,$what,$where);
        }
    }
    public function form($template, $action, $row=false) {
        $fields = $this->map();
        $formElements = array();
        foreach($fields as $fieldName => $field) {
            $formElements[$fieldName] = tpui::formField($field);
        }
        return tpui::form($template, $action, $formElements);
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
    private $name = false;
    /**
     * Path to the Toeprint Controller view directory
     * @var string
     */
    private $viewpath = false;
    /**
     * Toeprint Model (optional)
     * @var toeprint_Model
     */
    private $model = false;
    /**
     * Toeprint Controller Object
     * @param bool $name  Toeprint Controller name
     * @param bool $path  Path to the Toeprint Controller view directory
     * @param bool $model Toeprint Model (optional)
     */
    public function __construct(){}
    /**
     * Activate routing for the controller
     * @param $action
     * @param $params
     * @return mixed
     */
    public function route(&$app, $action,$params){
        $method = $action.'Route';
        if(method_exists($this,$method)){
            return $this->$method($app, $params);
        } else{
            return $this->indexRoute($app, $params);
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
    public function indexRoute(&$app, $params){
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