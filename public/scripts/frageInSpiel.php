<?php
/*
```json
{
	"": "/schema/game-question",
	"question": {
		"": "/questions/<cid>",
		"question": "Fragetext",
		"picture": null,
		"explanation": "Erklärung",
		"answers": ["Antwort 1 (richtig)", "Antwort 2", "Antwort 3", "Antwort 4"],
		"correct": 1
	}
	"answers": [
		{"player_": "/player/<id>", "ans": 0}
	]
}
*/
require_once __DIR__."/../../connection.php";
require_once __DIR__."/../checkAuthorization.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	require_once(__DIR__.'/askme.php');
	die();
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
	require_once(__DIR__.'/myanswer.php');
	die();
}

  //Prüfung auf exisitenz und daten holen
$stmt= $conn->prepare('select spiel.status from spiel, spiel_frage where spiel_frage.spiel=spiel.id and spiel_frage.spiel = ? and spiel_frage.fragennr=?; ');

$anzuzeigendesSpielID=$_GET['id'];
$anzuzeigendeFragennr=$_GET['qid'];

$stmt->execute([$anzuzeigendesSpielID, $anzuzeigendeFragennr]);
$row = $stmt->fetch();
//Überprüfung ob es das Spiel gibt
if($row===false){
    http_response_code(404);
    die("Das Spiel mit der ID ".$anzuzeigendesSpielID." und der fragennr ".$anzuzeigendeFragennr." exisitiert nicht");
}
frageInSpiel();

function frageInSpiel(){
    require_once __DIR__."/../../classes/ContentNegotation.php";
        global $conn;
        global $anzuzeigendesSpielID;
        global $anzuzeigendeFragennr;

    $contentType=ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'],"text/html,application/json;q=0.9");

    $stmt= $conn->prepare('select spiel_frage.frage as fragenID, frage.frage, frage.bild, frage.erklaerung, frage.richtig, frage.falsch1, frage.falsch2, frage.falsch3, antwort.spieler, antwort.antwort from (frage, spiel_frage) left join antwort on (spiel_frage.fragennr=antwort.fragennr and spiel_frage.spiel=antwort.spiel) where spiel_frage.frage=frage.id and spiel_frage.spiel=? and spiel_frage.fragennr=?;');
    $stmt->execute([$anzuzeigendesSpielID, $anzuzeigendeFragennr]);
    $RueckgabeDaten=$stmt->fetchall();
    $antwort=array();
    foreach ($RueckgabeDaten as $value){
            array_push($antwort,[
            "player_"=> "/players/".$value['spieler'],
            "ans"=>$value['antwort']
            ]);
    };
    $array=[
        ""=> "/schema/game-question",
        "question"=> [
            ""=> "/questions/".$RueckgabeDaten[0]['fragenID'],
            "question"=> $RueckgabeDaten[0]['frage'],
            "picture" => $RueckgabeDaten[0]['bild'],
            "explanation"=> $RueckgabeDaten[0]['erklaerung'],
            "answers"=> [
                $RueckgabeDaten[0]['richtig'],
                $RueckgabeDaten[0]['falsch1'],
                $RueckgabeDaten[0]['falsch2'],
                $RueckgabeDaten[0]['falsch3']
            ]
        ],    
        "answers"=>$antwort
    ];
    $json= json_encode($array);
    if($contentType==="application/json"){
        header('Content-Type: application/json');
        echo $json;
    }else{
        require_once __DIR__."/../embrowsen.php";
    }
    die();
}
?>
