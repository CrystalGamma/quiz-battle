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
//Authorisierung??    
$request=$_SERVER['REQUEST_METHOD'];
if ($request === 'GET') {
	getGame();
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
	if ($input['accept'] === true) {
		$stmt = $conn->prepare('UPDATE teilnahme t, spieler s SET t.akzeptiert=1 WHERE t.spieler=s.id AND spiel=:spiel AND s.name = :spieler');
		if (!$stmt->execute(['spiel' => $anzuzeigendesSpielID, 'spieler' => $username])) {
			http_response_code(500);
			var_dump($stmt->errorInfo());
			die();
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
    require_once(__DIR__."/choseCategorie.php");
}else{
    http_response_code(405);
    die();
}

function getGame (){
        //Players
        global $conn;
        global $anzuzeigendesSpielID;
        global $nutzername;
        $contentType=ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'],"text/html,application/json;q=0.9");
        $stmt= $conn->prepare('select spieler.name, spieler.id, teilnahme.akzeptiert from spieler, teilnahme where spieler.id=teilnahme.spieler and teilnahme.spiel=? order by spieler.id;');
        $stmt->execute([$anzuzeigendesSpielID]);
        $spieler=array();
        foreach ($stmt->fetchall() as $value){
            array_push($spieler,
                [
                ""=> "/players/".$value['id'],
                "name"=> $value['name'],
                "accepted"=>(bool)$value['akzeptiert']
                ]
            );
        };
        //bestimme die Stelle des angemeldeten Nutzers in dem Array
        $playerPos;
        foreach ($spieler as $key => $value) {
            if($value['name']===$nutzername) {
                $playerPos=$key;
            }
        }
        $stmt= $conn->prepare('select runde.rundennr, runde.kategorie as kategorieID, kategorie.name as kategorieName, spieler.id as dealerID, spieler.name as dealerName, runde.start from runde, kategorie, spieler where runde.dealer=spieler.id and runde.kategorie=kategorie.id and runde.spiel=? group by runde.rundennr order by runde.rundennr;');
        $stmt->execute([$anzuzeigendesSpielID]);
        $runden=array();
        foreach ($stmt->fetchall() as $value){
            array_push($runden,
                [
                "category_"=>[
                ""=>"/categories/".$value['kategorieID'],
                "name" =>$value['kategorieName']
                ],
            "dealer"=> [
                        ""=> "/players/".$value['dealerID'],
                        "name"=>$value['dealerName']
                        ],
            "started"=> date(DATE_ISO8601, strtotime($value['start']))
                ]
            );
        };
        $stmt= $conn->prepare('select spiel.runden, spiel.fragenzeit, spiel.rundenzeit, (case when spiel.dealer=NULL then "firstanswer" else spiel.dealer end) as dealingrule from spiel where spiel.id=?');
        $stmt->execute([$anzuzeigendesSpielID]);
        $spiel=$stmt->fetchall();
        $stmt= $conn->prepare('select spiel_frage.fragennr,teilnahme.spieler, (case when antwort.startzeit+spiel.fragenzeit < now() then "" else antwort.antwort end) as antwort, (case when antwort.startzeit+spiel.fragenzeit < now() then "abgel" else antwort.antwort end) as status from (spiel, teilnahme, spiel_frage) left join antwort on (spiel_frage.fragennr=antwort.fragennr and antwort.spiel=spiel_frage.spiel and teilnahme.spieler=antwort.spieler) where spiel_frage.spiel=? and teilnahme.spiel=spiel_frage.spiel and teilnahme.spiel=spiel.id order by spiel_frage.fragennr, teilnahme.spieler;

');
        $stmt->execute([$anzuzeigendesSpielID]);
        $RueckgabeWert=$stmt->fetchall();
        $fragen = [];
        $tmp = [];
        if (count($RueckgabeWert) > 0) {
            $fragenID=$RueckgabeWert[0]['fragennr'];
            foreach ($RueckgabeWert as $value) {
                    if ($value['fragennr'] === $fragenID) {
                            if($value['antwort']==="" or $value['antwort']===null ){
                            array_push($tmp, $value['antwort']);
                            }else{
                            array_push($tmp, (int)$value['antwort']);
                            }
                            if($value['status']===null){
                                $tmp=null;
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
                            if($value['status']===null){
                                $tmp=null;
                            }
                            if($value['status']===null){
                                $tmp=null;
                            }
                    };
            }
            array_push($fragen, [
                    "" => $fragenID,
                    "answers" => $tmp
            ]);
        }
        //Noch nicht beantwortete Fragen werden mit NULL belegt
        $stmt= $conn->prepare('select spiel.runden as AnzRunden, (spiel.runden*spiel.fragen_pro_runde) as gesAnzFragen, count(teilnahme.spieler) as anzSpieler from spiel, teilnahme where teilnahme.spiel=spiel.id and teilnahme.akzeptiert=true and spiel.id=1;');
        $stmt->execute([$anzuzeigendesSpielID]);
        $RueckgabeDaten=$stmt->fetchall();
        $anzVorhandenerFragen=count($fragen);
        if($anzVorhandenerFragen<$RueckgabeDaten[0]['gesAnzFragen']){
            for($i=0; $i<($RueckgabeDaten[0]['gesAnzFragen']-$anzVorhandenerFragen);$i++) {
                $tmp2=array();
                for($j=0;$j<$RueckgabeDaten[0]['anzSpieler'];$j++) {
                    array_push($tmp2, null);
                }
                array_push($fragen,[
                    ""=>$i,
                    "answers"=>$tmp2
                ]);
            }
        }
        $anzVorhandenerRunden= count($runden);
        if($anzVorhandenerRunden<$RueckgabeDaten[0]['AnzRunden']){
            for($k=0; $k<($RueckgabeDaten[0]['AnzRunden']-$anzVorhandenerRunden);$k++) {
            array_push($runden,null);    
            }
        }
	$array=[
		"" =>"/schema/game",
		"players" =>$spieler,
		"rounds" => $runden,
		"turns" =>$spiel[0]['runden'],
		"timelimit"=>$spiel[0]['fragenzeit'],
		"roundlimit"=>$spiel[0]['rundenzeit'],
		"questions"=>$fragen,
		"dealingrule"=>"/dealingrule/".$spiel[0]['dealingrule']
	];
        // FIXME: no longer varies over Authorization if game is over
        header('Vary: Accept, Authorization');
	if($contentType === "application/json"){
		header('Content-Type: application/json');
		echo json_encode($array);
	}else{
		// TODO: looks horrible
		error_log('output');
		$GLOBALS['array'] = $array;
		require_once __DIR__."/../game.html.php";
	}
}
?>
