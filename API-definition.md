# Startseite: /
## GET
### text/html
Startseite

# Spielerliste: /players/
## GET
Paginated: Query-Parameter
• `start` für Offset in der Liste (Default 0)
• `end` für Ende der Liste (Default `start` + z. B. 10)

Länge muss serverseitig beschränkt werden (auf beispielsweise 1000) um keine Oberfläche für DoS-Angriffe zu bieten.
`start` > Anzahl(Spieler) || `end` gesetzt && `end` > Anzahl(Spieler) ⇒ 404 Not Found

### text/html
Ranking-Liste

### application/json
```json
{
	"": "/schema/players",
	"count": 1234,
	"start": 0,
	"end": 10,
	"next_": "?start=10",
	"prev_": null,
	"players": [
		{"": "<id>", "name": "Spielername", "points": 12345}
	]
}
```

## POST
Spieler hinzufügen
Format wie in `GET /players/<id>` als `application/json`.

# Spieler: /players/<id>
## GET
Ggf. müssen für verschiedene Nutzer verschiedene Informationen sichtbar sein (bspw. Ranking für alle, Spiele nur für alle Spieler die beteiligt sind; impliziert `Vary: Authorization`).

### text/html
Spielerstatistiken

### application/json
```json
{
	"": "/schema/player",
	"name": "Spielername",
	"games_": ["/games/<id>"],
	"categorystats": [{
			"category": {"": "/categories/<id>", "name": "Kategorie"},
			"correct": 100,
			"incorrect": 42
	}]
}
```

### image/*
Avatar; derzeit noch nicht vorgesehen

# Spiele: /games/
## POST (Spiel erstellen)
Request-Body (`application/json`):

```json
{
	"": "/schema/game?new",
	"players_": ["/player/<id>"],
	"rounds": 5,
	"turns": 3,
	"timelimit": 10,
	"roundlimit": 172800,
	"dealingrule": "/player/<id>"
}
```
Felder analog zu `GET /games/<id>/` als `application/json`.
Wenn ein Spieler als Dealer angegeben ist, muss es der angemeldete Spieler sein.
Wenn der Spieler am Spiel teilnimmt, hat er automatisch angenommen.

# Spiel: /games/<id>/
## GET
Ggf. Zugriff nur für Beteiligte (`Vary: Authorization`).

Solange das Spiel noch läuft, sind für alle Fragen, die der Betrachter noch nicht beantwortet hat, nicht die Antworten angegeben (auch `Vary: Authorization`).

### text/html
Spielansicht

### application/json
```json
{
	"": "/schema/game",
	"players": [
		{"": "/player/<id>", "name": "Spielername", "accepted": true}
	],
	"rounds": [{
		"category_": "/category/<id>",
		"dealer": {"": "/player/<id>", "name": "Spielername"},
		"age": 3600
	}, {
		"candidates_": ["/category/<id>"],
		"dealer": {"": "/player/<id>", "name": "Spielername"},
		"age": 30
	}, {}, {}, {}],
	"turns": 3,
	"timelimit": 10,
	"roundlimit": 172800,
	"questions": [
		{"": "<qid>", "status": [true]}
	],
	"dealingrule": "/dealing/firstanswer"
}
```
`turns`: Anzahl Fragen pro Runde
`timelimit`: Antwortzeit in Sekunden
`roundlimit`: max. Dauer einer Runde (Sekunden)
`age`: Zeit (Sekunden), seit die Runde eröffnet wurde
`dealer`: Spieler, der die Kategorie der Runde bestimmt hat/bestimmen darf (wenn noch nicht gewählt)
`dealingrule`: URL für verschiedene Regeln, wer die nächste Runde bestimmen darf (`/dealing/firstanswer`: der Spieler, der nicht Dealer der letzten Runde war und alle Fragen der Runde zuerst beantwortet hat). Kann ggf. auch eine Spieler-URL sein, damit ein Spieler alle Runden bestimmen darf.

`questions.status`: Antwortstatus in der Reihenfolge der Spielerliste: `null` wenn noch nicht beantwortet, `true` wenn richtig, `false` wenn falsch, `""` wenn Zeit abgelaufen.

## PUT (Spiel annehmen/ablehnen)
Request-Body (`application/json`):

```json
{
	"": "/schema/response",
	"accept": true
}
```
Akzeptiert das Spiel/lehnt es ab (je nach `accept`) im Namen des angemeldeten Spielers (`Vary: Authorization`).
Response-Body ist leer.
Response Code ist `200 OK`, `202 Accepted`, `204 No Content` oder `205 Reset Content` (TBD).

## POST (Kategorie auswählen)
```json
{
	"": "/schema/deal"
	"category_": "/category/<id>"
}
```

Wählt die Kategorie für die erste Kategorie im Spiel, die man dealen darf.

# Frage im Spiel: /games/<gid>/<qid>
## GET
Solange Spiel läuft, muss der Zugriff von Spielern, die die Frage nicht beantwortet haben, verboten sein (`Vary: Authorization`).

### application/json
```json
{
	"": "/schema/game-question",
	"question": {
		"": "/questions/<cid>",
		"question": "Fragetext",
		"picture": null,
		"explanation": "Erklärung",
		"answers": ["Antwort 1", "Antwort 2 (richtig)", "Antwort 3", "Antwort 4"],
		"correct": 1
	}
	"answers": [
		{"player_": "/player/<id>", "ans": 0}
	]
}
```

## POST (Frage beginnen)
Request-Body:

```json
{"": "/schema/askme"}
```

Response (`application/json`):

```json
{
	"": "/schema/popquiz",
	"question": "Fragetext",
	"answers": ["Antwort 1", "Antwort 2", "Antwort 3", "Antwort 4"]
}
```

### PUT (Antwort eintragen)
Request-Body (`application/json`):

```json
{
	"": "/schema/myanswer",
	"answer": 1
}
```

Response wie für GET (mit `Content-Location` auf eigene URL).

Zeitlimit vorbei ⇒ 403 Forbidden

# Kategorienliste: /categories/
## GET
### text/html
Fragenkatalog

### application/json
```json
{
	"": "/schema/categories",
	"categories": [
		{"": "<id>", "name": "Kategoriename"}
	]
}
```

# Kategorie: /categories/<id>
## GET
### application/json
```json
{
	"": "/schema/category",
	"count": 1234,
	"start": 0,
	"end": 10,
	"next_": "?start=10",
	"prev_": null,
	"questions": [
		{"": "/questions/<id>", "text": "Fragentext"}
	]
}
```

# Katalog-Frage: /questions/<id>
## GET
### application/json
```json
{
	"": "/schema/question",
	"question": "Fragentext",
	"explanation": "Erklärung",
	"answers": ["Richtige Antwort", "Falsche Antwort 1", "Falsche Antwort 2", "Falsche Antwort 3"],
	"picture": "http://example.com/picture.svg",
	"categories_": ["/categories/<id"]
}
```

Die richtige Antwort wird immer zuerst geliefert.

