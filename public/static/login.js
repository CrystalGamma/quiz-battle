(() => {
const loginBoxes = document.querySelectorAll('.login');
if (!localStorage) {return}
let login = localStorage.getItem('login');
if (!login) {
	for (let el of loginBoxes) {
		el.appendChild(buildDom({'':'form.inline', c:[
			{'':'label', c:{'':'input', name:'user', placeholder:"Username"}},
			{'':'label', c:{'':'input', name:'password', type:'password', placeholder:"Passwort", autocomplete:'current-password'}},
			{'':'button', type:'submit', c: "Login"}
		]}));
	}
	console.log('listener');
	document.body.addEventListener('submit', ev => {
		console.log('test');
		if (!ev.target.parentNode.classList.contains('login')) {return}
		localStorage.setItem('login', JSON.stringify({username: ev.target.user.value}));
		location.reload();
		ev.preventDefault();
	});
} else {
	login = JSON.parse(login);
	for (let el of loginBoxes) {
		el.appendChild(buildDom({'':'p', c:["Hallo, ", {'':'a.user', c: login.username}, {'':'button.logout', type:'button', c:"Logout"}]}));
	}
	document.addEventListener('click', ev => {
		if (!ev.target.classList.contains('logout')) {return}
		localStorage.removeItem('login');
		location.reload();
	});
}
})();
