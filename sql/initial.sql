Insert into spieler(name, passwort, punkte) values('admin', '$2y$10$/Itd6yBRda9zB8zdlIIhguhDMPqJd9IOcEcYyD./xFtOkULJePZAu', 0); -- Passwort="admin" als hash
Insert into spieler(name, passwort, punkte) values('player2', '$2y$10$4QMfrcxre1wb9TsTfSIDwOhLmZWZVoNq80z9GyUohaYENVn5BSZ9m', 1); -- Passwort="player" als hash
Insert into spieler(name, passwort, punkte) values('player3', '$2y$10$4QMfrcxre1wb9TsTfSIDwOhLmZWZVoNq80z9GyUohaYENVn5BSZ9m', 1);
Insert into spieler(name, passwort, punkte) values('player4', '$2y$10$4QMfrcxre1wb9TsTfSIDwOhLmZWZVoNq80z9GyUohaYENVn5BSZ9m', 1);
Insert into spieler(name, passwort, punkte) values('player5', '$2y$10$4QMfrcxre1wb9TsTfSIDwOhLmZWZVoNq80z9GyUohaYENVn5BSZ9m', 1);
Insert into spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) values
                  (1,       1,      5,      3,                10,         172800,     'beendet'),
                  (6,       1,      5,      3,                10,         172800,     'laufend'),
                  (11,      1,      5,      3,                10,         172800,     'laufend');
Insert into teilnahme (spiel, spieler, akzeptiert) values
                      (1,     2,       true),
                      (2,     1,       true),
                      (1,     1,       true),
                      (2,     2,       true),
                      (3,     1,       true),
                      (3,     2,       false);
Insert into runde(spiel, rundennr, dealer, kategorie, start) values (1,1,1,1,current_date),(1,2,2,2,current_date),(2,1,1,3,current_date);
Insert into spiel_frage(spiel, fragennr, frage) values(1,1,1),(1,2,2),(2,1,3);
insert into antwort(spiel, spieler, fragennr, antwort, startzeit) values(1,1,1,0,current_date),(1,2,1,1,current_date),(1,1,2,0,current_date),(1,2,2,1,current_date),(2,1,1,2,current_date);