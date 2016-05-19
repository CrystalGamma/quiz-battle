<?php
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../checkAuthorization.php';
require_once __DIR__.'/../../classes/ContentNegotation.php';
require_once __DIR__.'/../../classes/PaginationHelper.php';

$contentType = ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'], 'text/html,application/json;q=0.9');

// POST test with: curl http://localhost/players/ -H "Content-Type: application/json" -X POST -d "{\"\":\"/schema/player\",\"name\":\"test\",\"password\":\"test\"}" -i
$requestBody = json_decode(file_get_contents('php://input'), true);
if (isset($requestBody)) {
    header('Content-Type: text/plain; charset=utf-8');
    
    $headers = getallheaders();
    if (strpos($headers['Content-Type'], 'application/json') === false) {
        http_response_code(400);
        die('Nicht erwarteter Content-Type; erwartete wurde application/json.');
    }
    if ($requestBody[''] !== '/schema/player') {
        http_response_code(400);
        die('Falsches Schema.');
    }
    if (!is_string($requestBody['name']) or !is_string($requestBody['password'])) {
        http_response_code(400);
        die('Username und Passwort müssen ein String sein.');
    }
    
    $conn->beginTransaction();
    $stmt = $conn->prepare('SELECT id FROM spieler WHERE name = ?');
    $stmt->execute([$requestBody['name']]);
    $player = $stmt->fetch();
    if ($player) {
        http_response_code(400);
        die('Username ist bereits vergeben.');
    } else {
        $stmt = $conn->prepare('INSERT INTO spieler (name, passwort, punkte) VALUES (:name, :password, :points)');
        $stmt->bindValue(':name', $requestBody['name']);
        $stmt->bindValue(':password', password_hash($requestBody['password'], PASSWORD_DEFAULT));
        $stmt->bindValue(':points', 0, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $conn->commit();
            die('Erfolgreich.');
        } else {
            $conn->rollBack();
            http_response_code(400);
            die('Nicht erfolgreich.'); // Error-Handling?
        }
    }
} else {
// GET ranking
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

    if ($contentType === 'application/json') {
        header("Content-Type: $contentType; charset: utf-8");
        echo $json;
    } else {
        header('Content-Type: text/html; charset=utf-8');
        require_once __DIR__.'/../ranking.php';
    }
}
?>