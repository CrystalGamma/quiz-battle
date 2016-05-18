<?php
$conn = new PDO('mysql:host=localhost;dbname=quizduell','root', '', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
if (!$conn){
    http_response_code(500);
    die('Datenbank nicht erreichbar');
}
?>