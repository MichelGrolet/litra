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
INSERT INTO compte (nom, prenom, adr_mail, mdp, privilege, url_p) VALUES ('Admin', 'Banque', 'adminBanque@fictivadress.com', '$2y$10$TYumsGQvtgC11kSj9oPPquYRj7T92k2JeQKb490ULoLsae0Xyep2q', 2, "1.png");

-- Tous les mots de passe, sauf celui de l'admin, sont "mdp"

-- Vendeurs
INSERT INTO compte (nom, prenom, adr_mail, mdp, num_tel, privilege, url_p) VALUES ('Lussier', 'Gaston', 'GastLuss@orange.fr', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W', 0179728109, 1, "2.jpg");


INSERT INTO compte (nom, prenom, adr_mail, mdp, privilege, url_p) VALUES ('Laberge', 'Anton', 'Laberge@gmail.com', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W', 1, "3.jpg");

INSERT INTO compte (nom, prenom, adr_mail, mdp, privilege) VALUES ('Poirier', 'Nicholas', 'nichoPoir@orange.fr', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W', 1);

-- Organisateurs
INSERT INTO compte (nom, prenom, adr_mail, mdp, num_tel, privilege, url_p) VALUES ('Chauvet', 'Lucille', 'Lucille.C@free.fr', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W', 0695032336, 3, "5.jpg");

INSERT INTO compte (nom, prenom, adr_mail, mdp, privilege, url_p) VALUES ('Austin', 'Neville', 'Austinevil@gmail.com', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W', 3, "6.jpg");

INSERT INTO compte (nom, prenom, adr_mail, mdp, privilege) VALUES ('Covillon', 'Philippe', 'CovilP@gmail.com', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W', 3);


-- Clients
INSERT INTO compte (nom, prenom, adr_mail, mdp) VALUES ('Beast', 'Jules', 'Jules.B@gmail.com', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W');

INSERT INTO compte (nom, prenom, adr_mail, mdp, url_p) VALUES ('Baron', 'Edouard', 'BarronEd@gmail.com', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W', "9.jpg");

INSERT INTO compte (nom, prenom, adr_mail, mdp, num_tel, url_p, carte_rfid) VALUES ('Wilson', 'Wade', 'WW.DP@gmail.com', '$2y$10$PeEzU8lwWyifV4MDjYn5HeSJzeb08IXyvhE72.x7Dxlt3zcMW2.6W', 0652117814, "10.jpg", '2600DDE2A8B1');

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

INSERT INTO monnaie (id_createur, nom_monnaie, valeur) VALUES (1, 'LitraToken', 1);
INSERT INTO monnaie (id_createur, nom_monnaie, valeur) VALUES (1, 'Florin', 4.5);
INSERT INTO monnaie (id_createur, nom_monnaie, valeur) VALUES (1, 'Orin', 1.5);
INSERT INTO monnaie (id_createur, nom_monnaie, valeur) VALUES (1, 'Couronne', 1.5);
INSERT INTO monnaie (id_createur, nom_monnaie, valeur) VALUES (5, 'Token du Val', 2.2);
INSERT INTO monnaie (id_createur, nom_monnaie, valeur) VALUES (6, 'Bayord', 1.8);

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

INSERT INTO evenement (id_createur, id_monnaie, nom, description, lieu, img) VALUES (1, 1, "Lancement de Litra", "C'est la fin du module", "QG", "1.jpg");
INSERT INTO evenement (id_createur, id_monnaie, nom, description, lieu, img) VALUES (5, 5, "Marché du haut Val", "Au printemps", "Val de Brei", "2.jpg");
INSERT INTO evenement (id_createur, id_monnaie, nom, description, lieu, img) VALUES (5, 5, "Fête du Val", "En hiver", "Val de Brei", "3.jpg");
INSERT INTO evenement (id_createur, id_monnaie, nom, description, lieu, img) VALUES (6, 6, "Cabaret gris", "Musique et BD", "Charleville", "4.jpg");

CREATE TABLE rvendeurevenement (
    id_vendeur INTEGER NOT NULL,
    id_evenement INTEGER NOT NULL,
    FOREIGN KEY (id_vendeur) REFERENCES compte(id_compte),
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement),
    PRIMARY KEY (id_vendeur, id_evenement)
);

INSERT INTO rvendeurevenement (id_vendeur, id_evenement) VALUES (2, 1);
INSERT INTO rvendeurevenement (id_vendeur, id_evenement) VALUES (2, 2);
INSERT INTO rvendeurevenement (id_vendeur, id_evenement) VALUES (2, 3);
INSERT INTO rvendeurevenement (id_vendeur, id_evenement) VALUES (3, 4);
INSERT INTO rvendeurevenement (id_vendeur, id_evenement) VALUES (4, 4);


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

INSERT INTO logrfid (carte_rfid, id_vendeur) VALUES ('2600DDE2A8B1', 4);
