<?php
/**
 * SCRIPT DI MIGRAZIONE PASSWORD
 * ================================
 * Esegui questo script UNA SOLA VOLTA dal browser o da terminale
 * per hashare tutte le password in chiaro gia' presenti nel database.
 *
 * Accesso: http://localhost/registrony/scripts/migrate_passwords.php
 * ELIMINA o SPOSTA questo file dopo l'esecuzione!
 */

define('MIGRATION_SECRET', 'cambia_questa_chiave_prima_di_usarla');

// Protezione minima: chiave segreta in GET
if (($_GET['key'] ?? '') !== MIGRATION_SECRET) {
    http_response_code(403);
    die('Accesso negato. Usa: ?key=cambia_questa_chiave_prima_di_usarla');
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$conn = getConnection();
$result = mysqli_query($conn, "SELECT id, email, password FROM utenti");

$migrated = 0;
$skipped  = 0;
$errors   = [];

while ($user = mysqli_fetch_assoc($result)) {
    // Salta le password gia' hashate con bcrypt
    if (str_starts_with($user['password'], '$2y$') || str_starts_with($user['password'], '$2b$')) {
        $skipped++;
        continue;
    }

    // Hasha la password in chiaro
    $hash = password_hash($user['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = mysqli_prepare($conn, "UPDATE utenti SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $hash, $user['id']);

    if (mysqli_stmt_execute($stmt)) {
        $migrated++;
        echo "[OK] Utente {$user['email']} migrato.\n";
    } else {
        $errors[] = $user['email'];
        echo "[ERR] Errore per {$user['email']}\n";
    }
    mysqli_stmt_close($stmt);
}

echo "\n================================\n";
echo "Migrati:  $migrated\n";
echo "Saltati (gia' hashati): $skipped\n";
echo "Errori:   " . count($errors) . "\n";
echo "================================\n";
echo "RICORDA: elimina questo file dopo l'uso!\n";
