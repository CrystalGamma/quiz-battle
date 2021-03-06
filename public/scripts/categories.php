<?php
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../checkAuthorization.php';
require_once __DIR__.'/../../classes/ContentNegotation.php';

$contentType = ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'], 'text/html,application/json;q=0.9');

// liest Kategorienamen und -id´s zu allen Kategorien aus
$stmt = $conn->prepare('SELECT id AS "", name AS name FROM kategorie');
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$json = json_encode(array(
    '' => '/schema/category',
    'categories' => array_values($categories) 
));

// darstellung der Abfrageergebnisse
if ($contentType === 'application/json') {
    header("Content-Type: $contentType; charset=UTF-8");
    echo $json;
} else {
    require_once __DIR__.'/../embrowsen.php';
}
?>