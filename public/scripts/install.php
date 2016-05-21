<?php
error_reporting(E_ALL);

$path = __DIR__.'/../../sql/';
$files = array(
    'database' => 'database.sql',
    'initial' => 'initial.sql',
    'questions' => 'questions.sql'
);

foreach ($files as $file) {
    if (!file_exists($path.$file))
        die("Das Skript $file fehlt.");
}

$pdo = new PDO('mysql:host=localhost;','root', '');
$pdo->exec('drop database if exists quizduell; create database quizduell;');
$pdo = null;

require_once __DIR__.'/../../connection.php';

if (false === $conn->exec(file_get_contents($path.'database.sql'))) {
    var_dump($conn->errorInfo());
    die('Datenbank konnte nicht erstellt werden.');
} else echo 'Datenbank wurde erstellt. ';

if (false === $conn->exec(file_get_contents($path.'initial.sql'))) {
    var_dump($conn->errorInfo());
    die('Initialbefüllung konnte nicht durchgeführt werden.');
} else echo 'Initialbefüllung wurde durchgeführt. ';

if (false === $conn->exec(file_get_contents($path.'questions.sql'))) {
    var_dump($conn->errorInfo());
    die('Fragenkatalog konnte nicht gefüllt werden.');
} else echo 'Fragenkatalog wurde gefüllt. ';

die('Alles bereit.');
?>