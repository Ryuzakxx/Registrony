<?php
/**
 * Configurazione Database MySQL - MySQLi procedurale
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'registrony');
define('DB_USER', 'root');
define('DB_PASS', '');

function getConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            die("Errore connessione database: " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, 'utf8mb4');
    }
    return $conn;
}