<?php
/*
```json
{
	"": "/schema/game",
	"players": [
		{"": "/players/<id>", "name": "Spielername", "accepted": true}
	],
	"rounds": [{
		"category_": {"":"/categories/<id>","name":"name"},
		"dealer": {"": "/players/<id>", "name": "Spielername"},
		"started": "2016-05-08T10:33:52Z"
	}, {
		"candidates_": ["/categories/<id>"],
		"dealer": {"": "/player/<id>", "name": "Spielername"},
		"started": "2016-05-08T11:33:52Z"
	}, {}, {}, {}],
	"turns": 3,
	"timelimit": 10,
	"roundlimit": 172800,
	"questions": [
		{"": "<qid>", "answers": antwortid}
		0-3 sind antworten, null ist noch nicht beantwortete "" ist abgelaufen
	],
	"dealingrule": "/dealing/firstanswer"
}
`turns`: Anzahl Fragen pro Runde
`timelimit`: Antwortzeit in Sekunden
`roundlimit`: max. Dauer einer Runde (Sekunden)
`dealer`: Spieler, der die Kategorie der Runde bestimmt hat/bestimmen darf (wenn noch nicht gewählt)
`dealingrule`: URL für verschiedene Regeln, wer die nächste Runde bestimmen darf (`/dealing/firstanswer`: der erste Spieler, der nicht Dealer der letzten Runde war und alle Fragen der Runde beantwortet hat). Kann ggf. auch eine Spieler-URL sein, damit ein Spieler alle Runden bestimmen darf.

`questions.status`: Antwortstatus in der Reihenfolge der Spielerliste: `null` wenn noch nicht beantwortet, `true` wenn richtig, `false` wenn falsch, `""` wenn Zeit abgelaufen.
*/

require_once __DIR__."/../../connection.php";
require_once __DIR__."/../checkAuthorization.php";
require_once __DIR__."/../../classes/ContentNegotation.php";
require_once(__DIR__.'/gameEnd.php');

//Spielername holen
$stmt= $conn->prepare('select spiel.status from spiel where spiel.id=?');
$anzuzeigendesSpielID=$_GET['id'];

//Überprüfen ob eine ID mitgegeben wurde-- nicht benötigt
$stmt->execute([$anzuzeigendesSpielID]);
$row = $stmt->fetch();
//Überprüfung ob es das Spiel gibt
if($row===false){
    http_response_code(404);
    die("Das Spiel mit der ID ".$anzuzeigendesSpielID." exisitiert nicht");
}
$conn->rollback();
cleanGame($conn, $anzuzeigendesSpielID);

