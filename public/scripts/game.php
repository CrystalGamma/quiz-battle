<?php
/*
```json
{
	"": "/schema/game",
	"players": [
		{"": "/players/<id>", "name": "Spielername", "accepted": true}
	],
	"rounds": [{
		"category_": "/categories/<id>",
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
if($request=='GET'){
    if($row['status']==="beendet") {
        getGame();
}else if($row['status']==="laufend"){
    $nutzername=getAuthorizationUser();
    if($nutzername===false){
    http_response_code(403);
    die("Sie müssen angemeldet sein");
    }
    //TODO was soll angezeigt werden wenn der angemeldete user noch nicht beantwortet wurde- null für diese fragenrunde oder nichts???
    $stmt= $conn->prepare('select spieler.name, antwort.antwort from spiel_frage, antwort, spieler where spiel_frage.fragennr=antwort.fragennr and spiel_frage.spiel=antwort.spiel and antwort.spieler=spieler.id and spiel_frage.spiel=? and spieler.name=?;');
    $stmt->execute([$anzuzeigendesSpielID,$nutzername]);
    $row = $stmt->fetch();
    //Überprüfen ob beantwortete
    if($row===false){
    http_response_code(403);
    die("Sie haben die Frage noch nicht beantwortet");
    }else{
        getGame();
    }
}
    
}else if($_SERVER['REQUEST_METHOD']=='PUT'){
	$inputJSON = file_get_contents('php://input');
	$input= json_decode( $inputJSON, TRUE ); //convert JSON into array
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
}else if($_SERVER['REQUEST_METHOD']=='POST'){
    $inputJSON = file_get_contents('php://input');
    $input= json_decode( $inputJSON, TRUE ); //convert JSON into array
    $categorie = explode('/',$input['category_'])[2];
    $stmt=$conn->prepare("UPDATE runde SET kategorie=:kategorie WHERE spiel=:spiel AND dealer=:spieler AND rundennr=:rundennr");
    $stmt->bindValue(':kategorie', (int) $categorie, PDO::PARAM_INT);
    $stmt->bindValue(':spieler', (int) $player, PDO::PARAM_INT); //Woher kriege ich hier den aktuell angemeldten Spieler?
    $stmt->bindValue(':spiel', (int) $anzuzeigendesSpielID, PDO::PARAM_INT);
    $stmt->bindValue(':rundennr', (int) $round, PDO::PARAM_INT); //Woher kenne ich die Runde? Einfach die höchste für das Spiel?
    if(!$stmt->execute()){
        var_dump($stmt->errorInfo());
        die();
    }
}else{
    http_response_code(405);
    die();
}
  
function getGame (){
        //Players
        global $conn;
        global $anzuzeigendesSpielID;
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
        $stmt= $conn->prepare('select runde.rundennr, runde.kategorie, spieler.id, spieler.name, runde.start from runde, kategorie, spieler where runde.dealer=spieler.id and runde.spiel=?  group by runde.rundennr order by runde.rundennr;');
        $stmt->execute([$anzuzeigendesSpielID]);
        $runden=array();
        foreach ($stmt->fetchall() as $value){
            array_push($runden,
                [
                "category_"=> "/categories/".$value['kategorie'],
            "dealer"=> [
                        ""=> "/players/".$value['id'],
                        "name"=>$value['name']
                        ],
            "started"=> date(DATE_ISO8601, strtotime($value['start']))
                ]
            );
        };
        $stmt= $conn->prepare('select spiel.runden, spiel.fragenzeit, spiel.rundenzeit, (case when spiel.dealer=NULL then "firstanswer" else spiel.dealer end) as dealingrule from spiel where spiel.id=?');
        $stmt->execute([$anzuzeigendesSpielID]);
        $spiel=$stmt->fetchall();
        $stmt= $conn->prepare('select spiel_frage.fragennr,teilnahme.spieler, (case when antwort.startzeit+spiel.fragenzeit < now() then "" else antwort.antwort end) as antwort from (spiel, teilnahme, spiel_frage) left join antwort on (spiel_frage.fragennr=antwort.fragennr and antwort.spiel=spiel_frage.spiel and teilnahme.spieler=antwort.spieler) where spiel_frage.spiel=? and teilnahme.spiel=spiel_frage.spiel and teilnahme.spiel=spiel.id order by spiel_frage.fragennr, teilnahme.spieler');
        $stmt->execute([$anzuzeigendesSpielID]);
        $RueckgabeWert=$stmt->fetchall();
        $fragen = [];
        $tmp = [];
        if (count($RueckgabeWert) > 0) {
            $fragenID=$RueckgabeWert[0]['fragennr'];
            foreach ($RueckgabeWert as $value) {
                    if ($value['fragennr'] === $fragenID) {
                            array_push($tmp, $value['antwort']);
                    } else {
                            array_push($fragen, [
                                    "" => $fragenID,
                                    "answers" => $tmp
                            ]);
                            $fragenID=$value['fragennr'];
                            $tmp=array();
                            array_push($tmp, $value['antwort']);
                    };
            }
            array_push($fragen, [
                    "" => $fragenID,
                    "answers" => $tmp
            ]);
        }
        $stmt= $conn->prepare('select (spiel.runden*spiel.fragen_pro_runde) as gesAnzFragen, count(teilnahme.spieler) as anzSpieler from spiel, teilnahme where teilnahme.spiel=spiel.id and teilnahme.akzeptiert=true and spiel.id=?;');
        $stmt->execute([$anzuzeigendesSpielID]);
        $RueckgabeDaten=$stmt->fetchall();
        $anzVorhandenerFragen=count($fragen);
        if($anzVorhandenerFragen<$RueckgabeDaten[0]['gesAnzFragen']){
            for($i=0; $i<($RueckgabeDaten[0]['gesAnzFragen']-$anzVorhandenerFragen);$i++) {
                $tmp2=array();
                for($j=0;$j<$RueckgabeDaten[0]['anzSpieler'];$j++) {
                    array_push($tmp2, "NULL");
                }
                array_push($fragen,[
                    ""=>$i,
                    "answers"=>$tmp2
                ]);
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
        $json= json_encode($array);
        if($contentType==="application/json"){
    header('Content-Type: application/json');
    echo $json;
}else{
    require_once __DIR__."/../embrowsen.php";
}  
}
?>
