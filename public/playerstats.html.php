<?php // This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<title>Statistiken für <?=$user['name']?> – Quiz Battle</title>
<link rel=stylesheet href=/styles>
<main>
<section id=stats>
<h1><span class=player><?=$user['name']?></span> – Statistiken</h1>
<ul class=stat>
<li><span class=figure><?=$numOldGames?></span> Spiele
<li><span class=figure><?=$user['punkte']?></span> Punkte
</ul>
</section>
<?php if ($numOldGames > 0) { ?>
<section id=closed-games>
<h1>Abgeschlossene Spiele</h1>
<ul></ul>
<a href=<?=$user['id']?>/oldgames rel=next>Weitere Spiele laden</a>
</section><?php
} ?>
<script src=/scripts/makexhr></script>
<script src=/scripts/builddom></script>
<script src=/scripts/login></script>
<script async src=/scripts/load-stats></script>
<script async src=/scripts/load-oldgames></script>
