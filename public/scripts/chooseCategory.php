<?php
	$inputJSON = file_get_contents('php://input');
	$input= json_decode( $inputJSON, TRUE ); //convert JSON into array
	if ($input[''] !== '/schema/deal') {
		http_response_code(400);
		die('Falsches Datenformat');
	}
	$username = getAuthorizationUser();
	if ($username === false) {
		http_response_code(401);
		header('WWW-Authenticate: Token');
		die('Zum Annehmen oder Ablehnen von Spielen muss ein gültiger Authentifikationstoken vorliegen');
	}
	$stmt=$conn->prepare('SELECT id FROM spieler WHERE name= ?');
	if(!$stmt->execute([$username])){
		var_dump($stmt->errorInfo());
        die();
	}
	$id=array_shift($stmt->fetchAll(PDO::FETCH_ASSOC))['id'];
	if (substr($input['category_'], 0, 12) !== '/categories/') {
		http_response_code(400);
		die('Ungültiger Kategorieverweis');
	}
	$categorie = (int) substr($input['category_'], 12);
	$stmt=$conn->prepare("SELECT MAX(rundennr) FROM runde WHERE spiel=?");
	if(!$stmt->execute([$anzuzeigendesSpielID])){
		var_dump($stmt->errorInfo());
		die();
	}
	$round= (int)$stmt->fetchcolumn();
	error_log("round$round");
    $stmt=$conn->prepare("UPDATE runde SET kategorie=:kategorie WHERE spiel=:spiel AND rundennr=:rundennr");
	if(!$stmt->execute(['kategorie' => $categorie, 'spiel' => $anzuzeigendesSpielID, 'rundennr' => $round])){
		var_dump($stmt->errorInfo());
		die();
	}
	$stmt=$conn->prepare("SELECT fragen_pro_runde FROM spiel WHERE spiel.id=?");
	if(!$stmt->execute([$anzuzeigendesSpielID])){
		var_dump($stmt->errorInfo());
        die();
	}
	$questionCount=array_shift($stmt->fetchAll(PDO::FETCH_ASSOC))['fragen_pro_runde'];
	$stmt=$conn->prepare("SELECT frage FROM frage_kategorie fk WHERE kategorie=:category AND frage NOT IN (SELECT frage FROM spiel_frage sf WHERE sf.frage=fk.frage AND sf.spiel = :gid)");
	if(!$stmt->execute(['category' => $categorie, 'gid' => $anzuzeigendesSpielID])){
		var_dump($stmt->errorInfo());
		die();
	}
	// TODO: don't fetch all questions into memory
	$questions=$stmt->fetchAll(PDO::FETCH_COLUMN);
	if(count($questions)<$questionCount){
		error_log(count($questions)." < $questionCount");
		http_response_code(500);
		die("Es existieren nicht genügend Fragen in dieser Kategorie");
	}
	require(__DIR__.'/hashPick.php');
	$keys = skyrimShuffle($anzuzeigendesSpielID.';'.$round.';'.$categorie, $questionCount, $questions);
	$stmt=$conn->prepare("INSERT INTO spiel_frage (fragennr, spiel, frage) VALUES (:fragennr, :spiel, :frage)");
	$base = $round*$questionCount;
	for($i = 0; $i < $questionCount; $i++){
		if(!$stmt->execute(['fragennr' => ($base+$i), 'spiel' => $anzuzeigendesSpielID, 'frage' => $keys[$i]])){
			http_response_code(500);
			var_dump($stmt->errorInfo());
			die();
		}
	}
	$addNextRound = $conn->prepare("INSERT INTO runde (spiel, rundennr, dealer, kategorie, start) SELECT :gid, :round, spieler, NULL, NOW() FROM teilnahme, spiel WHERE spiel = :gid AND spieler != :pid AND spiel.id = :gid AND spiel.runden > :round");
	$addNextRound->execute(['gid' => $anzuzeigendesSpielID, 'pid' => $id, 'round' => $round + 1]);
	if (!$conn->commit()) {
		http_response_code(500);
		header('Retry-After: 3');
		die('Transaktion fehlgeschlagen');
	}
?>
