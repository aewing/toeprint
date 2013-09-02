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
/**
 * The path to the views directory containing the layout(s)
 */
define('TOEPRINT_LAYOUT_PATH',TOEPRINT_VIEW_PATH.'/layouts');
define('TOEPRINT_LAYOUT_URL',TOEPRINT_VIEW_URL.'/layouts');
/**
 * The directory name of the default layout (default is bootstrap-responsive)
 */
define('TOEPRINT_LAYOUT_DEFAULT_LAYOUT','bootstrap-responsive');
/**
 * The default template file (default is layout.phtml)
 */
define('TOEPRINT_LAYOUT_DEFAULT_TEMPLATE','layout.phtml');
/**
 * Toeprint Layout Class.
 * Extends the toeprint_Template class to offer layout-specific functionality.
 * For more details visit http://toeprint.phenocode.com/docs/views
 * @package    toeprint
 * @subpackage Views
 */
class toeprint_Layout extends toeprint_Template{
    public $layoutURL = false;
    public $layoutPath = false;
    public $scripts = array();
    public $styles = array();
    /**
     * Navigation template
     * @var toeprint_Template
     */
    private $nav_template = false;
    /**
     * The active layout template (default layout.phtml)
     * @var string
     */
    private $active_template = TOEPRINT_LAYOUT_DEFAULT_TEMPLATE;
    /**
     * The active layout name (default bootstrap-responsive)
     * @var string
     */
    private $name = TOEPRINT_LAYOUT_DEFAULT_LAYOUT;
    /**
     * Temporary assignment container
     * @var array
     */
    private $_tassign = array();
    /**
     * Array of layout-owned templates
     * @var array
     */
    private $widgets;
    /**
     * Toeprint Layout Object
     * @param string $path      The path to the layout file
     * @param string $title     The title of the layout (optional)
     * @param array  $nav_items The navigation elements to be assigned (optional)
     */
    function __construct($name = false,$template = false,$assign = array(),$nav_items = array()){
        if($name) $this->name = $name;
        if($template) $this->active_template = $template;
        $nav_items[] = array('text' => 'Home','url' => '/');
        if(is_array($assign) || is_object($assign)){
            foreach($assign as $var => $val) $this->$var = $val;
        }
        $this->nav_items = $nav_items;
        $this->nav_template = false;
        $preurl = defined('APP_LAYOUT_URL') ? APP_LAYOUT_URL : TOEPRINT_LAYOUT_URL;
        $this->layoutURL = $preurl.'/'.$this->name;
        $prepath = defined('APP_LAYOUT_PATH') ? APP_LAYOUT_PATH : TOEPRINT_LAYOUT_PATH;
        $this->layoutPath = $prepath.'/'.$this->name;
    }

    /**
     * Retrieve and/or create a Layout widget object
     * @param $title        Widget title
     * @param bool $assign  Variables to assign
     * @return mixed        The widget template
     */
    function widget($title, $assign=false) {
        if(!isset($this->widgets[$title])) {
            $templatef = $this->layoutPath . '/widgets/' . $title . '.phtml';
            if(file_exists($templatef)) {
                $this->widgets[$title] = new toeprint_Template($templatef, $assign);
            } else {
                $this->widgets[$title] = false;
            }
        } else {
            if($this->widgets[$title] && $assign) {
                $this->widgets[$title]->assign($assign, true);
            }
        }
        return $this->widgets[$title];
    }
    /**
     * Add a script to the layout
     * @param $template
     */
    public function addScript($url, $full=false){
        $this->scripts[$url] = $full;
    }
    /**
     * Set the active layout template
     * @param $template
     */
    public function setTemplate($template){
        $this->active_template = $template;
    }
    /**
     * Render the layout
     * @param bool $return
     * @return string
     * @throws Exception
     */
    public function render($return = true){
        // Set the template path based on currently assigned template
        $prepath = defined('APP_LAYOUT_PATH') ? APP_LAYOUT_PATH : TOEPRINT_LAYOUT_PATH;
        $this->path = $prepath.'/'.$this->name.'/'.$this->active_template;
        // Assert template existance
        if(! file_exists($this->path)) throw new Exception("Unable to locate template '".$this->active_template.'" in layout "'.$this->name.'" (' . $this->path . ')');
        // Create the layout template
        return parent::render($return);
    }
}