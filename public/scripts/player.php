<?php
//die Spielerstatistik soll nur angezeigt werden, wenn man eingelogt ist

/* Format des JSON Spielerstatistiken
{
    "": "/schema/player",
    "name": "Spielername",
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
//Spielername holen
$stmt= $conn->prepare('select spieler.name from spieler where spieler.id=?');
$anzuzeigendeUserID=$_GET['id'];
//Überprüfen ob eine ID mitgegeben wurde-- nicht benötigt
$stmt->execute([$anzuzeigendeUserID]);
$row = $stmt->fetch();
//Überprüfung ob es einen User gibt
echo $anzuzeigenderUsername;
if($row===false){
    http_response_code(404);
    die("Der Player mit der ID".$anzuzeigendeUserID." exisitiert nicht");
}
$anzuzeigenderUsername=$row['name'];
$username=getAuthorizationUser();
if($username!==false)
{
    //JSON Element activegames_ wird zusammengebaut
    $stmt= $conn->prepare('select spiel.id, spieler.id from spieler left join teilnahme on spieler.id=teilnahme.spieler, spiel where spiel.id=teilnahme.spiel and spieler.name=? and spiel.status="laufend" order by status DESC;');
    $stmt->execute([$username]);
    $RueckgabeDaten=$stmt->fetchall();
    $laufendeSpiele=array();
    $spielerID=$RueckgabeDaten[0][1];
    foreach ($RueckgabeDaten as &$value){
        array_push($laufendeSpiele,"/games/".$value[0]);
    }

    //JSON-Element categorystats wird zusammengebaut
    /*Erklärung: erstes inner select alle im Spiel verwendeten kategorien mit anz richtigen und falschen, 
                    zweites inneres selct alle kategorien ausgeben
                    das union davon nach den katego1rien gruppieren*/ 
    $stmt= $conn->prepare('Select * from (select kategorie.name as katName, kategorie.id as katID, Sum(case when antwort.antwort=1 then 1 else 0 end) as richtigeAntwort, Sum(case when antwort.antwort!=1 then 1 else 0 end) as falscheAntwort, spiel.id as SpielID, spieler.name  from spiel, spieler, spiel_frage, antwort, frage, frage_kategorie, kategorie, teilnahme where spiel_frage.spiel=spiel.id and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr and spiel_frage.frage=frage.id and frage.id=frage_kategorie.frage and frage_kategorie.kategorie=kategorie.id and spieler.name="admin" and spiel.status="beendet" and spiel.id=teilnahme.spiel and spieler.id=teilnahme.spieler and teilnahme.akzeptiert=1 group by kategorie.name union select kategorie.name, kategorie.id, 0, 0,"NULL", "NULL" from kategorie) as tmp group by katName;');
    $stmt->execute([$username]);
    $RueckgabeDaten=$stmt->fetchall();
    //print_r($RueckgabeDaten);
    $Kategorie=array();
    foreach ($RueckgabeDaten as &$value){
        array_push($Kategorie,
            array(
                "category"=>array(
                    ""=>"/categories/".$value['katID'],
                    "name"=>$value['katName']
                ),
                "correct" => $value['richtigeAntwort'],
                "incorrect" => $value['falscheAntwort']
            )
        );
    }
}else{
    $username=$anzuzeigenderUsername;
    $laufendeSpiele="";
    $spielerID=$anzuzeigendeUserID;
    $Kategorie="";
}
//JSON array wird zusammengebaut
$array = array(
    ""=>"/schema/player",
    "name" =>$username,
    "activegames_" => $laufendeSpiele,
    "oldgames_"=> $spielerID."/oldgames",
    "categorystats"=>$Kategorie
);
$json= json_encode($array);
if($contentType==="application/json"){
    header('Content-Type: application/json');
    echo $json;
}else{
    require_once __DIR__."/../embrowsen.php";
}

?>
