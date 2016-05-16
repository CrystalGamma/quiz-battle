const doLogin = (url, user, password) => new Promise((resolve, reject) => {
	makeXHR('POST', url, {'Content-Type':'application/json'}, xhr => {
		if (xhr.status === 200 && xhr.getResponseHeader('Content-Type') === 'application/json') {
			const login = JSON.parse(xhr.responseText);
			login.user = user;
			localStorage.setItem('login', JSON.stringify(login));
			resolve(login);
		} else if (xhr.status >= 200 && xhr.status <300) {
			reject(`Interner Fehler: Unerwartetes Anmeldetoken-Format: Status ${xhr.status}, ${xhr.getResponseHeader('Content-Type')}`);
		} else if (xhr.status === 404) {
			reject("Kein Account mit diesem Namen vorhanden");
		} else if (xhr.status === 403) {
			reject(`Anmeldung fehlgeschlagen: ${xhr.responseText}`);
		} else {
			reject(`Anmeldung fehlgeschlagen (${xhr.statusText}): ${xhr.responseText}`);
		}
	}).send(JSON.stringify({user, password}));
});
const loginPromise = (() => {"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
const loginBoxes = Array.from(document.querySelectorAll('.login'));
if (!localStorage) {return}
let login = localStorage.getItem('login');
if (!login) {
	for (let el of loginBoxes) {
		el.appendChild(buildDom({'':'form', c:[
			{'':'input', name:'user', placeholder:"Username"},
			{'':'input', name:'password', type:'password', placeholder:"Passwort", autocomplete:'current-password'},
			{'':'button', type:'submit', c: "Login"}
		]}));
	}
	document.body.addEventListener('submit', ev => {
		const container = ev.target.parentNode;
		if (!container.classList.contains('login')) {return}
		ev.preventDefault();
		doLogin(container.dataset.auth, ev.target.user.value, ev.target.password.value).then(() => location.reload()).catch(error => alert(error));
	});
	return Promise.reject("nicht angemeldet");
} else {
	login = JSON.parse(login);
	for (let el of loginBoxes) {
		el.appendChild(buildDom({'':'span', c:["Hallo, ", {'':'a.user', href: login.player_, c: login.user}, {'':'button.logout', type:'button', c:"Logout"}]}));
	}
	document.addEventListener('click', ev => {
		if (!ev.target.classList.contains('logout')) {return}
		localStorage.removeItem('login');
		location.reload();
	});
	return Promise.resolve(login);
}
})();
