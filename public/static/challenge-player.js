(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	const submitUrl = document.currentScript.dataset.submit;
	loginPromise.then(login => {
		const challenge = player => {
			const data = JSON.stringify({'':'/schema/game?new', players_:[login.player_||login.player[''], player]});
			const trySubmit = () => makeXHR('POST', submitUrl, {'Content-Type':'application/json', Authorization: login.token}, xhr => {
				if (xhr.status === 201) {
					location.href = xhr.getResponseHeader('Location');
				} else {
					const retry = xhr.getResponseHeader('Retry-After')|0;
					if (retry) {
						setTimeout(trySubmit, retry*1000);
					} else {
						alert(`Konnte Spiel nicht erstellen: ${xhr.responseText}`);
					}
				}
			}).send(data);
			trySubmit();
		};
		document.addEventListener('click', ev => {if (ev.target.classList.contains('challenge-player')){
			challenge(ev.target.pathname);
			ev.preventDefault();
		}});
	});
})();
