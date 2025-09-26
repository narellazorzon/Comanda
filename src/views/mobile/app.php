<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Mozos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }

        .app-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .app-content {
            padding-top: 80px;
            padding-bottom: 80px;
            min-height: 100vh;
        }

        .pedido-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .pedido-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .pedido-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .pedido-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .status-pendiente {
            background-color: var(--warning-color);
            color: #212529;
        }

        .status-en_preparacion {
            background-color: #17a2b8;
            color: white;
        }

        .status-listo_para_servir {
            background-color: var(--success-color);
            color: white;
        }

        .pedido-body {
            padding: 1rem;
        }

        .pedido-items {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .pedido-footer {
            padding: 1rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .bottom-nav .nav-link {
            color: #6c757d;
            text-align: center;
            padding: 0.75rem;
            transition: color 0.2s;
        }

        .bottom-nav .nav-link.active {
            color: var(--primary-color);
        }

        .bottom-nav .nav-link i {
            font-size: 1.25rem;
            display: block;
            margin-bottom: 0.25rem;
        }

        .bottom-nav .nav-link span {
            font-size: 0.75rem;
        }

        .refresh-btn {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .refresh-btn:hover {
            transform: scale(1.1);
            background: var(--secondary-color);
        }

        .refresh-btn.spinning {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-buttons .btn {
            flex: 1;
            font-size: 0.875rem;
        }

        .notification-item {
            background: white;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="app-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Hola, <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></h4>
                <small class="opacity-75">Panel de mozos</small>
            </div>
            <button class="btn btn-light btn-sm" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>

    <!-- Content -->
    <div class="app-content" id="appContent">
        <!-- Lista de pedidos -->
        <div class="container" id="pedidosContainer">
            <h5 class="mb-3">Pedidos Activos</h5>
            <div id="pedidosList">
                <!-- Los pedidos se cargarán aquí -->
            </div>
        </div>

        <!-- Lista de notificaciones (oculta por defecto) -->
        <div class="container" id="notificacionesContainer" style="display: none;">
            <h5 class="mb-3">Notificaciones</h5>
            <div id="notificacionesList">
                <!-- Las notificaciones se cargarán aquí -->
            </div>
        </div>
    </div>

    <!-- Botón de refresh -->
    <button class="refresh-btn" id="refreshBtn" onclick="refreshPedidos()">
        <i class="fas fa-sync-alt"></i>
    </button>

    <!-- Navegación inferior -->
    <nav class="bottom-nav">
        <div class="nav nav-justified">
            <a class="nav-link active" href="#" onclick="showPedidos()">
                <i class="fas fa-clipboard-list"></i>
                <span>Pedidos</span>
            </a>
            <a class="nav-link" href="#" onclick="showNotificaciones()">
                <i class="fas fa-bell"></i>
                <span>Alertas</span>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            </a>
            <a class="nav-link" href="#" onclick="showEstadisticas()">
                <i class="fas fa-chart-bar"></i>
                <span>Stats</span>
            </a>
        </div>
    </nav>

    <script>
        let currentView = 'pedidos';
        let pedidosData = [];

        // Cargar pedidos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadPedidos();
            checkNotifications();

            // Auto-refresh cada 30 segundos
            setInterval(() => {
                if (currentView === 'pedidos') {
                    loadPedidos();
                }
                checkNotifications();
            }, 30000);
        });

        async function loadPedidos() {
            const refreshBtn = document.getElementById('refreshBtn');
            refreshBtn.classList.add('spinning');

            try {
                const response = await fetch('index.php?route=mobile/api-pedidos-activos');
                const data = await response.json();

                if (data.success) {
                    pedidosData = data.data;
                    renderPedidos(pedidosData);
                } else {
                    showError('Error al cargar pedidos');
                }
            } catch (error) {
                showError('Error de conexión');
            } finally {
                refreshBtn.classList.remove('spinning');
            }
        }

        function renderPedidos(pedidos) {
            const container = document.getElementById('pedidosList');

            if (pedidos.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h6>No hay pedidos activos</h6>
                        <p class="text-muted">Los nuevos pedidos aparecerán aquí</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = pedidos.map(pedido => `
                <div class="pedido-card" data-id-pedido="${pedido.id_pedido}">
                    <div class="pedido-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Pedido #${pedido.id_pedido}</h6>
                                <small class="text-muted">${pedido.modo_consumo === 'stay' ? 'Mesa ' + pedido.numero_mesa : 'Para llevar'}</small>
                            </div>
                            <span class="pedido-status status-${pedido.estado}">
                                ${getStatusText(pedido.estado)}
                            </span>
                        </div>
                    </div>
                    <div class="pedido-body">
                        <div class="pedido-items">
                            ${pedido.items}
                        </div>
                        <div class="mt-2">
                            <strong>Total: $${parseFloat(pedido.total).toFixed(2)}</strong>
                        </div>
                    </div>
                    <div class="pedido-footer">
                        <div class="action-buttons">
                            ${getActionButtons(pedido.estado, pedido.id_pedido)}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function getStatusText(estado) {
            const statusMap = {
                'pendiente': 'Pendiente',
                'en_preparacion': 'En Preparación',
                'listo_para_servir': 'Listo',
                'servido': 'Servido'
            };
            return statusMap[estado] || estado;
        }

        function getActionButtons(estado, idPedido) {
            let buttons = '';

            switch (estado) {
                case 'pendiente':
                    buttons = `
                        <button class="btn btn-primary btn-sm" onclick="actualizarEstado(${idPedido}, 'en_preparacion')">
                            <i class="fas fa-fire"></i> Cocina
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="cancelarPedido(${idPedido})">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    `;
                    break;
                case 'en_preparacion':
                    buttons = `
                        <button class="btn btn-success btn-sm" onclick="actualizarEstado(${idPedido}, 'listo_para_servir')">
                            <i class="fas fa-check"></i> Listo
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="actualizarEstado(${idPedido}, 'pendiente')">
                            <i class="fas fa-undo"></i> Volver
                        </button>
                    `;
                    break;
                case 'listo_para_servir':
                    buttons = `
                        <button class="btn btn-info btn-sm" onclick="actualizarEstado(${idPedido}, 'servido')">
                            <i class="fas fa-tray"></i> Servir
                        </button>
                    `;
                    break;
            }

            return buttons;
        }

        async function actualizarEstado(idPedido, nuevoEstado) {
            if (!confirm('¿Está seguro de cambiar el estado del pedido?')) {
                return;
            }

            try {
                const response = await fetch('index.php?route=mobile/api-actualizar-estado', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_pedido: idPedido,
                        nuevo_estado: nuevoEstado
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Mostrar notificación temporal
                    showSuccess('Estado actualizado correctamente');
                    // Recargar pedidos
                    loadPedidos();
                } else {
                    showError(data.error || 'Error al actualizar estado');
                }
            } catch (error) {
                showError('Error de conexión');
            }
        }

        function cancelarPedido(idPedido) {
            if (!confirm('¿Está seguro de cancelar este pedido?')) {
                return;
            }

            // Redirigir a la vista web para cancelar
            window.location.href = `index.php?route=pedidos&action=delete&id=${idPedido}`;
        }

        function showPedidos() {
            currentView = 'pedidos';
            document.getElementById('pedidosContainer').style.display = 'block';
            document.getElementById('notificacionesContainer').style.display = 'none';
            updateActiveNav(0);
            loadPedidos();
        }

        async function showNotificaciones() {
            currentView = 'notificaciones';
            document.getElementById('pedidosContainer').style.display = 'none';
            document.getElementById('notificacionesContainer').style.display = 'block';
            updateActiveNav(1);

            // Cargar notificaciones
            try {
                const response = await fetch('index.php?route=mobile/api-notificaciones');
                const data = await response.json();

                if (data.success) {
                    renderNotificaciones(data.data);
                }
            } catch (error) {
                console.error('Error cargando notificaciones:', error);
            }
        }

        function renderNotificaciones(notificaciones) {
            const container = document.getElementById('notificacionesList');
            const badge = document.getElementById('notificationBadge');

            if (notificaciones.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h6>No hay notificaciones</h6>
                    </div>
                `;
                badge.style.display = 'none';
                return;
            }

            container.innerHTML = notificaciones.map(notif => `
                <div class="notification-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${notif.titulo}</h6>
                            <p class="mb-0">${notif.mensaje}</p>
                            <small class="text-muted">${formatTime(notif.created_at)}</small>
                        </div>
                    </div>
                </div>
            `).join('');

            badge.style.display = 'block';
            badge.textContent = notificaciones.length;
        }

        async function showEstadisticas() {
            // Redirigir a la vista web de estadísticas
            window.location.href = 'index.php?route=reportes/dashboard';
        }

        async function checkNotifications() {
            if (currentView !== 'notificaciones') {
                try {
                    const response = await fetch('index.php?route=mobile/api-notificaciones');
                    const data = await response.json();

                    if (data.success && data.data.length > 0) {
                        const badge = document.getElementById('notificationBadge');
                        badge.style.display = 'block';
                        badge.textContent = data.data.length;
                    }
                } catch (error) {
                    console.error('Error checking notifications:', error);
                }
            }
        }

        function refreshPedidos() {
            loadPedidos();
        }

        function updateActiveNav(index) {
            document.querySelectorAll('.bottom-nav .nav-link').forEach((link, i) => {
                link.classList.toggle('active', i === index);
            });
        }

        function formatTime(datetime) {
            const date = new Date(datetime);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) return 'Ahora';
            if (diff < 3600000) return `Hace ${Math.floor(diff / 60000)} min`;
            return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }

        function showSuccess(message) {
            // Implementar notificación temporal
            const alert = document.createElement('div');
            alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-5';
            alert.style.zIndex = '9999';
            alert.textContent = message;
            document.body.appendChild(alert);

            setTimeout(() => alert.remove(), 3000);
        }

        function showError(message) {
            // Implementar notificación temporal
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-5';
            alert.style.zIndex = '9999';
            alert.textContent = message;
            document.body.appendChild(alert);

            setTimeout(() => alert.remove(), 3000);
        }

        async function logout() {
            if (confirm('¿Está seguro de cerrar sesión?')) {
                try {
                    await fetch('index.php?route=mobile/api-logout');
                    window.location.href = 'index.php?route=mobile';
                } catch (error) {
                    window.location.href = 'index.php?route=mobile';
                }
            }
        }
    </script>
</body>
</html>