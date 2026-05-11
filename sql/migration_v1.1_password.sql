-- ============================================================
-- Registrony — Migrazione v1.1
-- Aggiunge il flag must_change_password alla tabella utenti
-- e converte le password in chiaro esistenti a bcrypt.
-- ============================================================

-- 1. Aggiungi la colonna (se non esiste già)
ALTER TABLE `utenti`
    ADD COLUMN IF NOT EXISTS `must_change_password`
        TINYINT(1) NOT NULL DEFAULT 1
        COMMENT 'Se 1, l utente deve cambiare password al prossimo accesso'
        AFTER `attivo`;

-- 2. Gli utenti già esistenti al momento della migrazione:
--    impostare must_change_password = 1 così che
--    la prossima volta che accedono debbano impostare una password personale.
--    Se vuoi escludere l'admin (id=1), commenta la WHERE o aggiustala.
UPDATE `utenti` SET `must_change_password` = 1 WHERE `must_change_password` IS NULL OR `must_change_password` = 1;

-- 3. Nota: la conversione delle password in chiaro → bcrypt
--    avviene automaticamente al primo login tramite il codice PHP
--    in config/auth.php (funzione login).
--    Non è necessaria una migrazione SQL per gli hash.

-- Fine migrazione
