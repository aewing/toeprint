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
define('TOEPRINT_INC_PATH',dirname(__FILE__));
define('TOEPRINT_LIB_PATH',TOEPRINT_ROOT_PATH.'/lib');
define('TOEPRINT_VIEW_PATH',TOEPRINT_ROOT_PATH.'/views');
define('TOEPRINT_VIEW_URL',TOEPRINT_ROOT_URL.'/views');
define('TOEPRINT_SCRIPT_URL',TOEPRINT_ROOT_URL.'/scripts');
define('TOEPRINT_AUTOGLOBAL',true);
$mobile = false;
$tp_universal = array();
$tp_hooks = array();
/**
 * Toeprint Utility Class.
 * The core of the Toeprint framework. Provides singleton bindings for common utilities and provides core functionality.
 * For more details visit http://toeprint.phenocode.com/docs/utility
 * @package    toeprint
 * @subpackage Utility
 */
class tp{
    /**
     * Create a slug from a string or an array of strings
     * @param mixed $mixed
     * @return mixed|string
     */
    static function slug($mixed){
        $buffer = '';
        if(is_array($mixed)){
            $buffer = array();
            foreach($mixed as $val){
                $buffer[] = tp::slug($val);
            }
            $buffer = implode('-',$buffer);
        } else{
            if(is_string($mixed)){
                $buffer = strtolower(trim($mixed));
                $buffer = preg_replace('/[^a-z0-9-]/','-',$buffer);
                $buffer = preg_replace('/-+/',"-",$buffer);
            }
        }
        return $buffer;
    }
    /**
     * Get a new toeprint router object
     * @param bool $options
     * @return toeprint_Router
     */
    static function router($app = false){
        return new toeprint_Router($app);
    }
    /**
     * Parse the http request into a tokenized array
     * @return array
     */
    static function request(){
        $query = isset($_REQUEST['q'])?$_REQUEST['q']:false;
        if($query) $parts = stristr($query,'/')?explode('/',$query):array($query);
        return ($query)?$parts:array();
    }
    /**
     * Get a new toeprint template object
     * @param string $path
     * @param array  $assign
     * @return toeprint_Template
     */
    static function template($path,$assign = array()){
        return new toeprint_Template($path,$assign);
    }
    /**
     * Create PDO connector (or retrieve existing connector)
     * @param string $protocol
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $db
     * @param string $table_prefix
     * @return toeprint_PDO $pdo
     */
    static function pdo($protocol = null,$host = null,$user = null,$pass = null,$db = null,$table_prefix = ''){
        return new toeprint_PDO($protocol,$host,$user,$pass,$db,$table_prefix);
    }
    /**
     * Returns a boolean indicating if the client is a mobile device
     * @return bool
     */
    static function isMobile(){
        return (tp::mobile()->isMobile() || isset($_REQUEST['mobile'])) && ! isset($_REQUEST['tablet']);
    }
    /**
     * Returns a new mobile detector object
     * @return Mobile_Detect
     */
    static function mobile(){
        global $mobile;
        if(! $mobile){
            require_once(TOEPRINT_LIB_PATH.'/mobile_detect/Mobile_Detect.php');
            $mobile = new Mobile_Detect();
        }
        return $mobile;
    }
    /**
     * Returns a boolean indicating if the client is a tablet device
     * @return bool
     */
    static function isTablet(){
        return (tp::mobile()->isTablet() || isset($_REQUEST['tablet'])) && ! isset($_REQUEST['mobile']);
    }
    /**
     * Returns a boolean indicating if the client is a desktop
     * @return Mobile_Detect
     */
    static function isDesktop(){
        $mobile = tp::mobile();
        return ! ($mobile->isTablet() || $mobile->isMobile() || isset($_REQUEST['mobile']) || isset($_REQUEST['tablet']));
    }
    /**
     * Register a hook callback
     * @param $hook     Hook identifier
     * @param $callback The callback to be registered
     */
    static function onHook($hook,$callback){
        global $tp_hooks;
        if(! isset($tp_hooks[$hook])) $tp_hooks[$hook] = array();
        $tp_hooks[$hook][] = $callback;
    }
    /**
     * Activate registered hook callbacks
     * @param $hook   Hook identifier
     * @param $params Parameters to provide to the registered callbacks
     */
    static function hook($hook,&$params = false,$default = false){
        global $tp_hooks;
        if(! isset($tp_hooks[$hook])) $tp_hooks[$hook] = array();
        $result = $default;
        while(! empty($tp_hooks[$hook])){
            $method = array_pop($tp_hooks[$hook]);
            $result = call_user_func_array($method,array(&$params,&$result));
        }
        return $params;
    }
    /**
     * Get, and optionally set, a universal variable
     * @param string $name  Variable name
     * @param null   $value If not null, sets the value of the universal variable
     * @return mixed Returns the value of the universal variable
     */
    static function universal($name,$value = null, $merge=false){
        global $tp_universals;
        if($value != null){
            if($merge && is_array($value)) {
                if(isset($tp_universals[$name]) && is_array($tp_universals[$name])) {
                    $value = array_merge_recursive($tp_universals[$name], $value);
                }
            }
            $tp_universals[$name] = $value;
        }
        return $tp_universals[$name];
    }
}
/**
 * Toeprint App Class.
 * Used to manage your application environment and cache templates, routes, hooks, and pdo objects
 */
