<?php
error_reporting(E_ALL);

$path = __DIR__.'/../../sql/';
$files = array(
    'Datenbankerstellung' => 'database.sql',
    'Initialbefüllung' => 'initial.sql',
    'Fragenkatalog erstellen' => 'questions.sql'
);

foreach ($files as $file) {
    if (!file_exists($path.$file))
        die("Das Skript $file fehlt.");
}

$pdo = new PDO('mysql:host=localhost;','root', '');
$pdo->exec('drop database if exists quizduell; create database quizduell character set utf8mb4 collate utf8mb4_bin;');
$pdo = null;

require_once __DIR__.'/../../connection.php';

foreach ($files as $desc => $file)
if (false === $conn->exec(file_get_contents($path.$file))) {
    var_dump($conn->errorInfo());
    die("$desc konnte nicht durchgeführt werden.");
} else echo "$desc abgeschlossen. ";

die('Alles bereit.');
?>