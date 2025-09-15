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
    
    ModalConfirmacion.show({
        title: '🗑️ Eliminar Item de Carta',
        message: '¿Estás seguro de que quieres eliminar este ítem?',
        itemName: nombre,
        note: 'Esta acción eliminará permanentemente el ítem y todos sus registros relacionados en pedidos. Esta acción no se puede deshacer.',
        confirmText: 'Eliminar',
        cancelText: 'Cancelar',
        onConfirm: () => {
            const actionUrl = window.location.origin + window.location.pathname + '?route=carta/delete&delete=' + id;
            console.log('URL generada para eliminación:', actionUrl);
            console.log('Redirigiendo a:', actionUrl);
            window.location.href = actionUrl;
        },
        onCancel: () => {
            console.log('Eliminación cancelada');
        }
    });
}

function confirmarBorradoMozo(id, nombre) {
    console.log('confirmarBorradoMozo llamado con:', id, nombre);
    
    ModalConfirmacion.show({
        title: '⚠️ Eliminar Mozo',
        message: '¿Estás seguro de que quieres eliminar este mozo?',
        itemName: nombre,
        note: 'Esta acción no se puede deshacer y se eliminarán todos los datos asociados al mozo.',
        confirmText: '🗑️ Eliminar',
        cancelText: '❌ Cancelar',
        onConfirm: () => {
            const actionUrl = window.location.origin + window.location.pathname + '?delete=' + id;
            console.log('URL generada para eliminación de mozo:', actionUrl);
            window.location.href = actionUrl;
        },
        onCancel: () => {
            console.log('Eliminación de mozo cancelada');
        }
    });
}

function confirmarBorradoPedido(id, nombre) {
    console.log('confirmarBorradoPedido llamado con:', id, nombre);
    
    ModalConfirmacion.show({
        title: '⚠️ Eliminar Pedido',
        message: '¿Estás seguro de que quieres eliminar este pedido?',
        itemName: nombre,
        note: 'Esta acción no se puede deshacer y se eliminarán todos los datos asociados al pedido.',
        confirmText: '🗑️ Eliminar',
        cancelText: '❌ Cancelar',
        onConfirm: () => {
            const actionUrl = window.location.origin + window.location.pathname + '?route=pedidos/delete&delete=' + id;
            console.log('URL generada para eliminación de pedido:', actionUrl);
            window.location.href = actionUrl;
        },
        onCancel: () => {
            console.log('Eliminación de pedido cancelada');
        }
    });
}

function confirmarBorradoMesa(id, numero) {
    console.log('confirmarBorradoMesa llamado con:', id, numero);
    
    ModalConfirmacion.show({
        title: '⚠️ Desactivar Mesa',
        message: '¿Estás seguro de que quieres desactivar esta mesa?',
        itemName: `Mesa #${numero}`,
        note: 'La mesa se marcará como inactiva y no aparecerá en las listas, pero se mantendrá en el historial. Esta acción se puede revertir.',
        confirmText: '⚠️ Desactivar',
        cancelText: '❌ Cancelar',
        onConfirm: () => {
            // Crear un formulario temporal para enviar la solicitud de eliminación
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.origin + window.location.pathname + '?route=mesas/delete';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = id;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        },
        onCancel: () => {
            console.log('Desactivación de mesa cancelada');
        }
    });
}

function confirmarCambioEstadoMesa(idMesa, nuevoEstado, onConfirm) {
    console.log('confirmarCambioEstadoMesa llamado con:', idMesa, nuevoEstado);
    
    const estadoLabels = {
        'libre': 'Libre',
        'ocupada': 'Ocupada', 
        'reservada': 'Reservada'
    };
    
    const estadoIconos = {
        'libre': '🟢',
        'ocupada': '🔴',
        'reservada': '🟡'
    };
    
    ModalConfirmacion.show({
        title: '🔄 Cambiar Estado de Mesa',
        message: '¿Estás seguro de que quieres cambiar el estado de esta mesa?',
        itemName: `Mesa #${idMesa} → ${estadoIconos[nuevoEstado]} ${estadoLabels[nuevoEstado]}`,
        note: 'El cambio de estado se aplicará inmediatamente y afectará la disponibilidad de la mesa.',
        confirmText: '✅ Cambiar Estado',
        cancelText: '❌ Cancelar',
        onConfirm: () => {
            if (onConfirm) {
                onConfirm(idMesa, nuevoEstado);
            }
        },
        onCancel: () => {
            console.log('Cambio de estado de mesa cancelado');
        }
    });
}

// Hacer las funciones disponibles globalmente
window.ModalConfirmacion = ModalConfirmacion;
window.confirmarBorradoCarta = confirmarBorradoCarta;
window.confirmarBorradoMozo = confirmarBorradoMozo;
window.confirmarBorradoPedido = confirmarBorradoPedido;
window.confirmarBorradoMesa = confirmarBorradoMesa;
window.confirmarCambioEstadoMesa = confirmarCambioEstadoMesa;

console.log('Funciones del modal asignadas a window');