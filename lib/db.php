<?php

class cs_db {
    private static $_instance=null;

    public static function getInstance(){

        if( is_null(self::$_instance) ){
            self::$_instance = new PDO('mysql:dbname='.cs_config::get('db')->name.';host='.cs_config::get('db')->host, cs_config::get('db')->user, cs_config::get('db')->pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            self::$_instance->query('SET NAMES UTF8');
        }

        return self::$_instance;
    }

    public static function __callStatic($method, $arguments) {
        return call_user_func_array(array(self::getInstance(), $method), $arguments);
    }
}

?>