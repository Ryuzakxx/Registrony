<?php
$parts = explode('/', ltrim(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/'));
define('BASE_PATH', '/' . ($parts[0] ?? ''));
unset($parts);
