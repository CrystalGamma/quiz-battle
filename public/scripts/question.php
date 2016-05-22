<?php
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../checkAuthorization.php';
require_once __DIR__.'/../../classes/ContentNegotation.php';

$contentType = ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'], 'text/html,application/json;q=0.9');

if (empty($_GET['id'])) {
    http_response_code(400);
    die();
}
$stmt = $conn->prepare('SELECT id AS "", frage AS question, erklaerung AS explanation, richtig, falsch1, falsch2, falsch3, bild AS picture FROM frage WHERE id = ?');
$stmt->execute([$_GET['id']]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);
if (empty($question)) {
    http_response_code(404);
    die('Eine Frage mit der ID '.$_GET['id'].' exisitiert nicht.');
}

// Antworten aneinander hängen, ohne Bezeichnung ob richtig oder falsch
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

// Ermitteln in welchen Kategorien die Frage vorkommt
$stmt = $conn->prepare('SELECT * FROM frage_kategorie WHERE frage = ?');
$stmt->execute([$question['']]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ergänzen des Pfades zu den IDs der Kategorien
$question['categories_'] = array();
foreach ($categories as $category) {
    array_push($question['categories_'], '/categories/'.$category['kategorie']);
}

$question[''] = '/schema/question';

$json = json_encode($question);
header("Cache-Control: max-age=3");
if ($contentType === 'application/json') {
    header("Content-Type: $contentType; charset=UTF-8");
    echo $json;
} else {
    require_once __DIR__.'/../embrowsen.php';
}
?>