-- ============================================================
--  REGISTRONY DEL LABORATORIONY — Schema completo
--  ITT Enrico Fermi, Francavilla Fontana — A.S. 2025/2026
-- ============================================================

DROP DATABASE IF EXISTS registrony;
CREATE DATABASE registrony CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE registrony;

-- ============================================================
--  TABELLA: utenti
-- ============================================================
CREATE TABLE utenti (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100)    NOT NULL,
    cognome         VARCHAR(100)    NOT NULL,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL,
    ruolo           ENUM('admin','docente') NOT NULL DEFAULT 'docente',
    telefono        VARCHAR(20)     DEFAULT NULL,
    attivo          BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_utenti_ruolo          (ruolo),
    INDEX idx_utenti_cognome_nome   (cognome, nome),
    INDEX idx_utenti_attivo         (attivo)
) ENGINE=InnoDB;

INSERT INTO utenti (id, nome, cognome, email, password, ruolo, telefono) VALUES
(1, 'Daniele', 'Signorile',  'daniele.signorile@itsff.it', 'cambiami2026', 'admin',   '333-1111111'),
(2, 'Mario',   'Rossi',      'mario.rossi@scuola.it',      'admin123',     'admin',   '333-2222222'),
(3, 'Luigi',   'Bianchi',    'luigi.bianchi@scuola.it',    'admin456',     'admin',   '333-3333333'),
(4, 'Elena',   'Torricelli', 'elena.torricelli@itsff.it',  'tecnico2026',  'admin',   '333-4444444'),
(5, 'Roberto', 'Boyle',      'roberto.boyle@itsff.it',     'docente1',     'docente', NULL);

