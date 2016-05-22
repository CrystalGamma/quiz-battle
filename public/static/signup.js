(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	const authUrl = document.currentScript.dataset.auth;
	const registerUrl = document.currentScript.dataset.register;
	const $main = document.querySelector('main');
	loginPromise.then(login => {
		document.querySelector('header nav').appendChild(buildDom({'':'a', href:login.player_||login.player[''], c:"Meine Statistiken"}));
	}, () => {
		const $user = buildDom({'':'input', name:'user', autocomplete:'username'});
		const $password = buildDom({'':'input', type:'password', name:'password'});
		const $register = buildDom({'':'input', type:'checkbox', autocomplete:'off'});
		$main.appendChild(buildDom({'':'form.signup', c:[
			{'':'h1', c:"Willkommen bei Quiz Battle"},
			{'':'label', c:["Nickname: ", $user]},
			{'':'label', c:["Passwort: ", $password]},
			{'':'label.dense', c:[$register, "Neuen Account erstellen"]},
			{'':'button', type:'submit', c:"Anmelden"}
		]}));
		$user.focus();
		$main.addEventListener('submit', ev => {
			ev.preventDefault();
			if ($register.checked) {
				const tryRegister = () => makeXHR('POST', registerUrl, {Accept: 'application/json', 'Content-Type':'application/json'}, xhr => {
					if (xhr.status === 201) {
						localStorage.setItem('login', xhr.responseText);
						location.href = xhr.getResponseHeader('Location');
					} else {
						const retry = xhr.getResponseHeader('Retry-After');
						if (retry) {
							setTimeout(tryRegister, retry);
						} else {
							alert(`Registration fehlgeschlagen: ${xhr.responseText}`);
						}
					}
				}).send(JSON.stringify({'':'/schema/player', 'name':$user.value, 'password': $password.value}));
				tryRegister();
			} else {
				doLogin(authUrl, $user.value, $password.value).then(() => location.reload()).catch(([,err]) => alert(err));
			}
		});
	});
})();
