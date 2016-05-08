create table kategorie(
	id serial primary key not null,
	name varchar(255) not null
);

create table frage(
	id serial primary key not null,
	frage varchar(255) not null,
	richtig varchar(50) not null,
	falsch1 varchar(50) not null,
	falsch2 varchar(50) not null,
	falsch3 varchar(50) not null,
	bild bytea null,
	erklaerung varchar(255) not null	-- nicht vorhanden, wenn leerer string
);

create table frage_kategorie(
	frage int references frage(id) not null,
	kategorie int references kategorie(id) not null,
	primary key (frage, kategorie)
);

create table spieler(
	id serial primary key not null,
	name varchar(255) unique not null, -- nick
	passwort varchar(255) not null,
	punkte int not null
);

create table spiel(
	id serial primary key not null,
	einsatz int not null,
	dealer int references spieler(id) null,
	runden int not null,
	fragen_pro_runde int not null,
	fragenzeit int not null,
	rundenzeit int not null
);

create table teilnahme(
	spiel int references spiel(id) not null,
	spieler int references spieler(id) not null,
	akzeptiert boolean not null,
	primary key(spiel, spieler)
);

create table runde(
	spiel int references spiel(id) not null,
	rundennr int not null,
	start timestamp not null,
	dealer int references spieler(id) not null,
	kategorie int references kategorie(id) null,
	primary key (spiel, rundennr)
);

create table spiel_frage(
	spiel int references spiel(id) not null,
	fragennr int not null,
	frage int references frage(id) not null,
	primary key(spiel, fragennr),
	unique(spiel, frage)
);

create table antwort(
	spiel int references spiel(id) not null,
	spieler int references spieler(id) not null,
	fragennr int not null,
	antwort int null,
	startzeit timestamp not null,
	foreign key (spiel, fragennr) references spiel_frage(spiel, fragennr),
	foreign key (spiel, spieler) references teilnahme(spiel, spieler),
	primary key (spiel, spieler, fragennr)
);

create index ranking on spieler(punkte);
