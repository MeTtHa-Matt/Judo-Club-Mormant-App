CREATE TABLE account (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    pdp VARCHAR(255) NOT NULL DEFAULT "pdp_base.png",
    admin TINYINT(1) NOT NULL DEFAULT 0,
    last_activity DATETIME DEFAULT NULL,
    ban TINYINT(1) NOT NULL DEFAULT 0,
    maintenance TINYINT(1) NOT NULL DEFAULT 0,
    accept_email TINYINT(1) NOT NULL DEFAULT 1,
    reglement_accepte TINYINT(1) NOT NULL DEFAULT 0,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_token_expires DATETIME DEFAULT NULL,
    reset_token VARCHAR(64) NULL,
    reset_token_expires DATETIME NULL
);

INSERT INTO account (firstname, lastname, email, `password`, admin, email_verified) VALUES ("admin", "admin", "admin@admin.fr", "$2y$10$jgqlubHdvwg7cTs1V6C/a.RX92qQhmYV7wLzDMEA7K00g9zluuJmq", 1, 1), ("Damien", "admin", "damien@admin.fr", "$2y$10$399GY/UYPWBCfMcIJj0Z8OZpF3fDq10AMKp09pkilJEE9.SUMeV2O", 1, 1), ("Freddy", "admin", "freddy@admin.fr", "$2y$10$/.L1SUmYg6oFBUsKRLuT0O03lk2O/COAsMykbLfM2eFWbszQCXR/6", 1, 1);

CREATE TABLE IF NOT EXISTS persistent_tokens_jcm (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    user_agent VARCHAR(255),
    ip_address VARCHAR(45),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_used DATETIME DEFAULT NULL,
    FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE,
    INDEX (token),
    INDEX (account_id)
);

CREATE TABLE IF NOT EXISTS signalements_jcm (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE,
    INDEX (account_id),
    INDEX (created_at)
);

CREATE TABLE cible (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cible VARCHAR(100)
);

INSERT INTO cible (cible) VALUES ("Baby"), ("Pré-poussin"), ("Poussin"), ("Benjamin"), ("Minimes"), ("Cadets"), ("Junior"), ("Senior"), ("Veteran");

CREATE TABLE competitions (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    lieu VARCHAR(100),
    id_cible INT NULL,
    informations TEXT,
    date DATE NOT NULL,
    date_limite_inscription DATE NOT NULL,
    image TEXT,
    FOREIGN KEY (id_cible) REFERENCES cible(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS competition_cibles (
    competition_id INT NOT NULL,
    cible_id INT NOT NULL,
    PRIMARY KEY (competition_id, cible_id),
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (cible_id) REFERENCES cible(id) ON DELETE CASCADE
);

CREATE TABLE ceintures (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ceinture VARCHAR(100) NOT NULL
);

INSERT INTO ceintures (ceinture) VALUES ("Blanche"), ("Blanche 1 liseret"), ("Blanche 2 liserets"), ("Blanche Jaune"), ("Jaune"), ("Jaune Orange"), ("Orange"), ("Orange Verte"), ("Verte"), ("Verte Bleue"), ("Bleue"), ("Bleue Marron"), ("Violette"), ("Marron"), ("Noire");

CREATE TABLE inscrits (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    annee_naissance YEAR NOT NULL,
    id_ceinture INT NOT NULL,
    Poids INT(2) NOT NULL,
    id_account INT NOT NULL,
    id_competition INT NOT NULL,
    FOREIGN KEY (id_ceinture) REFERENCES ceintures(id) ON DELETE CASCADE,
    FOREIGN KEY (id_account) REFERENCES account(id) ON DELETE CASCADE,
    FOREIGN KEY (id_competition) REFERENCES competitions(id) ON DELETE CASCADE
);

CREATE TABLE child_profiles (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    annee_naissance YEAR NOT NULL,
    id_ceinture INT NOT NULL,
    Poids INT NULL,
    FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE,
    FOREIGN KEY (id_ceinture) REFERENCES ceintures(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS index_links_jcm (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    link_key VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    title VARCHAR(150) NOT NULL,
    url VARCHAR(2048) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);