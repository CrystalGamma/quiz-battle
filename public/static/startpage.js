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
					let selfPlayer = null;
					game.players.forEach((player, idx) => {if (player[''] === (login.player_||login.player[''])) {selfPlayer = idx}});
					if (selfPlayer === null) {
						$game.parentNode.removeChild($game);	// game was concurrently rejected
						return;
					}
					const scores = game.players.map(() => 0);
					game.questions.forEach(question => question && question.answers.forEach((status, player) => scores[player]+=0|(status === 0)));
					let bestPlayer = null, bestScore = -1;
					scores.forEach((score, idx) => {if (score > bestScore && idx != selfPlayer) {bestPlayer = idx;bestScore=score}});
					const title = game.players.length > 2
						? [game.players[bestPlayer].name]
						: [{'':'span.player', c:game.players[bestPlayer].name}, ` und ${game.players.length-2} weitere`];
					buildDom(title).forEach(x => $game.appendChild(x));
					$game.appendChild(buildDom({'':'span.game-points'+(
						scores[selfPlayer] > bestScore ? '.winning' :
						scores[selfPlayer] < bestScore ? '.losing'
						: '.tied'
					), c:`${scores[selfPlayer]} – ${bestScore}`}));
					const lookup = x => (
						x === 0 ? 'g' :
						x === null ? '-'
						: 'b'
					);
					$game.appendChild(buildDom({'':'ul.game-report', c:game.questions.map(({answers}) => ({'':'li.'+lookup(answers[selfPlayer])+lookup(answers[bestPlayer])}))}));
				}).send();
				const res = buildDom({'':'li', c:$game});
				return res;
			})}]},
			{'':'section#waiting-games', c:[{'':'h1', c:"Warten auf …"}]},
		]).forEach(x => $main.appendChild(x)) : console.error(xhr)).send();
	});
})();
