const showGame = login => url => {
	const $game = buildDom({'':'a.game', href: url});
	makeXHR('GET', url, {Accept: 'application/json'}, xhr => {
		if (xhr.status < 200 || xhr.status >= 300) {return}
		const ct = xhr.getResponseHeader('Content-Type');
		if (ct !== 'application/json') {console.error(`Wrong content type: ${ct}`);return}
		const game = JSON.parse(xhr.responseText);
		let selfPlayer = null;
		game.players.forEach((player, idx) => {if (player[''] === (login.player_||login.player[''])) {selfPlayer = idx}});
		if (selfPlayer === null) {
			$game.parentNode.removeChild($game);	// game was concurrently rejected
			return;
		}
		const scores = game.players.map(() => 0);
		game.questions.forEach(question => question && question.answers.forEach((status, player) => scores[player]+=0|(status === 0)));
		let bestPlayer = null, bestScore = -1;
		scores.forEach((score, idx) => {if (score > bestScore && idx != selfPlayer) {bestPlayer = idx;bestScore=score}});
		const title = game.players.length > 2
			? [game.players[bestPlayer].name]
			: [{'':'span.player', c:game.players[bestPlayer].name}, ` und ${game.players.length-2} weitere`];
		buildDom(title).forEach(x => $game.appendChild(x));
		$game.appendChild(buildDom({'':'span.game-points'+(
			scores[selfPlayer] > bestScore ? '.winning' :
			scores[selfPlayer] < bestScore ? '.losing'
			: '.tied'
		), c:`${scores[selfPlayer]} – ${bestScore}`}));
		const lookup = x => (
			x === 0 ? 'g' :
			x === null ? '-'
			: 'b'
		);
		$game.appendChild(buildDom({'':'ul.game-report', c:game.questions.map(({answers}) => ({'':'li.'+lookup(answers[selfPlayer])+lookup(answers[bestPlayer])}))}));
	}).send();
	const res = buildDom({'':'li', c:$game});
	return res;
};
