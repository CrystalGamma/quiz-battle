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
	$stmt=$conn->prepare("SELECT rundennr FROM runde WHERE spiel=? ORDER BY rundennr DESC LIMIT 1");
	if(!$stmt->execute([$anzuzeigendesSpielID])){
		var_dump($stmt->errorInfo());
		die();
	}
	$round=array_shift($stmt->fetchAll(PDO::FETCH_ASSOC))['rundennr'];
    $stmt=$conn->prepare("UPDATE runde SET kategorie=:kategorie WHERE spiel=:spiel AND dealer=:spieler AND rundennr=:rundennr");
	if(!$stmt->execute(['kategorie' => $categorie, 'spiel' => $anzuzeigendesSpielID, 'spieler' => $id, 'rundennr' => $round])){
		var_dump($stmt->errorInfo());
        die();
	}
	$stmt=$conn->prepare("SELECT fragen_pro_runde FROM spiel WHERE spiel.id=?");
	if(!$stmt->execute([$anzuzeigendesSpielID])){
		var_dump($stmt->errorInfo());
        die();
	}
	$questionCount=array_shift($stmt->fetchAll(PDO::FETCH_ASSOC))['fragen_pro_runde'];
	$stmt=$conn->prepare("SELECT frage FROM frage_kategorie fk WHERE kategorie=:category AND NOT EXISTS(SELECT * FROM spiel_frage sf WHERE sf.frage=fk.frage)");
	if(!$stmt->execute(['category' => $categorie])){
		var_dump($stmt->errorInfo());
		die();
	}
	// TODO: don't fetch all questions into memory
	$questions=$stmt->fetchAll(PDO::FETCH_COLUMN);
	if(count($questions)<$questionCount){
		http_response_code(500);
		die("Es existieren nicht genügend Fragen in dieser Kategorie");
	}
	require(__DIR__.'/hashPick.php');
	// FIXME: this fails (Warning: Second argument has to be between 1 and the number of elements in the array)
	$keys=skyrimShuffle($anzuzeigendesSpielID.';'.$round.';'.$categorie, $questionCount, $questions);
	error_log(implode(',', $keys));
	$stmt=$conn->prepare("INSERT INTO spiel_frage (fragennr, spiel, frage) VALUES (:fragennr, :spiel, :frage)");
	$base = $round*$questionCount;
	for($i = 0; $i < $questionCount; $i++){
		if(!$stmt->execute(['fragennr' => ($base+$i), 'spiel' => $anzuzeigendesSpielID, 'frage' => $keys[$i]])){
			http_response_code(500);
			var_dump($stmt->errorInfo());
			die();
		}
	}
	if (!$conn->commit()) {
		http_response_code(500);
		header('Retry-After: 3');
		die('Transaktion fehlgeschlagen');
	}
?>
