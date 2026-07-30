<?php
// Diagnostic tool - remove after fixing
$errors = [];
$warnings = [];

// PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $errors[] = "PHP 8.0+ requerido (actual: " . PHP_VERSION . ")";
}

// Extensions
foreach (['curl', 'gd', 'mbstring', 'json'] as $ext) {
    if (!extension_loaded($ext)) $errors[] = "Extensión faltante: $ext";
}

// Vendor (TCPDF)
$tcpdf = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdf)) {
    $errors[] = "Falta vendor/ — Ejecutá: composer install";
}

// Config
$cfg = __DIR__ . '/config.php';
if (!file_exists($cfg)) {
    $errors[] = "Falta config.php — Copiá config.example.php a config.php";
} else {
    require_once $cfg;
    if (str_contains(GROQ_API_KEY, 'tu-api-key') || str_contains(GROQ_API_KEY, 'gsk_')) {
        $warnings[] = "GROQ_API_KEY parece placeholder o la anterior";
    }
}

// Permissions
$dirs = [__DIR__ . '/generados', __DIR__ . '/assets'];
foreach ($dirs as $d) {
    if (!is_dir($d)) @mkdir($d, 0777, true);
    if (!is_writable($d)) $errors[] = "No hay permiso de escritura en: $d";
}

header('Content-Type: text/plain');
if ($errors) {
    echo "ERRORES:\n";
    foreach ($errors as $e) echo "  - $e\n";
}
if ($warnings) {
    echo "ADVERTENCIAS:\n";
    foreach ($warnings as $w) echo "  - $w\n";
}
if (!$errors && !$warnings) {
    echo "TODO OK — El sistema deberia funcionar\n";
}
