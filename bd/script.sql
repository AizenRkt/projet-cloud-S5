-- Réinitialisation des tables (ordre dépendances)
DROP TABLE IF EXISTS modification_signalement;
DROP TABLE IF EXISTS photo_signalement;
DROP TABLE IF EXISTS signalement_status;
DROP TABLE IF EXISTS signalement;
DROP TABLE IF EXISTS signalement_type_status;
DROP TABLE IF EXISTS type_signalement;
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

CREATE TABLE type_signalement (
    id_type_signalement SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    icon VARCHAR(100)
);

CREATE TABLE signalement (
    id_signalement SERIAL PRIMARY KEY,
    id_type_signalement INT NOT NULL REFERENCES type_signalement(id_type_signalement),
    id_entreprise INT NULL REFERENCES entreprise(id_entreprise),
    id_utilisateur INT NULL REFERENCES utilisateur(id_utilisateur),

    latitude DOUBLE PRECISION NOT NULL,
    longitude DOUBLE PRECISION NOT NULL,

    description TEXT,
    surface_m2 DOUBLE PRECISION,
    budget DOUBLE PRECISION,

    date_signalement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE INDEX idx_signalement_position
ON signalement (latitude, longitude);

CREATE TABLE signalement_type_status (
    id_signalement_type_status SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(20) NOT NULL
);

CREATE TABLE signalement_status (
    id_signalement_status SERIAL PRIMARY KEY,
    id_signalement INT NOT NULL REFERENCES signalement(id_signalement),
    id_signalement_type_status INT NOT NULL REFERENCES signalement_type_status(id_signalement_type_status),
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE photo_signalement (
    id_photo SERIAL PRIMARY KEY,
    id_signalement INT NOT NULL REFERENCES signalement(id_signalement),
    path VARCHAR(255) NOT NULL
);

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

INSERT INTO role (nom) VALUES ('Administrateur');
INSERT INTO role (nom) VALUES ('Utilisateur');
INSERT INTO role (nom) VALUES ('Moderateur');

-- Insert sample utilisateurs
INSERT INTO utilisateur (email, password, firebase_uid, nom, prenom, id_role) VALUES
('admin@gmail.com', 'password123', 'firebaseuid1', 'Admin', 'User', 1);

-- Insert sample entreprises
INSERT INTO entreprise (nom) VALUES
('Entreprise A'),
('Entreprise B'),
('Entreprise C');



