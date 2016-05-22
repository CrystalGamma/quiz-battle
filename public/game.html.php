<?php // This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<title><?=htmlspecialchars($array['players'][0]['name'], ENT_QUOTES, 'utf-8')?> vs. <?php
$othernames = [];
foreach ($array['players'] as $idx => $player) {
	if ($idx !== 0) {array_push($othernames, $player['name']);}
}
echo htmlspecialchars(implode(', ', $othernames));
?></title>
<?php require(__DIR__.'/header.html') ?>
<main data-timelimit=<?=$array['timelimit']?>>
<table>
<thead><tr><th>Kategorie<?php foreach ($array['players'] as $player) {
	?><th><a class=player href=<?=$player['']?> data-accepted=<?=$player['accepted'] ? 'true' : 'false' ?>><?=htmlspecialchars($player['name'], ENT_QUOTES, 'utf-8')?></a><?php
}
?></thead>
<tbody><?php
foreach ($array['rounds'] as $rid => $round) {
	?><tr><th><?php
	if ($round !== NULL && $round['category'] === NULL) {
		?><a class="dealer player" href="<?=$round['dealer']['']?>" data-candidates="<?=htmlspecialchars(json_encode($round['candidates']), ENT_QUOTES, 'utf-8')?>">Spieler</a> wählt die Kategorie<?php
	} else if ($round !== NULL) {
		echo htmlspecialchars($round['category']['name'], ENT_QUOTES, 'utf-8');
		$start = $array['turns']*$rid;
		$end = $array['turns']*($rid + 1);
		foreach ($array['players'] as $pidx => $player) {
			echo '<td>';
			for ($i = $start; $i < $end; $i += 1) {
				$question = $array['questions'][$i];
				$answers = $question['answers'];
				?><a class="answer <?=$answers === null ? 'unknown' : ($answers[$pidx] === 0 ? 'correct' : 'incorrect') ?>" href=<?=$question['']?>#<?=$player['']?>></a><?php
			}
		}
	} ?>
<?php }?>
</tbody>
</table>
</main>
<script src=/scripts/builddom></script>
<script src=/scripts/makexhr></script>
<script src=/scripts/login></script>
<script src=/scripts/game></script>