-- ============================================================
--  TABELLA: laboratori
-- ============================================================
CREATE TABLE laboratori (
    id                      INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nome                    VARCHAR(150)    NOT NULL,
    aula                    VARCHAR(50)     NOT NULL,
    id_assistente_tecnico   INT UNSIGNED    NOT NULL,
    id_responsabile         INT UNSIGNED    NOT NULL,
    descrizione             TEXT            DEFAULT NULL,
    attivo                  BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lab_assistente  FOREIGN KEY (id_assistente_tecnico) REFERENCES utenti(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lab_responsabile FOREIGN KEY (id_responsabile)      REFERENCES utenti(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_laboratori_aula   (aula),
    INDEX idx_laboratori_attivo (attivo)
) ENGINE=InnoDB;

INSERT INTO laboratori (nome, aula, id_assistente_tecnico, id_responsabile, descrizione) VALUES
('Lab Sistemi e Reti', 'SR-01',  1, 2, 'Laboratorio con 30 postazioni PC, switch e router'),
('Lab Informatica',    'INF-02', 1, 3, 'Laboratorio multimediale con 25 postazioni'),
('Lab Biennio',        'B-03',   4, 1, 'Laboratorio per classi del biennio'),
('Lab TPSIT',          'T-04',   1, 4, 'Laboratorio di programmazione e sviluppo web');

-- ============================================================
--  TABELLA: classi
-- ============================================================
CREATE TABLE classi (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(20)     NOT NULL,
    anno_scolastico VARCHAR(9)      NOT NULL,
    indirizzo       VARCHAR(100)    DEFAULT NULL,
    attivo          BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_classe_anno (nome, anno_scolastico),
    INDEX idx_classi_anno   (anno_scolastico),
    INDEX idx_classi_attivo (attivo)
) ENGINE=InnoDB;

INSERT INTO classi (nome, anno_scolastico, indirizzo) VALUES
('1AIT', '2025/2026', 'Informatica e Telecomunicazioni'),
('1BIT', '2025/2026', 'Informatica e Telecomunicazioni'),
('2AIT', '2025/2026', 'Informatica e Telecomunicazioni'),
('3AIA', '2025/2026', 'Informatica'),
('3BIA', '2025/2026', 'Informatica'),
('4AIA', '2025/2026', 'Informatica'),
('5AIA', '2025/2026', 'Informatica');

-- ============================================================
--  TABELLA: materiali
-- ============================================================
CREATE TABLE materiali (
    id                      INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nome                    VARCHAR(150)    NOT NULL,
    descrizione             TEXT            DEFAULT NULL,
    unita_misura            VARCHAR(30)     DEFAULT NULL,
    id_laboratorio          INT UNSIGNED    NOT NULL,
    quantita_disponibile    DECIMAL(10,2)   DEFAULT NULL,
    soglia_minima           DECIMAL(10,2)   DEFAULT NULL,
    attivo                  BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_materiale_laboratorio FOREIGN KEY (id_laboratorio) REFERENCES laboratori(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_materiali_laboratorio (id_laboratorio),
    INDEX idx_materiali_attivo      (attivo)
) ENGINE=InnoDB;

INSERT INTO materiali (nome, descrizione, unita_misura, id_laboratorio, quantita_disponibile, soglia_minima) VALUES
('Cavo Ethernet Cat.6',  'Cavi di rete per connessione postazioni', 'pezzi', 1, 50,   10),
('Mouse USB',            'Mouse ottico USB di ricambio',            'pezzi', 1, 15,    5),
('Tastiera USB',         'Tastiera standard USB di ricambio',       'pezzi', 1, 10,    3),
('Cavo HDMI 2m',         'Cavo HDMI maschio-maschio 2 metri',       'pezzi', 2, 20,    5),
('Adattatore VGA-HDMI',  'Convertitore VGA/HDMI per proiettori',    'pezzi', 2,  8,    2),
('Penna USB 32GB',       'Chiavette USB per studenti',              'pezzi', 2, 12,    4),
('Cavo di alimentazione','Cavo di alimentazione PC standard',       'pezzi', 3, 25,    5),
('Pasta termica',        'Pasta termica per processori',            'grammi',3, 30,   10),
('Cavo UTP spezzato',    'Cavo UTP per esercitazioni di crimpatura','metri', 4, 100,  20);

-- ============================================================
--  TABELLA: sessioni_laboratorio
-- ============================================================
CREATE TABLE sessioni_laboratorio (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_laboratorio  INT UNSIGNED    NOT NULL,
    id_classe       INT UNSIGNED    NOT NULL,
    data            DATE            NOT NULL,
    ora_ingresso    TIME            NOT NULL,
    ora_uscita      TIME            DEFAULT NULL,
    attivita_svolta TEXT            DEFAULT NULL,
    note            TEXT            DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessione_laboratorio FOREIGN KEY (id_laboratorio) REFERENCES laboratori(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sessione_classe      FOREIGN KEY (id_classe)       REFERENCES classi(id)     ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_sessioni_data       (data),
    INDEX idx_sessioni_laboratorio(id_laboratorio),
    INDEX idx_sessioni_classe     (id_classe),
    INDEX idx_sessioni_lab_data   (id_laboratorio, data)
) ENGINE=InnoDB;

-- Sessioni di esempio (oggi)
INSERT INTO sessioni_laboratorio (id_laboratorio, id_classe, data, ora_ingresso, ora_uscita, attivita_svolta) VALUES
(1, 4, CURDATE(), '08:30:00', '10:30:00', 'Configurazione indirizzo IP statico e analisi con Wireshark.'),
(2, 7, CURDATE(), '10:30:00', NULL,        'Sviluppo applicazione web PHP — in corso.');

-- ============================================================
--  TABELLA: firme_sessioni
-- ============================================================
CREATE TABLE firme_sessioni (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_sessione     INT UNSIGNED    NOT NULL,
    id_docente      INT UNSIGNED    NOT NULL,
    tipo_presenza   ENUM('titolare','compresenza') NOT NULL DEFAULT 'titolare',
    ora_firma       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_firma_sessione FOREIGN KEY (id_sessione) REFERENCES sessioni_laboratorio(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_firma_docente  FOREIGN KEY (id_docente)  REFERENCES utenti(id)               ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uk_firma_docente_sessione (id_sessione, id_docente),
    INDEX idx_firme_docente  (id_docente),
    INDEX idx_firme_sessione (id_sessione)
) ENGINE=InnoDB;

-- Trigger: massimo 2 firme per sessione
DELIMITER $$
CREATE TRIGGER trg_firme_max_due_insert
BEFORE INSERT ON firme_sessioni
FOR EACH ROW
BEGIN
    DECLARE conteggio INT;
    SELECT COUNT(*) INTO conteggio FROM firme_sessioni WHERE id_sessione = NEW.id_sessione;
    IF conteggio >= 2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Errore: massimo 2 firme per sessione (compresenza).';
    END IF;
END$$
DELIMITER ;

INSERT INTO firme_sessioni (id_sessione, id_docente, tipo_presenza) VALUES
(1, 5, 'titolare'),
(2, 5, 'titolare'),
(2, 2, 'compresenza');

-- ============================================================
--  TABELLA: utilizzo_materiali
-- ============================================================
CREATE TABLE utilizzo_materiali (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_sessione     INT UNSIGNED    NOT NULL,
    id_materiale    INT UNSIGNED    NOT NULL,
    quantita_usata  DECIMAL(10,2)   NOT NULL DEFAULT 0,
    esaurito        BOOLEAN         NOT NULL DEFAULT FALSE,
    note            TEXT            DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_utilizzo_sessione  FOREIGN KEY (id_sessione)  REFERENCES sessioni_laboratorio(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_utilizzo_materiale FOREIGN KEY (id_materiale) REFERENCES materiali(id)            ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uk_utilizzo_sessione_materiale (id_sessione, id_materiale),
    INDEX idx_utilizzo_sessione  (id_sessione),
    INDEX idx_utilizzo_materiale (id_materiale)
) ENGINE=InnoDB;

-- Trigger: aggiorna la giacenza dopo ogni utilizzo
DELIMITER $$
CREATE TRIGGER trg_aggiorna_quantita_materiale
AFTER INSERT ON utilizzo_materiali
FOR EACH ROW
BEGIN
    UPDATE materiali
    SET quantita_disponibile = GREATEST(0, IFNULL(quantita_disponibile, 0) - NEW.quantita_usata)
    WHERE id = NEW.id_materiale AND quantita_disponibile IS NOT NULL;

    IF NEW.esaurito = TRUE THEN
        UPDATE materiali SET quantita_disponibile = 0 WHERE id = NEW.id_materiale;
    END IF;
END$$
DELIMITER ;

INSERT INTO utilizzo_materiali (id_sessione, id_materiale, quantita_usata, note) VALUES
(1, 1, 4, 'Usati per cablaggio banchi'),
(1, 2, 1, 'Mouse sostitutivo postazione 7');

-- ============================================================
--  TABELLA: segnalazioni
-- ============================================================
CREATE TABLE segnalazioni (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_laboratorio      INT UNSIGNED    NOT NULL,
    id_sessione         INT UNSIGNED    DEFAULT NULL,
    id_utente           INT UNSIGNED    NOT NULL,
    titolo              VARCHAR(255)    NOT NULL,
    descrizione         TEXT            NOT NULL,
    priorita            ENUM('bassa','media','alta','urgente') NOT NULL DEFAULT 'media',
    stato               ENUM('aperta','in_lavorazione','risolta','chiusa') NOT NULL DEFAULT 'aperta',
    data_segnalazione   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_risoluzione    TIMESTAMP       DEFAULT NULL,
    note_risoluzione    TEXT            DEFAULT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_segnalazione_laboratorio FOREIGN KEY (id_laboratorio) REFERENCES laboratori(id)            ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_segnalazione_sessione    FOREIGN KEY (id_sessione)    REFERENCES sessioni_laboratorio(id)   ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_segnalazione_utente      FOREIGN KEY (id_utente)      REFERENCES utenti(id)                ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_segnalazioni_laboratorio (id_laboratorio),
    INDEX idx_segnalazioni_stato       (stato),
    INDEX idx_segnalazioni_priorita    (priorita),
    INDEX idx_segnalazioni_utente      (id_utente),
    INDEX idx_segnalazioni_data        (data_segnalazione)
) ENGINE=InnoDB;

INSERT INTO segnalazioni (id_laboratorio, id_sessione, id_utente, titolo, descrizione, priorita, stato) VALUES
(1, 1, 5, 'PC postazione 7 non si accende',
 'Il PC della postazione 7 non risponde alla pressione del tasto di accensione. Cavo testato, presa OK.',
 'alta', 'aperta'),
(2, NULL, 2, 'Proiettore con immagine distorta',
 'Il proiettore del laboratorio mostra righe verticali nella parte destra dello schermo.',
 'media', 'in_lavorazione');