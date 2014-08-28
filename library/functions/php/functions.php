<?php

function ppr( $data, $return = false ) {

    $debug = debug_backtrace();
    // if it doesn't look like we are in a web environment, display in a more command line friendly way...
    if(!isset($_SERVER['HTTP_HOST'])) {
        $str = print_r($data, true) . "\nCalled From: ". $debug[0]['file'] .' line '. $debug[0]['line'] ."\n\n";

        // web - wrap in pre
    } else {
        $str = '<pre>' . print_r($data, true) . '<br>Called From: '. $debug[0]['file'] .' line '. $debug[0]['line'] .'</pre>';
    }
    if( $return ) {
        return $str;
    }
    echo $str;
}