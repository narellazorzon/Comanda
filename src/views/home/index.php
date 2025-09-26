<?php
// src/views/home/index.php

// Determinar la ruta base del proyecto
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);
?>
<!-- Header con saludo -->
<div class="home-header">
    <h1>Hola, <?= htmlspecialchars($_SESSION['user']['nombre']) ?></h1>
    <p class="home-subtitle">Sistema de Gestión de Restaurante</p>
</div>

<!-- Grid de widgets -->
<div class="widgets-grid">
    
    <!-- Widget Mesas -->
    <div class="widget">
        <div class="widget-header">
            <div class="widget-icon mesas-bg">
                <div class="widget-symbol">🪑</div>
            </div>
            <h3 class="widget-title">Mesas</h3>
        </div>
        <div class="widget-actions">
            <a href="<?= $base_url ?>/index.php?route=mesas" class="widget-btn primary">
                <span class="btn-text">Ver Mesas</span>
            </a>
            <?php if ($_SESSION['user']['rol'] === 'administrador'): ?>
            <a href="<?= $base_url ?>/index.php?route=mesas/create" class="widget-btn secondary">
                <span class="btn-icon">➕</span>
                <span class="btn-text">Nueva Mesa</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Widget Pedidos -->
    <div class="widget">
        <div class="widget-header">
            <div class="widget-icon pedidos-bg">
                <div class="widget-symbol">🍽️</div>
            </div>
            <h3 class="widget-title">Pedidos</h3>
        </div>
        <div class="widget-actions">
            <a href="<?= $base_url ?>/index.php?route=pedidos" class="widget-btn primary">
                <span class="btn-text">Ver Pedidos</span>
            </a>
            <a href="<?= $base_url ?>/index.php?route=pedidos/create" class="widget-btn secondary">
                <span class="btn-icon">➕</span>
                <span class="btn-text">Nuevo Pedido</span>
            </a>
        </div>
    </div>

    <!-- Widget Carta -->
    <div class="widget">
        <div class="widget-header">
            <div class="widget-icon carta-bg">
                <div class="widget-symbol">📋</div>
            </div>
            <h3 class="widget-title">Carta</h3>
        </div>
        <div class="widget-actions">
            <a href="<?= $base_url ?>/index.php?route=carta" class="widget-btn primary">
                <span class="btn-text">Ver Carta</span>
            </a>
            <?php if ($_SESSION['user']['rol'] === 'administrador'): ?>
            <a href="<?= $base_url ?>/index.php?route=carta/create" class="widget-btn secondary">
                <span class="btn-icon">➕</span>
                <span class="btn-text">Nuevo Ítem</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($_SESSION['user']['rol'] === 'administrador'): ?>
    <!-- Widget Mozos -->
    <div class="widget">
        <div class="widget-header">
            <div class="widget-icon mozos-bg">
                <div class="widget-symbol">👥</div>
            </div>
            <h3 class="widget-title">Personal</h3>
        </div>
        <div class="widget-actions">
            <a href="<?= $base_url ?>/index.php?route=mozos" class="widget-btn primary">
                <span class="btn-text">Ver Personal</span>
            </a>
            <a href="<?= $base_url ?>/index.php?route=mozos/create" class="widget-btn secondary">
                <span class="btn-icon">➕</span>
                <span class="btn-text">Nuevo Mozo</span>
            </a>
        </div>
    </div>

    <!-- Widget Reportes -->
    <div class="widget">
        <div class="widget-header">
            <div class="widget-icon reportes-bg">
                <div class="widget-symbol">📊</div>
            </div>
            <h3 class="widget-title">Reportes</h3>
        </div>
        <div class="widget-actions">
            <a href="<?= $base_url ?>/index.php?route=reportes" class="widget-btn primary">
                <span class="btn-text">Ver Reportes</span>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($_SESSION['user']['rol'] === 'mozo'): ?>
    <!-- Widget Llamados -->
    <div class="widget">
        <div class="widget-header">
            <div class="widget-icon llamados-bg">
                <div class="widget-symbol">🔔</div>
            </div>
            <h3 class="widget-title">Llamados</h3>
        </div>
        <div class="widget-actions">
            <a href="<?= $base_url ?>/index.php?route=llamados" class="widget-btn primary">
                <span class="btn-icon">📋</span>
                <span class="btn-text">Ver Llamados</span>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
