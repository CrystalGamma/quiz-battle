INSERT INTO spieler(name, passwort, punkte) VALUES ('admin', '$2y$10$/Itd6yBRda9zB8zdlIIhguhDMPqJd9IOcEcYyD./xFtOkULJePZAu', 0); -- Passwort="admin" als hash
INSERT INTO spieler(name, passwort, punkte) VALUES ('player2', '$2y$10$4QMfrcxre1wb9TsTfSIDwOhLmZWZVoNq80z9GyUohaYENVn5BSZ9m', 1); -- Passwort="player" als hash
INSERT INTO spieler(name, passwort, punkte) VALUES ('player3', '$2y$10$4QMfrcxre1wb9TsTfSIDwOhLmZWZVoNq80z9GyUohaYENVn5BSZ9m', 1);
INSERT INTO spieler(name, passwort, punkte) VALUES ('player4', '$2y$10$4QMfrcxre1wb9TsTfSIDwOhLmZWZVoNq80z9GyUohaYENVn5BSZ9m', 1);
INSERT INTO spieler(name, passwort, punkte) VALUES ('player5', '$2y$10$4QMfrcxre1wb9TsTfSIDwOhLmZWZVoNq80z9GyUohaYENVn5BSZ9m', 1);
INSERT INTO spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) VALUES
                  (1,       1,      1,      3,                10,         172800,     'beendet'),
                  (6,       1,      5,      3,                10,         172800,     'laufend'),
                  (11,      1,      5,      3,                10,         172800,     'laufend');
INSERT INTO teilnahme (spiel, spieler, akzeptiert) VALUES
                      (1,     2,       true),
                      (2,     1,       true),
                      (1,     1,       true),
                      (2,     2,       true),
                      (3,     1,       true),
                      (3,     2,       false);
INSERT INTO runde (spiel, rundennr, dealer, kategorie) VALUES
                  (1,     1,        1,      4),
                  (2,     1,        1,      4);
INSERT INTO spiel_frage (spiel, fragennr, frage) VALUES
                        (1,     1,        1),
                        (1,     2,        2),
                        (1,     3,        3),
                        (2,     1,        3);
INSERT INTO antwort (spiel, spieler, fragennr, antwort) VALUES
                    (1,     1,       1,        0),
                    (1,     2,       1,        1),
                    (1,     1,       2,        0),
                    (1,     2,       2,        1),
                    (1,     1,       3,        1),
                    (1,     2,       3,        0),
                    (2,     1,       1,        2);