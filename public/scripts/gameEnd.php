<?php
function cleanGame($conn, $gid) {
	$conn->beginTransaction();
	// FIXME: handle lapsed dealers
	$elapsedRounds = $conn->prepare("
INSERT INTO antwort(spiel, spieler, fragennr, antwort, startzeit)
SELECT spiel.id, spieler, fragennr, NULL, runde.start FROM runde, spiel, teilnahme, spiel_frage
WHERE runde.spiel=spiel.id AND spiel.id=:gid AND teilnahme.spiel=spiel.id AND spiel_frage.spiel=spiel.id
AND spiel_frage.fragennr < (runde.rundennr+1)*spiel.fragen_pro_runde AND spiel_frage.fragennr >= runde.rundennr*spiel.fragen_pro_runde
AND timestampdiff(second, runde.start, now()) > spiel.rundenzeit
AND spiel.status != 'beendet'
AND NOT EXISTS(SELECT * FROM antwort WHERE spiel=spiel.id AND antwort.spieler=teilnahme.spieler AND antwort.fragennr=spiel_frage.fragennr)");
	$elapsedRounds->execute(['gid' => $gid]);
	endGame($conn, $gid);
	$conn->commit();
	$conn->beginTransaction();
}

function endGame($conn, $gid) {
	$countAnswers = $conn->prepare("
SELECT spiel.status = 'beendet' as done, COUNT(antwort.startzeit) as actual, spiel.runden*spiel.fragen_pro_runde*(
	SELECT COUNT(*) FROM teilnahme WHERE spiel=spiel.id
) as target, einsatz as bet
FROM spiel, antwort WHERE spiel.id=:gid AND antwort.spiel=spiel.id");
	$countAnswers->execute(['gid' => $gid]);
	$counts = $countAnswers->fetch();
	if (!$counts['done'] && $counts['actual'] === $counts['target']) {
		$fetchWinner = $conn->prepare("SELECT spieler FROM antwort WHERE antwort=0 AND spiel=:gid GROUP BY spieler HAVING COUNT(antwort) = (SELECT COUNT(antwort) as punkte FROM antwort WHERE spiel=:gid AND antwort=0 GROUP BY spieler ORDER BY punkte DESC LIMIT 1)");
		$fetchWinner->execute(['gid' => $gid]);
		$winners = $fetchWinner->fetchAll(PDO::FETCH_COLUMN, 0);
		$bonus = ceil((float)$counts['bet'] / count($winners));
		$grantBonus = $conn->prepare("UPDATE spieler SET punkte=punkte+:bonus WHERE id=:pid");
		foreach ($winners as $pid) {$grantBonus->execute(['bonus' => $bonus, 'pid' => $pid]);}
		$conn->prepare("UPDATE spiel SET status='beendet' WHERE id=?")->execute([$gid]);
		error_log("ended game $gid");
		return true;
	}
	return false;
}
