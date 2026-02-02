-- Réinitialisation des tables (ordre dépendances)
DROP TABLE IF EXISTS signalement;
DROP TABLE IF EXISTS entreprise;
DROP TABLE IF EXISTS tentative_connexion;
DROP TABLE IF EXISTS session;
DROP TABLE IF EXISTS utilisateur;
DROP TABLE IF EXISTS role;

-- Module Authentication


CREATE TABLE role (
    id_role SERIAL PRIMARY KEY,
    nom VARCHAR(20) UNIQUE NOT NULL
);

CREATE TABLE utilisateur (
    id_utilisateur SERIAL PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    firebase_uid VARCHAR(128) UNIQUE NOT NULL,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    id_role INT NOT NULL REFERENCES role(id_role),
    bloque BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE session (
    id_session SERIAL PRIMARY KEY,
    id_utilisateur INT NOT NULL REFERENCES utilisateur(id_utilisateur),
    token VARCHAR(255) UNIQUE NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_expiration TIMESTAMP NOT NULL
);

CREATE TABLE tentative_connexion (
    id_tentative SERIAL PRIMARY KEY,
    id_utilisateur INT REFERENCES utilisateur(id_utilisateur),
    date_tentative TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    succes BOOLEAN NOT NULL
);

-- Module Web / Mobile

CREATE TABLE entreprise (
    id_entreprise SERIAL PRIMARY KEY,
    nom VARCHAR(150) NOT NULL
);

CREATE TABLE signalement (
    id_signalement SERIAL PRIMARY KEY,
    id_utilisateur INT REFERENCES utilisateur(id_utilisateur),
    latitude DOUBLE PRECISION NOT NULL,
    longitude DOUBLE PRECISION NOT NULL,
    date_signalement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut VARCHAR(20) NOT NULL CHECK (
        statut IN ('nouveau', 'en cours', 'termine')
    ),
    surface_m2 DOUBLE PRECISION,
    budget DOUBLE PRECISION,
    id_entreprise INT REFERENCES entreprise(id_entreprise)
);

INSERT INTO role (nom) VALUES ('Administrateur');
INSERT INTO role (nom) VALUES ('Utilisateur');
INSERT INTO role (nom) VALUES ('Moderateur');

CREATE TABLE modification_signalement (
    id_modification SERIAL PRIMARY KEY,
    id_signalement INT NOT NULL REFERENCES signalement(id_signalement) ON DELETE CASCADE,
    id_utilisateur INT NOT NULL REFERENCES utilisateur(id_utilisateur),
    statut VARCHAR(20) NOT NULL,
    budget DOUBLE PRECISION,
    surface_m2 DOUBLE PRECISION,
    id_entreprise INT REFERENCES entreprise(id_entreprise),
    note TEXT,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



