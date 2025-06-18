<?php
// alta_mesa.php
require_once __DIR__ . '/includes/header.php';
?>

<h2><?= isset($mesa) ? 'Modificar Mesa' : 'Alta de Mesa' ?></h2>

<form method="post">
  <label>Número:</label>
  <input type="number" name="numero" required value="<?= $mesa['numero'] ?? '' ?>">

  <label>Ubicación:</label>
  <input type="text" name="ubicacion" value="<?= $mesa['ubicacion'] ?? '' ?>">

  <?php if (isset($mesa)): ?>
    <label>Estado:</label>
    <select name="estado">
      <option value="libre"    <?= $mesa['estado']=='libre'   ? 'selected':'' ?>>Libre</option>
      <option value="ocupada"  <?= $mesa['estado']=='ocupada' ? 'selected':'' ?>>Ocupada</option>
    </select>
  <?php endif ?>

  <button type="submit"><?= isset($mesa) ? 'Guardar cambios' : 'Crear mesa' ?></button>
</form>

<?php
require_once __DIR__ . '/includes/footer.php';
