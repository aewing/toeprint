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

class toeprint_Module extends toeprint_MVCApp {
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
        $this->routes[$name] = array();
        foreach($routes as $route => $action) {
            $this->routes[$name][$route] = array($controller, $action);
        }
    }
    public function route() {
        $this->router->reset();
        foreach($this->routes as $controllerName => $routes) {
            foreach($routes as $route => $controller) {
                list($class, $action) = $controller;
                $app = $this;
                $this->router->register($route, function($params, $request) use ($action, $class, &$app) {
                    $obj = new $class;
                    return $obj->route($app, $action, $params);
                });
            }
        }
        return $this->router->route();
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
            echo $this->layout->render();
        } else {
            echo $content;
        }
    }
}