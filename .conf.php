<?php

cs_config::set('site',array(
    'root_path'=>__DIR__.'/root',
    'cache'=>false
));
cs_config::set('containers',array(
    'dir'=>'.containers',
    'default'=>'page'
));
cs_config::set('languages',array(
    'dir'=>'.languages',
    'default'=>'en'
));
cs_config::set('templates',array(
    'default'=>'main'
));
cs_config::set('db',array(
    'host' => "localhost",
    'name' => "test",
    'user' => "root",
    'pass' => "virus"
));

?>