<?php
require_once('../../connection.php');

$stmt = $conn->query('SELECT COUNT(*) FROM spieler');
$count = $stmt->fetchColumn();
if ($_GET['start'] > $count || $_GET['end'] > $count) {
    http_response_code(404);
    die();
}

if (empty($_GET['end'])) {
    if ($count < $_GET['start'] + 10) $_GET['end'] = $count;
    else $_GET['end'] = $_GET['start'] + 10;
}
$stmt = $conn->prepare('SELECT id AS "", name, punkte AS points FROM spieler LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', (int) $_GET['end'], PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $_GET['start'], PDO::PARAM_INT);
$stmt->execute();
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$array = array(
    '' => '/schema/players',
    'count' => $count,
    'start' => (int) $_GET['start'],
    'end' => $_GET['end'],
    'next_' => $_GET['end'] > $count ? null : '?start='.$_GET['end'],
    'prev_' => $_GET['start'] == 0 ? null : '?start='.$_GET['start'],
    'players' => $players,
);

header('Content-Type: application/json');
echo json_encode($array);