class toeprint_App{
    public $start_time = false;
    public $name = 'Toeprint App';
    public $config;
    protected $extensions = array();
    protected $templates = array();
    protected $handles = array();
    protected $activeHandle = false;
    protected $router = false;
    protected $options = array();
    public function __construct($config = array()){
        if(isset($config->activeEnvironment) && isset($config->environments)){
            $env = $config->activeEnvironment;
            $env = $config->environments->$env;
            foreach($env as $var => $val) $config->$var = $val;
        }
        unset($config->environments);
        $this->config = $config;
        $this->start_time = microtime(true);
        $this->router = new toeprint_Router($this);
    }
    /**
     * Get a new toeprint template object
     * @param string $path
     * @param array  $assign
     * @return toeprint_Template
     */
    public function template($path,$assign = array()){
        $path = realpath($path);
        if(! isset($this->templates[$path])){
            $this->templates[$path] = tp::template($path,$assign);
        } else{
            $this->templates[$path]->reset($assign);
        }
        return $this->templates[$path];
    }
    /**
     * Get the current execution time as a float
     * @return float
     */
    public function exec_time(){
        return microtime(true) - $this->start_time;
    }
    /**
     * Create PDO connector (or retrieve existing connector)
     * @param string $protocol
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $db
     * @param string $table_prefix
     * @return toeprint_PDO $pdo
     */
    public function pdo($protocol = null,$host = null,$user = null,$pass = null,$db = null,$table_prefix = ''){
        if($protocol == null) if($this->activeHandle){
            return $this->activeHandle;
        } elseif($this->config->db){
            try{
                $protocol = $this->config->db->protocol;
                $host = $this->config->db->host;
                $user = $this->config->db->user;
                $pass = $this->config->db->pass;
                $db = $this->config->db->db;
                $prefix = isset($this->config->db->prefix)?$this->config->db->prefix:'';
            } catch(Exception $e){
                throw new Exception("Invalid database configuration");
            }
        }
        $slug = tp::slug(array($protocol,$host,$user,$db,$table_prefix));
        if(! isset($this->handles[$slug])){
            $this->handles[$slug] = tp::pdo($protocol,$host,$user,$pass,$db,$table_prefix);
        }
        $this->activeHandle = $this->handles[$slug];
        return $this->handles[$slug];
    }
    /**
     * Extend the toeprint connector class
     */
    public function extend($name,$function){
        $this->extensions[$name] = $function;
    }
    /**
     * Catch extension calls
     */
    public function __call($method,$params){
        if(isset($this->extensions[$method])){
            call_user_func_array($this->extensions[$method],$params);
        }
    }
    public function toestrap($file){
        if(file_exists($file)){
            $app = $this;
            require_once($file);
        }
    }
    public function route(){
        return $this->router->route();
    }
}
/**
 * Toeprint Router Class.
 * The core Toeprint request router, used to route incoming requests to methods or controllers.
 * For more details visit http://toeprint.phenocode.com/docs/routing
 * @package    toeprint
 * @subpackage Utility
 */
