(() => {"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	loginPromise.then(login => {
		const loadMore = () => makeXHR('GET', $link.href, {Accept: 'application/json', Authorization: login.token}, xhr => {
			if (xhr.status < 200 || xhr.status >= 300 || !xhr.getResponseHeader('Content-Type').startsWith('application/json')) {return}
			const json = JSON.parse(xhr.responseText);
			json.games_.map(showGame(login)).forEach(el => $list.appendChild(el));
			if (json.next_) {
				$link.href = json.next_;	// TODO: resolve relative URIs
			} else {
				$link.parentNode.removeChild($link);
			}
		}).send();
		const $section = document.getElementById('closed-games');
		if (!$section) {return}
		const $link = $section.querySelector('a[rel=next]');
		const $list = $section.querySelector('ul');
		if (!$link) {return}
		loadMore();
		$link.addEventListener('click', ev => {ev.preventDefault();loadMore()});
	});
})();
