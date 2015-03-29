<?php
#header('Content-Type:application/json; charset=utf-8');

print_r($_GET);

$res=cs_template::load();
if(is_array($res) || is_object($res)) echo json_encode($res);
?>