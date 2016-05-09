<?php
require_once('../../connection.php');

if (empty($_GET['id'])) {
    http_response_code(404);
    die();
}
$stmt = $conn->prepare('SELECT id AS "", frage AS question, erklaerung AS explanation, richtig, falsch1, falsch2, falsch3, bild AS picture FROM frage WHERE id = :id');
$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
$stmt->execute();
$question = $stmt->fetch(PDO::FETCH_ASSOC);

$question['answers'] = array(
    $question["richtig"],
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

header('Content-Type: application/json; charset: utf-8');
$question[''] = '/schema/question';
echo json_encode($question);
?>