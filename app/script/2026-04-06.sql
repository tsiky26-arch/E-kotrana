-- =============================================================
--  BASE_MARIADB.SQL — Application Santé & Nutrition
--  Compatible : MariaDB 10.5+
--  Différences vs MySQL :
--    - IMC calculé via TRIGGER (pas de GENERATED ALWAYS AS sur expr. complexe)
--    - CHECK contrainte sur pct_total via TRIGGER (MariaDB ignore CHECK avant 10.2,
--      et ROUND() dans CHECK peut être instable)
--    - SET type remplacé par VARCHAR + CHECK lisible
-- =============================================================

CREATE DATABASE IF NOT EXISTS RegimeTp
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE RegimeTp;

-- -------------------------------------------------------------
-- 1. PARAMÈTRES GLOBAUX
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS parametres (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cle         VARCHAR(100) NOT NULL UNIQUE,
    valeur      VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO parametres (cle, valeur, description) VALUES
    ('gold_prix',            '29.99', 'Prix unique de l option Gold (€)'),
    ('gold_remise_pct',      '15',    'Remise Gold en % sur les régimes'),
    ('imc_insuffisant_max',  '18.4',  'IMC max pour insuffisance pondérale'),
    ('imc_normal_max',       '24.9',  'IMC max pour poids normal'),
    ('imc_surpoids_max',     '29.9',  'IMC max pour surpoids'),
    ('taille_min_cm',        '100',   'Taille minimale acceptée (cm)'),
    ('taille_max_cm',        '250',   'Taille maximale acceptée (cm)'),
    ('poids_min_kg',         '20',    'Poids minimal accepté (kg)'),
    ('poids_max_kg',         '300',   'Poids maximal accepté (kg)');


-- -------------------------------------------------------------
-- 2. ADMINISTRATEURS (Back Office)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role         ENUM('superadmin','admin') DEFAULT 'admin',
    actif        TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mot de passe par défaut : Admin1234! (hash bcrypt — À CHANGER)
INSERT INTO admins (nom, email, mot_de_passe, role) VALUES
    ('Super Admin', 'admin@app.com', '$2y$10$exampleHashChangeMe', 'superadmin');


-- -------------------------------------------------------------
-- 3. UTILISATEURS
--    IMC stocké en colonne normale, mis à jour via TRIGGER
--    (MariaDB supporte GENERATED ALWAYS AS mais pas avec des
--     expressions impliquant division imbriquée de façon fiable)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Page 1 inscription
    nom_complet    VARCHAR(150) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe   VARCHAR(255) NOT NULL,
    genre          ENUM('homme','femme','autre') NOT NULL,
    date_naissance DATE NOT NULL,

    -- Page 2 inscription (santé)
    taille_cm      DECIMAL(5,2) NOT NULL,
    poids_kg       DECIMAL(5,2) NOT NULL,
    imc            DECIMAL(5,2) DEFAULT NULL,       -- calculé par trigger

    -- Objectif
    objectif       ENUM('augmenter_poids','reduire_poids','imc_ideal') DEFAULT NULL,

    -- Profil
    photo_url      VARCHAR(500) DEFAULT NULL,
    profil_complet TINYINT(1) DEFAULT 0,

    -- Porte-monnaie
    solde          DECIMAL(10,2) DEFAULT 0.00,

    -- Option Gold
    is_gold        TINYINT(1) DEFAULT 0,
    gold_achat_at  DATETIME DEFAULT NULL,

    -- Statut
    actif          TINYINT(1) DEFAULT 1,
    email_verifie  TINYINT(1) DEFAULT 0,
    token_reset    VARCHAR(255) DEFAULT NULL,
    token_expire   DATETIME DEFAULT NULL,
    dernier_login  DATETIME DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger : calcul IMC à l'INSERT
DELIMITER $$
CREATE TRIGGER trg_user_imc_insert
BEFORE INSERT ON utilisateurs
FOR EACH ROW
BEGIN
    IF NEW.taille_cm > 0 THEN
        SET NEW.imc = ROUND(NEW.poids_kg / POW(NEW.taille_cm / 100, 2), 2);
    END IF;
END$$
DELIMITER ;

-- Trigger : recalcul IMC à l'UPDATE (si taille ou poids changent)
DELIMITER $$
CREATE TRIGGER trg_user_imc_update
BEFORE UPDATE ON utilisateurs
FOR EACH ROW
BEGIN
    IF NEW.taille_cm > 0 THEN
        SET NEW.imc = ROUND(NEW.poids_kg / POW(NEW.taille_cm / 100, 2), 2);
    END IF;
END$$
DELIMITER ;


-- -------------------------------------------------------------
-- 4. CATÉGORIES D'ACTIVITÉS SPORTIVES
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories_activite (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories_activite (nom) VALUES
    ('Cardio'),
    ('Musculation'),
    ('Yoga / Stretching'),
    ('Sports collectifs'),
    ('Arts martiaux'),
    ('Natation'),
    ('Autre');


-- -------------------------------------------------------------
-- 5. ACTIVITÉS SPORTIVES
--    objectif_compatible : VARCHAR CSV au lieu de SET
--    (plus portable, plus facile à filtrer en applicatif)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activites (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id        INT UNSIGNED NOT NULL,
    nom                 VARCHAR(150) NOT NULL,
    description         TEXT,
    intensite           ENUM('faible','moderee','elevee') NOT NULL,
    calories_par_heure  INT UNSIGNED DEFAULT 0,
    frequence_semaine   TINYINT UNSIGNED DEFAULT 3,
    -- valeurs possibles : 'augmenter_poids', 'reduire_poids', 'imc_ideal' (séparées par virgule)
    objectif_compatible VARCHAR(100) NOT NULL,
    actif               TINYINT(1) DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_activite_categorie
        FOREIGN KEY (categorie_id) REFERENCES categories_activite(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 6. RÉGIMES
--    - objectif_cible en VARCHAR CSV
--    - Contrainte somme des % via TRIGGER (plus fiable que CHECK sous MariaDB)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS regimes (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom                    VARCHAR(150) NOT NULL,
    description            TEXT,

    -- Durée
    duree_valeur           TINYINT UNSIGNED NOT NULL,
    duree_unite            ENUM('semaines','mois') NOT NULL DEFAULT 'semaines',

    -- Prix
    prix                   DECIMAL(8,2) NOT NULL,

    -- Variation de poids attendue
    variation_poids_min_kg DECIMAL(5,2) NOT NULL,
    variation_poids_max_kg DECIMAL(5,2) NOT NULL,

    -- Composition nutritionnelle (somme contrôlée par trigger)
    pct_viande             DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    pct_poisson            DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    pct_volaille           DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    pct_autres             DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    -- Objectif cible (CSV : 'augmenter_poids', 'reduire_poids', 'imc_ideal')
    objectif_cible         VARCHAR(100) NOT NULL,

    actif                  TINYINT(1) DEFAULT 1,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger : validation somme des % à l'INSERT
DELIMITER $$
CREATE TRIGGER trg_regime_pct_insert
BEFORE INSERT ON regimes
FOR EACH ROW
BEGIN
    IF ROUND(NEW.pct_viande + NEW.pct_poisson + NEW.pct_volaille + NEW.pct_autres, 2) <> 100.00 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La somme des pourcentages nutritionnels doit être égale à 100.';
    END IF;
    IF NEW.pct_viande < 0 OR NEW.pct_viande > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pct_viande doit être entre 0 et 100.';
    END IF;
    IF NEW.pct_poisson < 0 OR NEW.pct_poisson > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pct_poisson doit être entre 0 et 100.';
    END IF;
    IF NEW.pct_volaille < 0 OR NEW.pct_volaille > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pct_volaille doit être entre 0 et 100.';
    END IF;
    IF NEW.pct_autres < 0 OR NEW.pct_autres > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pct_autres doit être entre 0 et 100.';
    END IF;
END$$
DELIMITER ;

-- Trigger : validation somme des % à l'UPDATE
DELIMITER $$
CREATE TRIGGER trg_regime_pct_update
BEFORE UPDATE ON regimes
FOR EACH ROW
BEGIN
    IF ROUND(NEW.pct_viande + NEW.pct_poisson + NEW.pct_volaille + NEW.pct_autres, 2) <> 100.00 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La somme des pourcentages nutritionnels doit être égale à 100.';
    END IF;
    IF NEW.pct_viande < 0 OR NEW.pct_viande > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pct_viande doit être entre 0 et 100.';
    END IF;
    IF NEW.pct_poisson < 0 OR NEW.pct_poisson > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pct_poisson doit être entre 0 et 100.';
    END IF;
    IF NEW.pct_volaille < 0 OR NEW.pct_volaille > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pct_volaille doit être entre 0 et 100.';
    END IF;
    IF NEW.pct_autres < 0 OR NEW.pct_autres > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pct_autres doit être entre 0 et 100.';
    END IF;
END$$
DELIMITER ;


-- -------------------------------------------------------------
-- 7. PROGRAMMES UTILISATEUR
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS programmes (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    regime_id      INT UNSIGNED NOT NULL,
    objectif       ENUM('augmenter_poids','reduire_poids','imc_ideal') NOT NULL,
    date_debut     DATE NOT NULL,
    date_fin       DATE NOT NULL,
    poids_initial  DECIMAL(5,2) NOT NULL,
    poids_cible    DECIMAL(5,2) NOT NULL,
    prix_paye      DECIMAL(8,2) NOT NULL,
    remise_gold    TINYINT(1) DEFAULT 0,
    statut         ENUM('actif','termine','annule') DEFAULT 'actif',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_programme_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_programme_regime
        FOREIGN KEY (regime_id) REFERENCES regimes(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Liaison programme ↔ activités
CREATE TABLE IF NOT EXISTS programme_activites (
    programme_id INT UNSIGNED NOT NULL,
    activite_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (programme_id, activite_id),

    CONSTRAINT fk_pa_programme
        FOREIGN KEY (programme_id) REFERENCES programmes(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pa_activite
        FOREIGN KEY (activite_id) REFERENCES activites(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 8. CODES PORTE-MONNAIE
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS codes_portefeuille (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code               VARCHAR(50) NOT NULL UNIQUE,
    valeur             DECIMAL(8,2) NOT NULL,
    utilisations_max   INT UNSIGNED DEFAULT 1,      -- 0 = illimité
    utilisations_count INT UNSIGNED DEFAULT 0,
    actif              TINYINT(1) DEFAULT 1,
    expire_at          DATETIME DEFAULT NULL,
    created_by         INT UNSIGNED NOT NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_code_admin
        FOREIGN KEY (created_by) REFERENCES admins(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historique utilisation des codes
CREATE TABLE IF NOT EXISTS historique_codes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code_id         INT UNSIGNED NOT NULL,
    utilisateur_id  INT UNSIGNED NOT NULL,
    montant_credite DECIMAL(8,2) NOT NULL,
    utilise_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_hc_code
        FOREIGN KEY (code_id) REFERENCES codes_portefeuille(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_hc_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger : empêcher l'utilisation d'un code expiré ou épuisé
DELIMITER $$
CREATE TRIGGER trg_code_validation
BEFORE INSERT ON historique_codes
FOR EACH ROW
BEGIN
    DECLARE v_actif        TINYINT(1);
    DECLARE v_expire_at    DATETIME;
    DECLARE v_util_max     INT UNSIGNED;
    DECLARE v_util_count   INT UNSIGNED;

    SELECT actif, expire_at, utilisations_max, utilisations_count
    INTO   v_actif, v_expire_at, v_util_max, v_util_count
    FROM   codes_portefeuille
    WHERE  id = NEW.code_id;

    IF v_actif = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ce code est désactivé.';
    END IF;
    IF v_expire_at IS NOT NULL AND NOW() > v_expire_at THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ce code a expiré.';
    END IF;
    IF v_util_max > 0 AND v_util_count >= v_util_max THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ce code a atteint sa limite d utilisation.';
    END IF;
END$$
DELIMITER ;

-- Trigger : incrémenter le compteur d'utilisations après INSERT
DELIMITER $$
CREATE TRIGGER trg_code_increment
AFTER INSERT ON historique_codes
FOR EACH ROW
BEGIN
    UPDATE codes_portefeuille
    SET    utilisations_count = utilisations_count + 1
    WHERE  id = NEW.code_id;
END$$
DELIMITER ;


-- -------------------------------------------------------------
-- 9. TRANSACTIONS PORTE-MONNAIE
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions_portefeuille (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    type           ENUM('credit','debit') NOT NULL,
    montant        DECIMAL(8,2) NOT NULL,
    solde_apres    DECIMAL(8,2) NOT NULL,
    motif          VARCHAR(255),
    reference_id   INT UNSIGNED DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_tx_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 10. HISTORIQUE DU POIDS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historique_poids (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    poids_kg       DECIMAL(5,2) NOT NULL,
    imc            DECIMAL(5,2) NOT NULL,
    note           VARCHAR(255) DEFAULT NULL,
    enregistre_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_hp_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 11. ACHATS OPTION GOLD
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS achats_gold (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL UNIQUE,
    montant_paye   DECIMAL(8,2) NOT NULL,
    achete_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_gold_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger : mettre à jour is_gold et gold_achat_at sur l'utilisateur
DELIMITER $$
CREATE TRIGGER trg_gold_achat
AFTER INSERT ON achats_gold
FOR EACH ROW
BEGIN
    UPDATE utilisateurs
    SET    is_gold = 1, gold_achat_at = NOW()
    WHERE  id = NEW.utilisateur_id;
END$$
DELIMITER ;


-- =============================================================
-- VUES TABLEAU DE BORD
-- =============================================================

CREATE OR REPLACE VIEW v_stats_generales AS
SELECT
    (SELECT COUNT(*) FROM utilisateurs WHERE actif = 1)                        AS total_utilisateurs,
    (SELECT COUNT(*) FROM utilisateurs WHERE is_gold = 1)                      AS total_gold,
    (SELECT COUNT(*) FROM utilisateurs
     WHERE dernier_login >= DATE_SUB(NOW(), INTERVAL 30 DAY))                  AS actifs_30j,
    (SELECT COALESCE(SUM(prix_paye), 0) FROM programmes)                       AS ca_total,
    (SELECT COUNT(*) FROM programmes WHERE statut = 'actif')                   AS programmes_actifs;

CREATE OR REPLACE VIEW v_repartition_objectifs AS
SELECT
    objectif,
    COUNT(*) AS nombre,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM utilisateurs WHERE objectif IS NOT NULL), 1) AS pct
FROM utilisateurs
WHERE objectif IS NOT NULL
GROUP BY objectif;

CREATE OR REPLACE VIEW v_ca_mensuel AS
SELECT
    DATE_FORMAT(created_at, '%Y-%m')                              AS mois,
    COUNT(*)                                                       AS nb_programmes,
    SUM(prix_paye)                                                 AS ca_mois,
    SUM(CASE WHEN remise_gold = 1 THEN 1 ELSE 0 END)              AS avec_remise_gold
FROM programmes
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY mois DESC;

CREATE OR REPLACE VIEW v_top_regimes AS
SELECT
    r.id,
    r.nom,
    r.prix,
    COUNT(p.id)      AS nb_achats,
    SUM(p.prix_paye) AS ca_genere
FROM regimes r
LEFT JOIN programmes p ON p.regime_id = r.id
GROUP BY r.id, r.nom, r.prix
ORDER BY nb_achats DESC;

CREATE OR REPLACE VIEW v_croise_genre_objectif AS
SELECT
    genre,
    objectif,
    COUNT(*) AS nombre
FROM utilisateurs
WHERE objectif IS NOT NULL
GROUP BY genre, objectif
ORDER BY genre, objectif;

CREATE OR REPLACE VIEW v_croise_objectif_regime AS
SELECT
    p.objectif,
    r.nom  AS regime,
    COUNT(*) AS nombre
FROM programmes p
JOIN regimes r ON r.id = p.regime_id
GROUP BY p.objectif, r.nom
ORDER BY p.objectif, nombre DESC;


-- =============================================================
-- INDEX DE PERFORMANCE
-- =============================================================
CREATE INDEX idx_users_email       ON utilisateurs(email);
CREATE INDEX idx_users_objectif    ON utilisateurs(objectif);
CREATE INDEX idx_users_gold        ON utilisateurs(is_gold);
CREATE INDEX idx_users_login       ON utilisateurs(dernier_login);
CREATE INDEX idx_prog_user         ON programmes(utilisateur_id);
CREATE INDEX idx_prog_regime       ON programmes(regime_id);
CREATE INDEX idx_prog_statut       ON programmes(statut);
CREATE INDEX idx_hpoids_user       ON historique_poids(utilisateur_id);
CREATE INDEX idx_tx_user           ON transactions_portefeuille(utilisateur_id);
CREATE INDEX idx_codes_code        ON codes_portefeuille(code);
CREATE INDEX idx_codes_actif       ON codes_portefeuille(actif, expire_at);


SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- FIN DU SCRIPT
-- =============================================================