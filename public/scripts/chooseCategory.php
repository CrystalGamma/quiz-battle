<?php
	$inputJSON = file_get_contents('php://input'); //auslesen JSON
	$input= json_decode( $inputJSON, TRUE ); //konvertieren des JSON in ein Array
	if ($input[''] !== '/schema/deal') { //Kontrolle ob das richtige Dateiformat angegeben wurde
		http_response_code(400);
		die('Falsches Datenformat');
	}
	if (substr($input['category_'], 0, 12) !== '/categories/') { //Kontrolle des richtigen Kategorieverweis
		http_response_code(400);
		die('Ungültiger Kategorieverweis');
	}
	$categorie = (int) substr($input['category_'], 12); //Auslesen der Kategorie-ID
	$stmt=$conn->prepare("SELECT MAX(rundennr) FROM runde WHERE spiel=?");
	if(!$stmt->execute([$anzuzeigendesSpielID])){
		handleError($stmt);
	}
	$round= (int)$stmt->fetchcolumn(); //Auslesen der letzten Runde des Spiels
	error_log("round$round");
    $stmt=$conn->prepare("UPDATE runde SET kategorie=:kategorie WHERE spiel=:spiel AND rundennr=:rundennr");
	if(!$stmt->execute(['kategorie' => $categorie, 'spiel' => $anzuzeigendesSpielID, 'rundennr' => $round])){
		handleError($stmt);
	}
	$stmt=$conn->prepare("SELECT fragen_pro_runde FROM spiel WHERE spiel.id=?"); //Setzen der gewünschten Kategorie für die letzte Runde
	if(!$stmt->execute([$anzuzeigendesSpielID])){
		handleError($stmt);
	}
	$questionCount=$stmt->fetch(PDO::FETCH_COLUMN); //Auslesen der Fragen pro Runde in diesem Spiel
	$stmt=$conn->prepare("SELECT frage FROM frage_kategorie fk WHERE kategorie=:category AND frage NOT IN (SELECT frage FROM spiel_frage sf WHERE sf.frage=fk.frage AND sf.spiel = :gid)");
	if(!$stmt->execute(['category' => $categorie, 'gid' => $anzuzeigendesSpielID])){
		var_dump($stmt->errorInfo());
		die();
	}
	// TODO: don't fetch all questions into memory
	$questions=$stmt->fetchAll(PDO::FETCH_COLUMN); //Sammeln der Fragen in dieser Kategorie
	if(count($questions)<$questionCount){
		error_log(count($questions)." < $questionCount");
		http_response_code(500);
		die("Es existieren nicht genügend Fragen in dieser Kategorie");
	}
	require(__DIR__.'/hashPick.php'); //Aussuche von zufälligen Fragen aus dieser Kategorie
	$keys = skyrimShuffle($anzuzeigendesSpielID.';'.$round.';'.$categorie, $questionCount, $questions);
	$stmt=$conn->prepare("INSERT INTO spiel_frage (fragennr, spiel, frage) VALUES (:fragennr, :spiel, :frage)");
	$base = $round*$questionCount;
	for($i = 0; $i < $questionCount; $i++){
		if(!$stmt->execute(['fragennr' => ($base+$i), 'spiel' => $anzuzeigendesSpielID, 'frage' => $keys[$i]])){
			handleError($stmt);
		}
	}
	$addNextRound = $conn->prepare("INSERT INTO runde (spiel, rundennr, dealer, kategorie, start) SELECT :gid, :round, spieler, NULL, NOW() FROM teilnahme, spiel WHERE spiel = :gid AND spieler != :pid AND spiel.id = :gid AND spiel.runden > :round");
	$addNextRound->execute(['gid' => $anzuzeigendesSpielID, 'pid' => $id, 'round' => $round + 1]); //Erstellen der nächsten Runde
	if (!$conn->commit()) {
		http_response_code(500);
		header('Retry-After: 3');
		die('Transaktion fehlgeschlagen');
	}
?>
