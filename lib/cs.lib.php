<?php

class cs_config {
    private static $_data=array();

    public static function set($name,$val){
        self::$_data[$name]=is_array($val)?(object)$val:$val;
    }

    public static function get($name){
        return self::$_data[$name];
    }
}

abstract class cs_tree_item {

    static private $_tree_items=array();
    static private $_tree_item2ids=array();
    protected $_id;

    private $_name;
    private $_path;
    private $_path_parts;
    protected $_fs_path;
    private $_child_names;

    static public function get($path) {

        $path=self::_realpath($path);
        $id=self::_getIdByPath($path);

        if( ($item=self::$_tree_items[$id]) instanceof self)
            return $item;

        $cc=get_called_class();
        return new $cc($path);

    }

    static private function _put(self $item){
        $id=self::_getIdByPath($item->_path);
        self::$_tree_items[$id]=$item;
        return $id;
    }

    static private function _getIdByPath($path){
        if( ($id=self::$_tree_item2ids[$path])===null ){
            $id=count(self::$_tree_item2ids);
            self::$_tree_item2ids[$path]=$id;
        }
        return $id;
    }

    protected function __construct($path) {

        if($path{0}!='/') throw new Exception('Path "'.$path.'" is not absolute');

        $this->_name=basename($path);

        $root_path=cs_config::get('site')->root_path;
        $full_path=$root_path.$path;
        if( !($full_path_correct=realpath( $full_path )) || !is_dir($full_path_correct) ) throw new Exception('Item "'.$full_path.'" not found');

        $this->_fs_path=$full_path_correct;
        $this->_path=$path;
        $this->_path_parts=preg_split('|/|', $path, null, PREG_SPLIT_NO_EMPTY);

        $this->_id = self::_put($this);

    }

    public function name(){return $this->_name;}
    public function path(){return $this->_path;}
    public function pathParts(){return $this->_path_parts;}

    public function parent($depth=1){
        $depth=(int)$depth;
        if($depth>count($this->_path_parts) || $depth<=0) return null;

        $tmp=$this->_path_parts;
        for($i=0;$i<$depth;$i++) array_pop($tmp);

        return self::get( '/'.implode('/',$tmp) );
    }

    /*protected*/ function _childs(){
        $list=array();
        foreach( $this->_getChildNames() as $name)
            $list[]=self::get( $this->_path.'/'.$name );

        return $list;
    }

    private function _getChildNames(){

        if( !is_array($this->_child_names) ){
            $this->_child_names=array();
            cs_log::start('@file operations');
            foreach( (array)glob( $this->_fs_path.'/*', GLOB_ONLYDIR ) as $dir)
                $this->_child_names[]= basename($dir);
            cs_log::end('@file operations');
        }

        return $this->_child_names;
    }

    private function _realpath($path){
        $path=preg_replace('|[/]{2,}|','/',$path);
        if(substr($path,-1)=='/'&&strlen($path)>1) $path=substr($path,0,-1);
        return $path;
    }

    /*debug*/
    function __sleep(){
        self::$_tree_items=array();
        return array_keys(get_object_vars($this));
    }
    /**/
}

class cs_item extends cs_tree_item {

    #private $container_name;
    private $_file_check=array();
    private $_storage;

    protected function __construct($path) {
        parent::__construct($path);
        $this->_storage=new cs_storage( $this->_fs_path.'/.ini' );
    }

    private function _exec($name, array $data, $type){
        $space=cs_space::getInstance();
        $this; #declaring variable as used
        extract($data, EXTR_SKIP);
        unset($data);

        if($this->have($name,$type,false)) $exec_obj=$this;
        #todo container#elseif($this->have($name,$type,false)) $exec_obj=$this;
        elseif($space->root->have($name,$type,false)) $exec_obj=$space->root;
        else throw new Exception(ucfirst($type).' "'.$name.'" not found in '.$this->path());

        return include($exec_obj->_fs_path.'/'.$name.'.php');
    }

    public function container(){
        if( ($cont_name=$this->_storage->special('container'))===null )
            $cont_name=cs_config::get('containers')->default;
        $container=cs_space::getInstance()->containers[ $cont_name ];
        return ($container instanceof cs_container)?$container:null;
    }

    public function spec($name, $extends=true){
        if( ($val=$this->_storage->special($name))===null ){
            if(!$extends) return;
            $container=$this->container();
            if( ($container instanceof cs_item)) $val=$container->spec($name, false);
            if($val===null && ($root=cs_space::getInstance()->root) && ($root instanceof cs_item) ) $val=$root->spec($name, false);
        }

        return $val;
    }

