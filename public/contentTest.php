<?php
    require_once "../classes/ContentNegotation.php";
    $header=$_SERVER['HTTP_ACCEPT'];
    $server="text/html,application/json;q=0.9";
    $content=ContentNegotation::getContent($header,$server);
    print_r($content);
?>