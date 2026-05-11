-- Migration v1.2 — Foto profilo utente
-- Esegui questo script una sola volta su phpMyAdmin o da terminale MySQL

ALTER TABLE `utenti`
    ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Percorso relativo alla foto profilo, es. uploads/avatars/42.jpg'
    AFTER `telefono`;

-- Crea la cartella uploads/avatars/ (fai anche mkdir manuale su XAMPP)
-- La colonna è opzionale: se NULL viene mostrato l'avatar con le iniziali
