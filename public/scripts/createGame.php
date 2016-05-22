<?php
    if($_SERVER['REQUEST_METHOD']=='POST'){
        $inputJSON = file_get_contents('php://input'); //auslesen JSON
        $input= json_decode( $inputJSON, TRUE ); //konvertieren des JSON in ein Array
		if ($input[''] !== '/schema/game?new') { //Kontrolle ob das richtige Dateiformat angegeben wurde
			http_response_code(400);
			die('Falsches Datenformat');
		}
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
		$id=(int) $stmt->fetchcolumn(); //Die ID des eingeloggten Users ermitteln
        $rounds=array_key_exists('rounds', $input) ? (int) $input["rounds"] : 6;
        $turns=array_key_exists('turns', $input) ? (int) $input["turns"] : 3;
        $timelimit= array_key_exists('timelimit', $input) ? (int) $input["timelimit"] : 10;
        $roundlimit= array_key_exists('roundlimit', $input) ? (int) $input["roundlimit"] : 172800;	// zwei Tage
        // TODO: dealing rule
        $dealingrule = NULL;
        //Für Matchmaking
        $vorhandenesSpiel=null;
        if(count($input["players_"])===1){
            $stmt = $conn->prepare("select spiel from (select spiel, spieler from teilnahme group by spiel having count(spiel)<2) as tmp where spieler!=?");
             $stmt->execute([$id]);
             //Erstes noch nicht vollständiges Spiel wurde identifiziert
            $vorhandenesSpiel=$stmt->fetchall()[0][0];
            $gameid=$vorhandenesSpiel;
        }
        //Wenn schon ein nicht vollständiges Spiel exisitiert muss kein neues erzeugt werden
        if($vorhandenesSpiel===null){
	$stmt = $conn->prepare("Insert Into spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) Values (100, :dealer, :runden, :fragen_pro_runde, :fragenzeit, :rundenzeit, 'offen')");
            if($stmt->execute(['dealer' => $dealingrule, 'runden' => $rounds, 'fragen_pro_runde' => $turns, 'fragenzeit' => $timelimit, 'rundenzeit' => $roundlimit])){
                    $gameid=$conn->lastInsertId();
            }else{
                    var_dump($stmt->errorInfo());
                    die('test');
            }
	}
	$insertPlayer = $conn->prepare("Insert Into teilnahme (spiel, spieler, akzeptiert) VALUES (:id, :spieler, :teilnahme)");
	$players = $input["players_"];
	foreach($players as $player){
		if (substr($player, 0, 9) !== '/players/') { //Kontrolle ob der Spieler richtig refenrenziert wurde
			http_response_code(400);
			die('Ungültiger Spielerverweis');
		}
		$playerid= (int) substr($player, 9); //Auslesen der Spieler-ID
		$stmt=$conn->prepare('SELECT * FROM spieler WHERE id=?');
		if(!$stmt->execute([$playerid])){
			handleError($stmt);
		}
		$erg=$stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($erg)!==1){ //Kontrolle ob die angegeben Spieler auch existieren
			http_response_code(400);
			die('Spieler mit der ID '.$playerid.' nicht vorhanden.');
		}
		if(!$insertPlayer->execute(['id' => $gameid, 'spieler' => $playerid, 'teilnahme' => $playerid === $id ? 1 : 0])){
			handleError($insertPlayer);
		}
		error_log("wir haben".count($input["players_"]));
		/*if(count($input["players_"])===1)
		{
                    if($vorhandenesSpiel===null){
                        $runde=0;
                    }else{
                        $runde=1;
                    }
                    error_log("ausgelöst");
                    $createFirstRound = $conn->prepare(' Insert into runde(spiel, rundennr, dealer, kategorie, start) values (:spiel, :rundenID ,:spieler,NULL, now())');
                    if (!$createFirstRound->execute(['spiel' => $gameid, 'spieler' => $playerid, 'rundenID'=>$runde])) {
                            http_response_code(500);
                            die('Konnte nicht erste Runde starten');
                    }
		}*/
		if($vorhandenesSpiel!==null)
		{
                    $createFirstRound = $conn->prepare(' Insert into runde(spiel, rundennr, dealer, kategorie, start) values (:spiel, 0 ,:spieler,NULL, now())');
                    if (!$createFirstRound->execute(['spiel' => $gameid, 'spieler' => $playerid])) {
                            http_response_code(500);
                            die('Konnte nicht erste Runde starten');
                    }
                }
	}
	if (!$conn->commit()) {
		http_response_code(500);
		header('Retry-After: 3');
		die('Transaktion fehlgeschlagen');
	}
	if((count($input["players_"])===2) or $vorhandenesSpiel!==null)
	http_response_code(201);
	header("Location: /games/$gameid/");
	die();
    }else{
        http_response_code(405);
        die();
    }
?>
