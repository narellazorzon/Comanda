/**
 * Gestor de Pedidos - Sistema Comanda
 *
 * Este módulo maneja toda la lógica de JavaScript para la gestión de pedidos,
 * incluyendo actualizaciones de estado, filtros y acciones masivas.
 */

class PedidosManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupFilters();
        this.setupBulkActions();
        this.initializeStatusTransitions();
    }

    setupEventListeners() {
        // Event listeners para botones de acción
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-update-status')) {
                this.handleStatusUpdate(e);
            } else if (e.target.matches('.btn-view-details')) {
                this.handleViewDetails(e);
            } else if (e.target.matches('.btn-edit-pedido')) {
                this.handleEditPedido(e);
            } else if (e.target.matches('.btn-delete-pedido')) {
                this.handleDeletePedido(e);
            } else if (e.target.matches('.btn-print')) {
                this.handlePrint(e);
            }
        });

        // Auto-refresh cada 30 segundos para pedidos activos
        setInterval(() => this.autoRefreshActiveOrders(), 30000);
    }

    setupFilters() {
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }

        // Borrar filtros
        const clearFilters = document.getElementById('clearFilters');
        if (clearFilters) {
            clearFilters.addEventListener('click', () => this.clearAllFilters());
        }
    }

    setupBulkActions() {
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                const checkboxes = document.querySelectorAll('.pedido-checkbox');
                checkboxes.forEach(cb => cb.checked = e.target.checked);
                this.updateBulkActionsVisibility();
            });
        }

        // Actualizar visibilidad de acciones masivas al cambiar selección
        document.addEventListener('change', (e) => {
            if (e.target.matches('.pedido-checkbox')) {
                this.updateBulkActionsVisibility();
            }
        });

        // Acciones masivas
        const bulkActions = document.getElementById('bulkActions');
        if (bulkActions) {
            bulkActions.addEventListener('change', (e) => {
                if (e.target.value) {
                    this.handleBulkAction(e.target.value);
                    e.target.value = '';
                }
            });
        }
    }

    initializeStatusTransitions() {
        // Cargar datos de transiciones desde PHP
        if (typeof window.pedidoStatusData !== 'undefined') {
            this.statusTransitions = window.pedidoStatusData.transitions || {};
            this.statusColors = window.pedidoStatusData.statusColors || {};
            this.statusNames = window.pedidoStatusData.statusNames || {};
        }
    }

    handleStatusUpdate(e) {
        const button = e.target;
        const pedidoId = button.dataset.pedidoId;
        const currentStatus = button.dataset.currentStatus;

        // Mostrar modal de selección de estado
        this.showStatusModal(pedidoId, currentStatus);
    }

    showStatusModal(pedidoId, currentStatus) {
        const modal = document.getElementById('statusModal');
        if (!modal) return;

        // Actualizar contenido del modal
        const title = modal.querySelector('.modal-title');
        const body = modal.querySelector('.modal-body');

        title.textContent = `Actualizar Estado - Pedido #${pedidoId}`;

        // Generar opciones de estado
        const options = this.generateStatusOptions(currentStatus);
        body.innerHTML = `
            <form id="statusUpdateForm">
                <input type="hidden" name="pedido_id" value="${pedidoId}">
                <input type="hidden" name="current_status" value="${currentStatus}">
                <div class="mb-3">
                    <label class="form-label">Nuevo Estado:</label>
                    <select name="nuevo_estado" class="form-select" required>
                        ${options}
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Observaciones (opcional):</label>
                    <textarea name="observaciones" class="form-control" rows="3"></textarea>
                </div>
            </form>
        `;

        // Mostrar modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Manejar envío del formulario
        const form = document.getElementById('statusUpdateForm');
        form.onsubmit = (e) => {
            e.preventDefault();
            this.submitStatusUpdate(form, modalInstance);
        };
    }

    generateStatusOptions(currentStatus) {
        const allowedTransitions = this.statusTransitions[currentStatus] || [];
        let options = '';

        for (const [status, statusName] of Object.entries(this.statusNames)) {
            if (status === currentStatus || allowedTransitions.includes(status)) {
                const selected = status === currentStatus ? 'selected' : '';
                const color = this.statusColors[status] || '#6c757d';
                options += `
                    <option value="${status}" ${selected} style="color: ${color};">
                        ${statusName}
                    </option>
                `;
            }
        }

        return options;
    }

    async submitStatusUpdate(form, modalInstance) {
        const formData = new FormData(form);

        try {
            const response = await fetch('index.php?route=pedidos/update-estado', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                // Actualizar la interfaz
                this.updatePedidoStatus(
                    formData.get('pedido_id'),
                    formData.get('nuevo_estado'),
                    formData.get('observaciones')
                );

                // Cerrar modal
                modalInstance.hide();

                // Mostrar notificación
                this.showNotification('Estado actualizado correctamente', 'success');
            } else {
                this.showNotification(result.error || 'Error al actualizar estado', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error de conexión', 'error');
        }
    }

    updatePedidoStatus(pedidoId, newStatus, observaciones) {
        const row = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
        if (!row) return;

        // Actualizar badge de estado
        const statusBadge = row.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.textContent = this.statusNames[newStatus] || newStatus;
            statusBadge.style.backgroundColor = this.statusColors[newStatus] || '#6c757d';
        }

        // Actualizar botones de acción según nuevo estado
        this.updateActionButtons(row, newStatus);

        // Actualizar timestamp
        const timestamp = row.querySelector('.timestamp');
        if (timestamp) {
            timestamp.textContent = new Date().toLocaleString();
        }

        // Agregar observaciones si existen
        if (observaciones) {
            const obsCell = row.querySelector('.observaciones');
            if (obsCell) {
                obsCell.textContent = observaciones;
            }
        }
    }

    updateActionButtons(row, status) {
        const actionsCell = row.querySelector('.actions-cell');
        if (!actionsCell) return;

        // Generar botones según estado
        let buttons = '';

        // Botón de ver detalles (siempre visible)
        buttons += '<button class="btn btn-sm btn-info btn-view-details" data-pedido-id="' +
                   row.dataset.pedidoId + '">Ver Detalles</button> ';

        // Botón de editar (solo para pedidos editables)
        if (this.isEditableStatus(status)) {
            buttons += '<button class="btn btn-sm btn-warning btn-edit-pedido" data-pedido-id="' +
                       row.dataset.pedidoId + '">Editar</button> ';
        }

        // Botón de actualizar estado (no para pedidos cerrados)
        if (!this.isFinalStatus(status)) {
            buttons += '<button class="btn btn-sm btn-primary btn-update-status" ' +
                      'data-pedido-id="' + row.dataset.pedidoId + '" ' +
                      'data-current-status="' + status + '">Actualizar Estado</button> ';
        }

        // Botón de imprimir (solo para pedidos cerrados)
        if (status === 'cerrado') {
            buttons += '<button class="btn btn-sm btn-secondary btn-print" data-pedido-id="' +
                       row.dataset.pedidoId + '">Imprimir</button> ';
        }

        actionsCell.innerHTML = buttons;
    }

    isEditableStatus(status) {
        return ['pendiente', 'en_preparacion'].includes(status);
    }

    isFinalStatus(status) {
        return ['cerrado', 'cancelado'].includes(status);
    }

    handleViewDetails(e) {
        const pedidoId = e.target.dataset.pedidoId;
        // Mostrar modal con detalles del pedido
        this.showPedidoDetails(pedidoId);
    }

    async showPedidoDetails(pedidoId) {
        try {
            const response = await fetch(`index.php?route=pedidos/info&id=${pedidoId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                const modal = document.getElementById('detailsModal');
                if (modal) {
                    const body = modal.querySelector('.modal-body');
                    body.innerHTML = this.generateDetailsHTML(result.data);

                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                }
            } else {
                this.showNotification('Error al cargar detalles', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error de conexión', 'error');
        }
    }

    generateDetailsHTML(pedido) {
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h5>Información del Pedido</h5>
                    <p><strong>Número:</strong> #${pedido.id_pedido}</p>
                    <p><strong>Fecha:</strong> ${new Date(pedido.fecha_hora).toLocaleString()}</p>
                    <p><strong>Estado:</strong> <span class="badge" style="background-color: ${this.statusColors[pedido.estado] || '#6c757d'}">${this.statusNames[pedido.estado] || pedido.estado}</span></p>
                    <p><strong>Total:</strong> $${parseFloat(pedido.total).toFixed(2)}</p>
                </div>
                <div class="col-md-6">
                    <h5>Información Adicional</h5>
                    <p><strong>Mesa:</strong> ${pedido.numero_mesa || 'N/A'}</p>
                    <p><strong>Mozo:</strong> ${pedido.nombre_mozo_completo || 'N/A'}</p>
                    <p><strong>Modo:</strong> ${pedido.modo_consumo === 'stay' ? 'Consumir en local' : 'Para llevar'}</p>
                    <p><strong>Forma de Pago:</strong> ${pedido.forma_pago || 'N/A'}</p>
                </div>
            </div>
        `;

        if (pedido.detalles && pedido.detalles.length > 0) {
            html += `
                <hr>
                <h5>Items del Pedido</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            pedido.detalles.forEach(item => {
                html += `
                    <tr>
                        <td>${item.item_nombre}</td>
                        <td>${item.cantidad}</td>
                        <td>$${parseFloat(item.precio_unitario).toFixed(2)}</td>
                        <td>$${parseFloat(item.subtotal).toFixed(2)}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }

        return html;
    }

    handleEditPedido(e) {
        const pedidoId = e.target.dataset.pedidoId;
        window.location.href = `index.php?route=pedidos/edit&id=${pedidoId}`;
    }

    handleDeletePedido(e) {
        const pedidoId = e.target.dataset.pedidoId;
        if (confirm('¿Está seguro de eliminar este pedido? Esta acción no se puede deshacer.')) {
            this.deletePedido(pedidoId);
        }
    }

    async deletePedido(pedidoId) {
        try {
            const response = await fetch(`index.php?route=pedidos/delete&id=${pedidoId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                // Remover fila de la tabla
                const row = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
                if (row) {
                    row.remove();
                }

                this.showNotification('Pedido eliminado correctamente', 'success');
            } else {
                this.showNotification(result.error || 'Error al eliminar pedido', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error de conexión', 'error');
        }
    }

    handlePrint(e) {
        const pedidoId = e.target.dataset.pedidoId;
        window.open(`index.php?route=pedidos/print&id=${pedidoId}`, '_blank');
    }

    applyFilters() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        // Actualizar URL con filtros
        window.location.href = 'index.php?route=pedidos&' + params.toString();
    }

    clearAllFilters() {
        window.location.href = 'index.php?route=pedidos';
    }

    updateBulkActionsVisibility() {
        const selectedCheckboxes = document.querySelectorAll('.pedido-checkbox:checked');
        const bulkActions = document.getElementById('bulkActionsContainer');

        if (bulkActions) {
            bulkActions.style.display = selectedCheckboxes.length > 0 ? 'block' : 'none';
        }
    }

    async handleBulkAction(action) {
        const selectedIds = Array.from(document.querySelectorAll('.pedido-checkbox:checked'))
            .map(cb => cb.value);

        if (selectedIds.length === 0) {
            this.showNotification('Seleccione al menos un pedido', 'warning');
            return;
        }

        if (action === 'delete') {
            if (!confirm(`¿Está seguro de eliminar ${selectedIds.length} pedidos?`)) {
                return;
            }
        }

        try {
            const formData = new FormData();
            formData.append('action', action);
            selectedIds.forEach(id => formData.append('pedidos[]', id));

            const response = await fetch('index.php?route=pedidos/bulk-action', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                // Recargar página para ver cambios
                window.location.reload();
            } else {
                this.showNotification(result.error || 'Error al ejecutar acción masiva', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error de conexión', 'error');
        }
    }

    autoRefreshActiveOrders() {
        // Solo refrescar si hay pedidos activos visibles
        const activeOrders = document.querySelectorAll('.status-pendiente, .status-en_preparacion, .status-listo_para_servir');
        if (activeOrders.length > 0) {
            // Realizar petición AJAX para actualizar datos
            this.refreshActiveOrdersData();
        }
    }

    async refreshActiveOrdersData() {
        try {
            const response = await fetch('index.php?route=pedidos/refresh-active', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success && result.data) {
                this.updateActiveOrders(result.data);
            }
        } catch (error) {
            console.error('Error refreshing orders:', error);
        }
    }

    updateActiveOrders(ordersData) {
        ordersData.forEach(order => {
            const row = document.querySelector(`tr[data-pedido-id="${order.id_pedido}"]`);
            if (row) {
                // Actualizar estado si cambió
                const currentStatus = row.querySelector('.status-badge').dataset.status;
                if (currentStatus !== order.estado) {
                    this.updatePedidoStatus(order.id_pedido, order.estado, order.observaciones);
                }

                // Actualizar timestamp
                const timestamp = row.querySelector('.timestamp');
                if (timestamp) {
                    timestamp.textContent = new Date().toLocaleString();
                }
            }
        });
    }

    showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.pedidosManager = new PedidosManager();
});