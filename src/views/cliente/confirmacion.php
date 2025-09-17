<?php
// src/views/cliente/confirmacion.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;
use App\Models\Propina;
use App\Config\ClientSession;

// Usar sesión de cliente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegurar contexto de cliente
if (!ClientSession::isClientContext()) {
    ClientSession::initClientSession();
}

// Asegurarse de que no hay sesión de administrador activa
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

$pedidoId = $_GET['pedido'] ?? null;
if (!$pedidoId) {
    header('Location: ' . url('cliente'));
    exit;
}

// Obtener información del pedido
$pedido = Pedido::findWithDetails($pedidoId);
if (!$pedido) {
    header('Location: ' . url('cliente'));
    exit;
}

// Obtener información de la propina si existe
$propina = Propina::findByPedido($pedidoId);

// Calcular total final
$totalFinal = $pedido['total'] + ($propina ? $propina['monto'] : 0);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Confirmado - Sistema Comanda</title>
    
    <style>
    :root {
        --color-fondo: #f7f1e1;
        --color-superficie: #ffffff;
        --color-primario: #a1866f;
        --color-secundario: #8b5e46;
        --color-exito: #28a745;
        --color-texto: #3f3f3f;
        --color-texto-suave: #6c757d;
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
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .confirmation-card {
        background: white;
        border-radius: 20px;
        box-shadow: var(--sombra-fuerte);
        max-width: 500px;
        width: 100%;
        overflow: hidden;
        animation: scaleIn 0.5s ease;
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .confirmation-header {
        background: linear-gradient(135deg, var(--color-exito) 0%, #20863a 100%);
        color: white;
        padding: 3rem 2rem;
        text-align: center;
    }

    .success-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        animation: checkmark 0.8s ease;
    }

    @keyframes checkmark {
        0% {
            transform: scale(0) rotate(45deg);
        }
        50% {
            transform: scale(1.2) rotate(-5deg);
        }
        100% {
            transform: scale(1) rotate(0);
        }
    }

    .confirmation-title {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }

    .confirmation-subtitle {
        opacity: 0.95;
        font-size: 1rem;
    }

    .confirmation-body {
        padding: 2rem;
    }

    .order-info {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        color: var(--color-texto-suave);
        font-size: 0.95rem;
    }

    .info-value {
        font-weight: 600;
        color: var(--color-texto);
    }

    .info-row.total {
        margin-top: 0.5rem;
        padding-top: 1rem;
        border-top: 2px solid var(--color-primario);
        font-size: 1.1rem;
    }

    .thank-you-message {
        background: linear-gradient(145deg, #e8f5e8, #c8e6c9);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .thank-you-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .thank-you-text {
        color: var(--color-exito);
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .thank-you-detail {
        color: var(--color-texto-suave);
        font-size: 0.9rem;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn {
        flex: 1;
        padding: 1rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        display: inline-block;
    }

    .btn-primary {
        background: var(--color-primario);
        color: white;
    }

    .btn-primary:hover {
        background: var(--color-secundario);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn-secondary {
        background: #e9ecef;
        color: var(--color-texto);
    }

    .btn-secondary:hover {
        background: #dee2e6;
    }

    .receipt-note {
        text-align: center;
        color: var(--color-texto-suave);
        font-size: 0.875rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e9ecef;
    }

    /* Animación de confeti */
    .confetti {
        position: fixed;
        width: 10px;
        height: 10px;
        background: var(--color-exito);
        position: absolute;
        animation: confetti-fall 3s ease-out;
    }

    @keyframes confetti-fall {
        0% {
            transform: translateY(-100vh) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="confirmation-header">
            <div class="success-icon">✅</div>
            <h1 class="confirmation-title">¡Pago Confirmado!</h1>
            <p class="confirmation-subtitle">Tu pedido ha sido procesado exitosamente</p>
        </div>

        <div class="confirmation-body">
            <!-- Información del pedido -->
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">Número de Pedido:</span>
                    <span class="info-value">#<?= str_pad($pedidoId, 6, '0', STR_PAD_LEFT) ?></span>
                </div>
                
                <?php if ($pedido['numero_mesa']): ?>
                <div class="info-row">
                    <span class="info-label">Mesa:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['numero_mesa']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($pedido['nombre_mozo_completo']): ?>
                <div class="info-row">
                    <span class="info-label">Atendido por:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['nombre_mozo_completo']) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="info-label">Subtotal:</span>
                    <span class="info-value">$<?= number_format($pedido['total'], 2) ?></span>
                </div>
                
                <?php if ($propina && $propina['monto'] > 0): ?>
                <div class="info-row">
                    <span class="info-label">Propina:</span>
                    <span class="info-value" style="color: var(--color-exito);">
                        $<?= number_format($propina['monto'], 2) ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="info-row total">
                    <span class="info-label">Total Pagado:</span>
                    <span class="info-value">$<?= number_format($totalFinal, 2) ?></span>
                </div>
            </div>

            <!-- Mensaje de agradecimiento por la propina -->
            <?php if ($propina && $propina['monto'] > 0): ?>
            <div class="thank-you-message">
                <div class="thank-you-icon">💝</div>
                <div class="thank-you-text">¡Gracias por tu propina!</div>
                <div class="thank-you-detail">
                    Tu generosidad es muy apreciada por nuestro equipo
                </div>
            </div>
            <?php endif; ?>

            <!-- Botones de acción -->
            <div class="action-buttons">
                <a href="index.php?route=cliente<?= (isset($_SESSION['mesa_qr']) ? '&mesa=' . $_SESSION['mesa_qr'] : '') ?>" class="btn btn-primary">
                    📱 Hacer Otro Pedido
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    🖨️ Imprimir Recibo
                </button>
            </div>

            <!-- Información adicional -->
            <div class="receipt-note">
                <?php if ($pedido['numero_mesa']): ?>
                    <p style="font-size: 1.1em; color: var(--color-texto); font-weight: 600;">
                        📍 Mesa <?= htmlspecialchars($pedido['numero_mesa']) ?>
                    </p>
                <?php endif; ?>
                <p>⏱️ Tu pedido está siendo preparado</p>
                <p style="margin-top: 0.5rem;">¡Gracias por tu preferencia!</p>

                <!-- Tiempo estimado -->
                <div style="margin-top: 1rem; padding: 1rem; background: #f0f8ff; border-radius: 8px;">
                    <p style="color: #0066cc; font-weight: 600; margin: 0;">
                        ⏰ Tiempo estimado: 15-20 minutos
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Crear efecto de confeti
    function createConfetti() {
        const colors = ['#28a745', '#ffc107', '#007bff', '#dc3545', '#6610f2'];
        
        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 0.5 + 's';
                confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                document.body.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 4000);
            }, i * 30);
        }
    }

    // Ejecutar confeti al cargar la página
    window.addEventListener('load', () => {
        createConfetti();

        // Opcional: Redirigir al menú después de 30 segundos
        let countdown = 30;
        const countdownElement = document.createElement('div');
        countdownElement.style.cssText = 'position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.7); color: white; padding: 10px 20px; border-radius: 20px; font-size: 14px; z-index: 1000;';
        countdownElement.innerHTML = `Volviendo al menú en <span id="countdown">${countdown}</span> segundos...`;
        document.body.appendChild(countdownElement);

        const interval = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;

            if (countdown <= 0) {
                clearInterval(interval);
                // Redirigir al menú manteniendo la mesa si existe
                <?php if (isset($_SESSION['mesa_qr'])): ?>
                    window.location.href = 'index.php?route=cliente&mesa=<?= $_SESSION['mesa_qr'] ?>';
                <?php else: ?>
                    window.location.href = 'index.php?route=cliente';
                <?php endif; ?>
            }
        }, 1000);

        // Permitir cancelar la redirección al hacer clic
        countdownElement.addEventListener('click', () => {
            clearInterval(interval);
            countdownElement.innerHTML = 'Redirección cancelada';
            setTimeout(() => countdownElement.remove(), 2000);
        });
    });
    </script>
</body>
</html>