    public function template($name, array $data=array()){
        $this->_exec($name,$data,'template');
    }

    public function method($name, array $data=array()){
        return $this->_exec($name,$data,'method');
    }

    public function ls(){
        #$items=$this->_childs();
        #space
        #this
    }

    public function variable($name, $type='string', $lang=null){
        if(!$lang) $lang=cs_space::getInstance()->current_language->name();
        return $this->_storage->variable($name, $type, $lang);
    }

    function __call($name, $attr){
        if($name=='var') return $this->variable($attr[0], isset($attr[1])?$attr[1]:'string', isset($attr[2])?$attr[2]:null);
        return;
    }

    public function have($name, $type, $extends=true){
        #types g1 = string, text, int, float, bool, array, object
        #types g2 = template, method
        #types g3 = item

        switch($type) {
            case 'string':
            case 'text':
            case 'int':
            case 'float':
            case 'bool':
            case 'array':
            case 'object':
                $res=$this->_storage->have($name, $type);
                if($extends){
                    #todo
                }
                return $res;
            case 'template':
            case 'method':
                $filename=$name.'.php';
                if(!isset($this->_file_check[$filename])){
                    cs_log::start('@file operations');
                    $this->_file_check[$filename]=is_file($this->_fs_path.'/'.$filename);
                    cs_log::end('@file operations');
                }

                if($extends){
                    #todo
                }
                return $this->_file_check[$filename];
            case 'item':
                #todo
                return false;
            default:
                return false;
        }
    }

    private function __clone() {}

}

class cs_language extends cs_item {

    function parent($d=1){
        return null;
    }
}

class cs_container extends cs_item {

    function parent($d=1){
        return null;
    }
}

class cs_space {

    public $root;
    public $current;
    public $languages=array();
    public $current_language=array();
    public $containers=array();
    public $tmp=array(); #for temporary (no cache) data

    static private $instance;

    static function getInstance() {
        if(self::$instance === null)
            self::$instance = new cs_space();
        return self::$instance;
    }

    private function __construct() {
        $init_path=$_SERVER["PHP_SELF"];

        #LANGUAGES
        $lang_dir=cs_config::get('languages')->dir;
        $langs_path=cs_config::get('site')->root_path.'/'.$lang_dir;
        cs_log::start('@file operations');
        foreach(glob($langs_path."/*", GLOB_ONLYDIR ) as $dir_path){
            $lang=basename($dir_path);
            if(strlen($lang)==2) $this->languages[$lang]=cs_language::get('/'.$lang_dir.'/'.$lang);
        }
        cs_log::end('@file operations');

        $current_lang=cs_config::get('languages')->default;
        if( preg_match('%^/([a-z]{2})(/|$)%', $init_path, $matches) ){
            if( ($lang_code=$matches[1]) && isset($this->languages[$lang_code]) ) {
                $current_lang=$lang_code;
                if( !($init_path=substr($init_path,3)) ) $init_path='/';
            }
        }
        $this->current_language=$this->languages[$current_lang];

        #CONTAINERS
        $cont_dir=cs_config::get('containers')->dir;
        $containers_path=cs_config::get('site')->root_path.'/'.$cont_dir;
        cs_log::start('@file operations');
        foreach(glob($containers_path."/*", GLOB_ONLYDIR) as $dir_path){
            $cont=basename($dir_path);
            $this->containers[$cont]=cs_container::get('/'.$cont_dir.'/'.$cont);
        }
        cs_log::end('@file operations');

        $this->root=cs_item::get('/');
        try{
            $this->current=cs_item::get($init_path);
            #todo# проверить current на привязаность к контейнеру
            /*#todo# here or not be here*/ header('Content-Type:text/html; charset=utf-8');#todo set mime type

        }catch(Exception $e){
            #todo# internal redirect -> error404 ?
        }

    }

    private function __clone() {}

    function __sleep(){
        $this->tmp=array();
        return array_keys(get_object_vars($this));
    }

}

class cs_ml_var {
    private $_type;
    private $_value;
    private $_ml_value=array();

    function __construct($type){
        $this->_type=$type;
    }

    public function put($val, $key=null, $lang=null){
        $val=$this->_typed($val);

        if($this->_type=='array'){
            if($lang) {
                if($key) $this->_ml_value[$lang][$key]=$val;
                else $this->_ml_value[$lang][]=$val;
            } else {
                if($key) $this->_value[$key]=$val;
                else $this->_value[]=$val;
            }
        }elseif($this->_type=='object'){
            if(!$key) return;
            if($lang) {
                $this->_ml_value[$lang]->$key=$val;
            } else {
                $this->_value->$key=$val;
            }
        }else{
            if($lang)
                $this->_ml_value[$lang]=$val;
            else
                $this->_value=$val;
        }

    }

