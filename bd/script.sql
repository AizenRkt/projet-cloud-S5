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

-- ==================== Module Authentication ====================

CREATE TABLE role (
    id_role SERIAL PRIMARY KEY,
    nom VARCHAR(20) UNIQUE NOT NULL
);

CREATE TABLE utilisateur (
    id_utilisateur SERIAL PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255),
    firebase_uid VARCHAR(128) UNIQUE,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    id_role INT NOT NULL REFERENCES role(id_role) DEFAULT 3,
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

-- ==================== Module Web / Mobile ====================

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

    date_signalement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut VARCHAR(50) DEFAULT 'nouveau' -- Nouveau champ statut direct pour simplifier les requêtes
);

CREATE INDEX idx_signalement_position ON signalement (latitude, longitude);

CREATE TABLE signalement_type_status (
    id_signalement_type_status SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(50) NOT NULL,
    pourcentage INT DEFAULT 0
);

CREATE TABLE signalement_status (
    id_signalement_status SERIAL PRIMARY KEY,
    id_signalement INT NOT NULL REFERENCES signalement(id_signalement),
    id_signalement_type_status INT REFERENCES signalement_type_status(id_signalement_type_status),
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE photo_signalement (
    id_photo SERIAL PRIMARY KEY,
    id_signalement INT NOT NULL REFERENCES signalement(id_signalement),
    path VARCHAR(255) NOT NULL,
    date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- ==================== Données de test ====================

-- Insertion des rôles par défaut
INSERT INTO role (nom) VALUES ('Manager'), ('Visiteur'), ('Utilisateur');

-- Création d'un manager par défaut (password: manager123)
INSERT INTO utilisateur (email, password, firebase_uid, nom, prenom, id_role)
VALUES ('manager@roadcheck.mg', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4J1L4GvKQH.LxW7e', 'manager-default-uid', 'Admin', 'Manager', 1);

-- Insertion d'entreprises de test
INSERT INTO entreprise (nom) VALUES
    ('COLAS Madagascar'),
    ('SOGEA SATOM'),
    ('RAVINALA Roads'),
    ('Travaux Publics SA');

-- Insertion des types de signalement
INSERT INTO type_signalement (nom, icon) VALUES
    ('Nid de poule', 'pothole'),
    ('Fissure', 'crack'),
    ('Affaissement', 'sinkhole'),
    ('Route inondée', 'flood'),
    ('Obstacle', 'obstacle');

-- Insertion des types de statut
INSERT INTO signalement_type_status (code, libelle, pourcentage) VALUES
    ('nouveau', 'Validé', 0),
    ('en_cours', 'En cours de traitement', 50),
    ('termine', 'Terminé', 100),
    ('en_attente', 'En attente', 0),
    ('annule', 'Annulé', 0);

-- Insertion de signalements de test (Status variés et dates différentes)

-- 1. Nid de poule - En cours (Colas)
INSERT INTO signalement (id_type_signalement, latitude, longitude, description, surface_m2, budget, id_entreprise, statut, date_signalement) VALUES
(1, -18.9137, 47.5361, 'Nid de poule important avenue de l''Indépendance', 15.5, 2500000, 1, 'en_cours', '2024-01-15 10:00:00');

-- 2. Fissure - Terminé (Sogea)
INSERT INTO signalement (id_type_signalement, latitude, longitude, description, surface_m2, budget, id_entreprise, statut, date_signalement) VALUES
(2, -18.9100, 47.5250, 'Fissure sur la route d''Ambohijatovo réparée', 8.2, 1200000, 2, 'termine', '2023-12-10 09:30:00');

-- 3. Nid de poule - Nouveau
INSERT INTO signalement (id_type_signalement, latitude, longitude, description, surface_m2, budget, id_entreprise, statut, date_signalement) VALUES
(1, -18.9200, 47.5400, 'Plusieurs nids de poule à Analakely', 25.0, 4500000, NULL, 'nouveau', '2024-02-01 14:15:00');

-- 4. Affaissement - En cours (Ravinala)
INSERT INTO signalement (id_type_signalement, latitude, longitude, description, surface_m2, budget, id_entreprise, statut, date_signalement) VALUES
(3, -18.9050, 47.5300, 'Affaissement près du lac Anosy', 12.0, 8000000, 3, 'en_cours', '2024-01-20 08:00:00');

-- 5. Route inondée - En attente
INSERT INTO signalement (id_type_signalement, latitude, longitude, description, surface_m2, budget, id_entreprise, statut, date_signalement) VALUES
(4, -18.9180, 47.5280, 'Route inondée à Isotry, nécessite drainage', 50.0, 15000000, NULL, 'en_attente', '2024-02-05 16:45:00');

-- 6. Obstacle - Annulé
INSERT INTO signalement (id_type_signalement, latitude, longitude, description, surface_m2, budget, id_entreprise, statut, date_signalement) VALUES
(5, -18.9300, 47.5100, 'Arbre tombé (déjà enlevé)', NULL, 0, NULL, 'annule', '2024-01-05 07:00:00');

-- 7. Nid de poule - Terminé
INSERT INTO signalement (id_type_signalement, latitude, longitude, description, surface_m2, budget, id_entreprise, statut, date_signalement) VALUES
(1, -18.9150, 47.5350, 'Réfection rue Pasteur', 30.0, 5000000, 1, 'termine', '2024-01-10 11:20:00');

