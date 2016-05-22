(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	loginPromise.then(login => {
		const $changepw = buildDom({'':'button', c:"Passwort ändern"});
		document.body.appendChild($changepw);
		$changepw.addEventListener('click', ev => {
			const newpw = prompt("Neues Passwort:");
			if (!newpw) {return}
			const trySet = attempt => makeXHR('PUT', login.player_||login.player[''], {Authorization:login.token, 'Content-Type':'application/json'}, xhr => {
				if (xhr.status >= 200 && xhr.status < 300) {
					localStorage.setItem('login', xhr.responseText);
					location.reload();
				} else {
					const retry = xhr.getResponseHeader('Retry-After')|0;
					if (xhr.status >= 500 && xhr.status < 600 && attempt < 3) {
						setTimeout(trySet.bind(null, attempt+1), retry*1000);
					} else {
						alert(`Konnte Passwort nicht setzen: ${xhr.responseText}`);
					}
				}
			}).send(JSON.stringify({'':'/schema/player?setpassword', password:newpw}));
			trySet(0);
		});
	});
})();
