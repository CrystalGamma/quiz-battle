<?php
require_once(__DIR__.'/hashPick.php');

$headers = getallheaders();
if (!array_key_exists('Content-Type', $headers) || substr($headers['Content-Type'], 0, 16) !== 'application/json') {
	http_response_code(415);
	die('Falscher Content-Type');
}
$input = json_decode(file_get_contents('php://input'), true);
if ($input[''] != '/schema/askme') {
	http_response_code(400);
	die('Falsches Schema');
}

$gid = $_GET['id'];
$qid = $_GET['qid'];

$player = getAuthorizationUser();

if ($player === false) {
	http_response_code(401);
	header('WWW-Authenticate: Token');
	die('Benötige korrektes Anmeldetoken');
}

$alreadyStarted = $conn->prepare("SELECT spieler.id, COUNT(fragennr) FROM spieler LEFT JOIN antwort ON (antwort.spieler = spieler.id) WHERE spieler.name=:player AND spiel=:game AND fragennr = :qid GROUP BY spieler.id");
if (!$alreadyStarted->execute(['player' => $player, 'game' => $gid, 'qid' => $qid])) {
	http_response_code(500);
	die('Datenbankfehler');
}
$row = $alreadyStarted->fetch();

if ($row[1] != 0) {
	http_response_code(400);
	die('Frage schon angefangen');
}

$startQuestion = $conn->prepare("INSERT INTO antwort(spiel, spieler, fragennr, antwort, startzeit) SELECT :gid, id, :qid, NULL, NOW() FROM spieler WHERE name=:player");
if (!$startQuestion->execute(['gid' => $gid, 'player' => $player, 'qid' => $qid])) {
	http_response_code(500);
	die('Datenbankfehler');
}

$fetchQuestion = $conn->prepare("SELECT id, f.frage, richtig, falsch1, falsch2, falsch3 FROM spiel_frage sf, frage f WHERE spiel = :gid AND fragennr = :qid AND sf.frage = id");
if (!$fetchQuestion->execute(['gid' => $gid, 'qid' => $qid])) {
	http_response_code(500);
	die('Datenbankfehler');
}
$question = $fetchQuestion->fetch(PDO::FETCH_NUM);

/*if (!$conn->commit()) {
	http_response_code(500);
	header('Retry-After: 3');
	die('Transaktion gescheitert');
}*/

$text = $question[1];
error_log(implode(',', array_slice($question, 2)));

header('Content-Type: application/json');
echo(json_encode([
	'' => '/schema/popquiz',
	'question' => $text,
	'answers' => skyrimShuffle("$gid;$player:".$question[0], 4, array_slice($question, 2))
]));
