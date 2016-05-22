<?php
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../checkAuthorization.php';
require_once __DIR__.'/../../classes/ContentNegotation.php';
require_once __DIR__.'/../../classes/PaginationHelper.php';

$contentType = ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'], 'text/html,application/json;q=0.9');

// liest den Kategorienamen aus und speichert ihn in $nameCat
$stmt = $conn->prepare('select name as name FROM kategorie WHERE id = :id');  
$stmt->bindValue(':id', (int) $_GET['id'], PDO::PARAM_INT);
$stmt->execute();
$nameCat = $stmt->fetch(PDO::FETCH_ASSOC);

// Fehler falls Kategorie nicht existiert
if (empty($nameCat)) {
    http_response_code(404);
    die();
}


// liest die Anzahl aller Fragen aus, die zu einer bestimmten kategorie vorhanden sind.
$stmt = $conn->prepare('SELECT COUNT(*) From frage join frage_kategorie on frage.id = frage_kategorie.frage join kategorie on kategorie.id = frage_kategorie.kategorie WHERE kategorie.id = :id');
$stmt->bindValue(':id', (int) $_GET['id'], PDO::PARAM_INT); 
$stmt->execute();
$count = (int) $stmt->fetchColumn();
$pagination = PaginationHelper::getHelper($count);

// liest alle Fragen einer Kategorie aus (id und Fragentext) und speichert diese in dem array $fragen 
$stmt = $conn->prepare('select frage.id, frage.frage From frage join frage_kategorie on frage.id = frage_kategorie.frage join kategorie on kategorie.id = frage_kategorie.kategorie WHERE kategorie.id = :id');
$stmt->bindValue(':id', (int) $_GET['id'], PDO::PARAM_INT);
$stmt->execute();
$fragen = $stmt->fetchAll(PDO::FETCH_ASSOC);

// speichert die ausgelesenen Werte in einem array
$array = array(
        '' => '/schema/category',
		'name' => $nameCat['name'],
        'count' => $count,
        'start' => $pagination->getStart(),
        'end' => $pagination->getEnd(),
        'next_' => $pagination->getNext(),
        'prev_' => $pagination->getPrevious(),
        'questions_' => array_values($fragen)
    );



$json = json_encode($array);



// darstellung des Arrays
if ($contentType === 'application/json') {
    header("Content-Type: $contentType; charset: utf-8");
    echo $json;
} else {
    require_once __DIR__.'/../embrowsen.php';
}
?>