/* Efectos bounce y animaciones globales */
@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0.3) translateY(-50px);
  }
  50% {
    opacity: 1;
    transform: scale(1.05) translateY(0);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Aplicar animación de entrada a elementos principales */
.home-header {
  animation: slideInUp 0.6s ease-out;
}

.widgets-grid {
  animation: fadeInScale 0.8s ease-out;
}

.widget {
  animation: slideInUp 0.5s ease-out;
  animation-fill-mode: both;
}

.widget:nth-child(1) { animation-delay: 0.1s; }
.widget:nth-child(2) { animation-delay: 0.2s; }
.widget:nth-child(3) { animation-delay: 0.3s; }
.widget:nth-child(4) { animation-delay: 0.4s; }
.widget:nth-child(5) { animation-delay: 0.5s; }
.widget:nth-child(6) { animation-delay: 0.6s; }
.widget:nth-child(7) { animation-delay: 0.7s; }
.widget:nth-child(8) { animation-delay: 0.8s; }
.widget:nth-child(9) { animation-delay: 0.9s; }
.widget:nth-child(10) { animation-delay: 1.0s; }

/* Efectos de hover mejorados */
.widget:hover {
  transform: translateY(-5px) scale(1.03);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.widget-btn:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

/* Estilos para el menú inicial con widgets */
.home-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1rem 0;
}

.home-header h1 {
    font-size: 2rem;
    color: var(--secondary);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.home-subtitle {
    color: #666;
    font-size: 1rem;
    margin: 0;
}

.widgets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    max-width: 1000px;
    margin: 0 auto;
    padding: 1rem;
}

.widget {
    background: var(--surface);
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    padding: 1rem;
    transition: all 0.3s ease;
    border: 1px solid var(--accent);
}

.widget:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.widget-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    gap: 0.75rem;
}

.widget-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.widget-symbol {
    font-size: 1.5rem;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

.widget-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--secondary);
    margin: 0;
}

.widget-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.widget-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 0.8rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.85rem;
}

.widget-btn.primary {
    background: var(--secondary);
    color: white;
}

.widget-btn.primary:hover {
    background: #8b5e46;
    transform: translateX(4px);
}

.widget-btn.secondary {
    background: var(--accent);
    color: var(--text);
    border: 1px solid var(--primary);
}

.widget-btn.secondary:hover {
    background: var(--primary);
    transform: translateX(4px);
}

.btn-icon {
    font-size: 0.9rem;
}

.btn-text {
    flex: 1;
}

/* Responsive para móviles */
@media (max-width: 768px) {
    .home-header h1 {
        font-size: 1.5rem;
    }
    
    .home-subtitle {
        font-size: 0.9rem;
    }
    
    .widgets-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
        padding: 0.5rem;
    }
    
    .widget {
        padding: 0.75rem;
    }
    
    .widget-header {
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .widget-icon {
        width: 40px;
        height: 40px;
    }
    
    .widget-symbol {
        font-size: 1.2rem;
    }
    
    .widget-title {
        font-size: 0.9rem;
    }
    
    .widget-btn {
        padding: 0.5rem 0.6rem;
        font-size: 0.8rem;
    }
}

/* Para pantallas muy pequeñas */
@media (max-width: 480px) {
    .widgets-grid {
        padding: 0.25rem;
    }
    
    .widget {
        padding: 0.6rem;
    }
    
    .widget-icon {
        width: 35px;
        height: 35px;
    }
    
    .widget-symbol {
        font-size: 1rem;
    }
    
    .widget-title {
        font-size: 0.85rem;
    }
    
    .widget-btn {
        padding: 0.4rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Animación de entrada */
.widget {
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
    transform: translateY(20px);
}

.widget:nth-child(1) { animation-delay: 0.1s; }
.widget:nth-child(2) { animation-delay: 0.2s; }
.widget:nth-child(3) { animation-delay: 0.3s; }
.widget:nth-child(4) { animation-delay: 0.4s; }
.widget:nth-child(5) { animation-delay: 0.5s; }
.widget:nth-child(6) { animation-delay: 0.6s; }

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Colores usando la paleta del sistema */
.mesas-bg {
    background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%) !important;
}

.pedidos-bg {
    background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%) !important;
}

.carta-bg {
    background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%) !important;
}

.nuevo-pedido-bg {
    background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%) !important;
}

.mozos-bg {
    background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%) !important;
}

.reportes-bg {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%) !important;
}

.nueva-mesa-bg {
    background: linear-gradient(135deg, var(--accent) 0%, var(--secondary) 100%) !important;
}

.nuevo-item-bg {
    background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%) !important;
}

.llamados-bg {
    background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%) !important;
}
</style>