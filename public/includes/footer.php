<?php
// Determinar la ruta base según la ubicación actual
$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_in_reportes = strpos($current_path, '/reportes/') !== false;
$base_path = $is_in_reportes ? '../' : '';
?>
</main>
<footer>
    <div class="footer-content">
        <p>&copy; <?= date('Y') ?> Comanda. Todos los derechos reservados.</p>
    </div>
</footer>
</body>
</html>