class toeprint_Router{
    /**
     * Route registry
     * @var Array of routes
     */
    public $routes;
    /**
     * Create a new toeprint router object
     * @param mixed $options
     */
    public function __construct(){ }
    /**
     * Register a route with the router
     * @param string $syntax The string to match against
     * @param method $method The method to call if matched
     */
    function register($syntax,$method){
        if($syntax == '/') $syntax = 'default';
        if(substr($syntax,0,1) == '/') $syntax = substr($syntax,1);
        $this->routes[$syntax] = $method;
    }
    function reset(){
        $this->routes = array();
    }
    /**
     * Route against the registered matches
     * @param mixed $oncomplete The method to call after routing
     */
    function route($oncomplete = false, $request=false){
        // Get the tokenized request elements
        if(!$request) $request = tp::request();
        // Route Match Values
        $rmv = array();
        // Route Match Params
        $rmp = array();
        // Prepare routes for iteration, prepare default route for fallback
        $routes = $this->routes;
        unset($routes['default']);
        // Iterate request, compare to routes
        foreach($routes as $syntax => $method){
            $sparts = (stristr($syntax,'/')?explode('/',$syntax):array($syntax));
            $i = 0;
            $iterating = true;
            $value = 0;
            $op = array();
            $om = false;
            // Iterates until there are no more routes or request elements, whichever number is greater
            while($iterating){
                // Check for a tokenized request element at this index
                if(isset($request[$i])){
                    // Check for a tokenized route syntax element at this index
                    if(isset($sparts[$i])){
                        if($sparts[$i] == $request[$i]){
                            // If we have a match increase the value by 2
                            $value += 2;
                        } elseif($sparts[$i] == '*'){
                            // Otherwise increase the value by 1, and open the system for further matching
                            $op[] = $request[$i];
                            $om = true;
                            $value ++;
                        } elseif(! $om){
                            $value = 0;
                            $iterating = false;
                        }
                    } elseif($om){
                        // If not a match element, but in an open match, add request element to params
                        $op[] = $request[$i];
                    } else{
                        $value = 0;
                        $iterating = false;
                    }
                } else{
                    if(! $om){
                        // If no match at this index and an open match, this route has no value
                        $value = 0;
                        $iterating = false;
                    }
                }
                // Advance iterator and make sure we still have something to iterate
                $i ++;
                if($i >= count($sparts) && $i >= count($request)) $iterating = false;
            }
            // Set the Route Match Value & Route Match Params for this route
            if($value){
                $rmp[$syntax] = $op;
                $rmv[$syntax] = $value;
            }
        }
        // Sort the routes by value
        arsort($rmv,SORT_ASC);
        reset($rmv);
        // Select the best match
        $winner = key($rmv);
        $params = isset($rmp[$winner])?$rmp[$winner]:array();
        // If there is no match revert to default
        if(! $winner) $winner = 'default';
        // Attempt to fulfill the matched route
        try{
            $result = $this->routes[$winner]($params,$request);
        } catch(Exception $e){
            echo '<h1>500 Error</h1>';
            exit($e->getMessage());
        }
        // Call the oncomplete callback
        if($oncomplete) call_user_func($oncomplete,$result);
        return $result;
    }
}
/**
 * Toeprint Iterator Class.
 * Used as an abstract class for several toeprint utilities.
 * For more details visit http://toeprint.phenocode.com/docs/utility
 * @package    toeprint
 * @subpackage Utility
 */
