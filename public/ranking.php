<?php // This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<title>Ranking – Quiz Battle</title>
<?php require(__DIR__.'/header.html') ?>
<main>
<h1>Rangliste</h1>
<div>
<?php if ($array['prev_']) {?><a rel=prev href="<?=htmlspecialchars($array['prev_'], ENT_QUOTES, 'utf-8')?>">Vorherige</a><?php } ?>
<table>
<thead><tr><th>Rang<th>Name<th>Punkte</thead>
<tbody><?php foreach($array['players'] as $idx => &$player) { ?><tr><td><?=$player['ranking']?>.<th><a class=player href="<?=$player['']?>"><?=htmlspecialchars($player['name'])?><td><?=$player['points']?><?php } ?></tbody>
</table>
<?php if ($array['next_']) {?><a rel=next href="<?=htmlspecialchars($array['next_'], ENT_QUOTES, 'utf-8')?>">Weitere</a><?php } ?>
</div>
</main>
<script src=/scripts/makexhr></script>
<script src=/scripts/builddom></script>
<script src=/scripts/login></script>
<script async src=/scripts/load-ranking></script>
