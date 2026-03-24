<?php
require_once __DIR__ . '/config/auth.php';

// Se gia' loggato, vai alla dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Inserisci email e password.';
    } elseif (login($email, $password)) {
        header('Location: ' . BASE_PATH . '/index.php');
        exit;
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
    <link rel="stylesheet" href="/assets/css/style.css">
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
            <div class="alert alert-danger">&#10060; <?= htmlspecialchars($error) ?></div>
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
