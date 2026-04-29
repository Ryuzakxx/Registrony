-- ============================================================
-- TABELLA PONTE: materiali utilizzati in una sessione
-- Esegui questo SQL in phpMyAdmin sulla tua tabella registrony
-- ============================================================

CREATE TABLE IF NOT EXISTS `sessioni_materiali` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_sessione`  INT UNSIGNED NOT NULL,
  `id_materiale` INT UNSIGNED NOT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sess_mat` (`id_sessione`, `id_materiale`),
  KEY `idx_sessione`  (`id_sessione`),
  KEY `idx_materiale` (`id_materiale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
