-- Module Authentication


CREATE TABLE role (
    id_role SERIAL PRIMARY KEY,
    nom VARCHAR(20) UNIQUE NOT NULL
);

CREATE TABLE utilisateur (
    id_utilisateur SERIAL PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
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
    nom VARCHAR(150) NOT NULL,
    logo VARCHAR(200)
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

    date_signalement DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_signalement_position
ON signalement (latitude, longitude);

CREATE TABLE signalement_type_status (
    id_signalement_type_status SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(20) NOT NULL
)

CREATE TABLE signalement_status (
    id_signalement_status SERIAL PRIMARY KEY,
    id_signalement INT NOT NULL REFERENCES signalement(id_signalement),
    id_signalement_type_status INT NOT NULL REFERENCES signalement_type_status(id_signalement_type_status),
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE photo_signalement (
    id_photo SERIAL PRIMARY KEY,
    id_signalement INT NOT NULL REFERENCES signalement(id_signalement),
    path VARCHAR(255) NOT NULL
);

INSERT INTO role (nom) VALUES ('Administrateur');
INSERT INTO role (nom) VALUES ('Utilisateur');
INSERT INTO role (nom) VALUES ('Moderateur');


