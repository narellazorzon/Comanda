<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determinar la ruta base según la ubicación actual
$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_in_reportes = strpos($current_path, '/reportes/') !== false;
$base_path = $is_in_reportes ? '../' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comanda</title>
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<main>
