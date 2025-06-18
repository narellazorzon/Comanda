<?php require_once __DIR__ . '/../includes/header.php'; ?>

<h2>Propinas por Mozo (<?= htmlspecialchars($_GET['mes'] ?? date('Y-m')) ?>)</h2>

<table>
  <tr><th>Mozo</th><th>Monto</th></tr>
  <?php foreach ($tips as $t): ?>
    <tr>
      <td><?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido']) ?></td>
      <td>$<?= number_format($t['monto'], 2) ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
