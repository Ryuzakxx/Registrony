-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 07, 2026 alle 14:46
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `registrony`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `classi`
--

CREATE TABLE `classi` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(20) NOT NULL,
  `anno_scolastico` varchar(9) NOT NULL,
  `indirizzo` varchar(100) DEFAULT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `classi`
--

INSERT INTO `classi` (`id`, `nome`, `anno_scolastico`, `indirizzo`, `attivo`, `created_at`, `updated_at`) VALUES
(1, '1AIT', '2025/2026', 'Informatica e Telecomunicazioni', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(2, '1BIT', '2025/2026', 'Informatica e Telecomunicazioni', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(3, '2AIT', '2025/2026', 'Informatica e Telecomunicazioni', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(4, '3AIA', '2025/2026', 'Informatica', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(5, '3BIA', '2025/2026', 'Informatica', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(6, '4AIA', '2025/2026', 'Informatica', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(7, '5AIA', '2025/2026', 'Informatica', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13');

-- --------------------------------------------------------

--
-- Struttura della tabella `docenti_laboratori`
--

CREATE TABLE `docenti_laboratori` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_docente` int(10) UNSIGNED NOT NULL,
  `id_laboratorio` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `docenti_laboratori`
--

INSERT INTO `docenti_laboratori` (`id`, `id_docente`, `id_laboratorio`, `created_at`) VALUES
(1, 5, 1, '2026-04-29 18:40:43'),
(2, 5, 2, '2026-04-29 18:40:43'),
(3, 8, 3, '2026-04-29 19:11:33'),
(4, 8, 2, '2026-04-29 19:11:33'),
(5, 8, 1, '2026-04-29 19:11:33'),
(6, 2, 2, '2026-04-30 09:08:59'),
(7, 2, 4, '2026-04-30 09:08:59');

-- --------------------------------------------------------

--
-- Struttura della tabella `firme_sessioni`
--

CREATE TABLE `firme_sessioni` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_sessione` int(10) UNSIGNED NOT NULL,
  `id_docente` int(10) UNSIGNED NOT NULL,
  `tipo_presenza` enum('titolare','compresenza') NOT NULL DEFAULT 'titolare',
  `ora_firma` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `firme_sessioni`
--

INSERT INTO `firme_sessioni` (`id`, `id_sessione`, `id_docente`, `tipo_presenza`, `ora_firma`, `created_at`) VALUES
(1, 1, 5, 'titolare', '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(2, 2, 5, 'titolare', '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(3, 2, 2, 'compresenza', '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(4, 3, 1, 'titolare', '2026-04-29 09:09:34', '2026-04-29 09:09:34'),
(5, 3, 2, 'compresenza', '2026-04-29 09:09:34', '2026-04-29 09:09:34'),
(6, 4, 1, 'titolare', '2026-04-29 10:13:19', '2026-04-29 10:13:19'),
(7, 4, 5, 'compresenza', '2026-04-29 10:13:19', '2026-04-29 10:13:19'),
(8, 5, 1, 'titolare', '2026-04-29 10:20:51', '2026-04-29 10:20:51'),
(9, 6, 8, 'titolare', '2026-04-29 11:52:26', '2026-04-29 11:52:26'),
(10, 6, 1, 'compresenza', '2026-04-29 11:52:26', '2026-04-29 11:52:26');

-- --------------------------------------------------------

--
-- Struttura della tabella `laboratori`
--

CREATE TABLE `laboratori` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `aula` varchar(50) NOT NULL,
  `id_assistente_tecnico` int(10) UNSIGNED NOT NULL,
  `id_responsabile` int(10) UNSIGNED NOT NULL,
  `descrizione` text DEFAULT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `laboratori`
--

INSERT INTO `laboratori` (`id`, `nome`, `aula`, `id_assistente_tecnico`, `id_responsabile`, `descrizione`, `attivo`, `created_at`, `updated_at`) VALUES
(1, 'Lab Sistemi e Reti', 'SR-01', 1, 2, 'Laboratorio con 30 postazioni PC', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(2, 'Lab Informatica', 'INF-02', 1, 8, 'Laboratorio multimediale con 25 postazioni', 1, '2026-04-28 10:17:13', '2026-04-29 11:35:44'),
(3, 'Lab Biennio', 'B-03', 4, 1, 'Laboratorio per classi del biennio', 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(4, 'Lab TPSIT', 'T-04', 9, 2, 'Laboratorio di programmazione', 1, '2026-04-28 10:17:13', '2026-04-30 09:05:21');

-- --------------------------------------------------------

--
-- Struttura della tabella `materiali`
--

CREATE TABLE `materiali` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `unita_misura` varchar(30) DEFAULT NULL,
  `id_laboratorio` int(10) UNSIGNED NOT NULL,
  `quantita_disponibile` decimal(10,2) DEFAULT NULL,
  `soglia_minima` decimal(10,2) DEFAULT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `materiali`
--

INSERT INTO `materiali` (`id`, `nome`, `descrizione`, `unita_misura`, `id_laboratorio`, `quantita_disponibile`, `soglia_minima`, `attivo`, `created_at`, `updated_at`) VALUES
(1, 'Cavo Ethernet Cat.6', 'Cavi di rete per postazioni', 'pezzi', 1, 50.00, 10.00, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(2, 'Mouse USB', 'Mouse ottico USB di ricambio', 'pezzi', 1, 15.00, 5.00, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(3, 'Tastiera USB', 'Tastiera standard USB di ricambio', 'pezzi', 1, 10.00, 3.00, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(4, 'Cavo HDMI 2m', 'Cavo HDMI maschio-maschio', 'pezzi', 2, 20.00, 5.00, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(5, 'Adattatore VGA-HDMI', 'Convertitore per proiettori', 'pezzi', 2, 8.00, 2.00, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(6, 'Penna USB 32GB', 'Chiavette USB per studenti', 'pezzi', 2, 12.00, 4.00, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(7, 'Cavo di alimentazione', 'Cavo alimentazione PC standard', 'pezzi', 3, 25.00, 5.00, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(8, 'Pasta termica', 'Pasta termica per processori', 'grammi', 3, 30.00, 10.00, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(9, 'Cavo UTP spezzato', 'Cavo UTP per esercitazioni crimpatura', 'metri', 4, 90.00, 20.00, 1, '2026-04-28 10:17:13', '2026-04-29 09:09:57');

-- --------------------------------------------------------

--
-- Struttura della tabella `segnalazioni`
--

CREATE TABLE `segnalazioni` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_laboratorio` int(10) UNSIGNED NOT NULL,
  `id_sessione` int(10) UNSIGNED DEFAULT NULL,
  `id_utente` int(10) UNSIGNED NOT NULL,
  `titolo` varchar(255) NOT NULL,
  `descrizione` text NOT NULL,
  `priorita` enum('bassa','media','alta','urgente') NOT NULL DEFAULT 'media',
  `stato` enum('aperta','in_lavorazione','risolta','chiusa') NOT NULL DEFAULT 'aperta',
  `data_segnalazione` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_risoluzione` datetime DEFAULT NULL,
  `note_risoluzione` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `segnalazioni`
--

INSERT INTO `segnalazioni` (`id`, `id_laboratorio`, `id_sessione`, `id_utente`, `titolo`, `descrizione`, `priorita`, `stato`, `data_segnalazione`, `data_risoluzione`, `note_risoluzione`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 5, 'PC postazione 7 non si accende', 'Il PC non risponde al tasto di accensione. Cavo testato, presa OK.', 'alta', 'risolta', '2026-04-28 10:17:13', '2026-04-29 13:53:55', NULL, '2026-04-28 10:17:13', '2026-04-29 11:53:55'),
(2, 2, NULL, 2, 'Proiettore con immagine distorta', 'Righe verticali nella parte destra dello schermo.', 'media', 'in_lavorazione', '2026-04-28 10:17:13', NULL, NULL, '2026-04-28 10:17:13', '2026-04-28 10:17:13');

-- --------------------------------------------------------

--
-- Struttura della tabella `sessioni_laboratorio`
--

CREATE TABLE `sessioni_laboratorio` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_laboratorio` int(10) UNSIGNED NOT NULL,
  `id_classe` int(10) UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `ora_ingresso` time NOT NULL,
  `ora_uscita` time DEFAULT NULL,
  `attivita_svolta` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `sessioni_laboratorio`
--

INSERT INTO `sessioni_laboratorio` (`id`, `id_laboratorio`, `id_classe`, `data`, `ora_ingresso`, `ora_uscita`, `attivita_svolta`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 4, '2026-04-28', '08:30:00', '10:30:00', 'Configurazione IP statico e analisi con Wireshark.', NULL, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(2, 2, 7, '2026-04-28', '10:30:00', '11:26:00', 'Sviluppo applicazione web PHP — in corso.', NULL, '2026-04-28 10:17:13', '2026-04-29 09:26:54'),
(3, 4, 2, '2026-04-29', '07:40:00', '08:20:00', 'fddvb', 'dffssd', '2026-04-29 09:09:34', '2026-04-29 09:09:34'),
(4, 2, 1, '2026-04-29', '08:05:00', '08:25:00', NULL, NULL, '2026-04-29 10:13:19', '2026-04-29 10:13:19'),
(5, 2, 2, '2026-04-07', '12:20:00', '12:20:00', NULL, NULL, '2026-04-29 10:20:51', '2026-04-29 10:20:54'),
(6, 2, 3, '2026-04-29', '07:55:00', '08:00:00', NULL, NULL, '2026-04-29 11:52:26', '2026-04-29 11:52:26');

-- --------------------------------------------------------

--
-- Struttura della tabella `sessioni_materiali`
--

CREATE TABLE `sessioni_materiali` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_sessione` int(10) UNSIGNED NOT NULL,
  `id_materiale` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

CREATE TABLE `utenti` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ruolo` enum('admin','docente','tecnico') NOT NULL DEFAULT 'docente',
  `telefono` varchar(20) DEFAULT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `nome`, `cognome`, `email`, `password`, `ruolo`, `telefono`, `attivo`, `created_at`, `updated_at`) VALUES
(1, 'Daniele', 'Signorile', 'daniele.signorile@itisff.it', '123456', 'admin', '+39 333-1111111', 1, '2026-04-28 10:17:13', '2026-04-29 11:42:56'),
(2, 'Peter', 'Old', 'pietro.vecchio@itisff.it', '123456', 'docente', '+39 333-2222222', 1, '2026-04-28 10:17:13', '2026-04-30 09:05:05'),
(4, 'Elena', 'Torricelli', 'elena.torricelli@itsff.it', 'tecnico2026', 'tecnico', '333-4444444', 1, '2026-04-28 10:17:13', '2026-04-29 18:40:42'),
(5, 'Roberto', 'Boyle', 'roberto.boyle@itsff.it', 'docente1', 'docente', NULL, 1, '2026-04-28 10:17:13', '2026-04-28 10:17:13'),
(8, 'Roberto', 'Invidia', 'Roberto.invidia@itisff.it', '123456', 'docente', NULL, 1, '2026-04-29 11:35:32', '2026-04-29 19:11:03'),
(9, 'Francesco', 'Camarda', 'francesco.camarda@itisff.it', '123456', 'tecnico', '+39 1234567889', 1, '2026-04-30 07:11:55', '2026-04-30 07:11:55'),
(12, 'Francesco', 'Camarda', 'daniele.signorile@itsff.it', 'cambiami2026', 'docente', '+39 2222222222222', 1, '2026-04-30 07:18:22', '2026-04-30 07:18:22');

-- --------------------------------------------------------

--
-- Struttura della tabella `utilizzo_materiali`
--

CREATE TABLE `utilizzo_materiali` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_sessione` int(10) UNSIGNED NOT NULL,
  `id_materiale` int(10) UNSIGNED NOT NULL,
  `quantita_usata` decimal(10,2) NOT NULL DEFAULT 0.00,
  `esaurito` tinyint(1) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `utilizzo_materiali`
--

INSERT INTO `utilizzo_materiali` (`id`, `id_sessione`, `id_materiale`, `quantita_usata`, `esaurito`, `note`, `created_at`) VALUES
(1, 1, 1, 4.00, 0, 'Usati per cablaggio banchi', '2026-04-28 10:17:13'),
(2, 1, 2, 1.00, 0, 'Mouse sostitutivo postazione 7', '2026-04-28 10:17:13'),
(3, 3, 9, 10.00, 0, NULL, '2026-04-29 09:09:57');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `classi`
--
ALTER TABLE `classi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_classe_anno` (`nome`,`anno_scolastico`);

--
-- Indici per le tabelle `docenti_laboratori`
--
ALTER TABLE `docenti_laboratori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_docente_lab` (`id_docente`,`id_laboratorio`),
  ADD KEY `fk_dl_laboratorio` (`id_laboratorio`);

--
-- Indici per le tabelle `firme_sessioni`
--
ALTER TABLE `firme_sessioni`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_firma` (`id_sessione`,`id_docente`),
  ADD KEY `fk_firma_docente` (`id_docente`);

--
-- Indici per le tabelle `laboratori`
--
ALTER TABLE `laboratori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lab_assistente` (`id_assistente_tecnico`),
  ADD KEY `fk_lab_responsabile` (`id_responsabile`);

--
-- Indici per le tabelle `materiali`
--
ALTER TABLE `materiali`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_materiale_lab` (`id_laboratorio`);

--
-- Indici per le tabelle `segnalazioni`
--
ALTER TABLE `segnalazioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_segn_lab` (`id_laboratorio`),
  ADD KEY `fk_segn_sessione` (`id_sessione`),
  ADD KEY `fk_segn_utente` (`id_utente`);

--
-- Indici per le tabelle `sessioni_laboratorio`
--
ALTER TABLE `sessioni_laboratorio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sessione_lab` (`id_laboratorio`),
  ADD KEY `fk_sessione_classe` (`id_classe`);

--
-- Indici per le tabelle `sessioni_materiali`
--
ALTER TABLE `sessioni_materiali`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sess_mat` (`id_sessione`,`id_materiale`),
  ADD KEY `idx_sessione` (`id_sessione`),
  ADD KEY `idx_materiale` (`id_materiale`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `utilizzo_materiali`
--
ALTER TABLE `utilizzo_materiali`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_utilizzo` (`id_sessione`,`id_materiale`),
  ADD KEY `fk_utilizzo_materiale` (`id_materiale`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `classi`
--
ALTER TABLE `classi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `docenti_laboratori`
--
ALTER TABLE `docenti_laboratori`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `firme_sessioni`
--
ALTER TABLE `firme_sessioni`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `laboratori`
--
ALTER TABLE `laboratori`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `materiali`
--
ALTER TABLE `materiali`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `segnalazioni`
--
ALTER TABLE `segnalazioni`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `sessioni_laboratorio`
--
ALTER TABLE `sessioni_laboratorio`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `sessioni_materiali`
--
ALTER TABLE `sessioni_materiali`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `utilizzo_materiali`
--
ALTER TABLE `utilizzo_materiali`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `docenti_laboratori`
--
ALTER TABLE `docenti_laboratori`
  ADD CONSTRAINT `fk_dl_docente` FOREIGN KEY (`id_docente`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dl_laboratorio` FOREIGN KEY (`id_laboratorio`) REFERENCES `laboratori` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `firme_sessioni`
--
ALTER TABLE `firme_sessioni`
  ADD CONSTRAINT `fk_firma_docente` FOREIGN KEY (`id_docente`) REFERENCES `utenti` (`id`),
  ADD CONSTRAINT `fk_firma_sessione` FOREIGN KEY (`id_sessione`) REFERENCES `sessioni_laboratorio` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `laboratori`
--
ALTER TABLE `laboratori`
  ADD CONSTRAINT `fk_lab_assistente` FOREIGN KEY (`id_assistente_tecnico`) REFERENCES `utenti` (`id`),
  ADD CONSTRAINT `fk_lab_responsabile` FOREIGN KEY (`id_responsabile`) REFERENCES `utenti` (`id`);

--
-- Limiti per la tabella `materiali`
--
ALTER TABLE `materiali`
  ADD CONSTRAINT `fk_materiale_lab` FOREIGN KEY (`id_laboratorio`) REFERENCES `laboratori` (`id`);

--
-- Limiti per la tabella `segnalazioni`
--
ALTER TABLE `segnalazioni`
  ADD CONSTRAINT `fk_segn_lab` FOREIGN KEY (`id_laboratorio`) REFERENCES `laboratori` (`id`),
  ADD CONSTRAINT `fk_segn_sessione` FOREIGN KEY (`id_sessione`) REFERENCES `sessioni_laboratorio` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_segn_utente` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id`);

--
-- Limiti per la tabella `sessioni_laboratorio`
--
ALTER TABLE `sessioni_laboratorio`
  ADD CONSTRAINT `fk_sessione_classe` FOREIGN KEY (`id_classe`) REFERENCES `classi` (`id`),
  ADD CONSTRAINT `fk_sessione_lab` FOREIGN KEY (`id_laboratorio`) REFERENCES `laboratori` (`id`);

--
-- Limiti per la tabella `utilizzo_materiali`
--
ALTER TABLE `utilizzo_materiali`
  ADD CONSTRAINT `fk_utilizzo_materiale` FOREIGN KEY (`id_materiale`) REFERENCES `materiali` (`id`),
  ADD CONSTRAINT `fk_utilizzo_sessione` FOREIGN KEY (`id_sessione`) REFERENCES `sessioni_laboratorio` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
