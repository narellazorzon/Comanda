/**
 * Modal de confirmación reutilizable para acciones críticas
 * Uso: ModalConfirmacion.show(options)
 */

class ModalConfirmacion {
    static show(options) {
        // Valores por defecto
        const defaults = {
            title: 'Confirmar Acción',
            message: '¿Estás seguro de realizar esta acción?',
            type: 'warning',
            confirmText: 'Confirmar',
            cancelText: 'Cancelar',
            confirmCallback: null,
            cancelCallback: null,
            size: 'normal',
            persistent: false
        };

        // Combinar opciones
        const config = {...defaults, ...options};

        // Crear modal
        const modal = document.createElement('div');
        modal.className = 'modal-confirmacion';
        modal.innerHTML = `
            <div class="modal-backdrop ${config.persistent ? 'persistent' : ''}" onclick="ModalConfirmacion.closeModal(event)"></div>
            <div class="modal-content ${config.size}">
                <div class="modal-header">
                    <h3>${config.title}</h3>
                </div>
                <div class="modal-body">
                    <p>${config.message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-cancel" onclick="ModalConfirmacion.handleCancel()">${config.cancelText}</button>
                    <button class="btn-confirm ${config.type}" onclick="ModalConfirmacion.handleConfirm()">${config.confirmText}</button>
                </div>
            </div>
        `;

        // Añadir al DOM
        document.body.appendChild(modal);

        // Añadir estilos si no existen
        if (!document.querySelector('#modal-confirmacion-styles')) {
            const styles = document.createElement('style');
            styles.id = 'modal-confirmacion-styles';
            styles.textContent = `
                .modal-confirmacion {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .modal-backdrop {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                }
                .modal-content {
                    position: relative;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                    max-width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                }
                .modal-content.small { width: 400px; }
                .modal-content.normal { width: 500px; }
                .modal-content.large { width: 600px; }
                .modal-header {
                    padding: 20px 20px 10px;
                    border-bottom: 1px solid #eee;
                }
                .modal-header h3 {
                    margin: 0;
                    color: #333;
                    font-size: 1.2rem;
                }
                .modal-body {
                    padding: 20px;
                    color: #666;
                    line-height: 1.5;
                }
                .modal-footer {
                    padding: 10px 20px 20px;
                    display: flex;
                    gap: 10px;
                    justify-content: flex-end;
                }
                .btn-cancel, .btn-confirm {
                    padding: 8px 16px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.2s;
                }
                .btn-cancel {
                    background: #6c757d;
                    color: white;
                }
                .btn-cancel:hover {
                    background: #5a6268;
                }
                .btn-confirm {
                    background: #007bff;
                    color: white;
                }
                .btn-confirm:hover {
                    background: #0056b3;
                }
                .btn-confirm.warning { background: #ffc107; color: #212529; }
                .btn-confirm.warning:hover { background: #e0a800; }
                .btn-confirm.danger { background: #dc3545; color: white; }
                .btn-confirm.danger:hover { background: #c82333; }
                .btn-confirm.success { background: #28a745; color: white; }
                .btn-confirm.success:hover { background: #218838; }
            `;
            document.head.appendChild(styles);
        }

        // Guardar callbacks
        ModalConfirmacion.currentModal = modal;
        ModalConfirmacion.confirmCallback = config.confirmCallback;
        ModalConfirmacion.cancelCallback = config.cancelCallback;

        return modal;
    }

    static closeModal(event) {
        if (event && event.target.classList.contains('persistent')) {
            return;
        }
        if (ModalConfirmacion.currentModal) {
            ModalConfirmacion.currentModal.remove();
            ModalConfirmacion.currentModal = null;
        }
    }

    static handleConfirm() {
        if (ModalConfirmacion.confirmCallback) {
            ModalConfirmacion.confirmCallback();
        }
        ModalConfirmacion.closeModal();
    }

    static handleCancel() {
        if (ModalConfirmacion.cancelCallback) {
            ModalConfirmacion.cancelCallback();
        }
        ModalConfirmacion.closeModal();
    }
}

// Funciones específicas para confirmación de acciones críticas
function confirmarBorradoPedido(idPedido, callback) {
    ModalConfirmacion.show({
        title: '🗑️ Eliminar Pedido',
        message: `¿Estás seguro de que deseas eliminar el pedido #${idPedido}?<br><br>
                 <strong>Esta acción no se puede deshacer.</strong>`,
        type: 'danger',
        confirmText: 'Eliminar Pedido',
        cancelText: 'Cancelar',
        size: 'small',
        persistent: false,
        confirmCallback: callback
    });
}

function confirmarBorradoMesa(idMesa, callback) {
    ModalConfirmacion.show({
        title: '🗑️ Eliminar Mesa',
        message: `¿Estás seguro de que deseas eliminar la mesa #${idMesa}?<br><br>
                 <strong>Esta acción no se puede deshacer.</strong>`,
        type: 'danger',
        confirmText: 'Eliminar Mesa',
        cancelText: 'Cancelar',
        size: 'small',
        persistent: false,
        confirmCallback: callback
    });
}

function confirmarCambioEstadoMesa(idMesa, nuevoEstado, callback) {
    const esActivo = nuevoEstado === 'activa';
    ModalConfirmacion.show({
        title: esActivo ? '✅ Activar Mesa' : '❌ Desactivar Mesa',
        message: `¿Estás seguro de que deseas ${esActivo ? 'activar' : 'desactivar'} la mesa #${idMesa}?`,
        type: esActivo ? 'success' : 'warning',
        confirmText: esActivo ? 'Activar' : 'Desactivar',
        cancelText: 'Cancelar',
        size: 'small',
        persistent: false,
        confirmCallback: callback
    });
}

// Asignar funciones al ámbito global para uso desde HTML
window.ModalConfirmacion = ModalConfirmacion;
window.confirmarBorradoPedido = confirmarBorradoPedido;
window.confirmarBorradoMesa = confirmarBorradoMesa;
window.confirmarCambioEstadoMesa = confirmarCambioEstadoMesa;