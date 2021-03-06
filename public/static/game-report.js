const renderGame = (refPlayer, game) => {
	let selfPlayer = null;
	game.players.forEach((player, idx) => {if (player[''] === refPlayer) {selfPlayer = idx}});
	if (selfPlayer === null) {return []}
	const scores = game.players.map(() => 0);
	game.questions.forEach(question => question && question.answers && question.answers.forEach((status, player) => scores[player]+=0|(status === 0)));
	let bestPlayer = null, bestScore = -1;
	scores.forEach((score, idx) => {if (score > bestScore && idx != selfPlayer) {bestPlayer = idx;bestScore=score}});
	const score = {'':'span.game-points'+(
		scores[selfPlayer] > bestScore ? '.winning' :
		scores[selfPlayer] < bestScore ? '.losing'
		: '.tied'
	), c:`${scores[selfPlayer]} – ${bestScore}`};
	const lookup = x => (
		x === 0 ? 'g' :
		x === null ? '-'
		: 'b'
	);
	return game.players.length === 2
		? [{'':'span.player', c:game.players[bestPlayer].name}, score, {'':'ul.game-report', c:game.questions.filter(x => !!x.answers).map(quest => ({'':'li.'+lookup(quest.answers[selfPlayer])+lookup(quest.answers[bestPlayer])}))}] 
		: ["Beliebiger Spieler"];
};
