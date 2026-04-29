<?php
/**
 * Registrony — UI labels (English)
 * All labels, placeholders, error messages and UI texts are centralised here.
 */

return [

    /* ------------------------------------------------------------------ */
    /* GENERAL                                                              */
    /* ------------------------------------------------------------------ */
    'salva_modifiche'    => 'Save Changes',
    'annulla'            => 'Cancel',
    'elimina'            => 'Delete',
    'modifica'           => 'Edit',
    'crea'               => 'Create',
    'dettagli'           => 'Details',
    'filtra'             => 'Filter',
    'reset'              => 'Reset',
    'tutti'              => 'All',
    'tutte'              => 'All',
    'seleziona'          => '-- Select --',
    'nessuno'            => '-- None --',
    'nessuna'            => '-- None --',
    'opzionale'          => 'optional',
    'obbligatorio_breve' => '*',
    'stato'              => 'Status',
    'attivo'             => 'Active',
    'disattivato'        => 'Disabled',
    'si'                 => 'Yes',
    'no'                 => 'No',
    'data'               => 'Date',
    'ora'                => 'Time',
    'note'               => 'Notes',
    'descrizione'        => 'Description',
    'nome'               => 'First Name',
    'cognome'            => 'Last Name',
    'email'              => 'Email',
    'telefono'           => 'Phone',
    'ruolo'              => 'Role',
    'password'           => 'Password',
    'azioni'             => 'Actions',
    'torna_indietro'     => '← Back',
    'vedi_tutte'         => 'View all',
    'nessun_risultato'   => 'No results found.',
    'char_contatore'     => '%d / %d',

    /* ------------------------------------------------------------------ */
    /* NAVIGATION / SIDEBAR                                                */
    /* ------------------------------------------------------------------ */
    'nav_dashboard'          => 'Dashboard',
    'nav_sessioni'           => 'Lab Sessions',
    'nav_nuova_sessione'     => 'New Session',
    'nav_materiali'          => 'Materials',
    'nav_segnalazioni'       => 'Reports',
    'nav_sezione_principale' => 'Main',
    'nav_sezione_admin'      => 'Administration',
    'nav_laboratori'         => 'Laboratories',
    'nav_utenti'             => 'Users',
    'nav_classi'             => 'Classes',
    'nav_gest_materiali'     => 'Manage Materials',

    /* ------------------------------------------------------------------ */
    /* LOGIN                                                                */
    /* ------------------------------------------------------------------ */
    'login_titolo'              => 'Login',
    'login_email_label'         => 'Institutional Email',
    'login_email_placeholder'   => 'name@school.edu',
    'login_password_label'      => 'Password',
    'login_password_placeholder'=> 'Your password',
    'login_btn'                 => 'Sign In',
    'login_err_campi_vuoti'     => 'Please enter your email and password.',
    'login_err_credenziali'     => 'Invalid email or password, or account disabled.',

    /* ------------------------------------------------------------------ */
    /* DASHBOARD                                                            */
    /* ------------------------------------------------------------------ */
    'dash_titolo'              => 'Dashboard',
    'dash_lab_attivi'          => 'Active laboratories',
    'dash_sessioni_oggi'       => "Today's sessions",
    'dash_segnalazioni_aperte' => 'Open reports',
    'dash_mat_esaurimento'     => 'Low-stock materials',
    'dash_sessioni_oggi_titolo'=> "Today's Sessions",
    'dash_nessuna_sessione'    => 'No sessions today.',
    'dash_segnalazioni_titolo' => 'Open Reports',
    'dash_tutto_ok'            => 'Everything is working correctly!',
    'dash_nessuna_segn'        => 'No open reports.',

    /* ------------------------------------------------------------------ */
    /* SESSIONS                                                             */
    /* ------------------------------------------------------------------ */
    'sess_titolo_lista'         => 'Lab Sessions',
    'sess_titolo_nuova'         => 'New Session',
    'sess_titolo_dettaglio'     => 'Session Details',
    'sess_laboratorio'          => 'Laboratory',
    'sess_classe'               => 'Class',
    'sess_data'                 => 'Session Date',
    'sess_ora_ingresso'         => 'Entry Time',
    'sess_ora_uscita'           => 'Exit Time',
    'sess_attivita'             => 'Activity Performed',
    'sess_attivita_placeholder' => 'Describe the activities carried out during the session...',
    'sess_note_placeholder'     => 'Additional notes (optional)...',
    'sess_docente_titolare'     => 'Lead Teacher',
    'sess_docente_compresenza'  => 'Co-present Teacher',
    'sess_in_corso'             => 'In progress',
    'sess_completata'           => 'Completed',
    'sess_chiudi'               => 'Close Session',
    'sess_ora_uscita_label'     => 'Actual Exit Time',
    'sess_btn_registra'         => '✔ Register Session',
    'sess_btn_nuova'            => '+ New Session',
    'sess_btn_chiudi'           => 'Close Session',
    'sess_nessuna'              => 'No sessions found.',
    'sess_nessuna_oggi'         => 'No sessions recorded for today.',
    'sess_aggiorna'             => 'Update Activity',
    'sess_hint_ora_uscita'      => 'Leave empty if the session is still in progress.',
    'sess_hint_attivita'        => 'Max 1000 characters.',
    'sess_hint_note'            => 'Max 500 characters.',
    'sess_seleziona_lab'        => '-- Select laboratory --',
    'sess_seleziona_classe'     => '-- Select class --',
    'sess_seleziona_docente'    => '-- Select teacher --',
    'sess_nessun_lab'           => 'No active laboratory available.',
    'sess_nessuna_classe'       => 'No active class available.',
    'sess_anno_scol'            => 'Academic year',

    /* Session errors */
    'sess_err_lab'              => 'Please select a laboratory.',
    'sess_err_classe'           => 'Please select a class.',
    'sess_err_data'             => 'Please enter the date.',
    'sess_err_data_futura'      => 'The date cannot be in the future.',
    'sess_err_ora_ingresso'     => 'Please enter the entry time.',
    'sess_err_ora_ingresso_futura' => 'The entry time cannot be in the future (today).',
    'sess_err_ora_uscita'       => 'The exit time must be after the entry time.',
    'sess_err_ora_uscita_futura'=> 'The exit time cannot be in the future.',
    'sess_err_docente_tit'      => 'Please select the lead teacher.',
    'sess_err_docente_comp'     => 'The co-present teacher must be different from the lead teacher.',
    'sess_err_attivita_lunga'   => 'Activity description: max 1000 characters.',
    'sess_err_note_lunghe'      => 'Notes: max 500 characters.',

    /* Session success */
    'sess_ok_creata'            => 'Session created successfully!',
    'sess_ok_aggiornata'        => 'Session updated!',
    'sess_ok_chiusa'            => 'Session closed!',

    /* ------------------------------------------------------------------ */
    /* SIGNATURES                                                           */
    /* ------------------------------------------------------------------ */
    'firme_titolo'          => 'Teacher Signatures',
    'firme_docente'         => 'Teacher',
    'firme_tipo'            => 'Presence type',
    'firme_ora'             => 'Sign time',
    'firme_titolare'        => 'Lead',
    'firme_compresenza'     => 'Co-present',
    'firme_aggiungi'        => 'Add Signature',
    'firme_aggiungi_label'  => '-- Add co-present signature --',
    'firme_nessuna'         => 'No signatures recorded.',

    /* ------------------------------------------------------------------ */
    /* MATERIALS                                                            */
    /* ------------------------------------------------------------------ */
    'mat_titolo_lista'        => 'Materials',
    'mat_titolo_gest'         => 'Manage Materials',
    'mat_nome'                => 'Material Name',
    'mat_nome_placeholder'    => 'E.g.: Cat.6 Ethernet Cable',
    'mat_lab'                 => 'Laboratory',
    'mat_unita'               => 'Unit of Measure',
    'mat_unita_vuota'         => '-- Not specified --',
    'mat_quantita'            => 'Available Quantity',
    'mat_quantita_placeholder'=> 'E.g.: 50',
    'mat_soglia'              => 'Minimum Stock Threshold',
    'mat_soglia_placeholder'  => 'E.g.: 10',
    'mat_soglia_hint'         => 'Below this threshold the status is shown as "Low stock".',
    'mat_desc_placeholder'    => 'Optional description...',
    'mat_stato_ok'            => 'OK',
    'mat_stato_esaurito'      => 'Out of stock',
    'mat_stato_esaurimento'   => 'Low stock',
    'mat_stato_nd'            => 'N/A',
    'mat_stato_disattivato'   => 'Disabled',
    'mat_attivo_label'        => 'Material active',
    'mat_btn_crea'            => '+ Create Material',
    'mat_btn_salva'           => '✔ Save Changes',
    'mat_seleziona_lab'       => '-- Select laboratory --',
    'mat_nessuno'             => 'No materials found.',
    'mat_filtra_lab'          => 'Filter by Laboratory',
    'mat_disponibile'         => 'Available',
    'mat_soglia_col'          => 'Min. Threshold',
    'mat_unita_col'           => 'Unit',

    /* Material errors */
    'mat_err_nome'          => 'Material name is required.',
    'mat_err_lab'           => 'Please select a laboratory.',
    'mat_err_quantita_neg'  => 'Available quantity cannot be negative.',
    'mat_err_soglia_neg'    => 'Minimum threshold cannot be negative.',
    'mat_err_soglia_sup'    => 'Minimum threshold cannot exceed available quantity.',
    'mat_err_soglia_live'   => 'Threshold cannot exceed available quantity.',
    'mat_err_nessun_lab'    => 'No active laboratory.',

    /* Material success */
    'mat_ok_creato'         => 'Material created!',
    'mat_ok_aggiornato'     => 'Material updated!',
    'mat_ok_eliminato'      => 'Material deleted!',
    'mat_err_in_uso'        => 'Cannot delete: material is in use',

    /* ------------------------------------------------------------------ */
    /* MATERIAL USAGE (session detail)                                     */
    /* ------------------------------------------------------------------ */
    'umat_titolo'           => 'Materials Used',
    'umat_materiale'        => 'Material',
    'umat_quantita'         => 'Quantity',
    'umat_unita'            => 'Unit',
    'umat_esaurito'         => 'Out of stock',
    'umat_note'             => 'Notes',
    'umat_aggiungi'         => 'Add material:',
    'umat_seleziona'        => '-- Select --',
    'umat_quantita_usata'   => 'Quantity Used',
    'umat_note_placeholder' => 'Optional',
    'umat_flag_esaurito'    => 'Material depleted/finished',
    'umat_btn_aggiungi'     => 'Add Material',
    'umat_nessuno'          => 'No materials recorded.',
    'umat_disp_fmt'         => '(avail: %s %s)',
    'umat_ok_aggiunto'      => 'Material recorded!',
    'umat_err_gia_presente' => 'Material already recorded for this session',

    /* ------------------------------------------------------------------ */
    /* REPORTS                                                              */
    /* ------------------------------------------------------------------ */
    'segn_titolo_lista'          => 'Reports',
    'segn_titolo_nuova'          => 'New Report',
    'segn_titolo_dettaglio'      => 'Report Details',
    'segn_lab'                   => 'Laboratory',
    'segn_priorita'              => 'Priority',
    'segn_stato'                 => 'Status',
    'segn_titolo_campo'          => 'Title',
    'segn_titolo_placeholder'    => 'E.g.: Workstation 5 PC not working',
    'segn_titolo_hint'           => 'max 255 characters',
    'segn_desc_label'            => 'Problem description',
    'segn_desc_placeholder'      => 'Describe the problem in detail: what is not working, since when, any resolution attempts...',
    'segn_desc_hint'             => 'max 2000 characters',
    'segn_segnalato_da'          => 'Reported by',
    'segn_data'                  => 'Date',
    'segn_collegata'             => 'Report linked to session #%d',
    'segn_btn_invia'             => '⚠ Submit Report',
    'segn_btn_nuova'             => '+ New Report',
    'segn_nessuna'               => 'No reports found.',
    'segn_tutto_ok'              => 'No open reports.',
    'segn_filtra_stato'          => 'Status',
    'segn_filtra_lab'            => 'Laboratory',
    'segn_seleziona_lab'         => '-- Select laboratory --',

    /* Priority */
    'segn_prio_bassa'   => '▼ Low',
    'segn_prio_media'   => '— Medium',
    'segn_prio_alta'    => '▲ High',
    'segn_prio_urgente' => '🔴 Urgent',

    /* Status */
    'segn_stato_aperta'         => 'Open',
    'segn_stato_in_lavorazione' => 'In progress',
    'segn_stato_risolta'        => 'Resolved',
    'segn_stato_chiusa'         => 'Closed',

    /* Admin report panel */
    'segn_admin_titolo'          => '🔧 Report Management (Admin)',
    'segn_admin_cambia_stato'    => 'Change Status',
    'segn_admin_note_ris'        => 'Resolution Notes',
    'segn_admin_note_placeholder'=> 'Describe how the problem was resolved...',
    'segn_admin_btn_aggiorna'    => 'Update Status',
    'segn_data_ris'              => 'Resolution date',
    'segn_note_ris'              => 'Resolution notes',

    /* Report errors */
    'segn_err_lab'          => 'Please select a laboratory.',
    'segn_err_titolo'       => 'Please enter a title.',
    'segn_err_titolo_lungo' => 'Title: max 255 characters.',
    'segn_err_desc'         => 'Please enter a problem description.',
    'segn_err_desc_lunga'   => 'Description: max 2000 characters.',

    /* Report success */
    'segn_ok_inviata'   => 'Report submitted successfully!',
    'segn_ok_aggiornata'=> 'Status updated!',

    /* ------------------------------------------------------------------ */
    /* ADMIN — USERS                                                        */
    /* ------------------------------------------------------------------ */
    'utenti_titolo'              => 'User Management',
    'utenti_btn_crea'            => '+ Create User',
    'utenti_form_titolo_crea'    => '+ New User',
    'utenti_form_titolo_mod'     => '✏ Edit User',
    'utenti_nome'                => 'First Name',
    'utenti_cognome'             => 'Last Name',
    'utenti_email'               => 'Email',
    'utenti_email_placeholder'   => 'name@school.edu',
    'utenti_pwd_label'           => 'Password',
    'utenti_pwd_nuova_label'     => 'New Password',
    'utenti_pwd_nuova_hint'      => '(leave blank to keep current)',
    'utenti_pwd_placeholder'     => 'Minimum 6 characters',
    'utenti_ruolo'               => 'Role',
    'utenti_ruolo_docente'       => '📋 Teacher',
    'utenti_ruolo_admin'         => '⚙ Administrator',
    'utenti_telefono'            => 'Phone',
    'utenti_telefono_placeholder'=> 'E.g.: 333-1234567',
    'utenti_telefono_hint'       => 'Format: digits, spaces, +, -, .',
    'utenti_attivo_label'        => 'Active account',
    'utenti_prefisso_label'      => 'Prefix',
    'utenti_nessuno'             => 'No users.',
    'utenti_btn_salva'           => '✔ Save Changes',
    'utenti_col_nome_cognome'    => 'Last Name First Name',
    'utenti_col_email'           => 'Email',
    'utenti_col_ruolo'           => 'Role',
    'utenti_col_telefono'        => 'Phone',
    'utenti_col_stato'           => 'Status',

    /* User errors */
    'utenti_err_nome'       => 'First name is required.',
    'utenti_err_cognome'    => 'Last name is required.',
    'utenti_err_email'      => 'Invalid email address.',
    'utenti_err_pwd'        => 'Password: minimum 6 characters.',
    'utenti_err_tel'        => 'Invalid phone number format.',
    'utenti_err_no_self'    => 'You cannot delete yourself!',
    'utenti_err_email_uso'  => 'Email already in use or DB error',

    /* User success */
    'utenti_ok_creato'      => 'User created!',
    'utenti_ok_aggiornato'  => 'User updated!',
    'utenti_ok_eliminato'   => 'User deleted!',

    /* Password strength */
    'pwd_molto_debole'  => 'Very weak',
    'pwd_debole'        => 'Weak',
    'pwd_accettabile'   => 'Acceptable',
    'pwd_forte'         => 'Strong',
    'pwd_molto_forte'   => 'Very strong',

    /* ------------------------------------------------------------------ */
    /* ADMIN — LABORATORIES                                                 */
    /* ------------------------------------------------------------------ */
    'lab_titolo'                 => 'Laboratory Management',
    'lab_form_titolo_crea'       => '+ New Laboratory',
    'lab_form_titolo_mod'        => '✏ Edit Laboratory',
    'lab_nome'                   => 'Laboratory name',
    'lab_aula'                   => 'Room',
    'lab_aula_placeholder'       => 'E.g.: SR-01',
    'lab_assistente'             => 'Technical Assistant',
    'lab_responsabile'           => 'Responsible',
    'lab_descrizione'            => 'Description',
    'lab_descrizione_placeholder'=> 'Optional description of the laboratory...',
    'lab_attivo_label'           => 'Laboratory active',
    'lab_btn_crea'               => 'Create Laboratory',
    'lab_btn_salva'              => 'Save Changes',
    'lab_nessuno'                => 'No laboratories.',
    'lab_col_nome'               => 'Name',
    'lab_col_aula'               => 'Room',
    'lab_col_assistente'         => 'Assistant',
    'lab_col_responsabile'       => 'Responsible',
    'lab_col_stato'              => 'Status',
    'lab_seleziona_assistente'   => '-- Select --',
    'lab_seleziona_responsabile' => '-- Select --',
    'lab_attivo_badge'           => 'Active',
    'lab_disattivato_badge'      => 'Disabled',

    /* Lab errors */
    'lab_err_campi'   => 'Please fill in all required fields',
    'lab_err_in_uso'  => 'Cannot delete: laboratory is in use',

    /* Lab success */
    'lab_ok_creato'     => 'Laboratory created!',
    'lab_ok_aggiornato' => 'Laboratory updated!',
    'lab_ok_eliminato'  => 'Laboratory deleted!',

    /* ------------------------------------------------------------------ */
    /* ADMIN — CLASSES                                                      */
    /* ------------------------------------------------------------------ */
    'classi_titolo'              => 'Class Management',
    'classi_form_titolo_crea'    => '+ New Class',
    'classi_form_titolo_mod'     => '✏ Edit Class',
    'classi_nome'                => 'Class Name',
    'classi_nome_placeholder'    => 'E.g.: 3A, 4AB',
    'classi_nome_hint'           => 'Format: digit + letters (e.g.: 3A, 4AB, 5INF)',
    'classi_anno'                => 'Academic Year',
    'classi_anno_vuoto'          => '-- Select --',
    'classi_indirizzo'           => 'Study track',
    'classi_indirizzo_vuoto'     => '-- None / Not specified --',
    'classi_attivo_label'        => 'Class active',
    'classi_btn_crea'            => '+ Create Class',
    'classi_btn_salva'           => '✔ Save Changes',
    'classi_nessuna'             => 'No classes.',
    'classi_col_nome'            => 'Name',
    'classi_col_anno'            => 'Year',
    'classi_col_indirizzo'       => 'Track',
    'classi_col_stato'           => 'Status',
    'classi_badge_attiva'        => 'Active',
    'classi_badge_disattivata'   => 'Disabled',

    /* Class errors */
    'classi_err_nome'          => 'Class name is required.',
    'classi_err_nome_formato'  => 'Invalid format (e.g.: 3A, 4AB).',
    'classi_err_anno'          => 'Academic year is required.',
    'classi_err_anno_formato'  => 'Invalid academic year format (e.g.: 2025/2026).',
    'classi_err_anno_seq'      => 'Academic year: the second year must be the next one.',
    'classi_err_gia_esistente' => 'Class already exists for this year',
    'classi_err_in_uso'        => 'Cannot delete: class is in use',

    /* Class success */
    'classi_ok_creata'     => 'Class created!',
    'classi_ok_aggiornata' => 'Class updated!',
    'classi_ok_eliminata'  => 'Class deleted!',

    /* ------------------------------------------------------------------ */
    /* DELETE CONFIRMATIONS                                                 */
    /* ------------------------------------------------------------------ */
    'confirm_elimina_classe'    => 'Delete class %s?',
    'confirm_elimina_lab'       => 'Are you sure you want to delete this laboratory?',
    'confirm_elimina_materiale' => 'Delete material %s?',
    'confirm_elimina_utente'    => 'Delete %s?',

    /* ------------------------------------------------------------------ */
    /* PHONE PREFIXES                                                       */
    /* ------------------------------------------------------------------ */
    'prefissi' => [
        '+39' => '🇮🇹 +39 (Italy)',
        '+1'  => '🇺🇸 +1 (USA/Canada)',
        '+44' => '🇬🇧 +44 (UK)',
        '+33' => '🇫🇷 +33 (France)',
        '+49' => '🇩🇪 +49 (Germany)',
        '+34' => '🇪🇸 +34 (Spain)',
        '+351'=> '🇵🇹 +351 (Portugal)',
        '+41' => '🇨🇭 +41 (Switzerland)',
    ],

    /* ------------------------------------------------------------------ */
    /* TIME SLOTS (entry/exit time dropdowns)                              */
    /* ------------------------------------------------------------------ */
    'orari_placeholder'    => '-- Select time --',
    'orari_label_ingresso' => 'Entry Time',
    'orari_label_uscita'   => 'Exit Time',
    'orari_hint_uscita'    => 'Leave empty if the session is still in progress.',

];
