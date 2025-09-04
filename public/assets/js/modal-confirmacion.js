/**
 * Modal de confirmación reutilizable
 * Uso: ModalConfirmacion.show(options)
 */

console.log('Modal de confirmación cargado correctamente');

class ModalConfirmacion {
    static show(options) {
        const {
            title = '⚠️ Confirmar Eliminación',
            message = '¿Estás seguro de que quieres eliminar este elemento?',
            itemName = '',
            note = '',
            confirmText = 'Eliminar',
            cancelText = 'Cancelar',
            onConfirm = () => {},
            onCancel = () => {}
        } = options;

        // Crear modal
        const modal = document.createElement('div');
        modal.className = 'modal-confirmacion';
        modal.style.display = 'flex';
        modal.innerHTML = `
            <div class="modal-confirmacion-content">
                <div class="modal-confirmacion-header">
                    ${title}
                </div>
                
                <div class="modal-confirmacion-icon">🗑️</div>
                
                <h3 class="modal-confirmacion-title">
                    ${message}
                </h3>
                
                ${itemName ? `
                <p class="modal-confirmacion-message">
                    Estás a punto de eliminar:<br>
                    <strong class="modal-confirmacion-item-name">${itemName}</strong>
                </p>
                ` : ''}
                
                ${note ? `
                <div class="modal-confirmacion-note">
                    <p><strong>Nota:</strong> ${note}</p>
                </div>
                ` : ''}
                
                <div class="modal-confirmacion-buttons">
                    <button class="modal-confirmacion-btn modal-confirmacion-btn-cancel">
                        ${cancelText}
                    </button>
                    <button class="modal-confirmacion-btn modal-confirmacion-btn-confirm">
                        ${confirmText}
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Event listeners
        const cancelBtn = modal.querySelector('.modal-confirmacion-btn-cancel');
        const confirmBtn = modal.querySelector('.modal-confirmacion-btn-confirm');
        
        const closeModal = () => {
            document.body.removeChild(modal);
        };
        
        cancelBtn.addEventListener('click', () => {
            closeModal();
            onCancel();
        });
        
        confirmBtn.addEventListener('click', () => {
            closeModal();
            onConfirm();
        });
        
        // Cerrar al hacer click fuera del modal
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
                onCancel();
            }
        });
        
        // Cerrar con tecla Escape
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                onCancel();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }
}

// Funciones de conveniencia para casos específicos
function confirmarBorradoCarta(id, nombre) {
    console.log('confirmarBorradoCarta llamado con:', id, nombre);
    
    if (confirm('¿Estás seguro de que quieres eliminar el ítem "' + nombre + '"?\n\nNota: Si este ítem está siendo usado en pedidos, se marcará como no disponible en lugar de eliminarse completamente.')) {
        // Usar window.location.href directamente
        const actionUrl = window.location.origin + window.location.pathname + '?route=carta/delete&delete=' + id;
        
        console.log('URL generada para eliminación:', actionUrl);
        console.log('Redirigiendo a:', actionUrl);
        
        window.location.href = actionUrl;
    }
}

function confirmarBorradoMozo(id, nombre) {
    console.log('confirmarBorradoMozo llamado con:', id, nombre);
    
    if (confirm('¿Estás seguro de que quieres eliminar al mozo "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
        // Usar window.location.href directamente
        const actionUrl = window.location.origin + window.location.pathname + '?delete=' + id;
        
        console.log('URL generada para eliminación de mozo:', actionUrl);
        window.location.href = actionUrl;
    }
}

function confirmarBorradoPedido(id, nombre) {
    console.log('confirmarBorradoPedido llamado con:', id, nombre);
    
    if (confirm('¿Estás seguro de que quieres eliminar el "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
        // Usar window.location.href directamente
        const actionUrl = window.location.origin + window.location.pathname + '?route=pedidos/delete&delete=' + id;
        
        console.log('URL generada para eliminación de pedido:', actionUrl);
        window.location.href = actionUrl;
    }
}

// Hacer las funciones disponibles globalmente
window.ModalConfirmacion = ModalConfirmacion;
window.confirmarBorradoCarta = confirmarBorradoCarta;
window.confirmarBorradoMozo = confirmarBorradoMozo;
window.confirmarBorradoPedido = confirmarBorradoPedido;

console.log('Funciones del modal asignadas a window');