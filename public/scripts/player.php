<?php
//die Spielerstatistik soll nur angezeigt werden, wenn man eingelogt ist

/* Format des JSON Spielerstatistiken
{
    "": "/schema/player",
    "name": "Spielername",
    rankingposition
    "activegames_": ["/games/<id>"],
    "oldgames_": "<pid>/oldgames"
    "categorystats": [{
            "category": {"": "/categories/<id>", "name": "Kategorie"},
            "correct": 100,
            "incorrect": 42
    }]
    mit foreach dadurch und nur die richtigen einträge an String dranfügen(mit .=)
}
*/
require_once __DIR__."/../../connection.php";
require_once __DIR__."/../checkAuthorization.php";
require_once __DIR__."/../../classes/ContentNegotation.php";

$contentType=ContentNegotation::getContent($_SERVER['HTTP_ACCEPT'],"text/html,application/json;q=0.9");

$stmt= $conn->prepare('select id, name, punkte as points,(select count(spieler.id)+1 from spieler where punkte > points)as rang from spieler where spieler.id=?');
$stmt->execute([$_GET['id']]);
$user = $stmt->fetch();
//Überprüfung ob es den User gibt
if($user===false){
    http_response_code(404);
    die("Der Spieler mit der ID ".$user['id']." exisitiert nicht");
}
$anzuzeigenderUsername=$user['name'];
$username=getAuthorizationUser();
if ($username === false) {$username = NULL;}
if($username === $anzuzeigenderUsername)
{
	//JSON Element activegames_ wird zusammengebaut
	$stmt= $conn->prepare('Select spiel.id From spiel, teilnahme Where teilnahme.spiel = spiel.id And spiel.status != \'beendet\' And teilnahme.spieler = :uid');
	$stmt->execute(['uid' => $user['id']]);
	$laufendeSpiele=array();
	foreach ($stmt->fetchall() as $value){
		array_push($laufendeSpiele,"/games/".$value[0].'/');
	}
} else {
	$laufendeSpiele = NULL;
}

//JSON-Element categorystats wird zusammengebaut
$stmt= $conn->prepare(
'Select k.name As katName, k.id As katID, Sum(
	Case When a.antwort=0 Then 1 Else 0 End
) As richtigeAntwort, Sum(
	Case When a.antwort!=0 Then 1 Else 0 End
) As falscheAntwort, s.name
From spiel, spieler s, spiel_frage sf, antwort a, frage f, frage_kategorie fk, kategorie k, teilnahme t, teilnahme t2
Where spiel.status="beendet" And sf.spiel=spiel.id And sf.frage=f.id And f.id=fk.frage And fk.kategorie=k.id 
And a.spiel=spiel.id And a.spieler=:statsfor and a.fragennr=sf.fragennr
And spiel.id=t.spiel And s.id=t.spieler And s.name=:authorized
And t2.spiel=spiel.id And t2.spieler=:statsfor
Group By k.id;');
$stmt->execute(["authorized" => $username, "statsfor" => $user['id']]);
$kategorie=[];
foreach ($stmt->fetchall() as $value){
	array_push($kategorie, [
		"category"=>[
			""=>"/categories/".$value['katID'],
			"name"=>$value['katName']
		],
		"correct" => (int) $value['richtigeAntwort'],
		"incorrect" => (int) $value['falscheAntwort']
	]);
}
$oldGameCount = $conn->prepare("Select Count(spiel.id) From spiel, teilnahme where teilnahme.spieler = ? And spiel.status = 'beendet' And spiel.id = teilnahme.spiel");
$oldGameCount->execute([$user['id']]);
$numOldGames = (int)$oldGameCount->fetchall()[0][0];
	
if($contentType==="application/json"){
    header('Content-Type: application/json');
    echo json_encode([
	""=>"/schema/player",
        "name" =>$user['name'],
        "score" =>$user['points'],
        "rang" =>$user['rang'],
        "activegames_" => $laufendeSpiele,
        "oldgames_"=> ['' => $user['id']."/oldgames", 'count' => $numOldGames],
        "categorystats"=>$kategorie
    ]);
}else{
    require_once __DIR__."/../playerstats.html.php";
}

?>
