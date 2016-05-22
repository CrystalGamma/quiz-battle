<?php
$username = getAuthorizationUser(); //Nachschauen welcher User eingeloggt ist
if ($username === false) {
	http_response_code(401);
	header('WWW-Authenticate: Token');
	die('Zum Annehmen oder Ablehnen von Spielen muss ein gültiger Authentifikationstoken vorliegen');
}
$stmt=$conn->prepare('SELECT id FROM spieler WHERE name= ?'); 
if(!$stmt->execute([$username])){
    handleError($stmt);
}
$id=$stmt->fetch(PDO::FETCH_COLUMN); //Die ID des eingeloggten Users ermitteln

$inputJSON = file_get_contents('php://input'); //auslesen JSON
$input = json_decode($inputJSON, TRUE); //konvertieren des JSON in ein Array
if ($input[''] !== '/schema/response') { //Kontrolle ob das richtige Dateiformat angegeben wurde
	http_response_code(400);
	die('Falsches Datenformat');
}
if ($input['accept'] === true) {
	$stmt= $conn->prepare('SELECT akzeptiert FROM teilnahme WHERE spiel=:spiel AND spieler=:spieler');
	if(!$stmt->execute(['spiel' => $anzuzeigendesSpielID, 'spieler' => $id])){
		handleError($stmt);
	}
	if($stmt->rowCount()!==1){  //Kontrolle ob der Spieler überhaupt eingeladen ist
		http_response_code(400);
		die('Spieler ist zu diesem Spiel nicht eingeladen');
	}
	$teilnahme=$stmt->fetch(PDO::FETCH_COLUMN); //Kontrolle ob der Spieler bereits zugesagt hat
	if($teilnahme==1){
		http_response_code(400);
		die('Der Spieler hat bereits zugesagt');
	}
	$stmt = $conn->prepare('UPDATE teilnahme SET akzeptiert=1 WHERE spieler=:spieler AND spiel=:spiel'); //Update Teilnahme auf akzeptiert
	if (!$stmt->execute(['spiel' => $anzuzeigendesSpielID, 'spieler' => $id])) {
		handleError($stmt);
	}
    $stmt=$conn->prepare('SELECT akzeptiert FROM teilnahme WHERE spiel=?');
    if (!$stmt->execute([$anzuzeigendesSpielID])) {
		handleError($stmt);
	}
    $zusagen=$stmt->fetchAll(PDO::FETCH_COLUMN);
    $alleZugesagt=true;
    foreach($zusagen as $zusage){
        if(!$zusage){
            $alleZugesagt=false;
        }
    }
    if($alleZugesagt){ //Kontrolle ob schon alle Spieler zugesagt haben
        $stmt=$conn->prepare('SELECT einsatz FROM spiel WHERE id=?'); //Raussuchen des Einsatzes für das Spiel
        if (!$stmt->execute([$anzuzeigendesSpielID])) {
            handleError($stmt);
        }
        $einsatz=$stmt->fetch(PDO::FETCH_COLUMN);
        $stmt=$conn->prepare('SELECT spieler FROM teilnahme WHERE spiel=?'); //Raussuchen der beteiligten Spieler
        if (!$stmt->execute([$anzuzeigendesSpielID])) {
            handleError($stmt);
        }
        $players=$stmt->fetchAll(PDO::FETCH_COLUMN);
	$conn->prepare("UPDATE spieler, teilnahme SET punkte = (CASE WHEN punkte < :points THEN 0 ELSE punkte - :points END) WHERE spieler=id AND spiel=:gid")->execute(['points'=>$einsatz/2/count($players), 'gid' => $anzuzeigendesSpielID]);
        $createFirstRound = $conn->prepare('INSERT INTO runde(spiel, rundennr, dealer, kategorie, start) SELECT :spiel, 0, id, NULL, now() FROM spieler WHERE name = :spieler');
        if (!$createFirstRound->execute(['spiel' => $anzuzeigendesSpielID, 'spieler' => $username])) { //Erstellen der ersten Runde
            http_response_code(500);
            die('Konnte nicht erste Runde starten');
        }
    }
} else {
	// TODO: should games only be deleted if there are less than 2 participants?<
	$stmt = $conn->prepare('DELETE FROM spiel where id = ?');
	if (!$stmt->execute([$anzuzeigendesSpielID])) { //Löschen des Spiels wenn ein Spieler absagt
		handleError($stmt);
	}
}
if (!$conn->commit()) {
	http_response_code(500);
	header('Retry-After: 3');
	die('Transaktion gescheitert');
}
if ($input['accept'] === true) {
	http_response_code(205);
	die();
} else {
	http_response_code(201);
	header('Location: /');
	die();
}
?>
