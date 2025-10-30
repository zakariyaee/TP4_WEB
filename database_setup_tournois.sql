-- Script de création de la table tournoi et tables associées
-- À exécuter dans phpMyAdmin ou via ligne de commande MySQL

-- Création de la table tournoi
CREATE TABLE IF NOT EXISTS `tournoi` (
  `id_tournoi` int(11) NOT NULL AUTO_INCREMENT,
  `nom_tournoi` varchar(255) NOT NULL,
  `type_tournoi` enum('elimination','poule','mixte') NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_equipes` int(11) NOT NULL DEFAULT 2,
  `prix_inscription` decimal(10,2) DEFAULT 0.00,
  `id_terrain` int(11) DEFAULT NULL,
  `statut` enum('planifie','en_cours','termine','annule') NOT NULL DEFAULT 'planifie',
  `description` text,
  `regles` text,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tournoi`),
  KEY `fk_tournoi_terrain` (`id_terrain`),
  CONSTRAINT `fk_tournoi_terrain` FOREIGN KEY (`id_terrain`) REFERENCES `terrain` (`id_terrain`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création de la table equipe_tournoi (liaison équipes-tournois)
CREATE TABLE IF NOT EXISTS `equipe_tournoi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tournoi` int(11) NOT NULL,
  `id_equipe` int(11) NOT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_inscription` enum('en_attente','confirmee','refusee') NOT NULL DEFAULT 'confirmee',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_equipe_tournoi` (`id_tournoi`, `id_equipe`),
  KEY `fk_et_tournoi` (`id_tournoi`),
  KEY `fk_et_equipe` (`id_equipe`),
  CONSTRAINT `fk_et_tournoi` FOREIGN KEY (`id_tournoi`) REFERENCES `tournoi` (`id_tournoi`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_et_equipe` FOREIGN KEY (`id_equipe`) REFERENCES `equipe` (`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création de la table match_tournoi (pour les matchs de tournoi)
CREATE TABLE IF NOT EXISTS `match_tournoi` (
  `id_match` int(11) NOT NULL AUTO_INCREMENT,
  `id_tournoi` int(11) NOT NULL,
  `id_equipe1` int(11) NOT NULL,
  `id_equipe2` int(11) NOT NULL,
  `date_match` datetime NOT NULL,
  `terrain_match` int(11) DEFAULT NULL,
  `score_equipe1` int(11) DEFAULT NULL,
  `score_equipe2` int(11) DEFAULT NULL,
  `statut` enum('programme','en_cours','termine','annule') NOT NULL DEFAULT 'programme',
  `phase` varchar(50) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_match`),
  KEY `fk_match_tournoi` (`id_tournoi`),
  KEY `fk_match_equipe1` (`id_equipe1`),
  KEY `fk_match_equipe2` (`id_equipe2`),
  KEY `fk_match_terrain` (`terrain_match`),
  CONSTRAINT `fk_match_tournoi` FOREIGN KEY (`id_tournoi`) REFERENCES `tournoi` (`id_tournoi`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_match_equipe1` FOREIGN KEY (`id_equipe1`) REFERENCES `equipe` (`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_match_equipe2` FOREIGN KEY (`id_equipe2`) REFERENCES `equipe` (`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_match_terrain` FOREIGN KEY (`terrain_match`) REFERENCES `terrain` (`id_terrain`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion de données de test pour les tournois
INSERT INTO `tournoi` (`nom_tournoi`, `type_tournoi`, `date_debut`, `date_fin`, `nb_equipes`, `prix_inscription`, `id_terrain`, `statut`, `description`, `regles`) VALUES
('Championnat d\'Été 2024', 'elimination', '2024-07-01', '2024-07-15', 16, 200.00, 1, 'planifie', 'Tournoi d\'élimination directe pour l\'été 2024', 'Matchs de 90 minutes, prolongations si égalité, tirs au but si nécessaire'),
('Coupe des Équipes Locales', 'poule', '2024-08-01', '2024-08-20', 8, 150.00, 2, 'en_cours', 'Tournoi par poules suivi d\'élimination directe', 'Phase de poules : 2 points pour victoire, 1 pour nul. Meilleures équipes qualifiées'),
('Tournoi Amical 5v5', 'mixte', '2024-06-15', '2024-06-16', 12, 50.00, 3, 'termine', 'Tournoi amical en 5 contre 5', 'Matchs de 20 minutes, pas de prolongations'),
('Championnat de Printemps', 'elimination', '2024-04-01', '2024-04-30', 32, 300.00, 1, 'annule', 'Championnat annulé pour cause de météo', 'Règles standard FIFA'),
('Ligue des Champions Locale', 'poule', '2024-09-01', '2024-10-15', 20, 250.00, 2, 'planifie', 'Compétition prestigieuse avec phase de poules', 'Format Champions League : poules de 4, 2 qualifiés par poule');

-- Insertion de quelques équipes de test si elles n'existent pas
INSERT IGNORE INTO `equipe` (`nom_equipe`, `email_equipe`, `statut`) VALUES
('Les Lions', 'lions@example.com', 'active'),
('Les Aigles', 'aigles@example.com', 'active'),
('Les Tigres', 'tigres@example.com', 'active'),
('Les Sharks', 'sharks@example.com', 'active'),
('Les Warriors', 'warriors@example.com', 'active'),
('Les Phoenix', 'phoenix@example.com', 'active'),
('Les Thunder', 'thunder@example.com', 'active'),
('Les Storm', 'storm@example.com', 'active');

-- Inscription de quelques équipes aux tournois
INSERT INTO `equipe_tournoi` (`id_tournoi`, `id_equipe`, `statut_inscription`) VALUES
(1, 1, 'confirmee'),
(1, 2, 'confirmee'),
(1, 3, 'confirmee'),
(1, 4, 'confirmee'),
(2, 5, 'confirmee'),
(2, 6, 'confirmee'),
(2, 7, 'confirmee'),
(2, 8, 'confirmee'),
(3, 1, 'confirmee'),
(3, 2, 'confirmee'),
(3, 3, 'confirmee'),
(3, 4, 'confirmee');
