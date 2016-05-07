(() => {"use strict";
const loginBoxes = document.querySelectorAll('.login');
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
		makeXHR('POST', container.dataset.auth, {'Content-Type':'application/json'}, xhr => {
			if (xhr.status === 200 && xhr.getResponseHeader('Content-Type') === 'text/plain; charset=UTF-8') {
				localStorage.setItem('login', JSON.stringify({user: ev.target.user.value}));
				location.reload();
			} else if (xhr.status >= 200 && xhr.status <300) {
				alert(`Interner Fehler: Unerwartetes Anmeldetoken-Format: Status ${xhr.status}, ${xhr.getResponseHeader('Content-Type')}`);
			} else if (xhr.status === 404) {
				alert("Kein Account mit diesem Namen vorhanden");
			} else {
				alert(`Anmeldung fehlgeschlagen (${xhr.statusText}): ${xhr.body}`);
			}
		}).send(JSON.stringify({user:ev.target.user.value, password: ev.target.password}));
	});
} else {
	login = JSON.parse(login);
	for (let el of loginBoxes) {
		el.appendChild(buildDom({'':'p', c:["Hallo, ", {'':'a.user', c: login.user}, {'':'button.logout', type:'button', c:"Logout"}]}));
	}
	document.addEventListener('click', ev => {
		if (!ev.target.classList.contains('logout')) {return}
		localStorage.removeItem('login');
		location.reload();
	});
}
})();
