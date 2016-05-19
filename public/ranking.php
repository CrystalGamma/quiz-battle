<!DOCTYPE html>
<title>Ranking – Quiz Battle</title>
<link rel=stylesheet href=/styles>
<header><span id=title>Quiz Battle</span><nav><a href="/">Startseite</a><a href="/players/">Rangliste</a></nav><span class=login data-auth="/auth"></span></header>
<main>
<h1>Rangliste</h1>
<?php if($array['prev_']) {?><a rel=prev href="<?=htmlspecialchars($array['prev_'])?>">Vorherige</a><?php } ?>
<table>
<thead><tr><th>Rang<th>Name<th>Punkte</thead>
<tbody><?php foreach($array['players'] as $idx => $player) { ?><tr><td><?=$array['start']+$idx+1?>.<th><a class=player><?=$player['name']?><td><?=$player['points']?><?php } ?></tbody>
</table>
<?php if ($array['next_']) {?><a rel=next href="<?=htmlspecialchars($array['next_'])?>">Weitere</a><?php } ?>
</main>
<script src=/scripts/makexhr></script>
<script src=/scripts/builddom></script>
<script src=/scripts/login></script>
<script async src=/scripts/load-ranking></script>
