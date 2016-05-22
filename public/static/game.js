(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
	loginPromise.then(login => {
		const $main = document.querySelector('main');
		const $players = Array.from($main.querySelectorAll('thead a'));
		document.addEventListener('click', ev => {
			if (ev.target.classList.contains('accept')) {
				const accept = JSON.parse(ev.target.dataset.accept);
				const tryAccept = attempt => makeXHR('PUT', '', {'Content-Type':'application/json', Authorization: login.token}, xhr => {
					if (xhr.status >= 200 && xhr.status < 300) {
						if (xhr.status === 205) {
							location.reload();
						} else if (xhr.getResponseHeader('Location')) {
							location.href = xhr.getResponseHeader('Location');
						}
					} else {
						const retry = xhr.getResponseHeader('Retry-After');
						if (xhr.status >= 500 && xhr.status < 600 && attempt < 3) {
							setTimeout(tryAccept.bind(null, attempt+1), (retry||1)*1000);
						} else {
							alert(`Fehler beim Annehmen des Spiels: ${xhr.responseText}`);
						}
					}
				}).send(JSON.stringify({'':'/schema/response', accept}));
				tryAccept(0);
			}
		});
		for (let $player of $players) {
			if ($player.pathname === (login.player_||login.player[''])) {
				if ($player.dataset.accepted !== 'true') {
					$main.insertBefore(buildDom({'':'.dialog', c:["Nimmst du diese Herausforderung an?", {c:[
						{'':'button.accept.start-game', 'data-accept': 'true', c: "Ja, Spiel starten"},
						{'':'button.accept', 'data-accept':'false', c:"Nein, Spiel beenden"}
					]}]}), $main.firstChild);
					return;
				}
			}
		}
		for (let $row of Array.from($main.querySelectorAll('tbody >tr'))) {
			const $dealer = $row.querySelector('a.dealer');
			if (!$dealer || $dealer.pathname !== (login.player_||login.player[''])) {continue}
			$main.appendChild(buildDom({'':'.dialog', c:["Wähle Fragenkategorie für die nächste Runde:", ...JSON.parse($dealer.dataset.candidates).map(cat => ({'':'a.candidate', href:cat[''], c:cat['name']}))]}));
			$main.addEventListener('click', ev => {
				if (!ev.target.classList.contains('candidate')) {return}
				ev.preventDefault();
				const tryChoose = attempt => makeXHR('POST', '', {'Content-Type':'application/json', Accept:'application/json', Authorization: login.token}, xhr => {
					if (xhr.status >= 200 && xhr.status < 300) {
						location.reload();
					} else {
						const retry = xhr.getResponseHeader('Retry-After')|0;
						if (attempt < 3 && retry) {
							setTimeout(tryChoose.bind(null, attempt+1), retry*1000);
						} else {
							alert(`Kategoriewahl konnte nicht übernommen werden: ${xhr.responseText}`);
						}
					}
				}).send(JSON.stringify({'':'/schema/deal', 'category_':ev.target.pathname}));
				tryChoose(0);
			});
			break;
		}
		const reloadUnknown = () => Promise.all(Array.from($main.querySelectorAll('a.answer.unknown')).map($answer => new Promise((resolve, reject) => {
			makeXHR('GET', $answer.href, {Accept:'application/json', Authorization: login.token}, xhr => {
				if (xhr.status < 200 || xhr.status >= 300) {return}
				if (!xhr.getResponseHeader('Content-Type').startsWith('application/json')) {return}
				const json = JSON.parse(xhr.responseText);
				if (json.question === null || json.answers === null || json.answers.some(x => (x.player_||x.player['']) === (login.player_||login.player['']) && x.ans === null)) {
					resolve([json, $answer]);
				} else {
					for (let ans of json.answers) {
						if ((ans.player_||ans.player['']) !== $answer.hash.substring(1)) {continue}
						$answer.classList.remove('unknown');
						$answer.classList.add(ans.ans === 0 ? 'correct' : 'incorrect');
						if (ans.ans !== null) {$answer.dataset.givenanswer = ans.ans === '' ? '' : json.question.answers[ans.ans]}
					}
					resolve(null);
				}
			}).send();
		})));
		reloadUnknown().then(unanswered => {
			console.log(unanswered);
			for (let pair of unanswered.filter(x => !!x)) {
				$main.appendChild(buildDom({'':'.dialog', c:{'':'a.askme.start-game', href: pair[1].href, c:"Nächste Frage"}}));
				break;
			}
		});
		$main.addEventListener('click', ev => {
			if (!ev.target.classList.contains('askme')) {return}
			const $dialog = ev.target.parentNode;
			const tryAnswer = (attempt, answer) => makeXHR('PUT', ev.target.href, {'Content-Type':'application/json', Accept: 'application/json', Authorization: login.token}, xhr => {
				if (xhr.getResponseHeader('Content-Type').startsWith('application/json')) {
					const json = JSON.parse(xhr.responseText);
					const $answers = Array.from($dialog.querySelectorAll('button.answer'));
					$answers.forEach(x => {x.disabled = true});
					if (answer !== null) {$answers[answer].classList.add('incorrect')}
					const $correct = $answers[json.answer];
					$correct.classList.remove('incorrect');
					$correct.classList.add('correct');
					if (json.explanation) {$dialog.appendChild(buildDom({'':'p.explanation', c:json.explanation}))}
					reloadUnknown();
				}
				if (xhr.status >= 200 && xhr.status < 300) {
					onTheClock = false;
					const nextQuestion = xhr.getResponseHeader('Location');
					if (xhr.getResponseHeader('Location')) {
						$dialog.appendChild(buildDom({'':'a.askme.start-game', href: nextQuestion, c:"Nächste Frage"}));
					}
				} else if (xhr.status === 403) {
					alert("Die Zeit ist abgelaufen");
					const $button = buildDom({'':'button.start-game', c: "Weiter"});
					$button.addEventListener('click', () => location.reload());
					$dialog.appendChild($button);
				} else if (attempt < 10 && xhr.status >= 500 && xhr.status < 600) {
					setTimeout(tryAnswer.bind(null, attempt+1, answer), (xhr.getResponseHeader('Retry-After')|0)*1000);
				} else {
					alert(`Konnte Antwort nicht speichern: ${xhr.responseText}`);
				}
			}).send(JSON.stringify({'':'/schema/myanswer', answer:answer||0}));
			let onTheClock = true;
			const timeLimit = ($main.dataset.timelimit|0)*1000;
			let endTime = performance.now() + timeLimit;
			const $timer = buildDom({'':'progress', max:timeLimit});
			const step = time => {
				if (time > endTime) {
					$timer.value = 0;
					setTimeout(tryAnswer.bind(null, 0, null), 1000);
				} else {
					$timer.value = endTime-time;
					if (onTheClock) {requestAnimationFrame(step)}
				}
			};
			step(performance.now());
			const tryAskMe = attempt => makeXHR('POST', ev.target.href, {Accept:'application/json', 'Content-Type':'application/json', Authorization:login.token}, xhr => {
				if (xhr.status >= 200 && xhr.status < 300) {
					const json = JSON.parse(xhr.responseText);
					$dialog.innerHTML='';
					$dialog.appendChild(buildDom({'':'.choice', c:[
						json.question,
						$timer,
						...json.answers.map((ans, idx) => ({'':'button.answer', 'data-value':''+idx, c:ans}))
					]}));
					const handler = ev => {
						if (!ev.target.classList.contains('answer')) {return}
						tryAnswer(0, ev.target.dataset.value|0);
						ev.preventDefault();
						$dialog.removeEventListener('click', handler);
					};
					$dialog.addEventListener('click', handler);
				} else {
					const retry = xhr.getResponseHeader('Retry-After')|0;
					if (attempt < 3 && retry) {
						setTimeout(tryAskMe.bind(null, attempt+1), retry*1000);
					} else {
						alert(`Frage konnte nicht geladen werden: ${xhr.responseText}`);
					}
				}
			}).send(JSON.stringify({'':'/schema/askme'}));
			tryAskMe(0);
			ev.preventDefault();
		});
	});
})();
