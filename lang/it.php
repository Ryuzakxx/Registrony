<?php
/**
 * Registrony del Laboratoriony — Etichette interfaccia (Italiano)
 * Tutte le label, placeholder, messaggi di errore e testi UI sono centralizzati qui.
 */

return [

    /* ------------------------------------------------------------------ */
    /* GENERALI                                                             */
    /* ------------------------------------------------------------------ */
    'salva_modifiche'    => 'Salva Modifiche',
    'annulla'            => 'Annulla',
    'elimina'            => 'Elimina',
    'modifica'           => 'Modifica',
    'crea'               => 'Crea',
    'dettagli'           => 'Dettagli',
    'filtra'             => 'Filtra',
    'reset'              => 'Reset',
    'tutti'              => 'Tutti',
    'tutte'              => 'Tutte',
    'seleziona'          => '-- Seleziona --',
    'nessuno'            => '-- Nessuno --',
    'nessuna'            => '-- Nessuna --',
    'opzionale'          => 'opzionale',
    'obbligatorio_breve' => '*',
    'stato'              => 'Stato',
    'attivo'             => 'Attivo',
    'disattivato'        => 'Disattivato',
    'si'                 => 'Sì',
    'no'                 => 'No',
    'data'               => 'Data',
    'ora'                => 'Ora',
    'note'               => 'Note',
    'descrizione'        => 'Descrizione',
    'nome'               => 'Nome',
    'cognome'            => 'Cognome',
    'email'              => 'Email',
    'telefono'           => 'Telefono',
    'ruolo'              => 'Ruolo',
    'password'           => 'Password',
    'azioni'             => 'Azioni',
    'torna_indietro'     => '← Torna indietro',
    'vedi_tutte'         => 'Vedi tutte',
    'nessun_risultato'   => 'Nessun risultato trovato.',
    'char_contatore'     => '%d / %d',

    /* ------------------------------------------------------------------ */
    /* NAVIGAZIONE / SIDEBAR                                               */
    /* ------------------------------------------------------------------ */
    'nav_dashboard'          => 'Dashboard',
    'nav_sessioni'           => 'Sessioni Lab',
    'nav_nuova_sessione'     => 'Nuova Sessione',
    'nav_materiali'          => 'Materiali',
    'nav_segnalazioni'       => 'Segnalazioni',
    'nav_sezione_principale' => 'Principale',
    'nav_sezione_admin'      => 'Amministrazione',
    'nav_laboratori'         => 'Laboratori',
    'nav_utenti'             => 'Utenti',
    'nav_classi'             => 'Classi',
    'nav_gest_materiali'     => 'Gestione Materiali',

    /* ------------------------------------------------------------------ */
    /* LOGIN                                                                */
    /* ------------------------------------------------------------------ */
    'login_titolo'             => 'Login',
    'login_email_label'        => 'Email istituzionale',
    'login_email_placeholder'  => 'nome@scuola.it',
    'login_password_label'     => 'Password',
    'login_password_placeholder'=> 'La tua password',
    'login_btn'                => 'Accedi',
    'login_err_campi_vuoti'    => 'Inserisci email e password.',
    'login_err_credenziali'    => 'Email o password non validi, oppure account disattivato.',

    /* ------------------------------------------------------------------ */
    /* DASHBOARD                                                            */
    /* ------------------------------------------------------------------ */
    'dash_titolo'              => 'Dashboard',
    'dash_lab_attivi'          => 'Laboratori attivi',
    'dash_sessioni_oggi'       => 'Sessioni oggi',
    'dash_segnalazioni_aperte' => 'Segnalazioni aperte',
    'dash_mat_esaurimento'     => 'Materiali in esaurimento',
    'dash_sessioni_oggi_titolo'=> 'Sessioni di oggi',
    'dash_nessuna_sessione'    => 'Nessuna sessione oggi.',
    'dash_segnalazioni_titolo' => 'Segnalazioni aperte',
    'dash_tutto_ok'            => 'Tutto funziona correttamente!',
    'dash_nessuna_segn'        => 'Nessuna segnalazione aperta.',

    /* ------------------------------------------------------------------ */
    /* SESSIONI                                                             */
    /* ------------------------------------------------------------------ */
    'sess_titolo_lista'         => 'Sessioni Laboratorio',
    'sess_titolo_nuova'         => 'Nuova Sessione',
    'sess_titolo_dettaglio'     => 'Dettaglio Sessione',
    'sess_laboratorio'          => 'Laboratorio',
    'sess_classe'               => 'Classe',
    'sess_data'                 => 'Data sessione',
    'sess_ora_ingresso'         => 'Ora Ingresso',
    'sess_ora_uscita'           => 'Ora Uscita',
    'sess_attivita'             => 'Attività Svolta',
    'sess_attivita_placeholder' => 'Descrivi le attività svolte durante la sessione...',
    'sess_note_placeholder'     => 'Note aggiuntive (facoltativo)...',
    'sess_docente_titolare'     => 'Docente Titolare',
    'sess_docente_compresenza'  => 'Docente in Compresenza',
    'sess_in_corso'             => 'In corso',
    'sess_completata'           => 'Completata',
    'sess_chiudi'               => 'Chiudi Sessione',
    'sess_ora_uscita_label'     => 'Ora Uscita effettiva',
    'sess_btn_registra'         => '✔ Registra Sessione',
    'sess_btn_nuova'            => '+ Nuova Sessione',
    'sess_btn_chiudi'           => 'Chiudi Sessione',
    'sess_nessuna'              => 'Nessuna sessione trovata.',
    'sess_nessuna_oggi'         => 'Non ci sono sessioni registrate per oggi.',
    'sess_aggiorna'             => 'Aggiorna Attività',
    'sess_hint_ora_uscita'      => 'Lascia vuoto se la sessione è ancora in corso.',
    'sess_hint_attivita'        => 'Max 1000 caratteri.',
    'sess_hint_note'            => 'Max 500 caratteri.',
    'sess_seleziona_lab'        => '-- Seleziona laboratorio --',
    'sess_seleziona_classe'     => '-- Seleziona classe --',
    'sess_seleziona_docente'    => '-- Seleziona docente --',
    'sess_nessun_lab'           => 'Nessun laboratorio attivo disponibile.',
    'sess_nessuna_classe'       => 'Nessuna classe attiva disponibile.',
    'sess_anno_scol'            => 'Anno scolastico',

    /* Errori sessioni */
    'sess_err_lab'              => 'Seleziona un laboratorio.',
    'sess_err_classe'           => 'Seleziona una classe.',
    'sess_err_data'             => 'Inserisci la data.',
    'sess_err_data_futura'      => 'La data non può essere futura.',
    'sess_err_ora_ingresso'     => "Inserisci l'ora di ingresso.",
    'sess_err_ora_uscita'       => "L'ora di uscita deve essere successiva all'ora di ingresso.",
    'sess_err_docente_tit'      => 'Seleziona il docente titolare.',
    'sess_err_docente_comp'     => 'Il docente in compresenza deve essere diverso dal titolare.',
    'sess_err_attivita_lunga'   => 'Attività svolta: max 1000 caratteri.',
    'sess_err_note_lunghe'      => 'Note: max 500 caratteri.',

    /* Successi sessioni */
    'sess_ok_creata'            => 'Sessione creata con successo!',
    'sess_ok_aggiornata'        => 'Sessione aggiornata!',
    'sess_ok_chiusa'            => 'Sessione chiusa!',

    /* ------------------------------------------------------------------ */
    /* FIRME                                                                */
    /* ------------------------------------------------------------------ */
    'firme_titolo'          => 'Firme Docenti',
    'firme_docente'         => 'Docente',
    'firme_tipo'            => 'Tipo presenza',
    'firme_ora'             => 'Ora firma',
    'firme_titolare'        => 'Titolare',
    'firme_compresenza'     => 'Compresenza',
    'firme_aggiungi'        => 'Aggiungi Firma',
    'firme_aggiungi_label'  => '-- Aggiungi firma compresenza --',
    'firme_nessuna'         => 'Nessuna firma registrata.',

    /* ------------------------------------------------------------------ */
    /* MATERIALI                                                            */
    /* ------------------------------------------------------------------ */
    'mat_titolo_lista'      => 'Materiali',
    'mat_titolo_gest'       => 'Gestione Materiali',
    'mat_nome'              => 'Nome Materiale',
    'mat_nome_placeholder'  => 'Es: Cavo Ethernet Cat.6',
    'mat_lab'               => 'Laboratorio',
    'mat_unita'             => 'Unità di Misura',
    'mat_unita_vuota'       => '-- Non specificata --',
    'mat_quantita'          => 'Quantità Disponibile',
    'mat_quantita_placeholder'=> 'Es: 50',
    'mat_soglia'            => 'Soglia Minima Scorta',
    'mat_soglia_placeholder'=> 'Es: 10',
    'mat_soglia_hint'       => 'Sotto questa soglia viene segnalato "In esaurimento".',
    'mat_desc_placeholder'  => 'Descrizione opzionale...',
    'mat_stato_ok'          => 'OK',
    'mat_stato_esaurito'    => 'Esaurito',
    'mat_stato_esaurimento' => 'In esaurimento',
    'mat_stato_nd'          => 'N/D',
    'mat_stato_disattivato' => 'Disattivato',
    'mat_attivo_label'      => 'Materiale attivo',
    'mat_btn_crea'          => '+ Crea Materiale',
    'mat_btn_salva'         => '✔ Salva Modifiche',
    'mat_seleziona_lab'     => '-- Seleziona laboratorio --',
    'mat_nessuno'           => 'Nessun materiale trovato.',
    'mat_filtra_lab'        => 'Filtra per Laboratorio',
    'mat_disponibile'       => 'Disponibile',
    'mat_soglia_col'        => 'Soglia Min.',
    'mat_unita_col'         => 'Unità',

    /* Errori materiali */
    'mat_err_nome'          => 'Nome materiale obbligatorio.',
    'mat_err_lab'           => 'Seleziona un laboratorio.',
    'mat_err_quantita_neg'  => 'La quantità disponibile non può essere negativa.',
    'mat_err_soglia_neg'    => 'La soglia minima non può essere negativa.',
    'mat_err_soglia_sup'    => 'La soglia minima non può essere maggiore della quantità disponibile.',
    'mat_err_soglia_live'   => 'La soglia non può superare la quantità disponibile.',
    'mat_err_nessun_lab'    => 'Nessun laboratorio attivo.',

    /* Successi materiali */
    'mat_ok_creato'         => 'Materiale creato!',
    'mat_ok_aggiornato'     => 'Materiale aggiornato!',
    'mat_ok_eliminato'      => 'Materiale eliminato!',
    'mat_err_in_uso'        => 'Impossibile eliminare: materiale in uso',

    /* ------------------------------------------------------------------ */
    /* UTILIZZO MATERIALI (dettaglio sessione)                             */
    /* ------------------------------------------------------------------ */
    'umat_titolo'           => 'Materiali Utilizzati',
    'umat_materiale'        => 'Materiale',
    'umat_quantita'         => 'Quantità',
    'umat_unita'            => 'Unità',
    'umat_esaurito'         => 'Esaurito',
    'umat_note'             => 'Note',
    'umat_aggiungi'         => 'Aggiungi materiale:',
    'umat_seleziona'        => '-- Seleziona --',
    'umat_quantita_usata'   => 'Quantità Usata',
    'umat_note_placeholder' => 'Opzionale',
    'umat_flag_esaurito'    => 'Materiale esaurito/finito',
    'umat_btn_aggiungi'     => 'Aggiungi Materiale',
    'umat_nessuno'          => 'Nessun materiale registrato.',
    'umat_disp_fmt'         => '(disp: %s %s)',
    'umat_ok_aggiunto'      => 'Materiale registrato!',
    'umat_err_gia_presente' => 'Materiale già registrato per questa sessione',

    /* ------------------------------------------------------------------ */
    /* SEGNALAZIONI                                                         */
    /* ------------------------------------------------------------------ */
    'segn_titolo_lista'         => 'Segnalazioni',
    'segn_titolo_nuova'         => 'Nuova Segnalazione',
    'segn_titolo_dettaglio'     => 'Dettaglio Segnalazione',
    'segn_lab'                  => 'Laboratorio',
    'segn_priorita'             => 'Priorità',
    'segn_stato'                => 'Stato',
    'segn_titolo_campo'         => 'Titolo',
    'segn_titolo_placeholder'   => 'Es: PC postazione 5 non funziona',
    'segn_titolo_hint'          => 'max 255 caratteri',
    'segn_desc_label'           => 'Descrizione del problema',
    'segn_desc_placeholder'     => "Descrivi il problema in dettaglio: cosa non funziona, da quando, eventuali tentativi di risoluzione...",
    'segn_desc_hint'            => 'max 2000 caratteri',
    'segn_segnalato_da'         => 'Segnalato da',
    'segn_data'                 => 'Data',
    'segn_collegata'            => 'Segnalazione collegata alla sessione #%d',
    'segn_btn_invia'            => '⚠ Invia Segnalazione',
    'segn_btn_nuova'            => '+ Nuova Segnalazione',
    'segn_nessuna'              => 'Nessuna segnalazione trovata.',
    'segn_tutto_ok'             => 'Nessuna segnalazione aperta.',
    'segn_filtra_stato'         => 'Stato',
    'segn_filtra_lab'           => 'Laboratorio',
    'segn_seleziona_lab'        => '-- Seleziona laboratorio --',

    /* Priorità */
    'segn_prio_bassa'   => '▼ Bassa',
    'segn_prio_media'   => '— Media',
    'segn_prio_alta'    => '▲ Alta',
    'segn_prio_urgente' => '🔴 Urgente',

    /* Stati */
    'segn_stato_aperta'         => 'Aperta',
    'segn_stato_in_lavorazione' => 'In lavorazione',
    'segn_stato_risolta'        => 'Risolta',
    'segn_stato_chiusa'         => 'Chiusa',

    /* Admin pannello segnalazione */
    'segn_admin_titolo'         => '🔧 Gestione Segnalazione (Admin)',
    'segn_admin_cambia_stato'   => 'Cambia Stato',
    'segn_admin_note_ris'       => 'Note Risoluzione',
    'segn_admin_note_placeholder'=> 'Descrivi come è stato risolto il problema...',
    'segn_admin_btn_aggiorna'   => 'Aggiorna Stato',
    'segn_data_ris'             => 'Data risoluzione',
    'segn_note_ris'             => 'Note risoluzione',

    /* Errori segnalazioni */
    'segn_err_lab'          => 'Seleziona un laboratorio.',
    'segn_err_titolo'       => 'Inserisci un titolo.',
    'segn_err_titolo_lungo' => 'Titolo: max 255 caratteri.',
    'segn_err_desc'         => 'Inserisci una descrizione del problema.',
    'segn_err_desc_lunga'   => 'Descrizione: max 2000 caratteri.',

    /* Successi segnalazioni */
    'segn_ok_inviata'       => 'Segnalazione inviata con successo!',
    'segn_ok_aggiornata'    => 'Stato aggiornato!',

    /* ------------------------------------------------------------------ */
    /* ADMIN — UTENTI                                                       */
    /* ------------------------------------------------------------------ */
    'utenti_titolo'              => 'Gestione Utenti',
    'utenti_btn_crea'            => '+ Crea Utente',
    'utenti_form_titolo_crea'    => '+ Nuovo Utente',
    'utenti_form_titolo_mod'     => '✏ Modifica Utente',
    'utenti_nome'                => 'Nome',
    'utenti_cognome'             => 'Cognome',
    'utenti_email'               => 'Email',
    'utenti_email_placeholder'   => 'nome@scuola.it',
    'utenti_pwd_label'           => 'Password',
    'utenti_pwd_nuova_label'     => 'Nuova Password',
    'utenti_pwd_nuova_hint'      => '(lascia vuoto per non cambiare)',
    'utenti_pwd_placeholder'     => 'Minimo 6 caratteri',
    'utenti_ruolo'               => 'Ruolo',
    'utenti_ruolo_docente'       => '📋 Docente',
    'utenti_ruolo_admin'         => '⚙ Amministratore',
    'utenti_telefono'            => 'Telefono',
    'utenti_telefono_placeholder'=> 'Es: 333-1234567',
    'utenti_telefono_hint'       => 'Formato: cifre, spazi, +, -, .',
    'utenti_attivo_label'        => 'Account attivo',
    'utenti_prefisso_label'      => 'Prefisso',
    'utenti_nessuno'             => 'Nessun utente.',
    'utenti_btn_salva'           => '✔ Salva Modifiche',
    'utenti_col_nome_cognome'    => 'Cognome Nome',
    'utenti_col_email'           => 'Email',
    'utenti_col_ruolo'           => 'Ruolo',
    'utenti_col_telefono'        => 'Telefono',
    'utenti_col_stato'           => 'Stato',

    /* Errori utenti */
    'utenti_err_nome'       => 'Nome obbligatorio.',
    'utenti_err_cognome'    => 'Cognome obbligatorio.',
    'utenti_err_email'      => 'Email non valida.',
    'utenti_err_pwd'        => 'Password: minimo 6 caratteri.',
    'utenti_err_tel'        => 'Formato telefono non valido.',
    'utenti_err_no_self'    => 'Non puoi eliminare te stesso!',
    'utenti_err_email_uso'  => 'Email già in uso o errore DB',

    /* Successi utenti */
    'utenti_ok_creato'      => 'Utente creato!',
    'utenti_ok_aggiornato'  => 'Utente aggiornato!',
    'utenti_ok_eliminato'   => 'Utente eliminato!',

    /* Forza password */
    'pwd_molto_debole'  => 'Molto debole',
    'pwd_debole'        => 'Debole',
    'pwd_accettabile'   => 'Accettabile',
    'pwd_forte'         => 'Forte',
    'pwd_molto_forte'   => 'Molto forte',

    /* ------------------------------------------------------------------ */
    /* ADMIN — LABORATORI                                                   */
    /* ------------------------------------------------------------------ */
    'lab_titolo'                => 'Gestione Laboratori',
    'lab_form_titolo_crea'      => '+ Nuovo Laboratorio',
    'lab_form_titolo_mod'       => '✏ Modifica Laboratorio',
    'lab_nome'                  => 'Nome laboratorio',
    'lab_aula'                  => 'Aula',
    'lab_aula_placeholder'      => 'Es: SR-01',
    'lab_assistente'            => 'Assistente Tecnico',
    'lab_responsabile'          => 'Responsabile',
    'lab_descrizione'           => 'Descrizione',
    'lab_descrizione_placeholder'=> 'Descrizione opzionale del laboratorio...',
    'lab_attivo_label'          => 'Laboratorio attivo',
    'lab_btn_crea'              => 'Crea Laboratorio',
    'lab_btn_salva'             => 'Salva Modifiche',
    'lab_nessuno'               => 'Nessun laboratorio.',
    'lab_col_nome'              => 'Nome',
    'lab_col_aula'              => 'Aula',
    'lab_col_assistente'        => 'Assistente',
    'lab_col_responsabile'      => 'Responsabile',
    'lab_col_stato'             => 'Stato',
    'lab_seleziona_assistente'  => '-- Seleziona --',
    'lab_seleziona_responsabile'=> '-- Seleziona --',
    'lab_attivo_badge'          => 'Attivo',
    'lab_disattivato_badge'     => 'Disattivato',

    /* Errori laboratori */
    'lab_err_campi'     => 'Compila tutti i campi obbligatori',
    'lab_err_in_uso'    => 'Impossibile eliminare: laboratorio in uso',

    /* Successi laboratori */
    'lab_ok_creato'     => 'Laboratorio creato!',
    'lab_ok_aggiornato' => 'Laboratorio aggiornato!',
    'lab_ok_eliminato'  => 'Laboratorio eliminato!',

    /* ------------------------------------------------------------------ */
    /* ADMIN — CLASSI                                                       */
    /* ------------------------------------------------------------------ */
    'classi_titolo'              => 'Gestione Classi',
    'classi_form_titolo_crea'    => '+ Nuova Classe',
    'classi_form_titolo_mod'     => '✏ Modifica Classe',
    'classi_nome'                => 'Nome Classe',
    'classi_nome_placeholder'    => 'Es: 3A, 4AB',
    'classi_nome_hint'           => 'Formato: cifra + lettere (es: 3A, 4AB, 5INF)',
    'classi_anno'                => 'Anno Scolastico',
    'classi_anno_vuoto'          => '-- Seleziona --',
    'classi_indirizzo'           => 'Indirizzo di studio',
    'classi_indirizzo_vuoto'     => '-- Nessuno / Non specificato --',
    'classi_attivo_label'        => 'Classe attiva',
    'classi_btn_crea'            => '+ Crea Classe',
    'classi_btn_salva'           => '✔ Salva Modifiche',
    'classi_nessuna'             => 'Nessuna classe.',
    'classi_col_nome'            => 'Nome',
    'classi_col_anno'            => 'Anno',
    'classi_col_indirizzo'       => 'Indirizzo',
    'classi_col_stato'           => 'Stato',
    'classi_badge_attiva'        => 'Attiva',
    'classi_badge_disattivata'   => 'Disattivata',

    /* Errori classi */
    'classi_err_nome'         => 'Nome classe obbligatorio.',
    'classi_err_nome_formato' => 'Formato non valido (es: 3A, 4AB).',
    'classi_err_anno'         => 'Anno scolastico obbligatorio.',
    'classi_err_anno_formato' => "Formato anno scolastico non valido (es: 2025/2026).",
    'classi_err_anno_seq'     => 'Anno scolastico: il secondo anno deve essere il successivo.',
    'classi_err_gia_esistente'=> 'Classe già esistente per questo anno',
    'classi_err_in_uso'       => 'Impossibile eliminare: classe in uso',

    /* Successi classi */
    'classi_ok_creata'    => 'Classe creata!',
    'classi_ok_aggiornata'=> 'Classe aggiornata!',
    'classi_ok_eliminata' => 'Classe eliminata!',

    /* ------------------------------------------------------------------ */
    /* CONFERME ELIMINAZIONE                                               */
    /* ------------------------------------------------------------------ */
    'confirm_elimina_classe'   => "Eliminare la classe %s?",
    'confirm_elimina_lab'      => 'Sei sicuro di voler eliminare questo laboratorio?',
    'confirm_elimina_materiale'=> "Eliminare il materiale %s?",
    'confirm_elimina_utente'   => "Eliminare %s?",

    /* ------------------------------------------------------------------ */
    /* PREFISSI TELEFONICI                                                  */
    /* ------------------------------------------------------------------ */
    'prefissi' => [
        '+39' => '🇮🇹 +39 (Italia)',
        '+1'  => '🇺🇸 +1 (USA/Canada)',
        '+44' => '🇬🇧 +44 (UK)',
        '+33' => '🇫🇷 +33 (Francia)',
        '+49' => '🇩🇪 +49 (Germania)',
        '+34' => '🇪🇸 +34 (Spagna)',
        '+351'=> '🇵🇹 +351 (Portogallo)',
        '+41' => '🇨🇭 +41 (Svizzera)',
    ],

    /* ------------------------------------------------------------------ */
    /* SLOT ORARI (dropdown ora ingresso/uscita)                           */
    /* ------------------------------------------------------------------ */
    'orari_placeholder' => '-- Seleziona orario --',
    'orari_label_ingresso' => 'Ora Ingresso',
    'orari_label_uscita'   => 'Ora Uscita',
    'orari_hint_uscita'    => 'Lascia vuoto se la sessione è ancora in corso.',

];
