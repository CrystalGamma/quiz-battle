(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	const newGameUrl = document.currentScript.dataset.newgame;
	const $main = document.querySelector('main');
	loginPromise.then(login => makeXHR('GET', login.player_||login.player[''], {Accept: 'application/json', Authorization: login.token}, xhr => {
		if (xhr.status < 200 || xhr.status >= 300 || !xhr.getResponseHeader('Content-Type').startsWith('application/json')) {return}
		const json = JSON.parse(xhr.responseText);
		$main.appendChild(buildDom({'':'section#status', c:[
			{'':'h1', c:login.user},
			{c:{'':'ul.stat', c:[
				{'':'li', c:["Ranking: ", {'':'span.figure', c: ''+json.ranking}]},
				{'':'li', c:["Punkte: ", {'':'span.figure', c: ''+json.score}]}
			]}},
			{'':'a.start-game.matchmaking', href: newGameUrl, c: "Neues Spiel"}
		]}));
		const createSection = (id, headertext) => {
			const $el = buildDom({'':'section#'+id});
			let $list = null;
			return [$el, game => {
				if (!$list) {
					$list = buildDom({'':'ul'});
					$el.appendChild(buildDom({'':'h1', c: headertext}));
					$el.appendChild($list);
				}
				$list.appendChild(buildDom(game));
			}]
		};
		const chall = createSection('challenges', "Herausforderungen von …");
		const open = createSection('open-games', "Spiele gegen …");
		const wait = createSection('waiting-games', "Warten auf …");
		let $chall = chall[0], addChall = chall[1];
		let $open = open[0], addOpen = open[1];
		let $waiting = wait[0], addWait = wait[1];
		json.activegames_.forEach((gameUrl, index) => makeXHR('GET', gameUrl, {Accept:'application/json', Authorization: login.token}, xhr => {
			if (xhr.status < 200 || xhr.status >=300 || !xhr.getResponseHeader('Content-Type').startsWith('application/json')) {return}
			const json = JSON.parse(xhr.responseText);
			const render = {'':'li', style:'order:'+index, c:{'':'a.game', href:gameUrl, c:renderGame(login.player_||login.player[''], json)}};
			if (json.players.some(player => player[''] === (login.player_||login.player['']) && !player.accepted)) {
				addChall(render);
			} else if (json.questions.some(q => q.answers === null) || json.rounds.some(r => r && r.dealer === (login.player_||login.player['']))) {
				addOpen(render);
			} else {
				addWait(render);
			}
		}).send());
		[$chall, $open, $waiting].forEach(x => $main.appendChild(x));
	}).send());
})();
