(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	loginPromise.then(login => makeXHR('GET', '', {Accept: 'application/json', Authorization: login.token}, xhr => {
		if (xhr.status < 200 || xhr.status >= 300) {return}
		let json = JSON.parse(xhr.responseText);
		let players = json.players;
		document.querySelector('main').appendChild(buildDom({'':'section#ranking', c:[
			{'':'h1', c:'Ranking-Liste'},
			{'':'table', c:[
				{'':'thead', c:{'':'tr', c:[
					{'':'th', c:'Rang'},
					{'':'th', c:'Name'},
					{'':'th', c:'Punkte'}
				]}},
				{'':'tbody', c:players.map((player, idx) => ({'':'tr', c:[
					{'':'td', c:`${json.start + idx + 1}.`},
					{'':'th', c:player['name']},
					{'':'td', c:''+player['points']}
				]}))},
				{'':'tfoot', c:{'':'tr', c:[
					{'':'td', c:[
						json.prev_ != null ? {'':'a', href:json.prev_, c:'⬅'} : ''
					]},
					{'':'td'},
					{'':'td', c:[
						json.next_ ? {'':'a', href:json.next_, c:'➡'} : ''
					]}
				]}}
			]}
		]}));
	}).send());
})();