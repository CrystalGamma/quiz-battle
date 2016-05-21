(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	loginPromise.then(login => {
		const $main = document.querySelector('main');
		const $players = Array.from($main.querySelectorAll('thead a'));
		document.addEventListener('click', ev => {
			if (ev.target.classList.contains('accept')) {
				const accept = JSON.parse(ev.target.dataset.accept);
				const tryAccept = attempt => makeXHR('PUT', '', {'Content-Type':'application/json', Authorization: login.token}, xhr => {
					if (xhr.status >= 200 && xhr.status < 300) {
						if (xhr.status === 205) {
							location.reload();
						} else if (xhr.getResponseHeader('Location')) {
							location.href = xhr.getResponseHeader('Location');
						}
					} else {
						const retry = xhr.getResponseHeader('Retry-After');
						if (xhr.status >= 500 && xhr.status < 600 && attempt < 3) {
							setTimeout(tryAccept.bind(attempt+1), (retry||1)*1000);
						} else {
							alert(`Fehler beim Annehmen des Spiels: ${xhr.responseText}`);
						}
					}
				}).send(JSON.stringify({'':'/schema/response', accept}));
				tryAccept(0);
			}
		});
		for (let $player of $players) {
			if ($player.pathname === (login.player_||login.player[''])) {
				if ($player.dataset.accepted !== 'true') {
					$main.insertBefore(buildDom({'':'.dialog', c:["Nimmst du diese Herausforderung an?", {c:[
						{'':'button.accept.start-game', 'data-accept': 'true', c: "Ja, Spiel starten"},
						{'':'button.accept', 'data-accept':'false', c:"Nein, Spiel beenden"}
					]}]}), $main.firstChild);
					return;
				}
			}
		}
		for (let $row of Array.from($main.querySelectorAll('tbody >tr'))) {
			const $dealer = $row.querySelector('a.dealer');
			if (!$dealer) {continue}
			$main.appendChild(buildDom({'':'.dialog', c:["Wähle Fragenkategorie für die nächste Runde:", ...JSON.parse($dealer.dataset.candidates).map(cat => ({'':'a.candidate', href:cat[''], c:cat['name']}))]}));
			$main.addEventListener('click', ev => {
				if (!ev.target.classList.contains('candidate')) {return}
				ev.preventDefault();
				const tryChoose = attempt => makeXHR('POST', '', {'Content-Type':'application/json', Accept:'application/json', Authorization: login.token}, xhr => {
					if (xhr.status >= 200 && xhr.status < 300) {
						location.reload();
					} else {
						const retry = xhr.getResponseHeader('Retry-After')|0;
						if (attempt < 3 && retry) {
							setTimeout(tryChoose.bind(attempt+1), retry*1000);
						} else {
							alert(`Kategoriewahl konnte nicht übernommen werden: ${xhr.responseText}`);
						}
					}
				}).send(JSON.stringify({'':'/schema/deal', 'category_':ev.target.pathname}));
				tryChoose(0);
			});
			break;
		}
	});
})();
