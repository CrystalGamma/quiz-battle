create table kategorie(
	id int auto_increment primary key not null,
	name varchar(255) not null
);

create table frage(
	id int auto_increment primary key not null,
	frage varchar(255) not null,
	richtig varchar(50) not null,
	falsch1 varchar(50) not null,
	falsch2 varchar(50) not null,
	falsch3 varchar(50) not null,
	bild blob null,
	erklaerung varchar(255) not null	-- nicht vorhanden, wenn leerer string
);

create table frage_kategorie(
	frage int not null,
	kategorie int not null,
	primary key (frage, kategorie),
	FOREIGN KEY(frage) REFERENCES frage(id),
	FOREIGN KEY(kategorie) REFERENCES kategorie(id)
);

create table spieler(
	id int auto_increment primary key not null,
	name varchar(255) unique not null, -- nick
	passwort varchar(255) not null,
	punkte int not null
);

create table spiel(
	id int auto_increment primary key not null,
	einsatz int not null,
	dealer int null,
	runden int not null,
	fragen_pro_runde int not null,
	fragenzeit int not null,
	rundenzeit int not null,
	status varchar(50) not null;
	foreign key(dealer) references spieler(id)
);

create table teilnahme(
	spiel int not null,
	spieler int not null,
	akzeptiert boolean not null,
	primary key(spiel, spieler),
	foreign Key(spiel) references spiel(id),
	foreign Key(spieler) references spieler(id)
);

create table runde(
	spiel int not null,
	rundennr int not null,
	start timestamp not null,
	dealer int not null,
	kategorie int null,
	primary key (spiel, rundennr),
	foreign Key(spiel) references spiel(id),
	foreign Key(dealer) references spieler(id),
	foreign Key(kategorie) references kategorie(id)
);

create table spiel_frage(
	spiel int not null,
	fragennr int not null,
	frage int not null,
	primary key(spiel, fragennr),
	unique(spiel, frage),
	foreign Key(spiel) references spiel(id),
	foreign Key(frage) references frage(id)
);

create table antwort(
	spiel int not null,
	spieler int not null,
	fragennr int not null,
	antwort int null,
	startzeit timestamp not null,
	foreign key (spiel, fragennr) references spiel_frage(spiel, fragennr),
	foreign key (spiel, spieler) references teilnahme(spiel, spieler),
	primary key (spiel, spieler, fragennr),
	foreign Key(spiel) references spiel(id),
	foreign Key(spieler) references spieler(id)
	
);

create index ranking on spieler(punkte);