class toeprint_Iterator implements Iterator{
    private $data = array();
    /**
     * Toeprint Iterator Object - used for objects that need to be iterated without bloating
     * @param array $data
     */
    public function __construct($data = array()){
        $this->data = $data;
    }
    /**
     * Rewind the toeprint iterator (used for iteration)
     */
    public function rewind(){
        reset($this->data);
    }
    /**
     * Get the current toeprint iterator value (used for iteration)
     * @return mixed Current ResultSet iterator item
     */
    public function current(){
        return current($this->data);
    }
    /**
     * Get the current toeprint iterator key (used for iteration)
     * @return mixed Current ResultSet iterator key
     */
    public function key(){
        return key($this->data);
    }
    /**
     * @return mixed|void Advance the toeprint iterator
     */
    public function next(){
        return next($this->data);
    }
    /**
     * Check if the toeprint iterator offset is valid
     * @return bool
     */
    public function valid(){
        $key = key($this->data);
        return ($key !== null && $key !== false);
    }
}
/**
 * Toeprint Template Class.
 * Provides an interface for template integration similar to that of Zend Framework.
 * For more details visit http://toeprint.phenocode.com/docs/views
 * @package    toeprint
 * @subpackage Views
 */
class toeprint_Template{
    /**
     * Path to template file
     * @var string
     */
    public $path = false;
    /**
     * Toeprint Template Object - Provides a basic but very fast templating engine similar to that of Zend Framwork
     * @param string $path   The path to the template file
     * @param array  $assign An optional array of data to assign to the template
     */
    public function __construct($path,$assign = array()){
        $this->path = $path;
        if(is_array($assign) || is_object($assign)){
            foreach($assign as $var => $val) $this->$var = $val;
        }
    }
    /**
     * Render the template with the assigned data
     * @param bool $return
     * @return string
     */
    public function render($return = true){
        if(file_exists($this->path) & substr($this->path,- 6) == '.phtml' && stristr($this->path,TOEPRINT_VIEW_PATH)){
            ob_start();
            require($this->path);
            $result = ob_get_clean();
            if($return) return $result; else echo $result;
            return true;
        } else{
            throw new Exception("Unable to render, invalid or non-existant template: '".$this->path."'");
        }
    }
    /**
     * Reset assignment, optionally with new assignments
     * @param $assign New assignment variables
     */
    public function reset($assign){
        foreach($this as $var => $val) if($var !== 'path') unset($this->$var);
        $this->assign($assign);
    }
    /**
     * Assign a variable to the template
     * @param string $var The name of the variable being assigned
     * @param mixed  $val The value being assigned
     */
    public function assign($var,$val = false){
        if(is_array($var)){
            foreach($var as $tvar => $tval){
                $this->$tvar = $tval;
            }
        } else{
            $this->$var = $val;
        }
    }
}
/**
 * Toeprint PDO Result Class.
 * Returned by the toeprint PDO object when fetching a single row, or when iterating a toeprint_PDO_ResultSet.
 * For more details visit http://toeprint.phenocode.com/docs/pdo
 * @package    toeprint
 * @subpackage PDO
 */
