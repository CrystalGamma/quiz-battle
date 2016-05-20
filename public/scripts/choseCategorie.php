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
			die('Ungültiger Spielerverweis');
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
	$stmt=$conn->prepare("SELECT frage FROM frage_kategorie WHERE kategorie=?");
	if(!$stmt->execute([$categorie])){
		var_dump($stmt->errorInfo());
        die();
	}
	$questions=$stmt->fetchAll(PDO::FETCH_ASSOC);
	if(count($questions)<$questionCount){
		http_response_code(500);
		die("Es existieren nicht genügend Fragen in dieser Kategorie");
	}
	$keys=array_rand($questions,$questionCount);
	foreach($keys as $key){
		echo $questions[$key]['frage']; //Wohin mit den Fragen?
	}
	if (!$conn->commit()) {
		http_response_code(500);
		header('Retry-After: 3');
		die('Transaktion fehlgeschlagen');
	}
?>