<?php
// src/views/cliente/pago.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;
use App\Models\Mesa;
use App\Models\DetallePedido;
use App\Config\ClientSession;

// Usar sesión de cliente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegurar contexto de cliente
if (!ClientSession::isClientContext()) {
    ClientSession::initClientSession();
}

// Obtener ID del pedido desde GET o sesión
$idPedido = $_GET['pedido'] ?? $_SESSION['ultimo_pedido_id'] ?? null;

if (!$idPedido) {
    header('Location: ' . url('cliente'));
    exit;
}

// Obtener detalles del pedido
$pedido = Pedido::findWithDetails($idPedido);
if (!$pedido) {
    header('Location: ' . url('cliente'));
    exit;
}

// Obtener items del pedido
$items = DetallePedido::getByPedido($idPedido);

// Calcular totales con diferentes porcentajes de propina
$opcionesPropina = [
    ['porcentaje' => 0, 'label' => 'Sin propina'],
    ['porcentaje' => 10, 'label' => '10%'],
    ['porcentaje' => 15, 'label' => '15%'],
    ['porcentaje' => 20, 'label' => '20%'],
    ['porcentaje' => 'custom', 'label' => 'Otro monto']
];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proceso de Pago - Sistema Comanda</title>
    
    <style>
    :root {
        --color-fondo: #f7f1e1;
        --color-superficie: #ffffff;
        --color-primario: #a1866f;
        --color-secundario: #8b5e46;
        --color-acento: #007BFF;
        --color-exito: #28a745;
        --color-peligro: #dc3545;
        --color-texto: #3f3f3f;
        --color-texto-suave: #6c757d;
        --sombra-suave: 0 2px 8px rgba(0,0,0,0.08);
        --sombra-media: 0 4px 12px rgba(0,0,0,0.12);
        --sombra-fuerte: 0 8px 24px rgba(0,0,0,0.15);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f7f1e1 0%, #f0e8d8 100%);
        color: var(--color-texto);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .payment-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--sombra-fuerte);
        overflow: hidden;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .payment-header {
        background: linear-gradient(135deg, var(--color-primario) 0%, var(--color-secundario) 100%);
        color: white;
        padding: 2rem;
    }

    .payment-header h1 {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }

    .payment-header p {
        opacity: 0.9;
        font-size: 0.95rem;
    }

    .payment-body {
        padding: 2rem;
    }

    /* Sección de resumen del pedido */
    .order-summary {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .order-summary h2 {
        font-size: 1.25rem;
        color: var(--color-secundario);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .order-items {
        margin-bottom: 1rem;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .item-name {
        flex: 1;
        font-size: 0.95rem;
    }

    .item-qty {
        color: var(--color-texto-suave);
        margin: 0 1rem;
        font-size: 0.9rem;
    }

    .item-price {
        font-weight: 600;
        color: var(--color-texto);
    }

    .order-total {
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 2px solid var(--color-primario);
        font-size: 1.1rem;
        font-weight: 700;
    }

    /* Sección de propinas */
    .tip-section {
        margin-bottom: 2rem;
    }

    .tip-section h2 {
        font-size: 1.25rem;
        color: var(--color-secundario);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tip-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .tip-button {
        background: white;
        border: 2px solid var(--color-primario);
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        position: relative;
    }

    .tip-button:hover {
        transform: translateY(-2px);
        box-shadow: var(--sombra-media);
    }

    .tip-button.selected {
        background: var(--color-primario);
        color: white;
        border-color: var(--color-secundario);
    }

    .tip-button.selected::after {
        content: '✓';
        position: absolute;
        top: 0.25rem;
        right: 0.5rem;
        font-size: 1.2rem;
    }

    .tip-percentage {
        font-size: 1.5rem;
        font-weight: 700;
        display: block;
        margin-bottom: 0.25rem;
    }

    .tip-amount {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    /* Campo personalizado de propina */
    .custom-tip-container {
        display: none;
        margin-top: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .custom-tip-container.show {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            max-height: 0;
        }
        to {
            opacity: 1;
            max-height: 200px;
        }
    }

    .custom-tip-input {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .custom-tip-input label {
        font-weight: 600;
        color: var(--color-texto);
    }

    .custom-tip-input input {
        flex: 1;
        padding: 0.75rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .custom-tip-input input:focus {
        outline: none;
        border-color: var(--color-primario);
        box-shadow: 0 0 0 3px rgba(161, 134, 111, 0.1);
    }

    /* Resumen final con propina */
    .final-summary {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border: 2px solid var(--color-primario);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .final-summary h3 {
        font-size: 1.1rem;
        color: var(--color-secundario);
        margin-bottom: 1rem;
    }

    .summary-line {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        font-size: 0.95rem;
    }

    .summary-line.tip {
        color: var(--color-exito);
    }

    .summary-line.total {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--color-secundario);
        border-top: 2px solid var(--color-primario);
        margin-top: 0.5rem;
        padding-top: 1rem;
    }

    /* Información del mozo */
    .waiter-info {
        background: #e8f5e8;
        border: 1px solid #c8e6c9;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .waiter-icon {
        font-size: 2rem;
    }

    .waiter-details {
        flex: 1;
    }

    .waiter-name {
        font-weight: 600;
        color: var(--color-texto);
        margin-bottom: 0.25rem;
    }

    .waiter-message {
        font-size: 0.875rem;
        color: var(--color-texto-suave);
    }

    /* Métodos de pago */
    .payment-methods {
        margin-bottom: 2rem;
    }

    .payment-methods h2 {
        font-size: 1.25rem;
        color: var(--color-secundario);
        margin-bottom: 1rem;
    }

    .payment-method-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .payment-method {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .payment-method:hover {
        border-color: var(--color-primario);
        transform: translateY(-2px);
        box-shadow: var(--sombra-suave);
    }

    .payment-method.selected {
        background: var(--color-primario);
        color: white;
        border-color: var(--color-secundario);
    }

    .payment-method-icon {
        font-size: 1.5rem;
    }

    .payment-method-name {
        font-weight: 600;
    }

    /* Botones de acción */
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: space-between;
    }

    .btn {
        padding: 1rem 2rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        flex: 1;
    }

    .btn-secondary {
        background: #e9ecef;
        color: var(--color-texto);
    }

    .btn-secondary:hover {
        background: #dee2e6;
    }

    .btn-primary {
        background: var(--color-acento);
        color: white;
    }

    .btn-primary:hover:not(:disabled) {
        background: #0056b3;
        transform: translateY(-2px);
        box-shadow: var(--sombra-media);
    }

    .btn-primary:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    /* Toast de confirmación */
    .toast {
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: var(--color-exito);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: var(--sombra-fuerte);
        font-weight: 600;
        display: none;
        animation: slideIn 0.4s ease;
        z-index: 1000;
    }

    .toast.show {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .tip-options {
            grid-template-columns: repeat(2, 1fr);
        }

        .payment-method-options {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h1>💳 Proceso de Pago</h1>
                <p>Completa tu pedido y añade una propina si lo deseas</p>
            </div>

            <div class="payment-body">
                <!-- Resumen del pedido -->
                <div class="order-summary">
                    <h2>📋 Resumen del Pedido #<?= htmlspecialchars($idPedido) ?></h2>
                    
                    <?php if ($pedido['numero_mesa']): ?>
                    <div style="margin-bottom: 1rem; color: var(--color-texto-suave);">
                        <strong>Mesa <?= htmlspecialchars($pedido['numero_mesa']) ?></strong>
                        <?php if ($pedido['ubicacion_mesa']): ?>
                            - <?= htmlspecialchars($pedido['ubicacion_mesa']) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="order-items">
                        <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <span class="item-name"><?= htmlspecialchars($item['nombre']) ?></span>
                            <span class="item-qty">x<?= htmlspecialchars($item['cantidad']) ?></span>
                            <span class="item-price">$<?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-total">
                        <span>Subtotal:</span>
                        <span id="subtotal">$<?= number_format($pedido['total'], 2) ?></span>
                    </div>
                </div>

                <!-- Información del mozo -->
                <?php if ($pedido['nombre_mozo_completo']): ?>
                <div class="waiter-info">
                    <div class="waiter-icon">👨‍🍳</div>
                    <div class="waiter-details">
                        <div class="waiter-name">Tu mozo: <?= htmlspecialchars($pedido['nombre_mozo_completo']) ?></div>
                        <div class="waiter-message">Si el servicio fue de tu agrado, considera dejar una propina</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sección de propinas -->
                <div class="tip-section">
                    <h2>💰 ¿Deseas añadir una propina?</h2>
                    
                    <div class="tip-options">
                        <?php foreach ($opcionesPropina as $opcion): ?>
                            <?php if ($opcion['porcentaje'] !== 'custom'): ?>
                                <?php 
                                    $calculo = Pedido::calcularTotalConPropina($idPedido, $opcion['porcentaje']);
                                ?>
                                <div class="tip-button" 
                                     data-percentage="<?= $opcion['porcentaje'] ?>"
                                     data-amount="<?= $calculo['propina'] ?>"
                                     onclick="selectTip(this, <?= $opcion['porcentaje'] ?>)">
                                    <span class="tip-percentage"><?= $opcion['label'] ?></span>
                                    <?php if ($opcion['porcentaje'] > 0): ?>
                                        <span class="tip-amount">$<?= number_format($calculo['propina'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="tip-button" onclick="showCustomTip(this)">
                                    <span class="tip-percentage">💵</span>
                                    <span class="tip-amount"><?= $opcion['label'] ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Campo personalizado -->
                    <div id="custom-tip-container" class="custom-tip-container">
                        <div class="custom-tip-input">
                            <label for="custom-tip-amount">Monto de propina:</label>
                            <input type="number" 
                                   id="custom-tip-amount" 
                                   placeholder="0.00" 
                                   min="0" 
                                   step="0.01"
                                   onchange="updateCustomTip(this.value)">
                        </div>
                    </div>
                </div>

                <!-- Resumen final con propina -->
                <div class="final-summary">
                    <h3>Total a Pagar</h3>
                    <div class="summary-line">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($pedido['total'], 2) ?></span>
                    </div>
                    <div class="summary-line tip" id="tip-line" style="display: none;">
                        <span>Propina:</span>
                        <span id="tip-amount">$0.00</span>
                    </div>
                    <div class="summary-line total">
                        <span>Total Final:</span>
                        <span id="final-total">$<?= number_format($pedido['total'], 2) ?></span>
                    </div>
                </div>

                <!-- Métodos de pago -->
                <div class="payment-methods">
                    <h2>💳 Método de Pago</h2>
                    <div class="payment-method-options">
                        <div class="payment-method" onclick="selectPaymentMethod(this, 'efectivo')">
                            <span class="payment-method-icon">💵</span>
                            <span class="payment-method-name">Efectivo</span>
                        </div>
                        <div class="payment-method" onclick="selectPaymentMethod(this, 'tarjeta')">
                            <span class="payment-method-icon">💳</span>
                            <span class="payment-method-name">Tarjeta</span>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="cancelPayment()">
                        Cancelar
                    </button>
                    <button class="btn btn-primary" id="confirm-payment" onclick="confirmPayment()" disabled>
                        Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast de confirmación -->
    <div id="toast" class="toast">
        <span>✓</span>
        <span id="toast-message">Pago procesado exitosamente</span>
    </div>

    <script>
    // Variables globales
    let selectedTipPercentage = 0;
    let selectedTipAmount = 0;
    let selectedPaymentMethod = null;
    const subtotal = <?= $pedido['total'] ?>;
    const pedidoId = <?= $idPedido ?>;
    const mozoId = <?= $pedido['id_mozo'] ?? 'null' ?>;

    // Seleccionar propina predefinida
    function selectTip(button, percentage) {
        // Limpiar selección anterior
        document.querySelectorAll('.tip-button').forEach(btn => {
            btn.classList.remove('selected');
        });
        
        // Ocultar campo personalizado
        document.getElementById('custom-tip-container').classList.remove('show');
        document.getElementById('custom-tip-amount').value = '';
        
        // Marcar como seleccionado
        button.classList.add('selected');
        
        // Actualizar valores
        selectedTipPercentage = percentage;
        selectedTipAmount = parseFloat(button.dataset.amount || 0);
        
        // Actualizar resumen
        updateSummary();
    }

    // Mostrar campo de propina personalizada
    function showCustomTip(button) {
        // Limpiar selección anterior
        document.querySelectorAll('.tip-button').forEach(btn => {
            btn.classList.remove('selected');
        });
        
        // Marcar como seleccionado
        button.classList.add('selected');
        
        // Mostrar campo personalizado
        document.getElementById('custom-tip-container').classList.add('show');
        document.getElementById('custom-tip-amount').focus();
    }

    // Actualizar propina personalizada
    function updateCustomTip(value) {
        selectedTipPercentage = 'custom';
        selectedTipAmount = parseFloat(value) || 0;
        updateSummary();
    }

    // Seleccionar método de pago
    function selectPaymentMethod(element, method) {
        // Limpiar selección anterior
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Marcar como seleccionado
        element.classList.add('selected');
        selectedPaymentMethod = method;
        
        // Habilitar botón de confirmación
        validateForm();
    }

    // Actualizar resumen
    function updateSummary() {
        const tipLine = document.getElementById('tip-line');
        const tipAmountEl = document.getElementById('tip-amount');
        const finalTotalEl = document.getElementById('final-total');
        
        if (selectedTipAmount > 0) {
            tipLine.style.display = 'flex';
            tipAmountEl.textContent = `$${selectedTipAmount.toFixed(2)}`;
        } else {
            tipLine.style.display = 'none';
        }
        
        const finalTotal = subtotal + selectedTipAmount;
        finalTotalEl.textContent = `$${finalTotal.toFixed(2)}`;
        
        validateForm();
    }

    // Validar formulario
    function validateForm() {
        const confirmBtn = document.getElementById('confirm-payment');
        confirmBtn.disabled = !selectedPaymentMethod;
    }

    // Cancelar pago
    function cancelPayment() {
        if (confirm('¿Estás seguro de que deseas cancelar el proceso de pago?')) {
            window.location.href = 'index.php?route=cliente<?= isset($_SESSION['mesa_qr']) ? '&mesa=' . $_SESSION['mesa_qr'] : '' ?>';
        }
    }

    // Confirmar pago
    async function confirmPayment() {
        if (!selectedPaymentMethod) {
            alert('Por favor selecciona un método de pago');
            return;
        }
        
        // Deshabilitar botón
        const confirmBtn = document.getElementById('confirm-payment');
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Procesando...';
        
        try {
            // Preparar datos
            const data = new FormData();
            data.append('pedido_id', pedidoId);
            data.append('propina_monto', selectedTipAmount);
            data.append('mozo_id', mozoId);
            data.append('metodo_pago', selectedPaymentMethod);
            
            // Enviar solicitud
            const response = await fetch('index.php?route=cliente/procesar-pago', {
                method: 'POST',
                body: data
            });
            
            if (response.ok) {
                // Mostrar toast de éxito
                showToast('✅ Pago procesado exitosamente. ¡Gracias por tu propina!');
                
                // Redirigir después de 2 segundos manteniendo el QR
                setTimeout(() => {
                    window.location.href = 'index.php?route=cliente<?= isset($_SESSION['mesa_qr']) ? '&mesa=' . $_SESSION['mesa_qr'] : '' ?>';
                }, 2000);
            } else {
                throw new Error('Error al procesar el pago');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Hubo un error al procesar el pago. Por favor intenta nuevamente.');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirmar Pago';
        }
    }

    // Mostrar toast
    function showToast(message) {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        
        toastMessage.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Inicialización
    document.addEventListener('DOMContentLoaded', () => {
        // Seleccionar "Sin propina" por defecto
        const noTipButton = document.querySelector('[data-percentage="0"]');
        if (noTipButton) {
            selectTip(noTipButton, 0);
        }
    });
    </script>
</body>
</html>