(() => {"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	const loadMore = () => makeXHR('GET', $link.href, {Accept: 'application/json'}, xhr => {}).send();
	const $section = document.getElementById('closed-games');
	const $link = $section.querySelector('a[rel=next]');
	if (!$link) return;
	loadMore();
	$link.addEventListener('click', ev => {ev.preventDefault();loadMore()});
})();
