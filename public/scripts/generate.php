<?php
require_once __DIR__.'/../../connection.php';
srand($_GET['seed']);

// TODO Punkte zufällig verteilen (Rangliste)

$stmt = $conn->query('SELECT COUNT(*) FROM spieler');
$spieler = (int) $stmt->fetchColumn();
$stmt = $conn->query('SELECT COUNT(*) FROM frage');
$fragen = (int) $stmt->fetchColumn();
$stmt = $conn->query('SELECT COUNT(*) FROM kategorie');
$kategorien = (int) $stmt->fetchColumn();

$stmtspiel = $conn->prepare("INSERT INTO spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) Values (:einsatz, :dealer, :runden, :fragen_pro_runde, :fragenzeit, :rundenzeit, 'Offen')");
$stmtteilnahme = $conn->prepare("INSERT INTO teilnahme (spiel, spieler, akzeptiert) Values (:spiel, :spieler, 1)");
$stmtrunden = $conn->prepare("INSERT INTO runde (spiel, rundennr, dealer, kategorie) Values (:spiel, :rundennr, :dealer, :kategorie)");
$stmtfragen = $conn->prepare("INSERT INTO spiel_frage (spiel, fragennr, frage) Values (:spiel, :fragennr, :frage)");

for ($i = 1; $i <= $_GET['seed']; $i++) {
    spiel_erstellen();
    $conn->commit();
    echo "Spiel $i erstellt.";
}

function spiel_erstellen() {
    global $conn, $spieler, $fragen;
    global $stmtspiel, $stmtteilnahme, $stmtrunden, $stmtfragen;
    
    $status = 0; //?? 
    
    $runden = rand(1, 3);
    $fragen_pro_runde = min(rand(1, 3), ($fragen / $runden));
    $dealer = rand(1, $spieler);
    $stmtspiel->execute([
        'einsatz' => rand(1, 100),
        'dealer' => $dealer,
        'runden' => $runden,
        'fragen_pro_runde' => $fragen_pro_runde,
        'fragenzeit' => rand(2,5),
        'rundenzeit' => rand(2,5)
    ]);
    $spiel = $conn->lastInsertId();
    
    // Teilnahmen hinzufügen
    for($i = 1; $i <= rand(2,5); $i++) {
        $spieler = $dealer;
        while($spieler == $dealer) {
            $spieler = rand(1, $spieler);
        }
        
        $stmtteilnahme->execute([
            'spiel' => $spiel,
            'spieler' => $spieler
        ]);
    }
    
    // Runden erstellen
    for($runde = 1; $runde <= $runden; $runde++) {
        $kategorie = rand(1, $kategorien);
        $stmtrunden->execute([
            'spiel' => $spiel,
            'rundennr' => $runde,
            'dealer' => $dealer,
            'kategorie' => $kategorie
        ]);
    }
    
    // Fragen hinzufügen
    for($frage = 1; $frage <= $runden * $fragen_pro_runde; $runde++) {
        $kategorie = rand(1, $kategorien);
        $stmtfragen->execute([
            'spiel' => $spiel,
            'fragennr' => $frage,
            'frage' => rand(1, $fragen)
        ]);
    }
}

$conn->commit();
?>