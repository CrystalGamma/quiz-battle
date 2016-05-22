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
$fetchQuestion = $conn->prepare("SELECT sf.frage as id, a.antwort as antwort, s.id as pid FROM antwort a, spiel_frage sf, spieler s WHERE a.fragennr = :qid AND a.spieler = s.id AND s.name = :player AND a.spiel = :gid AND sf.spiel = :gid AND sf.fragennr = :qid");
$fetchQuestion->execute(['player' => $player, 'gid' => $gid, 'qid' => $qid]);
$question = $fetchQuestion->fetch();
$pid = $question['pid'];

$answerIndices = skyrimShuffle("$gid;$player:".$question['id'], 4, [0, 1, 2, 3]);

$answer = $answerIndices[$scrambledAnswer];

//Überprüfung zeit abgelaufen
$stmt= $conn->prepare('select timestampdiff(second, startzeit, now())>spiel.fragenzeit from antwort, spiel where antwort.spiel=spiel.id and spieler= :pid and spiel= :gid and fragennr= :qid;');
//1= ist abgelaufen, 0 = noch zeit
$stmt->execute(['pid' => $pid,'gid' => $gid,'qid' => $qid]);
$zeitUeberschreitung=(int) $stmt->fetchcolumn();
if($zeitUeberschreitung === 0){
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
}
$conn->commit();
//Antwort ausgeben
$stmt=$conn->prepare("select frage.erklaerung from frage, spiel_frage where frage.id=spiel_frage.frage and spiel_frage.spiel= :gid and spiel_frage.fragennr= :qid");
$stmt->execute(['gid' => $gid, 'qid' => $qid]);
$erklaerung= $stmt->fetchcolumn();

$scrambledCorrectAnswer = 0;
foreach ($answerIndices as $idx) {if($answerIndices[$idx] === 0) {$scrambledCorrectAnswer = $idx;break;}}

$checkForNextQuestion = $conn->prepare("SELECT fragennr FROM spiel_frage sf WHERE spiel=:gid AND NOT EXISTS(SELECT * FROM antwort a WHERE a.fragennr=sf.fragennr AND a.spiel=sf.spiel AND spieler=:pid) ORDER BY fragennr LIMIT 1");
$checkForNextQuestion->execute(['gid' => $gid, 'pid' => $pid]);
$nextQuestion = $checkForNextQuestion->fetchall(PDO::FETCH_COLUMN, 0);
if($zeitUeberschreitung===1){
    http_response_code(403);
}
else if (count($nextQuestion) > 0) {
        http_response_code(201);
	header("Location: /games/$gid/".$nextQuestion[0]);
}
require_once(__DIR__.'/gameEnd.php');
cleanGame($conn, $gid);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['' => '/schema/correctanswer', 'answer' => $scrambledCorrectAnswer, 'explanation' => $erklaerung]);
