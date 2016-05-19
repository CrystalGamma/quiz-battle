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
        $players=$input["players_"];
		if(count($players)<2){
			http_response_code(400);
			die();
		}
		$stmt=$conn->query("Select id from spieler");
		$stmt->execute();
		$spieler=$stmt->fetchAll(PDO::FETCH_ASSOC);
		$spielerids=array();
		foreach($spieler as $datensatz){
			array_push($spielerids, $datensatz['id']);
		}
		foreach($players as $player){
			$playerid=explode("/",$player)[2];
			if(!in_array($playerid, $spielerids)){
				http_response_code(400);
				die();
			}
		}
        $rounds=$input["rounds"];
        $turns=$input["turns"];
        $timelimit=$input["timelimit"];
        $roundlimit=$input["roundlimit"];
        $dealingrule=(int) explode("/",$input["dealingrule"])[2];
        if($dealingrule===$id){
			$stmt = $conn->prepare("Insert Into spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) Values (100, :dealer, :runden, :fragen_pro_runde, :fragenzeit, :rundenzeit, 'Offen')");
			if($stmt->execute(['dealer' => $dealingrule, 'runden' => $rounds, 'fragen_pro_runde' => $turns, 'fragenzeit' => $timelimit, 'rundenzeit' => $roundlimit])){
				$gameid=$conn->lastInsertId();
			}else{    
				var_dump($stmt->errorInfo());
				die();
			}
			$stmt = $conn->prepare("Insert Into teilnahme (spiel, spieler, akzeptiert) VALUES (:id, :spieler, :teilnahme)");
			foreach($players as $player){
				$playerid=explode("/",$player)[2];
				if($playerid===$dealingrule){
					if(!$stmt->execute(['id' => $gameid, 'spieler' => $playerid, 'teilnahme' => 1])){   
						var_dump($stmt->errorInfo());
						die();
					}
				}else{	
					if(!$stmt->execute(['id' => $gameid, 'spieler' => $playerid, 'teilnahme' => 0])){   
						var_dump($stmt->errorInfo());
						die();
					}
				}
			}
			$conn->commit();
		}else{
			http_response_code(400);
			die();
		}
		http_response_code(201);
		header('Location: http://localhost/games/$gameid');
        die();
    }else{
        http_response_code(405);
        die();
    }
?>