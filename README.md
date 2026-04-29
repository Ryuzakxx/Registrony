# 🔬 Registrony del Laboratoriony

> Sistema di gestione dei laboratori scolastici — ITT Enrico Fermi, Francavilla Fontana

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-compatible-FB7A24?style=flat-square&logo=xampp&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)

---

## 📋 Cos'è

**Registrony del Laboratoriony** è un'applicazione web PHP/MySQL per la gestione dei laboratori di un istituto tecnico. Permette di registrare le sessioni di laboratorio, tracciare l'utilizzo dei materiali, raccogliere le firme dei docenti e gestire segnalazioni di problemi alle attrezzature.

---

## ✨ Funzionalità

- **Dashboard** — panoramica delle sessioni odierne, segnalazioni aperte e materiali in esaurimento
- **Sessioni di laboratorio** — registrazione con ora ingresso/uscita, classe, attività svolta e firme docenti (titolare + compresenza)
- **Materiali** — inventario per laboratorio con soglie minime e alert di esaurimento
- **Segnalazioni** — sistema di ticketing con priorità (`bassa` / `media` / `alta` / `urgente`) e stati (`aperta` / `in lavorazione` / `risolta` / `chiusa`)
- **Pannello Admin** — gestione di utenti, laboratori, classi e materiali

---

## 🛠️ Stack tecnologico

| Layer | Tecnologia |
|---|---|
| Backend | PHP 8+ |
| Database | MySQL 8 (via MySQLi procedurale) |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| Server locale | XAMPP |

---

## 📁 Struttura del progetto

```
registrony/
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   └── img/
├── config/
│   ├── app.php          # Configurazione base (BASE_PATH)
│   ├── auth.php         # Autenticazione e sessioni
│   └── database.php     # Connessione MySQLi
├── includes/
│   ├── header.php       # Layout header + sidebar
│   └── footer.php       # Layout footer
|   └── form_helpers.php
├── lang/
|   ├── it.php
├── pages/
│   ├── admin/
│   │   ├── classi.php
│   │   ├── laboratori.php
│   │   ├── materiali.php
│   │   └── utenti.php
│   ├── materiali/
│   │   └── utilizzo.php
│   ├── segnalazioni/
│   │   ├── index.php
│   │   ├── nuova.php
│   │   └── dettaglio.php
│   └── sessioni/
│       ├── index.php
│       ├── nuova.php
│       └── dettaglio.php
├── index.php            # Dashboard
├── login.php
├── logout.php
├── registrony.sql       # Schema + dati iniziali
└── README.md
```

---

## 🚀 Installazione

### Requisiti

- [Git](https://git-scm.com/)
- [Visual Studio Code](https://code.visualstudio.com/)
- [XAMPP](https://www.apachefriends.org/it/index.html) (con Apache e MySQL)

---

### 1. Clonare la repository

Apri Visual Studio Code, premi `Ctrl+Shift+P` e seleziona **Git: Clone**, oppure usa il terminale integrato (`Ctrl+ò`):

```bash
git clone https://github.com/TUO-USERNAME/registrony.git
```

---

### 2. Installare XAMPP

Scarica e installa XAMPP da [apachefriends.org](https://www.apachefriends.org/it/index.html).  
Durante l'installazione assicurati di includere i componenti **Apache** e **MySQL**.

---

### 3. Copiare il progetto in htdocs

Sposta (o copia) la cartella clonata nella directory `htdocs` di XAMPP:

| Sistema operativo | Percorso htdocs |
|---|---|
| Windows | `C:\xampp\htdocs\` |
| macOS | `/Applications/XAMPP/htdocs/` |
| Linux | `/opt/lampp/htdocs/` |

La struttura finale deve essere:

```
C:\xampp\htdocs\registrony\
```

---

### 4. Avviare Apache e MySQL con XAMPP

Apri il **XAMPP Control Panel** e clicca **Start** su:

- ✅ **Apache**
- ✅ **MySQL**

Verifica che entrambi i servizi siano verdi prima di procedere.

---

### 5. Importare il database su phpMyAdmin

1. Apri il browser e vai su **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**
2. Clicca su **Nuovo** nel pannello sinistro per creare un database (oppure lascia fare al file SQL)
3. Vai su **Importa** nella barra in alto
4. Clicca su **Scegli file** e seleziona il file `registrony.sql` dalla cartella del progetto
5. Clicca su **Esegui**

> ⚠️ Se phpMyAdmin restituisce un errore durante l'import, apri prima la scheda **SQL** e lancia il comando:
> ```sql
> SET sql_mode = '';
> ```
> Poi ritenta l'importazione.

---

### 6. Aprire l'applicazione

Apri il browser e vai su:

```
http://localhost/registrony
```

> 💡 Se hai rinominato la cartella del progetto, sostituisci `registrony` con il nome che hai scelto. Il `BASE_PATH` viene rilevato automaticamente.

---

## 🔑 Credenziali di accesso (dati iniziali)

| Utente | Email | Password | Ruolo |
|---|---|---|---|
| Daniele Signorile | daniele.signorile@itsff.it | `cambiami2026` | Admin |
| Mario Rossi | mario.rossi@scuola.it | `admin123` | Admin |
| Luigi Bianchi | luigi.bianchi@scuola.it | `admin456` | Admin |
| Elena Torricelli | elena.torricelli@itsff.it | `tecnico2026` | Admin |
| Roberto Boyle | roberto.boyle@itsff.it | `docente1` | Docente |

> ⚠️ Cambia le password predefinite dopo il primo accesso tramite il pannello **Admin → Utenti**.

---

## 📌 Note tecniche

- Il `BASE_PATH` viene rilevato automaticamente — la repo funziona con qualsiasi nome di cartella sotto `htdocs/`
- Il pannello Admin è visibile solo agli utenti con ruolo `admin`
- Il trigger MySQL `trg_firme_max_due_insert` limita a 2 le firme per sessione
- Il trigger `trg_aggiorna_quantita_materiale` aggiorna automaticamente la giacenza dopo ogni utilizzo
- La connessione al database usa **MySQLi procedurale** (non PDO)

---

## 👥 Collaboratori

| Nome | Ruolo |
|---|---|
| **Francesco Moretto** | Sviluppatore |
| **Daniele Signorile** | Sviluppatore |
| **Patrick Colucci** | Sviluppatore |
| **Simone Moretto** | Sviluppatore |

---

*Progetto scolastico — ITT Enrico Fermi, Francavilla Fontana — A.S. 2025/2026*
