(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	const $tbody = document.querySelector('main >table >tbody');
	const $next = document.querySelector('main a[rel="next"]');
	$next && $next.addEventListener(ev => {makeXHR('GET', $next.href, {Accept: 'application/json'}, xhr => {
		if (xhr.status < 200 || xhr.status >= 300) {return}
		let json = JSON.parse(xhr.responseText);
		json.players.forEach((player, idx) => tbody.appendChild(buildDom({'':'tr', c:[
			{'':'td', c:`${json.start + idx + 1}.`},
			{'':'th', c:player['name']},
			{'':'td', c:''+player['points']}
		]})));
		if (json.next_) {
			$next.href = json.next_;	// TODO: resolve relative URI reference
		} else {
			$next.parentNode.removeChild($next);
		}
	}).send();ev.preventDefault()});
})();
