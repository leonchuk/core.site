<?php
$_SERVER["REQUEST_URI_ORIGINAL"]=$_SERVER["REQUEST_URI"];

list($RU,$QS)=explode("?",$_SERVER["REQUEST_URI"]);

#$RU=$GLOBALS["REQUEST_URI"]=$_SERVER["REQUEST_URI"]=cs_do_rewrite($RU);
if($QS!="") $GLOBALS["REQUEST_URI"]=$_SERVER["REQUEST_URI"]=$RU.((strpos($RU,'?'))?("&".$QS):("?".$QS));
$url_info = parse_url("http".(isset($_SERVER['HTTPS'])?'s':'')."://".$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"]);

$GLOBALS["PHP_SELF"]=$_SERVER["PHP_SELF"]=$url_info['path'];
$GLOBALS["QUERY_STRING"]=$_SERVER["QUERY_STRING"]=$url_info['query'];
parse_str($_SERVER["QUERY_STRING"], $GLOBALS["_GET"]);
parse_str($_SERVER["QUERY_STRING"], $GLOBALS["HTTP_GET_VARS"]);
####

if (get_magic_quotes_gpc()) {
    function strip_slashes_recurs($value) {
        return is_array($value) ? array_map('strip_slashes_recurs', $value) : stripslashes($value);
    }
    $_GET = array_map('strip_slashes_recurs', $_GET);
    $_POST = array_map('strip_slashes_recurs', $_POST);
    $_COOKIE = array_map('strip_slashes_recurs', $_COOKIE);
}

date_default_timezone_set('Europe/Kiev');
mb_internal_encoding("UTF-8");
setlocale(LC_ALL, "C");

if(!defined('__DIR__')) define('__DIR__', dirname(__FILE__));

foreach(glob(__DIR__.'/lib/*.php') as $libPath)
    require_once($libPath);

require_once(__DIR__.'/.conf.php');