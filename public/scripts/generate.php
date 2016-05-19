<?php
require_once __DIR__.'/../../connection.php';
header("Cache-Control: no-store");
srand($_GET['seed']);

// TODO Punkte zufällig verteilen (Rangliste)

$stmt = $conn->query('SELECT COUNT(*) FROM spieler');
$anzahlspieler = (int) $stmt->fetchColumn();
$stmt = $conn->query('SELECT COUNT(*) FROM frage');
$fragen = (int) $stmt->fetchColumn();
$stmt = $conn->query('SELECT COUNT(*) FROM kategorie');
$kategorien = (int) $stmt->fetchColumn();

$stmtspiel = $conn->prepare("INSERT INTO spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) Values (:einsatz, :dealer, :runden, :fragen_pro_runde, :fragenzeit, :rundenzeit, :status)");
$stmtteilnahme = $conn->prepare("INSERT INTO teilnahme (spiel, spieler, akzeptiert) Values (:spiel, :spieler, 1)");
$stmtrunden = $conn->prepare("INSERT INTO runde (spiel, rundennr, dealer, kategorie) Values (:spiel, :rundennr, :dealer, :kategorie)");
$stmtfragen = $conn->prepare("INSERT INTO spiel_frage (spiel, fragennr, frage) Values (:spiel, :fragennr, :frage)");
$stmtantworten = $conn->prepare("INSERT INTO antwort (spiel, spieler, fragennr, antwort) Values (:spiel, :spieler, :fragennr, :antwort)");

for ($i = 1; $i <= $_GET['seed']; $i++) {
    spiel_erstellen();
    $conn->commit();
    echo "Spiel $i erstellt.";
    flush();
    $conn->beginTransaction();
}

function spiel_erstellen() {
    global $conn, $anzahlspieler, $fragen;
    global $stmtspiel, $stmtteilnahme, $stmtrunden, $stmtfragen, $stmtantworten;
    
    $runden = rand(1, 3);
    $fragen_pro_runde =  min(rand(1, 6), ($fragen / $runden));
    $dealer = rand(1, $anzahlspieler-1);
    $status = rand(0, 5);
    switch ($status) {
        case 0: $statustext = 'offen'; break;
        case 1: $statustext = 'laufend'; break;
        default: $statustext = 'beendet';
    }
        
    $stmtspiel->execute([
        'einsatz' => rand(1, 100),
        'dealer' => $dealer,
        'runden' => $runden,
        'fragen_pro_runde' => $fragen_pro_runde,
        'fragenzeit' => 300,
        'rundenzeit' => 300 * $fragen_pro_runde,
        'status' => $statustext
    ]);
    $spiel = $conn->lastInsertId();
    
    // Teilnahmen hinzufügen
    $teilnehmer = array();
    for ($i = 0; $i <= rand(2, 5); $i++) {
        $spieler = $dealer;
        while ($spieler == $dealer || in_array($spieler, $teilnehmer)) {
            $spieler = rand(1, $anzahlspieler-1);
        }
        array_push($teilnehmer, $spieler);
        
        $stmtteilnahme->execute([
            'spiel' => $spiel,
            'spieler' => $spieler
        ]);
    }
    
    // Runden erstellen
    $anzahlrunden = 0;
    switch ($status) {
        case 0: $anzahlrunden = 0; break;
        case 1: $anzahlrunden = rand(1, $runden-1); break;
        default: $anzahlrunden = $runden; 
    }
    for ($runde = 0; $runde < $anzahlrunden; $runde++) {
        $kategorie = rand(1, $kategorien-1);
        $stmtrunden->execute([
            'spiel' => $spiel,
            'rundennr' => $runde,
            'dealer' => $dealer,
            'kategorie' => $kategorie
        ]);
        
        // Fragen hinzufügen
        for ($frage = 0; $frage < $fragen_pro_runde; $frage++) {
            $stmtfragen->execute([
                'spiel' => $spiel,
                'fragennr' => $frage + ($runden * $fragen_pro_runde),
                'frage' => rand(1, $fragen-1)
            ]);
            
            // Antworten hinzufügen
            foreach ($teilnehmer as $spieler) {
                $stmtantworten->execute([
                    'spiel' => $spiel,
                    'spieler' => $spieler,
                    'fragennr' => $frage,
                    'antwort' => rand(0, 3)
                ]);
            }
        }
    }
}

$conn->commit();
?>