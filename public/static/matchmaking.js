(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	loginPromise.then(login => document.addEventListener('click', ev => {
		if (!ev.target.classList.contains('matchmaking')) {return}
		ev.preventDefault();
		makeXHR('POST', ev.target.href, {'Content-Type':'application/json', Authorization:login.token}, xhr => {
			if (xhr.status >= 200 && xhr.status < 300) {
				if (xhr.getResponseHeader('Location')) {
					location.href = xhr.getResponseHeader('Location');
				} else {
					location.reload();
				}
			} else {
				alert(`Konnte das Spiel nicht erstellen: ${xhr.responseText}`);
			}
		}).send(JSON.stringify({'':'/schema/game?new', players_:[login.player_||login.player['']]}));
	}));
})();
