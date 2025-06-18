<?php require_once __DIR__ . '/../includes/header.php'; ?>

<h2>Recaudación Mensual (<?= htmlspecialchars($_GET['mes'] ?? date('Y-m')) ?>)</h2>

<?php if (empty($recaud) || $recaud[0]['total'] === null): ?>
  <p>No hay ingresos registrados para este mes.</p>
<?php else: ?>
  <p>Total recaudado: $<?= number_format($recaud[0]['total'], 2) ?></p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
