<?php
require_once __DIR__."/../../connection.php";
require_once __DIR__."/../checkAuthorization.php";
require_once __DIR__."/../../classes/ContentNegotation.php";

$contentType=ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'],"text/html,application/json;q=0.9");

if (isset($_GET['pid'])) {
    $stmt = $conn->query('SELECT COUNT(*) FROM spiel s, teilnahme t WHERE s.id = t.spiel');
    $count = (int) $stmt->fetchColumn();
    
    if ($_GET['start'] > $count || $_GET['end'] > $count) {
        http_response_code(404);
        die();
    }

    if (empty($_GET['end'])) {
        $steps = 10;
        if ($count < $_GET['start'] + $steps) $_GET['end'] = $count;
        else $_GET['end'] = $_GET['start'] + $steps;
    } else {
        $steps = $_GET['end'] - $_GET['start'];
    }
    
    $stmt = $conn->prepare('SELECT s.id AS id FROM spiel s, teilnahme t WHERE s.id = t.spiel LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', (int) $steps, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $_GET['start'], PDO::PARAM_INT);
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($games as &$game) {
        $game = '/games/'.$game['id'];
    }
    
    $next = $_GET['end'] >= $count ? null : $_GET['end'];
    if(!is_null($next)) {
        if ($steps != 10)
            $next .= "&end=".($next + $steps);
        $next = "?start=$next";
    }

    $prev = $_GET['start'] == 0 ? null : max(0, $_GET['start'] - $steps);
    if (!is_null($prev)) {
        if ($steps != 10)
            $prev .= "&end=".($prev + $steps);
        $prev = "?start=$prev";
    }
    
    $array = array(
        '' => '/schema/games',
        'count' => $count,
        'start' => (int) $_GET['start'],
        'end' => (int) $_GET['end'],
        'next_' => $next,
        'prev_' => $prev,
        'games' => array_values($games)
    );
} else {
    require_once __DIR__."/createGame.php";
}

$json = json_encode($array);

if ($contentType === "application/json"){
    header("Content-Type: $contentType; charset: utf-8");
    echo $json;
} else {
    require_once __DIR__."/../embrowsen.php";
}
?>