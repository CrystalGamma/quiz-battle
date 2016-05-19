<?php
    if($_SERVER['REQUEST_METHOD']=='POST'){
        $inputJSON = file_get_contents('php://input');
        $input= json_decode( $inputJSON, TRUE ); //convert JSON into array
		if ($input[''] !== '/schema/game?new') {
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
		$id=(int) $stmt->execute([$username]);
        $rounds=array_key_exists('rounds', $input) ? (int) $input["rounds"] : 6;
        $turns=array_key_exists('turns', $input) ? (int) $input["turns"] : 3;
        $timelimit= array_key_exists('timelimit', $input) ? (int) $input["timelimit"] : 10;
        $roundlimit= array_key_exists('roundlimit', $input) ? (int) $input["roundlimit"] : 172800;	// zwei Tage
        // TODO: dealing rule
        $dealingrule = NULL;
	$stmt = $conn->prepare("Insert Into spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) Values (100, :dealer, :runden, :fragen_pro_runde, :fragenzeit, :rundenzeit, 'Offen')");
	if($stmt->execute(['dealer' => $dealingrule, 'runden' => $rounds, 'fragen_pro_runde' => $turns, 'fragenzeit' => $timelimit, 'rundenzeit' => $roundlimit])){
		$gameid=$conn->lastInsertId();
	}else{
		var_dump($stmt->errorInfo());
		die('test');
	}
	$insertPlayer = $conn->prepare("Insert Into teilnahme (spiel, spieler, akzeptiert) VALUES (:id, :spieler, :teilnahme)");
	$players = $input["players_"];
	if (count($players) < 2) {
		http_response_code(400);
		die('Weniger als zwei Spieler im Spiel');
	}
	foreach($players as $player){
		if (substr($player, 0, 9) !== '/players/') {
			http_response_code(400);
			die('Ungültiger Spielerverweis');
		}
		$playerid= (int) substr($player, 9);
		// FIXME: testen, ob der Spieler auch existiert
		if(!$insertPlayer->execute(['id' => $gameid, 'spieler' => $playerid, 'teilnahme' => $playerid === $id ? 1 : 0])){
			var_dump($stmt->errorInfo());
			die();
		}
	}
	if (!$conn->commit()) {
		http_response_code(500);
		header('Retry-After: 3');
		die('Transaktion fehlgeschlagen');
	}
	http_response_code(201);
	header("Location: /games/$gameid/");
	die();
    }else{
        http_response_code(405);
        die();
    }
?>
