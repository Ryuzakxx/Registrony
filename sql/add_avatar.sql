-- ============================================================
-- Migration: aggiunge la colonna avatar alla tabella utenti
-- Esegui questo script SE hai già il database installato.
-- È idempotente: non fallisce se la colonna esiste già.
-- ============================================================

ALTER TABLE `utenti`
  ADD COLUMN IF NOT EXISTS `avatar` varchar(500) DEFAULT NULL
  COMMENT 'Path relativo alla foto profilo (es. uploads/avatars/1_1234567890.jpg)'
  AFTER `telefono`;
