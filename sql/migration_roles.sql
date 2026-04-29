-- ============================================================
-- Migration: sistema permessi a 3 ruoli
-- Eseguire su un database registrony GIA' ESISTENTE.
-- Se stai installando da zero usa registrony.sql.
-- ============================================================

USE registrony;

-- 1. Aggiunge il valore 'tecnico' all'ENUM ruolo
ALTER TABLE utenti
    MODIFY ruolo ENUM('admin','docente','tecnico') NOT NULL DEFAULT 'docente';

-- 2. Tabella di assegnazione docenti → laboratori (M:N)
CREATE TABLE IF NOT EXISTS docenti_laboratori (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_docente     INT UNSIGNED NOT NULL,
    id_laboratorio INT UNSIGNED NOT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_docente_lab  (id_docente, id_laboratorio),
    CONSTRAINT fk_dl_docente     FOREIGN KEY (id_docente)     REFERENCES utenti(id)       ON DELETE CASCADE,
    CONSTRAINT fk_dl_laboratorio FOREIGN KEY (id_laboratorio) REFERENCES laboratori(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Aggiorna i dati di esempio:
--    Elena Torricelli (id=4) è assistente tecnico → ruolo tecnico
UPDATE utenti SET ruolo = 'tecnico' WHERE id = 4;

-- 4. Assegna Roberto Boyle (id=5, docente) ai lab 1 e 2
INSERT IGNORE INTO docenti_laboratori (id_docente, id_laboratorio) VALUES
(5, 1),
(5, 2);
