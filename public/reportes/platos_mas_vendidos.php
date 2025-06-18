<?php require_once __DIR__ . '/../includes/header.php'; ?>

<h2>Platos más vendidos</h2>

<table>
  <tr><th>Plato</th><th>Cantidad</th></tr>
  <?php foreach ($platos as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['nombre']) ?></td>
      <td><?= $p['cantidad'] ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
