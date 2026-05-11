<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin(); // non entrerà in loop perché cambia_password.php è escluso dal check

$error   = '';
$success = '';
$userId  = (int)getCurrentUserId();
$conn    = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuova   = $_POST['nuova_password']     ?? '';
    $conferma = $_POST['conferma_password'] ?? '';

    if (strlen($nuova) < 8) {
        $error = 'La password deve essere di almeno 8 caratteri.';
    } elseif (!preg_match('/[A-Z]/', $nuova)) {
        $error = 'La password deve contenere almeno una lettera maiuscola.';
    } elseif (!preg_match('/[0-9]/', $nuova)) {
        $error = 'La password deve contenere almeno un numero.';
    } elseif ($nuova !== $conferma) {
        $error = 'Le due password non coincidono.';
    } else {
        $hash = mysqli_real_escape_string($conn, password_hash($nuova, PASSWORD_BCRYPT));
        $ok   = mysqli_query($conn,
            "UPDATE utenti SET password = '$hash', must_change_password = 0 WHERE id = $userId"
        );
        if ($ok) {
            $_SESSION['must_change_password'] = false;
            $success = 'Password aggiornata con successo! Verrai reindirizzato...';
        } else {
            $error = 'Errore nel salvataggio. Riprova.';
        }
    }
}

$pageTitle = 'Imposta la tua password';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Registrony</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/responsive.css">
    <style>
        .change-pw-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f1e30 0%, #1e3a5f 60%, #2563eb 100%);
            padding: 20px;
        }
        .change-pw-card {
            background: #fff;
            border-radius: 14px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }
        .change-pw-icon {
            width: 64px; height: 64px;
            background: #dbeafe;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .change-pw-card h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1e3a5f;
            text-align: center;
            margin-bottom: 6px;
        }
        .change-pw-card .subtitle {
            font-size: .85rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.5;
        }
        .pw-req {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 18px;
            font-size: .8rem;
            color: #475569;
        }
        .pw-req ul { margin: 4px 0 0 16px; }
        .pw-req li { margin-bottom: 2px; }
        .pw-strength {
            height: 4px;
            border-radius: 4px;
            background: #e2e8f0;
            margin-top: 6px;
            transition: all .3s;
            overflow: hidden;
        }
        .pw-strength-bar {
            height: 100%;
            border-radius: 4px;
            width: 0;
            transition: width .3s, background .3s;
        }
    </style>
</head>
<body>
<div class="change-pw-page">
    <div class="change-pw-card">
        <div class="change-pw-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                 fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <h2>Imposta la tua password</h2>
        <p class="subtitle">
            Benvenuto/a! Prima di continuare devi creare una password personale sicura.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:16px">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" id="successMsg">
                <?= htmlspecialchars($success) ?>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '<?= BASE_PATH ?>/index.php';
                }, 1800);
            </script>
        <?php else: ?>

        <div class="pw-req">
            <strong>Requisiti password:</strong>
            <ul>
                <li>Minimo 8 caratteri</li>
                <li>Almeno una lettera maiuscola</li>
                <li>Almeno un numero</li>
            </ul>
        </div>

        <form method="POST" action="" id="changePwForm">
            <div class="form-group">
                <label for="nuova_password">Nuova password</label>
                <input type="password" id="nuova_password" name="nuova_password"
                       class="form-control" placeholder="Crea la tua password"
                       required autocomplete="new-password" minlength="8">
                <div class="pw-strength"><div class="pw-strength-bar" id="strengthBar"></div></div>
            </div>
            <div class="form-group" style="margin-top:14px">
                <label for="conferma_password">Conferma password</label>
                <input type="password" id="conferma_password" name="conferma_password"
                       class="form-control" placeholder="Ripeti la password"
                       required autocomplete="new-password" minlength="8">
                <div id="matchMsg" style="font-size:.78rem;margin-top:4px;height:16px"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:18px">
                Salva password e accedi
            </button>
        </form>

        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var inp    = document.getElementById('nuova_password');
    var conf   = document.getElementById('conferma_password');
    var bar    = document.getElementById('strengthBar');
    var matchM = document.getElementById('matchMsg');
    if (!inp || !bar) return;

    function calcStrength(pw) {
        var score = 0;
        if (pw.length >= 8)  score++;
        if (pw.length >= 12) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return score; // 0-5
    }

    inp.addEventListener('input', function () {
        var s = calcStrength(inp.value);
        var pct   = Math.min(100, s * 20) + '%';
        var color = s <= 1 ? '#ef4444' : s <= 2 ? '#f59e0b' : s <= 3 ? '#3b82f6' : '#16a34a';
        bar.style.width     = pct;
        bar.style.background = color;
        checkMatch();
    });

    conf.addEventListener('input', checkMatch);

    function checkMatch() {
        if (!conf.value) { matchM.textContent = ''; return; }
        if (inp.value === conf.value) {
            matchM.textContent = '✓ Le password coincidono';
            matchM.style.color = '#16a34a';
        } else {
            matchM.textContent = '✗ Le password non coincidono';
            matchM.style.color = '#ef4444';
        }
    }
})();
</script>
</body>
</html>
