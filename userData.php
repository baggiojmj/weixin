<?php
class UserData {
    
    private static $_Instance; 
    
    private $memcache; 
    
    private function __construct() {  
        $this->memcache = new Memcache;    
        $this->memcache->connect('127.0.0.1', 11211) or die ("Could not connect");  
    }  
    
    public static function getInstance() {    
        if(empty(self::$_Instance)) 
            self::$_Instance = new self();  
        return self::$_Instance; 
    }

    function __set($name, $val) {    
        $this->memcache->set($name, $val, false);
    } 

    function __get($name) { 
        return $this->memcache->get($name);  
    }
}
