CREATE TABLE compte (
    id_compte INTEGER NOT NULL AUTO_INCREMENT,
    nom text NOT NULL,
    prenom text NOT NULL,
    num_tel INTEGER,
    adr_mail text NOT NULL,
    mdp text NOT NULL,
    url_p text,
    -- privilege : 0=user, 1=vendeur, 2=admin, 3=organisateur
    privilege INTEGER DEFAULT 0,
    carte_rfid text,
    PRIMARY KEY (id_compte)
);

-- Le mdp de L'Admin Banque c'est 'admin', parce que bon, on va oublier sinon...
-- Plus important, on va laisser cet insert d'admin comme premier insert de compte, toujours
INSERT INTO compte (nom, prenom, adr_mail, mdp, privilege, url_p) VALUES ('Org', 'Litra', 'litra@gmail.com', '$2y$10$TYumsGQvtgC11kSj9oPPquYRj7T92k2JeQKb490ULoLsae0Xyep2q', 2, "1.png");


CREATE TABLE article(
    id_article INTEGER NOT NULL AUTO_INCREMENT,
    label text,
    prix FLOAT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_article)
);

CREATE TABLE vend(
    id_vendeur INTEGER NOT NULL,
    id_article INTEGER NOT NULL,
    FOREIGN KEY (id_vendeur) REFERENCES compte(id_compte),
    FOREIGN KEY (id_article) REFERENCES article(id_article),
    PRIMARY KEY (id_vendeur, id_article)
);

CREATE TABLE monnaie (
    id_monnaie INTEGER NOT NULL AUTO_INCREMENT,
    id_createur INTEGER NOT NULL,
    nom_monnaie text NOT NULL,
    valeur FLOAT NOT NULL DEFAULT 1,
    date_expiration DATE DEFAULT NULL,
    reconvertible BOOLEAN DEFAULT 0,
    FOREIGN KEY (id_createur) REFERENCES compte(id_compte),
    PRIMARY KEY (id_monnaie)
);

CREATE TABLE evenement (
    id_evenement INTEGER NOT NULL AUTO_INCREMENT,
    id_createur INTEGER NOT NULL,
    id_monnaie INTEGER,
    nom text NOT NULL,
    description text,
    date DATE,
    lieu text,
    img text,
    FOREIGN KEY (id_createur) REFERENCES compte(id_compte),
    FOREIGN KEY (id_monnaie) REFERENCES monnaie(id_monnaie),
    PRIMARY KEY (id_evenement)
);

CREATE TABLE rvendeurevenement (
    id_vendeur INTEGER NOT NULL,
    id_evenement INTEGER NOT NULL,
    FOREIGN KEY (id_vendeur) REFERENCES compte(id_compte),
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement),
    PRIMARY KEY (id_vendeur, id_evenement)
);

CREATE TABLE transactions (
    id_transac INTEGER NOT NULL AUTO_INCREMENT,
    id_emetteur INTEGER NOT NULL, 
    id_recepteur INTEGER NOT NULL,
    id_monnaie INTEGER NOT NULL,
    qte_monnaie DECIMAL(9, 2) NOT NULL,
    transac_hash text,
    transac_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_monnaie) REFERENCES monnaie(id_monnaie),
    FOREIGN KEY (id_emetteur) REFERENCES compte(id_compte),
    FOREIGN KEY (id_recepteur) REFERENCES compte(id_compte),
    PRIMARY KEY (id_transac)
);

CREATE TABLE logrfid (
    id_log INTEGER NOT NULL AUTO_INCREMENT,
    carte_rfid text NOT NULL,
    id_vendeur text NOT NULL,
    PRIMARY KEY (id_log)
);