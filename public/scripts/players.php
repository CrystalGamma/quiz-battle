<?php
require_once __DIR__."/../../connection.php";
require_once __DIR__."/../checkAuthorization.php";
require_once __DIR__."/../../classes/ContentNegotation.php";
require_once __DIR__."/../../classes/PaginationHelper.php";

$contentType=ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'],"text/html,application/json;q=0.9");

$stmt = $conn->query('SELECT COUNT(*) FROM spieler');
$count = (int) $stmt->fetchColumn();
$pagination = PaginationHelper::getHelper($count);

$stmt = $conn->prepare('SELECT id AS "", name, punkte AS points FROM spieler ORDER BY points DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $pagination->getSteps(), PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination->getStart(), PDO::PARAM_INT);
$stmt->execute();
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($players as &$player) {
    $player['points'] = (int) $player['points'];
}

$array = array(
    '' => '/schema/players',
    'count' => $count,
    'start' => $pagination->getStart(),
    'end' => (int) $pagination->getEnd(),
    'next_' => $pagination->getNext(),
    'prev_' => $pagination->getPrevious(),
    'players' => $players,
);

$json = json_encode($array);

if ($contentType === "application/json"){
    header("Content-Type: $contentType; charset: utf-8");
    echo $json;
} else {
    require_once __DIR__."/../embrowsen.php";
}
?>