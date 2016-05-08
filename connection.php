<?php
$conn = new PDO('mysql:host=localhost;dbname=quizduell',"root", "");
if(!$conn){
    http_response_code(500);
    die("Datenbank nicht erreichbar");
}
?>
