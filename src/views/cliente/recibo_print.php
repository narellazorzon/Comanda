<?php
// src/views/cliente/recibo_print.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;
use App\Models\Propina;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pedidoId = isset($_GET['pedido']) ? (int)$_GET['pedido'] : 0;
if ($pedidoId <= 0) {
    echo 'Pedido inválido';
    exit;
}

// Datos del pedido
$pedido = Pedido::find($pedidoId);
$detalles = Pedido::getDetalles($pedidoId);
$propina = Propina::findByPedido($pedidoId);
$subtotal = (float)($pedido['total'] ?? 0);
$propinaMonto = $propina ? (float)$propina['monto'] : 0.0;
$total = $subtotal + $propinaMonto;

$numero = str_pad((string)$pedidoId, 6, '0', STR_PAD_LEFT);
$baseUrl = getBaseUrl();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?="comanda_recibo_{$numero}"?></title>
  <style>
    :root{--prim:#a1866f;--sec:#8b5e46;--text:#3f3f3f;--muted:#6c757d}
    @page{size:auto;margin:12mm}
    body{font:14px/1.5 -apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--text);background:#fff;margin:0}
    .receipt{max-width:760px;margin:0 auto;padding:8px 12px}
    .brand{text-align:center;margin-bottom:8px}
    .brand img{height:42px}
    .brand h1{margin:6px 0 0;font-size:20px;color:var(--prim)}
    .meta{color:var(--muted);font-size:12px;text-align:center;margin-bottom:12px}
    .section-title{margin:8px 0 4px;font-weight:700;color:#111}
    .pago-item{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed #ddd}
    .pago-item-info{display:flex;align-items:center;gap:6px}
    .pago-item-cantidad{font-weight:600;color:var(--prim)}
    .pago-item-precio{font-weight:700;color:var(--sec)}
    .divider{border-top:1px solid #cdb5a3;margin:10px 0}
    .row{display:flex;justify-content:space-between;padding:4px 0}
    .tot{display:flex;justify-content:space-between;margin-top:6px;padding-top:10px;border-top:2px solid var(--prim);font-weight:700}
    .footer{margin-top:14px;text-align:center;color:var(--muted);font-size:12px}
    @media (max-width: 480px){ body{font-size:13px} .brand h1{font-size:18px} }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="brand">
      <img src="<?= $baseUrl ?>/assets/img/logo.png" alt="Comanda">
      <h1>Comanda</h1>
      <div class="meta">
        <strong>Pedido #<?= $numero ?></strong>
        <?php if (!empty($pedido['numero_mesa'])): ?> - Mesa <?= htmlspecialchars($pedido['numero_mesa']) ?><?php endif; ?>
        - <?= date('n/j/Y, g:i:s A') ?>
      </div>
    </div>

    <div class="section-title">Detalle del pedido</div>
    <div class="items">
      <?php foreach ($detalles as $it): 
            $qty = (int)$it['cantidad'];
            $nombre = $it['item_nombre'];
            $lineTotal = number_format($it['cantidad'] * $it['precio_unitario'], 2);
      ?>
      <div class="pago-item">
        <div class="pago-item-info">
          <span class="pago-item-cantidad"><?= $qty ?>x</span>
          <span class="pago-item-nombre"><?= htmlspecialchars($nombre) ?></span>
        </div>
        <span class="pago-item-precio">$<?= $lineTotal ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="divider"></div>
    <div class="row"><span>Subtotal</span><span>$<?= number_format($subtotal,2) ?></span></div>
    <?php if ($propinaMonto > 0): ?>
      <div class="row"><span>Propina</span><span>$<?= number_format($propinaMonto,2) ?></span></div>
    <?php endif; ?>
    <div class="tot"><span>Total</span><span>$<?= number_format($total,2) ?></span></div>

    <div class="footer">¡Gracias por tu preferencia!</div>
  </div>

  <script>
  window.addEventListener('load', () => {
    // asegurar título para nombre sugerido del PDF
    document.title = 'comanda_recibo_<?= $numero ?>';
    try { window.print(); } catch(e) {}
  });
  </script>
</body>
</html>

