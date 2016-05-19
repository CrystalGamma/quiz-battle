<?php
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../checkAuthorization.php';
require_once __DIR__.'/../../classes/ContentNegotation.php';

$contentType = ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'], 'text/html,application/json;q=0.9');

$stmt = $conn->prepare('SELECT id AS id, name AS name FROM kategorie where 1');
$stmt->execute();
$category = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($category)) {
    http_response_code(404);
    die();
}



$category[''] = '/schema/category';


$json = json_encode($category);




if ($contentType === 'application/json') {
    header("Content-Type: $contentType; charset: utf-8");
    echo $json;
} else {
    require_once __DIR__.'/../embrowsen.php';
}
?>