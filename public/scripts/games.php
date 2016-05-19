<?php
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../checkAuthorization.php';
require_once __DIR__.'/../../classes/ContentNegotation.php';
require_once __DIR__.'/../../classes/PaginationHelper.php';
$contentType = ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'], 'text/html,application/json;q=0.9');
if (isset($_GET['pid'])) {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM spiel s, teilnahme t WHERE s.id = t.spiel AND t.spieler = :player');
    $stmt->bindValue(':player', (int) $_GET['pid'], PDO::PARAM_INT);
    $stmt->execute();
    $count = (int) $stmt->fetchColumn();
    $pagination = PaginationHelper::getHelper($count);
    
    $stmt = $conn->prepare('SELECT s.id AS id FROM spiel s, teilnahme t WHERE s.status = \'beendet\' AND s.id = t.spiel AND t.spieler = :player LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':player', (int) $_GET['pid'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $pagination->getSteps(), PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination->getStart(), PDO::PARAM_INT);
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($games as &$game) {
        $game = '/games/'.$game['id'].'/';
    }
    
    $array = array(
        '' => '/schema/games',
        'count' => $count,
        'start' => $pagination->getStart(),
        'end' => $pagination->getEnd(),
        'next_' => $pagination->getNext(),
        'prev_' => $pagination->getPrevious(),
        'games_' => array_values($games)
    );
	$json = json_encode($array);
	if ($contentType === 'application/json') {
		header("Content-Type: $contentType; charset: utf-8");
		echo $json;
	} else {
		require_once __DIR__.'/../embrowsen.php';
	}
} else {
    require_once __DIR__.'/createGame.php';
}
?>
