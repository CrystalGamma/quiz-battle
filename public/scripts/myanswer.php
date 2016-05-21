<?php
require_once(__DIR__.'/hashPick.php');

$headers = getallheaders();
if (!array_key_exists('Content-Type', $headers) || substr($headers['Content-Type'], 0, 16) !== 'application/json') {
	http_response_code(415);
	die('Falscher Content-Type');
}
$input = json_decode(file_get_contents('php://input'), true);
if ($input[''] != '/schema/myanswer') {
	http_response_code(400);
	die('Falsches Schema');
}

$scrambledAnswer = $input['answer'];

$gid = $_GET['id'];
$qid = $_GET['qid'];

$player = getAuthorizationUser();

if ($player === false) {
	http_response_code(401);
	header('WWW-Authenticate: Token');
	die('Benötige korrektes Anmeldetoken');
}

// FIXME: check if we are within the time limit
$fetchQuestion = $conn->prepare("SELECT sf.frage as id, a.antwort as antwort, s.id as pid FROM antwort a, spiel_frage sf, spieler s WHERE a.fragennr = :qid AND a.spieler = s.id AND s.name = :player AND a.spiel = :gid AND sf.spiel = :gid AND sf.fragennr = :qid");
$fetchQuestion->execute(['player' => $player, 'gid' => $gid, 'qid' => $qid]);
$question = $fetchQuestion->fetch();
$pid = $question['pid'];
error_log("$pid");

$answerIndices = skyrimShuffle("$gid;$player:".$question['id'], 4, [0, 1, 2, 3]);

$answer = $answerIndices[$scrambledAnswer];

error_log("$scrambledAnswer -> ".implode(',', $answerIndices)." -> $answer");

$saveAnswer = $conn->prepare("UPDATE antwort SET antwort = :ans WHERE spieler = :pid AND spiel = :gid AND fragennr = :qid AND antwort IS NULL");

if(!$saveAnswer->execute(['ans' => $answer, 'pid' => $pid, 'gid' => $gid, 'qid' => $qid])) {
	http_response_code(500);
	die('Datenbankfehler');
}
if ($saveAnswer->rowCount() !== 1) {
	http_response_code(400);
	die('Es wurde schon eine Antwort gespeichert');
}
// FIXME:retry?
$conn->commit();

$scrambledCorrectAnswer = 0;
foreach ($answerIndices as $idx) {if($answerIndices[$idx] === 0) {$scrambledCorrectAnswer = $idx;break;}}

header('Content-Type: application/json');
echo json_encode(['' => '/schema/correctanswer', 'answer' => $scrambledCorrectAnswer]);
