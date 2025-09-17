<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comanda</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/modal-confirmacion.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/responsive-fixes.css?v=<?= time() ?>">
    <script src="assets/js/modal-confirmacion.js?v=<?= time() ?>"></script>
    <script src="js/mobile-enhancements.js?v=<?= time() ?>" defer></script>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>