    public function get($lang){
        return isset($this->_ml_value[$lang])?$this->_ml_value[$lang]:$this->_value;
    }

    private function _typed($val){
        switch($this->_type) {
            case 'string':
            case 'text':
                return (string)$val;
            case 'int':
                return (int)$val;
            case 'float':
                return (float)$val;
            case 'bool':
                if(in_array($val,array('false','null'))) return false;
                return (bool)$val;
            default:
                return (string)$val;
        }
    }
}

class cs_storage {

    private $_source_file;
    private $_spec=array();
    private $_data=array(
        'string'=>array(),
        'text'=>array(),
        'int'=>array(),
        'float'=>array(),
        'bool'=>array(),
        'array'=>array(),
        'object'=>array()
    );
    private $_used_vars; #todo# for cache
    private $_loaded=false;

    function __construct($source){
        $this->_source_file=$source;
    }

    public function have($name, $type='string'){
        $this->_load();
        return (bool)(isset($this->_data[$type][$name]) && ($this->_data[$type][$name] instanceof cs_ml_var));
    }

    public function variable($name, $type='string', $lang){
        $this->_load();
        if(!$this->have($name, $type)) return null;
        return $this->_data[$type][$name]->get($lang);
    }

    public function special($name){
        $this->_load();
        return $this->_spec[$name];
    }

    private function _load(){
        if($this->_loaded) return;

        cs_log::start('@file operations');
        if(is_file($this->_source_file))
            $this->_parse( file_get_contents($this->_source_file) );
        cs_log::end('@file operations');

        $this->_loaded=true;
    }

    private function _parse($str){

        $is_read_text_data=false;
        $text_value='';

        foreach(explode("\n",$str) as $s){

            if($is_read_text_data){

                $pos_close_symbol=null;
                if($s{0}=='}') $pos_close_symbol=0;
                else {
                    $_pos=0;
                    while($_pos=strpos($s ,'}',$_pos+1) ){
                        if($s{$_pos-1}!='\\') {
                            $pos_close_symbol=$_pos;
                            break;
                        }
                    }
                }
                if($pos_close_symbol!==null){
                    $value=$text_value.substr($s,0,$pos_close_symbol);
                    $is_read_text_data=false;
                }else{
                    $text_value.=$s;
                    continue;
                }
            }else{

                if(!trim($s)) continue;
                list($var_s,$value)=explode('=',$s); #explode into variable and value parts
                if($value===null) continue;
                $var_s=trim($var_s);
                $value=ltrim($value);

                list($var_n,$type)=array_map('trim',explode(':',$var_s)); #explode into variable name and variable type
                $type=$type?:'string';

                if( in_array($type,array('array','object')) && substr($var_n,-1)==']' ) {
                    $key=substr( strrchr($var_n,'['), 1,-1 );
                    $var_n=trim(substr($var_n,0,-(strlen($key)+2) ));
                }else $key=''; #get key for 'array' or 'object' types

                list($var_n,$lang)=array_map('trim',explode('.',$var_n)); #explode into variable name and lang

                if($type=='text' && $value{0}=='{') {
                    $text_value=substr($value,1);
                    $is_read_text_data=true;
                }#start read text data

            }

            #dump(array($var_n,$type,$key,$lang,$value));
            if($var_s{0}=='@'){
                $this->_spec[substr($var_s,1)]=trim($value);
            }elseif(isset($this->_data[$type])){
                if(!isset($this->_data[$type][$var_n])) $this->_data[$type][$var_n]=new cs_ml_var($type);
                $this->_data[$type][$var_n]->put(trim($value), $key?:null, $lang?:null);
            }

        }
        #dump($this->_data);

    }

}

class cs_log {

    static private $_stats=array();
    static private $_stat_times=array();
    static private $_start_times=array();

    static function add($name,$val=1){
        self::$_stats[$name]+=(float)$val;
    }

    static function start($name){
        self::$_start_times[$name]=microtime(true);
    }

    static function end($name){
        self::add($name);
        self::$_stat_times[$name]+=microtime(true)-self::$_start_times[$name];
    }

    static function dump(){
        $res=array('total'=>self::$_stats,'total_time'=>self::$_stat_times);
        if(function_exists('dump')) dump($res);
        else echo "<pre>".print_r($res,true)."</pre>";
    }
}

#todo #cache{}
?>