class toeprint_PDO_Result{
    /**
     * Iterator buffer
     * @var array
     */
    private $data = array();
    /**
     * Database table
     * @var string
     */
    private $table = false;
    /**
     * Toeprint PDO handle
     * @var toeprint_PDO
     */
    private $handle = false;
    /**
     * Database identifier field (default 'id')
     * @var bool|string
     */
    private $identifier = false;
    /**
     * Toeprint PDO Result Object
     * @param array        $result        PDO fetch result
     * @param string       $table         PDO table name
     * @param toeprint_PDO $handle        toeprint_PDO handle
     * @param bool         $resultset     Is this element part of a resultset
     * @param string       $identifier    Database identifier field (default 'id')
     */
    public function __construct($result,$table,$handle,$resultset = false,$identifier = 'id'){
        $this->table = $table;
        $this->handle = $handle;
        $this->identifier = $identifier;
        if($resultset) $result = array_shift($result);
    }
    /**
     * Set or override a database field value
     * @param string $var Database field name
     * @param bool   $val Value
     */
    public function set($var,$val = false){
        $this->data->$var = $val;
    }
    /**
     * Get a database field value
     * @param $var Database field name
     * @return mixed Database field value
     */
    public function get($var){
        return isset($this->data->$var)?$this->data->$var:false;
    }
    /**
     * Update this row with the current values
     * @return bool
     */
    public function update(){
        $id = $this->identifier;
        return $this->handle->update($this->table,$this->data,array($id => $this->data->$id));
    }
    /**
     * Delete this row (NO CONFIRMATION)
     * @return resource
     */
    public function delete(){
        $id = $this->identifier;
        return $this->handle->delete($this->table,$this->data,array($id => $this->data->$id));
    }
}
/**
 * Toeprint PDO ResultSet Class.
 * Returned by the toeprint PDO object when fetching multiple rows.
 * Provides an iterator for toeprint_PDO_Result.
 * For more details visit http://toeprint.phenocode.com/docs/pdo
 * @package    toeprint
 * @subpackage PDO
 */
class toeprint_PDO_ResultSet{
    /**
     * Class to cast to results
     * @var bool
     */
    private $data = array();
    private $table = false;
    private $handle = false;
    /**
     * Toeprint PDO ResultSet Object
     * @param array        $results    An array of PDO result objects
     * @param string       $table      Database table name
     * @param toeprint_PDO $handle     toeprint_PDO handle
     * @param bool         $fetchclass A class to cast all result objects to
     */
    public function __construct($results,$table,$handle){
        $this->table = $table;
        $this->handle = $handle;
        $this->data = $results;
    }
    /**
     * Returns the processed results
     * @return array|bool
     */
    public function results(){
        return $this->data;
    }
    /**
     * Catch all non-existant ResultSet requests and run them on the ResultSet Rows if applicable (called by system)
     * @param $method_name The method being called
     * @param $args        The arguments being passed
     * @return array An array of all ResultSet Row method call returns
     */
    public function __call($method_name,$args){
        if(! in_array($method_name,array('_castResults','__construct','rewind','current','key','next','valid'))){
            $return = array();
            foreach($this->data as $result){
                if(method_exists($result,$method_name)){
                    $return[] = call_user_func_array(array($result,$method_name),$args);
                }
            }
            return $return;
        } else return true;
    }
}
/**
 * Toeprint PDO Class.
 * Provides a dynamically caching PDO interface for toeprint applications.
 * For more details visit http://toeprint.phenocode.com/docs/pdo
 * @package    toeprint
 * @subpackage PDO
 */
