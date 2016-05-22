<?php
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
        $update=$conn->prepare('UPDATE spieler SET punkte=:punkte WHERE id=:player');
        $select=$conn->prepare('SELECT punkte From spieler WHERE id=?');
        foreach($players as $player){
            if (!$select->execute([$player])) {
                handleError($select);
            }
            $punkte=$select->fetch(PDO::FETCH_COLUMN); //Raussuchen des Punktestands des Spielers
            if($punkte>$einsatz){ //Vergleich der Punkte mit dem Einsatz. Punkte > Einsatz -> Abziehen, Punkte < Einsatz -> Punkte auf 0 setzen
                if (!$update->execute(['punkte' => $punkte-$einsatz, 'player' => $player])) {
                    handleError($update);
                }
            }else{
                if (!$update->execute(['punkte' => 0, 'player' => $player])) {
                    handleError($update);
                }
            }
        }
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
	http_response_code(200);
	header('Location: /');
	die();
}
?>