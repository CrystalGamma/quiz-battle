<?php
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../checkAuthorization.php';
require_once __DIR__.'/../../classes/ContentNegotation.php';

$contentType = ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'], 'text/html,application/json;q=0.9');

if (empty($_GET['id'])) {
    http_response_code(404);
    die();
}
$stmt = $conn->prepare('SELECT id AS "", frage AS question, erklaerung AS explanation, richtig, falsch1, falsch2, falsch3, bild AS picture FROM frage WHERE id = :id');
$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
$stmt->execute();
$question = $stmt->fetch(PDO::FETCH_ASSOC);
if (empty($question)) {
    http_response_code(404);
    die();
}

$question['answers'] = array(
    $question['richtig'],
    $question['falsch1'],
    $question['falsch2'],
    $question['falsch3']
);
unset($question['richtig']);
unset($question['falsch1']);
unset($question['falsch2']);
unset($question['falsch3']);

$stmt = $conn->prepare('SELECT * FROM frage_kategorie WHERE frage = :frage');
$stmt->bindParam(':frage', $question[''], PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$question['categories_'] = array();
foreach ($categories as $category) {
    array_push($question['categories_'], '/categories/'.$category['kategorie']);
}

$question[''] = '/schema/question';

$json = json_encode($question);

if ($contentType === 'application/json') {
    header("Content-Type: $contentType; charset: utf-8");
    echo $json;
} else {
    require_once __DIR__.'/../embrowsen.php';
}
?>