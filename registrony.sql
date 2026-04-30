SET sql_mode = '';
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS registrony;
CREATE DATABASE registrony CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE registrony;

-- ============================================================
-- UTENTI
-- Ruoli:
--   admin         → accesso completo
--   tecnico       → gestisce i propri laboratori (id_assistente_tecnico)
--   docente       → accede ai lab assegnati via docenti_laboratori;
--                   può essere responsabile di un lab
-- ============================================================
CREATE TABLE utenti (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(100) NOT NULL,
    cognome      VARCHAR(100) NOT NULL,
    email        VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    password     VARCHAR(255) NOT NULL,
    ruolo        ENUM('admin','docente','tecnico') NOT NULL DEFAULT 'docente',
    telefono     VARCHAR(20) DEFAULT NULL,
    attivo       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO utenti (id, nome, cognome, email, password_hash, password, ruolo, telefono) VALUES
(1, 'Daniele', 'Signorile',  'daniele.signorile@itsff.it',  '$2y$10$cKPIQ9CtwzE2Ofd4IssESuFPT1ZGNZ1v80eqqYUWhthJC5GP.e78O', '$2y$10$cKPIQ9CtwzE2Ofd4IssESuFPT1ZGNZ1v80eqqYUWhthJC5GP.e78O', 'admin',   '333-1111111'),
(2, 'Mario',   'Rossi',      'mario.rossi@scuola.it',       '$2y$10$k.r3NrnU6Y4r/8YT7SWe/ONxlQYLb895TKAoPXRtfnuGRHD7azdi6', '$2y$10$k.r3NrnU6Y4r/8YT7SWe/ONxlQYLb895TKAoPXRtfnuGRHD7azdi6', 'admin',   '333-2222222'),
(3, 'Luigi',   'Bianchi',    'luigi.bianchi@scuola.it',     '$2y$10$ZeoGHoiR5lbQ9qTWjFCvPO1L54RIGcO0YmfFxGMgTCBSx85lDT532', '$2y$10$ZeoGHoiR5lbQ9qTWjFCvPO1L54RIGcO0YmfFxGMgTCBSx85lDT532', 'admin',   '333-3333333'),
(4, 'Elena',   'Torricelli', 'elena.torricelli@itsff.it',   '$2y$10$WgakvRt24Bkt5spnaHA9JeOAFioQw9RC//f/oB3GvDdpUjcqaHpou', '$2y$10$WgakvRt24Bkt5spnaHA9JeOAFioQw9RC//f/oB3GvDdpUjcqaHpou', 'tecnico', '333-4444444'),
(5, 'Roberto', 'Boyle',      'roberto.boyle@itsff.it',      '$2y$10$tr3dZNpum/8/j3t/iYr4pOUnZQiHrLjAFrmnU4FlBg/BqZpU5y9Ha', '$2y$10$tr3dZNpum/8/j3t/iYr4pOUnZQiHrLjAFrmnU4FlBg/BqZpU5y9Ha', 'docente', NULL);

-- ============================================================
-- LABORATORI
-- Vincoli architetturali:
--   id_assistente_tecnico → 1 solo tecnico per lab
--   id_responsabile       → 1 solo responsabile per lab (può essere docente)
-- ============================================================
CREATE TABLE laboratori (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome                  VARCHAR(150) NOT NULL,
    aula                  VARCHAR(50)  NOT NULL,
    id_assistente_tecnico INT UNSIGNED NOT NULL,
    id_responsabile       INT UNSIGNED NOT NULL,
    descrizione           TEXT,
    attivo                BOOLEAN NOT NULL DEFAULT TRUE,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lab_assistente   FOREIGN KEY (id_assistente_tecnico) REFERENCES utenti(id),
    CONSTRAINT fk_lab_responsabile FOREIGN KEY (id_responsabile)       REFERENCES utenti(id)
) ENGINE=InnoDB;

INSERT INTO laboratori (nome, aula, id_assistente_tecnico, id_responsabile, descrizione) VALUES
('Lab Sistemi e Reti', 'SR-01',  4, 2, 'Laboratorio con 30 postazioni PC'),
('Lab Informatica',    'INF-02', 4, 3, 'Laboratorio multimediale con 25 postazioni'),
('Lab Biennio',        'B-03',   4, 1, 'Laboratorio per classi del biennio'),
('Lab TPSIT',          'T-04',   4, 5, 'Laboratorio di programmazione');

-- ============================================================
-- DOCENTI_LABORATORI
-- Assegna uno o più laboratori a ciascun docente.
-- Il tecnico NON usa questa tabella (usa id_assistente_tecnico).
-- ============================================================
CREATE TABLE docenti_laboratori (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_docente     INT UNSIGNED NOT NULL,
    id_laboratorio INT UNSIGNED NOT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_docente_lab  (id_docente, id_laboratorio),
    CONSTRAINT fk_dl_docente     FOREIGN KEY (id_docente)     REFERENCES utenti(id)      ON DELETE CASCADE,
    CONSTRAINT fk_dl_laboratorio FOREIGN KEY (id_laboratorio) REFERENCES laboratori(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Roberto Boyle (docente) assegnato ai lab 1, 2 e 4
-- (è anche responsabile del lab 4)
INSERT INTO docenti_laboratori (id_docente, id_laboratorio) VALUES
(5, 1),
(5, 2),
(5, 4);

CREATE TABLE classi (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(20)  NOT NULL,
    anno_scolastico VARCHAR(9)   NOT NULL,
    indirizzo       VARCHAR(100),
    attivo          BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_classe_anno (nome, anno_scolastico)
) ENGINE=InnoDB;

INSERT INTO classi (nome, anno_scolastico, indirizzo) VALUES
('1AIT', '2025/2026', 'Informatica e Telecomunicazioni'),
('1BIT', '2025/2026', 'Informatica e Telecomunicazioni'),
('2AIT', '2025/2026', 'Informatica e Telecomunicazioni'),
('3AIA', '2025/2026', 'Informatica'),
('3BIA', '2025/2026', 'Informatica'),
('4AIA', '2025/2026', 'Informatica'),
('5AIA', '2025/2026', 'Informatica');

CREATE TABLE materiali (
    id                   INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    nome                 VARCHAR(150)  NOT NULL,
    descrizione          TEXT,
    unita_misura         VARCHAR(30),
    id_laboratorio       INT UNSIGNED  NOT NULL,
    quantita_disponibile DECIMAL(10,2),
    soglia_minima        DECIMAL(10,2),
    attivo               BOOLEAN NOT NULL DEFAULT TRUE,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_materiale_lab FOREIGN KEY (id_laboratorio) REFERENCES laboratori(id)
) ENGINE=InnoDB;

INSERT INTO materiali (nome, descrizione, unita_misura, id_laboratorio, quantita_disponibile, soglia_minima) VALUES
('Cavo Ethernet Cat.6',   'Cavi di rete per postazioni',         'pezzi',  1, 50,  10),
('Mouse USB',             'Mouse ottico USB di ricambio',         'pezzi',  1, 15,   5),
('Tastiera USB',          'Tastiera standard USB di ricambio',    'pezzi',  1, 10,   3),
('Cavo HDMI 2m',          'Cavo HDMI maschio-maschio',            'pezzi',  2, 20,   5),
('Adattatore VGA-HDMI',   'Convertitore per proiettori',          'pezzi',  2,  8,   2),
('Penna USB 32GB',        'Chiavette USB per studenti',           'pezzi',  2, 12,   4),
('Cavo di alimentazione', 'Cavo alimentazione PC standard',       'pezzi',  3, 25,   5),
('Pasta termica',         'Pasta termica per processori',         'grammi', 3, 30,  10),
('Cavo UTP spezzato',     'Cavo UTP per esercitazioni crimpatura','metri',  4, 100, 20);

CREATE TABLE sessioni_laboratorio (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_laboratorio  INT UNSIGNED NOT NULL,
    id_classe       INT UNSIGNED NOT NULL,
    data            DATE NOT NULL,
    ora_ingresso    TIME NOT NULL,
    ora_uscita      TIME,
    attivita_svolta TEXT,
    note            TEXT,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessione_lab    FOREIGN KEY (id_laboratorio) REFERENCES laboratori(id),
    CONSTRAINT fk_sessione_classe FOREIGN KEY (id_classe)      REFERENCES classi(id)
) ENGINE=InnoDB;

INSERT INTO sessioni_laboratorio (id_laboratorio, id_classe, data, ora_ingresso, ora_uscita, attivita_svolta) VALUES
(1, 4, CURDATE(), '08:30:00', '10:30:00', 'Configurazione IP statico e analisi con Wireshark.'),
(2, 7, CURDATE(), '10:30:00', NULL,        'Sviluppo applicazione web PHP — in corso.');

CREATE TABLE firme_sessioni (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sessione   INT UNSIGNED NOT NULL,
    id_docente    INT UNSIGNED NOT NULL,
    tipo_presenza ENUM('titolare','compresenza') NOT NULL DEFAULT 'titolare',
    ora_firma     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_firma (id_sessione, id_docente),
    CONSTRAINT fk_firma_sessione FOREIGN KEY (id_sessione) REFERENCES sessioni_laboratorio(id) ON DELETE CASCADE,
    CONSTRAINT fk_firma_docente  FOREIGN KEY (id_docente)  REFERENCES utenti(id)
) ENGINE=InnoDB;

INSERT INTO firme_sessioni (id_sessione, id_docente, tipo_presenza) VALUES
(1, 5, 'titolare'),
(2, 5, 'titolare'),
(2, 2, 'compresenza');

CREATE TABLE utilizzo_materiali (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    id_sessione    INT UNSIGNED  NOT NULL,
    id_materiale   INT UNSIGNED  NOT NULL,
    quantita_usata DECIMAL(10,2) NOT NULL DEFAULT 0,
    esaurito       BOOLEAN NOT NULL DEFAULT FALSE,
    note           TEXT,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_utilizzo (id_sessione, id_materiale),
    CONSTRAINT fk_utilizzo_sessione  FOREIGN KEY (id_sessione)  REFERENCES sessioni_laboratorio(id) ON DELETE CASCADE,
    CONSTRAINT fk_utilizzo_materiale FOREIGN KEY (id_materiale) REFERENCES materiali(id)
) ENGINE=InnoDB;

INSERT INTO utilizzo_materiali (id_sessione, id_materiale, quantita_usata, note) VALUES
(1, 1, 4, 'Usati per cablaggio banchi'),
(1, 2, 1, 'Mouse sostitutivo postazione 7');

CREATE TABLE segnalazioni (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_laboratorio    INT UNSIGNED NOT NULL,
    id_sessione       INT UNSIGNED,
    id_utente         INT UNSIGNED NOT NULL,
    titolo            VARCHAR(255) NOT NULL,
    descrizione       TEXT NOT NULL,
    priorita          ENUM('bassa','media','alta','urgente') NOT NULL DEFAULT 'media',
    stato             ENUM('aperta','in_lavorazione','risolta','chiusa') NOT NULL DEFAULT 'aperta',
    data_segnalazione TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_risoluzione  DATETIME,
    note_risoluzione  TEXT,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_segn_lab      FOREIGN KEY (id_laboratorio) REFERENCES laboratori(id),
    CONSTRAINT fk_segn_sessione FOREIGN KEY (id_sessione)    REFERENCES sessioni_laboratorio(id) ON DELETE SET NULL,
    CONSTRAINT fk_segn_utente   FOREIGN KEY (id_utente)      REFERENCES utenti(id)
) ENGINE=InnoDB;

INSERT INTO segnalazioni (id_laboratorio, id_sessione, id_utente, titolo, descrizione, priorita, stato) VALUES
(1, 1, 5, 'PC postazione 7 non si accende',   'Il PC non risponde al tasto di accensione. Cavo testato, presa OK.', 'alta',  'aperta'),
(2, NULL, 2, 'Proiettore con immagine distorta', 'Righe verticali nella parte destra dello schermo.',                'media', 'in_lavorazione');

SET FOREIGN_KEY_CHECKS = 1;
