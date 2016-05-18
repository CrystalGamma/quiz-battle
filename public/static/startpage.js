(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	const newGameUrl = document.currentScript.dataset.newgame;
	const $main = document.querySelector('main');
	loginPromise.then(login => {
		$main.appendChild(buildDom({'':'section#status', c:[
			{'':'h1', c:login.user},
			{c:{'':'ul.stat', c:[
				{'':'li', c:["Ranking: ", {'':'span.figure', c: '1234'}]},
				{'':'li', c:["Punkte: ", {'':'span.figure', c: '123'}]}
			]}},
			{'':'a.start-game', href: newGameUrl, c: "Neues Spiel"}
		]}));
		makeXHR('GET', login.player_||login.player[''], {Accept: 'application/json', Authorization: login.token}, xhr => xhr.status >= 200 && xhr.status < 300 && xhr.getResponseHeader('Content-Type') === 'application/json' ? buildDom([
			{'':'section#open-games', c:[{'':'h1', c:"Spiele gegen …"}, {'':'ul', c:JSON.parse(xhr.responseText).activegames_.map(url => {
				const $game = buildDom({'':'a.game', href: url});
				makeXHR('GET', url, {Accept: 'application/json'}, xhr => {
					if (xhr.status < 200 || xhr.status >= 300) {return}
					const ct = xhr.getResponseHeader('Content-Type');
					if (ct !== 'application/json') {console.error(`Wrong content type: ${ct}`);return}
					const game = JSON.parse(xhr.responseText);
					let title = game.players.length > 1
						? [game.players[0].name]
						: [{'':'span.player', c:game.players[0].name}, ` und ${game.players.length-1} weitere`];
					title.forEach(x => $game.appendChild(x));
				}).send();
				const res = buildDom({'':'li', c:$game});
				return res;
			})}]},
			{'':'section#waiting-games', c:[{'':'h1', c:"Warten auf …"}]},
		]).forEach(x => $main.appendChild(x)) : console.error(xhr)).send();
	});
})();
