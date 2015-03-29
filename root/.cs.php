<?php
require(__DIR__.'/../.init.php');

try{
    cs_log::start('@exec');
    $space=cs_space::getInstance();
    $space->current->template( cs_config::get('templates')->default );
    #$space->current->template( 'main', array('asd'=>'asd','qwe'=>'qwe','this'=>'fail this','space'=>'fail space',5,6,'param') );

    /*
    dump($space->current->have( 'main','string' ));
    dump($space->current->have( 'main','template' ));
    dump($space->current->have( 'main','method' ));
    dump($space->current->have( 'name2','method' ));
    dump($space->current->have( 'name3','method' ));
    dump($space->current->have( 'name4','method' ));
    dump($space->current->have( 'main','хз' ));
    */
    dump($space);
    /*
    #dump($space->current);
    #dump( $space->current->parent() );
    dump( $space->current );
    #cs_item::get('/test/123')->_childs();
    dump( cs_item::get('/test/123') );
    */

    cs_log::end('@exec');
    cs_log::dump();
}catch (Exception $e){
    echo '<pre>'.$e->getMessage().PHP_EOL.$e->getFile().' ('.$e->getLine().')</pre>';
}
?>
