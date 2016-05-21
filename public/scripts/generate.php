<?php
require_once __DIR__.'/../../connection.php';
header("Cache-Control: no-store");
srand($_GET['seed']);

$stmt = $conn->query('SELECT COUNT(*) FROM spieler');
$anzahlspieler = (int) $stmt->fetchColumn();
$stmt = $conn->query('SELECT COUNT(*) FROM frage');
$fragen = (int) $stmt->fetchColumn();
$stmt = $conn->query('SELECT COUNT(*) FROM kategorie');
$kategorien = (int) $stmt->fetchColumn();

$stmt = array(
    'spieler' => $conn->prepare("INSERT INTO spieler (name, passwort, punkte) Values (:name, :passwort, :punkte)"),
    'spiel' => $conn->prepare("INSERT INTO spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) Values (:einsatz, :dealer, :runden, :fragen_pro_runde, :fragenzeit, :rundenzeit, :status)"),
    'teilnahme' => $conn->prepare("INSERT INTO teilnahme (spiel, spieler, akzeptiert) Values (:spiel, :spieler, 1)"),
    'runden' => $conn->prepare("INSERT INTO runde (spiel, rundennr, dealer, kategorie) Values (:spiel, :rundennr, :dealer, :kategorie)"),
    'fragen' => $conn->prepare("INSERT INTO spiel_frage (spiel, fragennr, frage) Values (:spiel, :fragennr, :frage)"),
    'antworten' => $conn->prepare("INSERT INTO antwort (spiel, spieler, fragennr, antwort) Values (:spiel, :spieler, :fragennr, :antwort)") 
);

for ($i = 1; $i <= $_GET['seed'] * 2; $i++) {
    $playerid = $anzahlspieler + 1;
    spieler_erstellen($playerid);
    if ($conn->commit()) {
        $anzahlspieler++;
        echo "Spieler $playerid erstellt. ";
        flush();
        $conn->beginTransaction();
    }
}

for ($i = 1; $i <= $_GET['seed']; $i++) {
    spiel_erstellen();
    if ($conn->commit()) {
        echo "Spiel $i erstellt. ";
        flush();
        $conn->beginTransaction();
    }
}

function spieler_erstellen($id) {
    global $stmt;
    
    $stmt['spieler']->execute([
        'name' => "player$id",
        'passwort' => password_hash('player', PASSWORD_DEFAULT),
        'punkte' => rand(1, 100)
    ]);
}

function spiel_erstellen() {
    global $conn, $anzahlspieler, $fragen, $kategorien, $stmt;
    
    $runden = rand(1, 3);
    $fragen_pro_runde =  min(rand(1, 6), ($fragen / $runden));
    $dealer = rand(1, $anzahlspieler-1);
    $status = rand(0, 5);
    switch ($status) {
        case 0: $statustext = 'offen'; break;
        case 1: $statustext = 'laufend'; break;
        default: $statustext = 'beendet';
    }
        
    $stmt['spiel']->execute([
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
        
        $stmt['teilnahme']->execute([
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
        $stmt['runden']->execute([
            'spiel' => $spiel,
            'rundennr' => $runde,
            'dealer' => $dealer,
            'kategorie' => $kategorie
        ]);
        
        // Fragen hinzufügen
        for ($frage = 0; $frage < $fragen_pro_runde; $frage++) {
            $stmt['fragen']->execute([
                'spiel' => $spiel,
                'fragennr' => $frage + ($runden * $fragen_pro_runde),
                'frage' => rand(1, $fragen-1)
            ]);
            
            // Antworten hinzufügen
            foreach ($teilnehmer as $spieler) {
                $stmt['antworten']->execute([
                    'spiel' => $spiel,
                    'spieler' => $spieler,
                    'fragennr' => $frage + ($runden * $fragen_pro_runde),
                    'antwort' => rand(0, 3)
                ]);
            }
        }
    }
}

$conn->commit();
?>
