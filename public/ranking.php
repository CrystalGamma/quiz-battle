<!DOCTYPE html>
<title>Ranking – Quiz Battle</title>
<?php require(__DIR__.'/header.html') ?>
<main>
<h1>Rangliste</h1>
<div>
<?php if($array['prev_']) {?><a rel=prev href="<?=htmlspecialchars($array['prev_'])?>">Vorherige</a><?php } ?>
<table>
<thead><tr><th>Rang<th>Name<th>Punkte</thead>
<tbody><?php foreach($array['players'] as $idx => &$player) { ?><tr><td><?=$array['start']+$idx+1?>.<th><a class=player href="<?=$player['']?>"><?=$player['name']?><td><?=$player['points']?><?php } ?></tbody>
</table>
<?php if ($array['next_']) {?><a rel=next href="<?=htmlspecialchars($array['next_'])?>">Weitere</a><?php } ?>
</div>
</main>
<script src=/scripts/makexhr></script>
<script src=/scripts/builddom></script>
<script src=/scripts/login></script>
<script async src=/scripts/load-ranking></script>
