<?php // This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<title><?=htmlspecialchars($array['players'][0]['name'])?> vs. <?php
$othernames = [];
foreach ($array['players'] as $idx => $player) {
	if ($idx !== 0) {array_push($othernames, $player['name']);}
}
echo htmlspecialchars(implode(', ', $othernames));
?></title>
<?php require(__DIR__.'/header.html') ?>
<main>
<table>
<thead><tr><th>Kategorie<?php foreach ($array['players'] as $player) {
	?><th><a class=player href=<?=$player['']?> data-accepted=<?=$player['accepted'] ? 'true' : 'false' ?>><?=htmlspecialchars($player['name'])?></a><?php
} ?></thead>
<tbody><?php foreach ($array['rounds'] as $round) {
	?><tr><th><?php
	if ($round !== NULL && $round['category_'] === NULL) {
		?><a class=dealer href=<?=$round['dealer']?>>Spieler wählt die Kategorie</a><?php
	} else if ($round !== NULL) {
		echo htmlspecialchars($round['category_']);
	}
}?>
</tbody>
</main>
<script src=/scripts/builddom></script>
<script src=/scripts/makexhr></script>
<script src=/scripts/login></script>
<script src=/scripts/game></script>
