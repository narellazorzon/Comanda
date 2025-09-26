/**
 * Mesas Manager - Sistema Comanda
 *
 * Este módulo maneja toda la lógica de JavaScript para la gestión de mesas,
 * incluyendo filtros, vistas responsive y acciones sobre mesas.
 */

class MesasManager {
    constructor() {
        this.currentSearchTerm = '';
        this.currentStatusFilter = 'all';
        this.init();
    }

    init() {
        this.setupElements();
        this.setupEventListeners();
        this.initTabs();
        this.initFilters();
    }

    setupElements() {
        this.mesaSearch = document.getElementById('mesaSearch');
        this.statusFilter = document.getElementById('statusFilter');
        this.filtroNumero = document.getElementById('filtro-numero');
        this.filtroEstado = document.getElementById('filtro-estado');
        this.filtroUbicacion = document.getElementById('filtro-ubicacion');
        this.filtroMozo = document.getElementById('filtro-mozo');
        this.numMesas = document.getElementById('num-mesas');
    }

    setupEventListeners() {
        // Eventos para el buscador y filtros principales
        if (this.mesaSearch) {
            this.mesaSearch.addEventListener('input', (e) => {
                this.currentSearchTerm = e.target.value;
                this.filterMesas();
            });
        }

        if (this.statusFilter) {
            this.statusFilter.addEventListener('change', (e) => {
                this.currentStatusFilter = e.target.value;
                this.filterMesas();
            });
        }

        // Eventos para filtros avanzados
        if (this.filtroNumero) {
            this.filtroNumero.addEventListener('input', () => this.aplicarFiltrosMesas());
        }
        if (this.filtroEstado) {
            this.filtroEstado.addEventListener('change', () => this.aplicarFiltrosMesas());
        }
        if (this.filtroUbicacion) {
            this.filtroUbicacion.addEventListener('change', () => this.aplicarFiltrosMesas());
        }
        if (this.filtroMozo) {
            this.filtroMozo.addEventListener('change', () => this.aplicarFiltrosMesas());
        }

        // Eventos para tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                this.switchTab(button);
            });
        });

        // Eventos para botones de estado
        document.querySelectorAll('.state-btn').forEach(button => {
            button.addEventListener('click', () => {
                this.changeMesaStatus(button);
            });
        });
    }

    initTabs() {
        // Mostrar tab activa al cargar
        const activeTab = document.querySelector('.tab-button.active');
        if (activeTab) {
            const tabName = activeTab.dataset.tab;
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.toggle('active', content.dataset.tab === tabName);
            });
        }
    }

    initFilters() {
        // Inicializar filtros al cargar la página
        this.filterMesas();
        this.aplicarFiltrosMesas();
    }

    switchTab(clickedTab) {
        // Actualizar estado de tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        clickedTab.classList.add('active');

        // Mostrar contenido correspondiente
        const tabName = clickedTab.dataset.tab;
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.dataset.tab === tabName);
        });

        // Aplicar filtros nuevamente
        this.filterMesas();
    }

    getMesaStatus(row) {
        const statusCell = row.querySelector('td:nth-child(3)');
        if (statusCell) {
            const statusSpan = statusCell.querySelector('span');
            if (statusSpan) {
                return statusSpan.textContent.toLowerCase().trim();
            }
        }
        return '';
    }

    getMesaStatusFromCard(card) {
        if (card.classList.contains('mesa-inactiva')) {
            return 'inactiva';
        }

        const statusItems = card.querySelectorAll('.mobile-card-item');
        for (let item of statusItems) {
            const label = item.querySelector('.mobile-card-label');
            if (label && label.textContent.includes('Estado')) {
                const statusSpan = item.querySelector('.mobile-card-value span');
                if (statusSpan) {
                    return statusSpan.textContent.toLowerCase().trim();
                }
            }
        }
        return '';
    }

    filterMesas() {
        const searchTerm = this.currentSearchTerm.toLowerCase().trim();
        const statusFilter = this.currentStatusFilter;
        let visibleCount = 0;

        // Filtrar filas de la tabla
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach((row) => {
            const firstCell = row.querySelector('td:first-child');
            if (!firstCell) return;

            const mesaNumber = firstCell.textContent.toLowerCase();
            const mesaStatus = this.getMesaStatus(row);

            const matchesSearch = searchTerm === '' || mesaNumber.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || mesaStatus === statusFilter;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Filtrar tarjetas móviles
        const mobileCards = document.querySelectorAll('.mobile-card');
        mobileCards.forEach((card) => {
            const numberElement = card.querySelector('.mobile-card-number');
            if (!numberElement) return;

            const mesaNumber = numberElement.textContent.toLowerCase();
            const mesaStatus = this.getMesaStatusFromCard(card);

            const matchesSearch = searchTerm === '' || mesaNumber.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || mesaStatus === statusFilter;

            // Verificar si la tarjeta debe mostrarse según la pestaña activa
            const activeTab = document.querySelector('.tab-button.active');
            const isActiveTab = activeTab && activeTab.textContent.includes('Activas');
            const isInactiveTab = activeTab && activeTab.textContent.includes('Inactivas');

            let shouldShow = false;
            if (isActiveTab && card.classList.contains('mesa-activa')) {
                shouldShow = true;
            } else if (isInactiveTab && card.classList.contains('mesa-inactiva')) {
                shouldShow = true;
            }

            if (matchesSearch && matchesStatus && shouldShow) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Actualizar contador
        const resultCount = document.getElementById('resultCount');
        if (resultCount) {
            resultCount.textContent = visibleCount;
        }
    }

    aplicarFiltrosMesas() {
        const filtroNumero = document.getElementById('filtro-numero')?.value || '';
        const filtroEstado = document.getElementById('filtro-estado')?.value || '';
        const filtroUbicacion = document.getElementById('filtro-ubicacion')?.value || '';
        const filtroMozo = document.getElementById('filtro-mozo')?.value || '';

        let contadorVisible = 0;
        const filas = document.querySelectorAll('.mesa-row');

        filas.forEach(fila => {
            const celdas = fila.querySelectorAll('td');
            const numero = celdas[0]?.textContent.toLowerCase() || '';
            const estado = celdas[2]?.textContent.toLowerCase() || '';
            const ubicacion = celdas[3]?.textContent.toLowerCase() || '';
            const mozo = celdas[4]?.textContent.toLowerCase() || '';

            const matchNumero = !filtroNumero || numero.includes(filtroNumero.toLowerCase());
            const matchEstado = !filtroEstado || estado === filtroEstado.toLowerCase();
            const matchUbicacion = !filtroUbicacion || ubicacion.includes(filtroUbicacion.toLowerCase());
            const matchMozo = !filtroMozo || mozo.includes(filtroMozo.toLowerCase());

            if (matchNumero && matchEstado && matchUbicacion && matchMozo) {
                fila.style.display = '';
                contadorVisible++;
            } else {
                fila.style.display = 'none';
            }
        });

        // Actualizar contador
        if (this.numMesas) {
            this.numMesas.textContent = contadorVisible;
        }

        // Mostrar mensaje si no hay resultados
        if (contadorVisible === 0 && filas.length > 0) {
            let filaNoResultados = document.getElementById('fila-no-mesas');
            if (!filaNoResultados) {
                const tbody = document.querySelector('.table tbody');
                const nuevaFila = document.createElement('tr');
                nuevaFila.id = 'fila-no-mesas';
                const numColumnas = document.querySelector('.table thead tr').children.length;
                nuevaFila.innerHTML = `<td colspan="${numColumnas}" style="text-align: center; padding: 2rem; color: #6c757d;">
                    <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">🪑 No se encontraron mesas con los filtros aplicados</div>
                    <div style="font-size: 0.9rem;">Intenta ajustar los criterios de búsqueda</div>
                </td>`;
                tbody.appendChild(nuevaFila);
            }
        } else {
            const filaNoResultados = document.getElementById('fila-no-mesas');
            if (filaNoResultados) {
                filaNoResultados.remove();
            }
        }
    }

    limpiarFiltrosMesas() {
        if (this.filtroNumero) this.filtroNumero.value = '';
        if (this.filtroEstado) this.filtroEstado.value = '';
        if (this.filtroUbicacion) this.filtroUbicacion.value = '';
        if (this.filtroMozo) this.filtroMozo.value = '';

        const filas = document.querySelectorAll('.mesa-row');
        filas.forEach(fila => {
            fila.style.display = '';
        });

        if (this.numMesas) {
            this.numMesas.textContent = filas.length;
        }

        const filaNoResultados = document.getElementById('fila-no-mesas');
        if (filaNoResultados) {
            filaNoResultados.remove();
        }
    }

    changeMesaStatus(button) {
        if (button.classList.contains('disabled')) {
            return;
        }

        const mesaId = button.dataset.mesaId;
        const nuevoEstado = button.dataset.status;

        // Mostrar confirmación
        if (!confirm(`¿Está seguro de cambiar la mesa ${mesaId} a estado "${nuevoEstado}"?`)) {
            return;
        }

        // Enviar solicitud AJAX
        fetch('index.php?route=mesas/update-estado', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `id_mesa=${mesaId}&nuevo_estado=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar página para ver cambios
                window.location.reload();
            } else {
                alert(data.error || 'Error al actualizar estado de la mesa');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.mesasManager = new MesasManager();
});