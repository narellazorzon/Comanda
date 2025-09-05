<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determinar la ruta base del proyecto
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);

// Ruta absoluta para CSS
$css_path = $base_url . '/assets/css/style.css';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comanda</title>
    <link rel="stylesheet" href="<?= $css_path ?>?v=<?= time() ?>">
    <!-- Modal de confirmación -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/modal-confirmacion.css?v=<?= time() ?>">
    <script src="<?= $base_url ?>/assets/js/modal-confirmacion.js?v=<?= time() ?>"></script>
    <script></script>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<main>
