<?php
require_once __DIR__ . '/config/auth.php';

// Se già loggato, vai alla dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Inserisci email e password.';
    } elseif (login($email, $password)) {
        $ruolo  = $_SESSION['user_ruolo'];
        $userId = (int)$_SESSION['user_id'];

        if ($ruolo === 'docente') {
            $labs = getDocenteLabs($userId);

            if (count($labs) === 0) {
                /*
                 * Nessun lab assegnato.
                 * NON usare logout() perché fa header(Location) + exit
                 * prima che $error venga mostrato.
                 * Svuotiamo la sessione manualmente e mostriamo il messaggio.
                 */
                session_unset();
                $error = "Il tuo account non ha laboratori assegnati. Contatta l'amministratore.";
            } elseif (count($labs) === 1) {
                setSelectedLabId((int)$labs[0]['id']);
                header('Location: ' . BASE_PATH . '/index.php');
                exit;
            } else {
                header('Location: ' . BASE_PATH . '/pages/seleziona_laboratorio.php');
                exit;
            }
        } else {
            // Admin e Tecnico → dashboard
            header('Location: ' . BASE_PATH . '/index.php');
            exit;
        }
    } else {
        $error = 'Email o password non validi, oppure account disattivato.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Registrony del Laboratoriony</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="logo">
            <div class="icon">&#128300;</div>
            <h1>Registrony</h1>
            <p>del Laboratoriony</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <button class="alert-close" onclick="this.closest('.alert').remove()" aria-label="Chiudi">&times;</button>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="nome@scuola.it"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="La tua password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 8px;">
                Accedi
            </button>
        </form>
    </div>
</div>
</body>
</html>
