(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	const newGameUrl = document.currentScript.dataset.newgame;
	const $main = document.querySelector('main');
	loginPromise.then(login => makeXHR('GET', login.player_||login.player[''], {Accept: 'application/json', Authorization: login.token}, xhr => {
		if (xhr.status < 200 || xhr.status >= 300 || xhr.getResponseHeader('Content-Type') !== 'application/json') {return}
		const json = JSON.parse(xhr.responseText);
		$main.appendChild(buildDom({'':'section#status', c:[
			{'':'h1', c:login.user},
			{c:{'':'ul.stat', c:[
				{'':'li', c:["Ranking: ", {'':'span.figure', c: '1234'}]},
				{'':'li', c:["Punkte: ", {'':'span.figure', c: ''+json.score}]}
			]}},
			{'':'a.start-game', href: newGameUrl, c: "Neues Spiel"}
		]}));
		buildDom([
			{'':'section#open-games', c:[{'':'h1', c:"Spiele gegen …"}, {'':'ul', c:json.activegames_.map(showGame(login))}]},
			{'':'section#waiting-games', c:[{'':'h1', c:"Warten auf …"}]},
		]).forEach(x => $main.appendChild(x));
	}).send());
})();
