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
	getGame($conn, $anzuzeigendesSpielID);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
	require_once(__DIR__."/requestResponse.php");
}else if($_SERVER['REQUEST_METHOD']=='POST'){
    require_once(__DIR__."/chooseCategory.php");
}else{
    http_response_code(405);
    die();
}

function handleError($stmt){
    http_response_code(500);
    var_dump($stmt->errorInfo());
    die();
}

function getGame ($conn, $anzuzeigendesSpielID) {
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
        $listVisibility = $conn->prepare("
SELECT ((
	SELECT COUNT(spieler)
	FROM antwort a
	WHERE sf.spiel=a.spiel and sf.fragennr=a.fragennr and (antwort is not null or timestampdiff(second, startzeit, now()) > fragenzeit)
) = (SELECT COUNT(spieler) FROM teilnahme t WHERE t.spiel=sf.spiel)) OR EXISTS(
	SELECT * FROM antwort a, spieler s
	WHERE a.spieler=s.id AND s.name=:player
	AND a.spiel=sf.spiel AND a.fragennr=sf.fragennr
	AND (a.antwort IS NOT NULL OR TIMESTAMPDIFF(SECOND, startzeit, now()) > fragenzeit)
) FROM spiel_frage sf, spiel WHERE spiel=id and id=:gid ORDER BY fragennr");
	$listVisibility->execute(['gid' => $anzuzeigendesSpielID, 'player' => $nutzername === false ? '' : $nutzername]);
	$visibility = $listVisibility->fetchall(PDO::FETCH_COLUMN, 0);
        foreach ($stmt->fetchall() as $key => $value){
            array_push($spieler,
                [
                ""=> "/players/".$value['id'],
                "name"=> $value['name'],
                "accepted"=> (bool)$value['akzeptiert']
                ]
            );
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
SELECT spiel_frage.fragennr,teilnahme.spieler, (
	case when TIMESTAMPDIFF(SECOND, antwort.startzeit, now()) > spiel.fragenzeit and antwort.antwort IS NULL
	THEN 'abgel'
	ELSE antwort.antwort end) as antwort
FROM (spiel, teilnahme, spiel_frage) LEFT JOIN antwort on (spiel_frage.fragennr=antwort.fragennr and antwort.spiel=spiel_frage.spiel and teilnahme.spieler=antwort.spieler)
WHERE spiel_frage.spiel=? and teilnahme.spiel=spiel_frage.spiel and teilnahme.spiel=spiel.id
ORDER BY spiel_frage.fragennr, teilnahme.spieler");
        $stmt->execute([$anzuzeigendesSpielID]);
        $RueckgabeWert=$stmt->fetchall();
        $fragen = [];
        $tmp = [];
	if (count($RueckgabeWert) > 0) {
		$fragenID=$RueckgabeWert[0]['fragennr'];
		foreach ($RueckgabeWert as $value) {
		//solange die FragenID gleich ist wird das Array der Frage gefüllt wenn nicht wird ein neues Array angefangen
			if ($value['fragennr'] !== $fragenID) {
				array_push($fragen, [
					'' => "$fragenID",
					'answers' => $visibility[$fragenID] ? $tmp : null
				]);
				$fragenID=$value['fragennr'];
				$tmp=[];
			}
			if($value['antwort']==="" or $value['antwort']===null ){
				array_push($tmp, null);
			} else if ($value['antwort'] === 'abgel') {
				array_push($tmp, "");
			} else {
				array_push($tmp, (int)$value['antwort']);
			}
		}
		array_push($fragen, [
			'' => "$fragenID",
			'answers' => $visibility[$fragenID] ? $tmp : null
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
        header( 'Cache-Control: max-age=3' );
        $contentType=ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'],"text/html,application/json;q=0.9");
	if($contentType === "application/json"){
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($array);
	}else{
		// TODO: looks horrible
		$GLOBALS['array'] = $array;
		require_once __DIR__."/../game.html.php";
	}
}
?>