$request=$_SERVER['REQUEST_METHOD'];
if ($request === 'GET') {
        $Status=$row['status'];
	getGame($conn, $anzuzeigendesSpielID, $Status);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, TRUE); //convert JSON into array
	if ($input[''] !== '/schema/response') {
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
	if ($input['accept'] === true) {
		$stmt= $conn->prepare('SELECT akzeptiert FROM teilnahme WHERE spiel=:spiel AND spieler=:spieler');
		if(!$stmt->execute(['spiel' => $anzuzeigendesSpielID, 'spieler' => $id])){
			http_response_code(500);
			var_dump($stmt->errorInfo());
			die();
		}
		if($stmt->rowCount()!==1){
			http_response_code(400);
			die('Spieler ist zu diesem Spiel nicht eingeladen');
		}
		$teilnahme=$stmt->fetch();
		if($teilnahme['akzeptiert']==1){
			http_response_code(400);
			die('Der Spieler hat bereits zugesagt');
		}
		$stmt = $conn->prepare('UPDATE teilnahme SET akzeptiert=1 WHERE spieler=:spieler AND spiel=:spiel');
		if (!$stmt->execute(['spiel' => $anzuzeigendesSpielID, 'spieler' => $id])) {
			http_response_code(500);
			var_dump($stmt->errorInfo());
			die();
		}
		$stmt=$conn->prepare('SELECT einsatz FROM spiel WHERE id=?');
		if (!$stmt->execute([$anzuzeigendesSpielID])) {
			http_response_code(500);
			var_dump($stmt->errorInfo());
			die();
		}
		$einsatz=$stmt->fetch()['einsatz'];
		$stmt=$conn->prepare('SELECT spieler FROM teilnahme WHERE spiel=?');
		if (!$stmt->execute([$anzuzeigendesSpielID])) {
			http_response_code(500);
			var_dump($stmt->errorInfo());
			die();
		}
		$players=$stmt->fetchAll(PDO::FETCH_COLUMN);
		$update=$conn->prepare('UPDATE spieler SET punkte=:punkte WHERE id=:player');
		$select=$conn->prepare('SELECT punkte From spieler WHERE id=?');
		foreach($players as $player){
			if (!$select->execute([$player])) {
				http_response_code(500);
				var_dump($stmt->errorInfo());
				die();
			}
			$punkte=$select->fetch(PDO::FETCH_COLUMN);
			if($punkte>$einsatz){
				if (!$update->execute(['punkte' => $punkte-$einsatz, 'player' => $player])) {
					http_response_code(500);
					var_dump($stmt->errorInfo());
					die();
				}
			}else{
				if (!$update->execute(['punkte' => 0, 'player' => $player])) {
					http_response_code(500);
					var_dump($stmt->errorInfo());
					die();
				}
			}
		}
		$createFirstRound = $conn->prepare('INSERT INTO runde(spiel, rundennr, dealer, kategorie, start) SELECT :spiel, 0, id, NULL, now() FROM spieler WHERE name = :spieler');
		if (!$createFirstRound->execute(['spiel' => $anzuzeigendesSpielID, 'spieler' => $username])) {
			http_response_code(500);
			die('Konnte nicht erste Runde starten');
		}
	} else {
		// TODO: should games only be deleted if there are less than 2 participants?
		// FIXME: cascading deletes
		$stmt = $conn->prepare('DELETE FROM spiel where id = ?');
		if (!$stmt->execute([$anzuzeigendesSpielID])) {
			http_response_code(500);
			var_dump($stmt->errorInfo());
			die();
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
}else if($_SERVER['REQUEST_METHOD']=='POST'){
    require_once(__DIR__."/chooseCategory.php");
}else{
    http_response_code(405);
    die();
}

function getGame ($conn, $anzuzeigendesSpielID, $Status) {
        //Informationen für JSON Element Spiel
        $stmt= $conn->prepare('select spiel.fragen_pro_runde, spiel.runden, spiel.fragenzeit, spiel.rundenzeit, (case when spiel.dealer=NULL then "firstanswer" else spiel.dealer end) as dealingrule from spiel where spiel.id=?');
        $stmt->execute([$anzuzeigendesSpielID]);
        $spiel=$stmt->fetchall()[0];
        //Aufbau des JSON Teil-Element Players
        $stmt= $conn->prepare('select spieler.name, spieler.id, teilnahme.akzeptiert from spieler, teilnahme where spieler.id=teilnahme.spieler and teilnahme.spiel=? order by spieler.id;');
        $stmt->execute([$anzuzeigendesSpielID]);
        $spieler=array();
        // Feststellung on angemeldeter User am Spiel beteiligt ist 
        $nutzername=getAuthorizationUser();
        $SpielerIstBeteiligt=false;
        $SpielerPos;
        foreach ($stmt->fetchall() as $key => $value){
            array_push($spieler,
                [
                ""=> "/players/".$value['id'],
                "name"=> $value['name'],
                "accepted"=> (bool)$value['akzeptiert']
                ]
            );
                if($nutzername!==false){
                    if($value['name']===$nutzername){
                    $SpielerIstBeteiligt=true;
                    $SpielerPos=$key;
                    }
                }
        };
        //Aufbau des JSON-Teilelements  rounds
        $stmt= $conn->prepare('select runde.rundennr, runde.kategorie as kategorieID, kategorie.name as kategorieName, spieler.id as dealerID, spieler.name as dealerName, runde.start from spieler, runde left join kategorie on (runde.kategorie=kategorie.id) where runde.dealer=spieler.id and runde.spiel=? group by runde.rundennr order by runde.rundennr;');
        $stmt->execute([$anzuzeigendesSpielID]);
        $runden = [];
	foreach ($stmt->fetchall() as $value){
		$runde = [
			'category' => $value['kategorieID'] ? [
				'' => '/categories/'.$value['kategorieID'],
				'name' => $value['kategorieName']
			] : null,
			"dealer" => [
				'' => "/players/".$value['dealerID'],
				'name' => $value['dealerName']
			],
			'started' => date(DATE_ISO8601, strtotime($value['start']))
		];
		if (!$value['kategorieID']) {
			// FIXME: make proper pseudorandom selection
			$categorySelection = $conn->prepare('SELECT id, name FROM kategorie');
			$categorySelection->execute();
			$candidates = [];
			foreach ($categorySelection->fetchAll() as $cat) {array_push($candidates, ['' => '/categories/'.$cat['id'], 'name' => $cat['name']]);}
			$runde['candidates'] = $candidates;
		}
		array_push($runden, $runde);
	};
	//Aufbau des JSON-Teilelements questions
	//mit answers pro frage in der Reihenfolge der Spieler in players
	for ($restrunden = $spiel['runden'] - count($runden);$restrunden > 0;$restrunden -= 1) {array_push($runden, null);}
        $stmt= $conn->prepare("
SELECT spiel_frage.fragennr,teilnahme.spieler, (case when antwort.startzeit+spiel.fragenzeit < now() and antwort.antwort IS NULL then '' else antwort.antwort end) as antwort, (case when antwort.startzeit+spiel.fragenzeit < now() then 'abgel' else antwort.antwort end) as status
FROM (spiel, teilnahme, spiel_frage) LEFT JOIN antwort on (spiel_frage.fragennr=antwort.fragennr and antwort.spiel=spiel_frage.spiel and teilnahme.spieler=antwort.spieler)
WHERE spiel_frage.spiel=? and teilnahme.spiel=spiel_frage.spiel and teilnahme.spiel=spiel.id
ORDER BY spiel_frage.fragennr, teilnahme.spieler");
        $stmt->execute([$anzuzeigendesSpielID]);
        $RueckgabeWert=$stmt->fetchall();
        $fragen = [];
        $tmp = [];
	if (count($RueckgabeWert) > 0) {
		$fragenID=$RueckgabeWert[0]['fragennr'];
		foreach ($RueckgabeWert as $key2 => $value) {
		//solange die FragenID gleich ist wird das Array der Frage gefüllt wenn nicht wird ein neues Array angefangen
			if ($value['fragennr'] === $fragenID) {
                                //Unterscheidung zwischen int und anderen Wertetypen
				if($value['antwort']==="" or $value['antwort']===null ){
					//$tmp and
					array_push($tmp, $value['antwort']);
				} else {
                                       // echo "wert".(int) $value['antwort'];
					//FIXME $tmp and löste irgendeinproblem verursacht aber ein anders
					array_push($tmp, (int)$value['antwort']);
				}
                                //Änderung des Inhalts nach Status der Antwort
                                
				if($value['status']===null){
					//$tmp=null;
                                    if($Status!=="beendet"){
                                        //nicht angemeldet oder nicht Teil des Spiels
                                        if($nutzername!==false or $SpielerIstBeteiligt===false) {
                                            $tmp=null;
                                        }
                                        //angemeldet und Teil des Spiels und hat noch nicht geantwortet
                                        if($SpielerIstBeteiligt===true and $SpielerPos===$key2){
                                            $tmp=null;
                                        }
                                    }
				}
			} else {
				array_push($fragen, [
					"" => $fragenID,
					"answers" => $tmp
				]);
				$fragenID=$value['fragennr'];
				$tmp=array();
				if($value['antwort']==="" or $value['antwort']===null ){
				array_push($tmp, $value['antwort']);
				}else{
				array_push($tmp, (int)$value['antwort']);
				}
				//Änderung des Angezeigten nach Status meiner Antwort(geantwortete oder nicht) und Spielstatus
				if($value['status']===null){
				//	$tmp=null;//--> es werden keine Antworten zum Spiel gezeigt
                                    if($Status!=="beendet"){
                                        //nicht angemeldet oder nicht Teil des Spiels
                                        if($nutzername!==false or $SpielerIstBeteiligt===false) {
                                            $tmp=null;
                                        }
                                        //angemeldet und Teil des Spiels und hat noch nicht geantwortet
                                        if($SpielerIstBeteiligt===true and $SpielerPos===$key2){
                                            $tmp=null;
                                        }
                                    }
				}
			};
		}
		array_push($fragen, [
			"" => $fragenID,
			"answers" => $tmp
		]);
	}
	//Zusamenfügen der Teilelemente zum Gesamtelement
	$array=[
		"" =>"/schema/game",
		'players' =>$spieler,
		'rounds' => $runden,
		'turns' => $spiel['fragen_pro_runde'],
		'timelimit' => $spiel['fragenzeit'],
		'roundlimit' => $spiel['rundenzeit'],
		'questions' => $fragen,
		'dealingrule' => '/dealingrule/'.$spiel['dealingrule']
	];
        // FIXME: no longer varies over Authorization if game is over
        header('Vary: Accept, Authorization');
        $contentType=ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'],"text/html,application/json;q=0.9");
	if($contentType === "application/json"){
		header('Content-Type: application/json');
		echo json_encode($array);
	}else{
		// TODO: looks horrible
		$GLOBALS['array'] = $array;
		require_once __DIR__."/../game.html.php";
	}
}
?>
