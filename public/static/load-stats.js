(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	loginPromise.then(login => makeXHR('GET', '', {Accept: 'application/json', Authorization: login.token}, xhr => {
		if (xhr.status < 200 || xhr.status >= 300) {return}
		let json = JSON.parse(xhr.responseText);
		let categories = json.categorystats.map(({category, correct, incorrect}) => ({category, correct, incorrect, percentage: correct*100/(correct+incorrect)})).sort((a, b) => (b.percentage-a.percentage)||(b.correct-a.correct));
		document.querySelector('main').appendChild(buildDom({'':'section#category-stats', c:[
			{'':'h1', c:"Erfolg in Kategorien"},
			...(location.path !== (login.player_||login.player['']) ? [{'':'p', c:["Aus allen gemeinsamen Spielen von ", {'':'a.player', href:login.player_||login.player[''], c:login.user||login.player['']}, " und ", {'':'a.player', href:'', c:json.name}]}]: []),
			{'':'table', c:[
				{'':'thead', c:{'':'tr', c:[
					{'':'th'},
					{'':'th', c:"Kategorie"},
					{'':'th', c:"Richtig", title:"Richtige Antworten in dieser Kategorie"},
					{'':'th', c:"Falsch", title:"Falsche Antworten in dieser Kategorie"},
					{'':'th', c:"Gesamt"},
					{'':'th', c:"Anteil", title:"Anteil richtiger Antworten in dieser Kategorie"}
				]}},
				{'':'tbody', c:categories.map((stat, idx) => ({'':'tr', c:[
					{'':'td', c:`${idx+1}.`},
					{'':'th', c:stat.category['name']},
					{'':'td', c:''+stat.correct},
					{'':'td', c:''+stat.incorrect},
					{'':'td', c:''+(stat.correct+stat.incorrect)},
					{'':'td', c:stat.percentage+'%'}
				]}))}
			]}
		]}));
	}).send());
	loginPromise.then(() => document.getElementById('status').appendChild(buildDom({'':'a.challenge-player.start-game', href: '', c: "Herausfordern"})));
})();
