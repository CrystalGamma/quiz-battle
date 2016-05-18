Insert into spieler(name, passwort, punkte) values('admin', '$2y$10$/Itd6yBRda9zB8zdlIIhguhDMPqJd9IOcEcYyD./xFtOkULJePZAu', 0); --Passwort="admin" als hash
Insert into spieler(name, passwort, punkte) values('admin2', '$2y$10$/Itd6yBRda9zB8zdlIIhguhDMPqJd9IOcEcYyD./xFtOkULJePZAu', 1);
Insert into kategorie(name) values ('kat1'), ('kat2'), ('kat3');
Insert into frage(frage, richtig, falsch1, falsch2, falsch3, erklaerung) values ('frage1', 'richtig', 'falsch1', 'falsch2', 'falsch3', 'erklaerung'), ('frage2', 'richtig', 'falsch1', 'falsch2', 'falsch3', 'erklaerung'), ('frage3', 'richtig', 'falsch1', 'falsch2', 'falsch3', 'erklaerung');
Insert into frage_kategorie(frage, kategorie) values(1,1),(2,2),(3,3);
Insert into spiel(einsatz, dealer, runden, fragen_pro_runde,	fragenzeit, rundenzeit, status) values(1,1,2,3,4,5,'beendet'),(6,1,7,8,9,10,'laufend'),(11,1,12,13,14,15,'laufend');
Insert into teilnahme(spiel, spieler, akzeptiert) values(1,2,true),(2,1,true),(1,1,true),(2,2,true),(3,1,true),(3,2,false);
Insert into runde(spiel, rundennr, dealer, kategorie, start) values (1,1,1,1,current_date),(1,2,2,2,current_date),(2,1,1,3,current_date);
Insert into spiel_frage(spiel, fragennr, frage) values(1,1,1),(1,2,2),(2,1,3);
insert into antwort(spiel, spieler, fragennr, antwort, startzeit) values(1,1,1,0,current_date),(1,2,1,1,current_date),(1,1,2,0,current_date),(1,2,2,1,current_date),(2,1,1,2,current_date);

