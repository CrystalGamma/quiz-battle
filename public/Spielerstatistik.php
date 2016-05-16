<pre>
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
require_once "../connection.php";
require_once "checkAuthorization.php";

$username="admin";

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
$stmt= $conn->prepare('Select * from (select kategorie.name as katName, kategorie.id as katID, Sum(case when antwort.antwort=1 then 1 else 0 end) as richtigeAntwort, Sum(case when antwort.antwort!=1 then 1 else 0 end) as falscheAntwort, spiel.id as SpielID, spieler.name  from spiel, spieler, spiel_frage, antwort, frage, frage_kategorie, kategorie, teilnahme where spiel_frage.spiel=spiel.id and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr and spiel_frage.frage=frage.id and frage.id=frage_kategorie.frage and frage_kategorie.kategorie=kategorie.id and spieler.name="admin" and spiel.status="beendet" and spiel.id=teilnahme.spiel and spieler.id=teilnahme.spieler and teilnahme.akzeptiert=1 group by kategorie.name union select kategorie.name, kategorie.id, 0, 0,"NULL", "NULL" from kategorie) as tmp group by katName;');
$stmt->execute([$username]);
$RueckgabeDaten=$stmt->fetchall();
//print_r($RueckgabeDaten);
$Kategorie=array();
foreach ($RueckgabeDaten as &$value){
     array_push($Kategorie,
        array(
            "category"=>array(
                ""=>"/categories/".$value[katID],
                "name"=>$value[katName]
            ),
            "correct" => $value[richtigeAntwort],
            "incorrect" => $value[falscheAntwort]
        )
    );
}
//JSON array wird zusammengebaut
$array = array(
    ""=>"/schema/player",
    "name" =>$username,
    "activegames_" => $laufendeSpiele,
    "oldgames_"=> $spielerID."/oldgames",
    "categorystats"=>$Kategorie
);
header('Content-Type: application/json');
echo json_encode($array, JSON_PRETTY_PRINT);

?>

</pre>

<?php
/*
select * from (select spiel.id , spieler.name, antwort.antwort, spiel_frage.frage, Sum(case when antwort.antwort=1 then 1 else 0 end) as richtigeAntwort, Sum(case when antwort.antwort!=1 then 1 else 0 end) as falscheAntwort from spiel, spieler, antwort, spiel_frage where spiel.id=spiel_frage.spiel and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr) as tmp;


select kategorie.name, frage.id from kategorie, frage_kategorie, frage where kategorie.id=frage_kategorie.kategorie and frage_kategorie.frage=frage.id

select * from (select spiel.id , spieler.name, antwort.antwort, spiel_frage.frage as frageID from spiel, spieler, antwort, spiel_frage where spiel.id=spiel_frage.spiel and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr) as tmp right join (select kategorie.name, frage.id as frageID from kategorie, frage_kategorie, frage where kategorie.id=frage_kategorie.kategorie and frage_kategorie.frage=frage.id) as tmpKat on tmpKat.frageID=tmp.frageID;

select * from (select spiel.id , spieler.name, antwort.antwort, spiel_frage.frage, Sum(case when antwort.antwort=1 then 1 else 0 end) as richtigeAntwort, Sum(case when antwort.antwort!=1 then 1 else 0 end) as falscheAntwort from spiel, spieler, antwort, spiel_frage where spiel.id=spiel_frage.spiel and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr) as tmp  right join (select kategorie.name, frage.id as frageID from kategorie, frage_kategorie, frage where kategorie.id=frage_kategorie.kategorie and frage_kategorie.frage=frage.id) as tmpKat on tmpKat.frageID=tmp.frageID;

select * from (select spiel.id , spieler.name, antwort.antwort, spiel_frage.frage as frageID, Sum(case when antwort.antwort=1 then 1 else 0 end) as richtigeAntwort, Sum(case when antwort.antwort!=1 then 1 else 0 end) as falscheAntwort from spiel, spieler, antwort, spiel_frage where spiel.id=spiel_frage.spiel and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr) as tmp  right join (select kategorie.name, frage.id as frageID from kategorie, frage_kategorie, frage where kategorie.id=frage_kategorie.kategorie and frage_kategorie.frage=frage.id) as tmpKat on tmpKat.frageID=tmp.frageID group by tmpKat.name;
geht nixht neue idee
auf alle kategorien verzichten und fehlende am ende dazu

select spiel.id, spieler.name, kategorie.name,  Sum(case when antwort.antwort=1 then 1 else 0 end) as richtigeAntwort, Sum(case when antwort.antwort!=1 then 1 else 0 end) as falscheAntwort from spiel, spieler, spiel_frage, antwort, frage, frage_kategorie, kategorie where spiel_frage.spiel=spiel.id and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr and spiel_frage.frage=frage.id and frage.id=frage_kategorie.frage and frage_kategorie.kategorie=kategorie.id group by kategorie.name;

select* from (select kategorie.name as katName,  Sum(case when antwort.antwort=1 then 1 else 0 end) as richtigeAntwort, Sum(case when antwort.antwort!=1 then 1 else 0 end) as falscheAntwort, spiel.id, spieler.name  from spiel, spieler, spiel_frage, antwort, frage, frage_kategorie, kategorie where spiel_frage.spiel=spiel.id and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr and spiel_frage.frage=frage.id and frage.id=frage_kategorie.frage and frage_kategorie.kategorie=kategorie.id group by kategorie.name) as tmp;


wahrscheinlich die Lösung

Select * from (select kategorie.name as katName,  Sum(case when antwort.antwort=1 then 1 else 0 end) as richtigeAntwort, Sum(case when antwort.antwort!=1 then 1 else 0 end) as falscheAntwort, spiel.id, spieler.name  from spiel, spieler, spiel_frage, antwort, frage, frage_kategorie, kategorie where spiel_frage.spiel=spiel.id and antwort.spiel=spiel.id and antwort.spieler=spieler.id and antwort.fragennr=spiel_frage.fragennr and spiel_frage.frage=frage.id and frage.id=frage_kategorie.frage and frage_kategorie.kategorie=kategorie.id group by kategorie.name union select kategorie.name, 0, 0,"NULL", "NULL" from kategorie) as tmp group by katName;

Erklärung: erstes inner select alle im Spiel verwendeten kategorien mit anz richtigen und falschen, 
                zweites inneres selct alle kategorien ausgeben
                das union davon nach den kategorien gruppieren 
                
*/
?>
