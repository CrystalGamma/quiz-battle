<?php
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../checkAuthorization.php';
require_once __DIR__.'/../../classes/ContentNegotation.php';
require_once __DIR__.'/../../classes/PaginationHelper.php';

$contentType = ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'], 'text/html,application/json;q=0.9');

$stmt = $conn->prepare('select name as name FROM kategorie WHERE id = :id');
$stmt->bindValue(':id', (int) $_GET['id'], PDO::PARAM_INT);
$stmt->execute();
$nameyy = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt = $conn->prepare('SELECT COUNT(*) From frage join frage_kategorie on frage.id = frage_kategorie.frage join kategorie on kategorie.id = frage_kategorie.frage  WHERE kategorie.id = :id');
$stmt->bindValue(':id', (int) $_GET['id'], PDO::PARAM_INT); 
$stmt->execute();
$count = (int) $stmt->fetchColumn();
$pagination = PaginationHelper::getHelper($count);

$stmt = $conn->prepare('select frage.id, frage.frage From frage join frage_kategorie on frage.id = frage_kategorie.frage join kategorie on kategorie.id = frage_kategorie.frage  WHERE kategorie.id = :id');
$stmt->bindValue(':id', (int) $_GET['id'], PDO::PARAM_INT);
$stmt->execute();
$name = $stmt->fetchAll(PDO::FETCH_ASSOC);


$array = array(
        '' => '/schema/category',
		'name' => $nameyy['name'],
        'count' => $count,
        'start' => $pagination->getStart(),
        'end' => $pagination->getEnd(),
        'next_' => $pagination->getNext(),
        'prev_' => $pagination->getPrevious(),
        'questions_' => array_values($name)
    );



$json = json_encode($array);




if ($contentType === 'application/json') {
    header("Content-Type: $contentType; charset=UTF-8");
    echo $json;
} else {
    require_once __DIR__.'/../embrowsen.php';
}
?>