class toeprint_PDO{
    /**
     * Database error handle
     * @var bool|Exception|PDOException
     */
    private $err = false;
    /**
     * Database error message
     * @var mixed
     */
    private $errMsg = false;
    /**
     * Database handle
     * @var PDO
     */
    private $handle = false;
    /**
     * Database table prefix
     * @var bool|string
     */
    private $table_prefix = '';
    /**
     * Toeprint PDO Database Object
     * @param string $protocol     PDO database protocol (mysql,oracle,etc...)
     * @param string $host         PDO database host (localhost)
     * @param string $user         PDO database user (root)
     * @param string $pass         PDO database pass (blank)
     * @param string $db           PDO database name
     * @param string $table_prefix
     */
    public function __construct($protocol,$host,$user,$pass,$db,$table_prefix = '',$persistent = true){
        $this->table_prefix = $table_prefix;
        try{
            $prefix = $protocol.':host='.$host.';dbname='.$db;
            $this->handle = new PDO($prefix,$user,$pass);
            $this->handle->setAttribute(PDO::ATTR_PERSISTENT,true);
        } catch(PDOException $e){
            $this->err = $e;
        }
    }
    /**
     * Set the PDO Object table prefix
     * @param string $prefix
     */
    public function setTablePrefix($prefix){
        $this->table_prefix = $prefix;
    }
    /**
     * Return a count of the rows in table matching the provided WHERE statement
     * @param string $table PDO Table Name (without prefix)
     * @param mixed  $where WHERE clause (db_where_pair or string)
     * @return int|bool         Returns count on success, false on failure
     */
    public function count($table,$where = 1){
        if(is_array($where)){
            $where = $this->where_pair($this->table_prefix.$table,$where);
        }
        $query = 'SELECT COUNT(*) FROM '.$this->table_prefix.$table.' WHERE '.$where;
        $res = $this->result($query);
        return $res;
    }
    /**
     * Create PDO WHERE clause from an array
     * @param string $table PDO Table Name (with prefix)
     * @param array  $array Array of var => val
     * @return string string            PDO WHERE clause string
     */
    public function where_pair($table,$array){
        if(! is_array($array) && ! is_object($array)){
            return $array;
        }
        $where = '';
        $off = 0;
        foreach($array as $var => $val){
            if($off > 0){
                $where .= ' AND ';
            }
            if(is_string($var)){
                if(! stristr($var,'.')){
                    $var = $table.'.'.$var;
                }
                $where .= $var.'="'.$val.'"';
            } else{
                $where .= $val;
            }
            $off ++;
        }
        return $where;
    }
    /**
     * Get a single column result from a PDO query
     * @param unknown $query
     * @param int     $col
     * @return mixed
     */
    public function result($query,$col = 0){
        $query = $this->query($query);
        return $query->fetchColumn($col);
    }
    /**
     * Execute PDO query
     * @param string $query The PDO query to be executed
     * @return resource                 The result of the executed query
     * @throws Exception                Throws an exception on failure
     */
    public function query($query){
        try{
            $handle = $this->handle->prepare($query);
            $handle->execute();
            return $handle;
        } catch(PDOException $e){
            $this->err = $e;
            return false;
        }
    }
    /**
     * Delete any rows in table matching the provided WHERE statement
     * @param string       $table PDO Table Name (without prefix)
     * @param array|string $where WHERE clause (db_where_pair or string)
     * @return resource         Returns PDO Query result
     */
    public function delete($table,$where){
        if(is_array($where)){
            $where = $this->where_pair($this->table_prefix.$table,$where);
        }
        $query = 'DELETE FROM '.$this->table_prefix.$table.' WHERE '.$where;
        return $this->query($query);
    }
    /**
     * Select row(s) from given table based on given parameters
     * @param string            $table  PDO Table Name (without prefix)
     * @param array|string|bool $cols   Columns (array or string)
     * @param array|string|bool $where  WHERE clause (db_where_pair or string)
     * @param bool              $single Return single or multiple rows
     * @param array|string|bool $join   JOIN statement
     * @param array|string|bool $limit  LIMIT statement
     * @param array|string|bool $order  ORDER statement
     * @return toeprint_PDO_Result|toeprint_PDO_ResultSet   Returns matching row(s) on success, false on failure
     */
    public function fetch($table,$cols = false,$where = false,$single = false,$join = false,$limit = false,$order = false){
        if(! $cols){
            $cols = '*';
        }
        if(is_array($cols)){
            $cols = implode(',',$cols);
        }
        if(is_array($where)){
            $where = $this->where_pair($this->table_prefix.$table,$where);
        } elseif(! $where) $where = 1;
        $query = 'SELECT '.$cols.' FROM '.$this->table_prefix.$table.' '.$join.' WHERE '.$where;
        if($order != false){
            $query .= ' ORDER BY '.$order;
        }
        if($limit != false){
            $query .= ' '.$limit;
        }
        $result = $this->query($query);
        if($single && $result){
            $result = $result->fetchAll(PDO::FETCH_CLASS,"toeprint_PDO_Result",array($table,$this,true));
            $result = $result[0];
        } else{
            if(! class_exists('toeprint_PDO_Result')) exit();
            $results = $result->fetchAll(PDO::FETCH_CLASS,"toeprint_PDO_Result",array($table,$this,true));
            $result = new toeprint_PDO_ResultSet($results,$table,$this);
        }
        return $result;
    }
    /**
     * Update row(s) in the given table based on given parameters
     * @param string            $table PDO Table Name (without prefix)
     * @param array             $data  Array of new row values
     * @param array|string|bool $where WHERE clause (db_where_pair or string)
     * @return bool                     Returns true on success, false on failure
     */
    public function update($table,$data,$where = false){
        if(is_array($data) || is_object($data)){
            $data = str_replace(" AND ",", ",$this->where_pair($this->table_prefix.$table,$data));
        }
        if(is_array($where)){
            $where = $this->where_pair($this->table_prefix.$table,$where);
        }
        $query = 'UPDATE '.$this->table_prefix.$table.' SET '.$data.' WHERE '.$where;
        return $this->query($query);
    }
    /**
     * Insert row(s) into the given table based with the given values
     * @param string  $table PDO Table Name (without prefix)
     * @param unknown $data  The data to be inserted
     * @return bool|int                 Returns row ID on success, false on failure
     */
    public function insert($table,$data){
        $tmp = $data;
        $data = array();
        if($table){
            foreach($tmp as $col => $val){
                $data[$this->table_prefix.$table.'.'.$col] = $val;
            }
        }
        list($cols,$vals) = $this->data_pair($data);
        $query = 'INSERT INTO '.$this->table_prefix.$table.' ('.$cols.') VALUES('.$vals.')';
        $result = $this->query($query);
        if($result){
            return $this->handle->lastInsertId();
        }
        return false;
    }
    /**
     * Create PDO column definition from an array
     * @param array $data The data to be used for column definition
     * @return string array             The column definition string
     */
    public function data_pair($data){
        if(is_object($data)){
            $data = (array)$data;
        } elseif(! is_array($data)){
            return array(array(),array());
        }
        $vars = array();
        $vals = array();
        foreach($data as $var => $val){
            $vars[] = $var;
            if(is_numeric($val)) $vals[] = $val; else
                $vals[] = '"'.$val.'"';
        }
        return array(implode(',',$vars),implode(',',$vals));
    }
    /**
     * Get a single column value from a PDO database row
     * @param string $table  The table to select from
     * @param string $column The column to select
     * @param int    $where  The where clause or where pair array used for filtering
     * @return mixed
     */
    public function value($table,$column,$where = 1){
        if(is_array($where)){
            $where = $this->where_pair($this->table_prefix.$table,$where);
        }
        return $this->result('SELECT '.$column.' FROM '.$this->table_prefix.$table.' WHERE '.$where.' LIMIT 0,1');
    }
    /**
     * Check the PDO object for an error (returns true/false)
     * @return bool Returns true if PDO Object has experienced an error
     */
    public function hasError(){
        return ($this->err)?true:false;
    }
    /**
     * Get the exception from a PDO error, or false if an error is not present
     * @return Exception|PDOException
     */
    public function getError(){
        return $this->err;
    }
    /**
     * Get the error message from a PDO error, or false if an error is not present
     * @return bool|string] Error nessage (false if no error)
     */
    public function getErrorMessage(){
        return ($this->err)?$this->err->getMessage():false;
    }
    /**
     * Get the PDO Object database handle
     * @return PDO PDO Object database handle
     */
    public function handle(){
        return $this->handle;
    }
}