/**
 * MEJORAS DE EXPERIENCIA MÓVIL
 * Optimizaciones JavaScript para mejor usabilidad en dispositivos táctiles
 */

(function() {
    'use strict';

    // Detectar si es dispositivo móvil
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    // Inicializar mejoras al cargar el DOM
    document.addEventListener('DOMContentLoaded', function() {
        if (isMobile || isTouchDevice) {
            initMobileEnhancements();
        }
        initResponsiveTables();
        initSmoothScroll();
        initFormEnhancements();
        initModalEnhancements();
    });

    /**
     * Mejoras específicas para móvil
     */
    function initMobileEnhancements() {
        // Agregar clase al body para estilos específicos
        document.body.classList.add('is-mobile');

        // Mejorar interacciones táctiles
        improveTouchInteractions();

        // Optimizar menú de navegación
        improveNavigation();

        // Mejorar scroll en elementos con overflow
        improveScrollableElements();

        // Prevenir zoom accidental en double tap
        preventAccidentalZoom();
    }

    /**
     * Mejorar interacciones táctiles
     */
    function improveTouchInteractions() {
        // Agregar feedback táctil a botones
        const buttons = document.querySelectorAll('button, .btn, [role="button"]');
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.classList.add('touch-active');
            });

            button.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.classList.remove('touch-active');
                }, 150);
            });
        });

        // Mejorar swipe en elementos scrollables
        const scrollables = document.querySelectorAll('.table-responsive, .menu-filters, .filter-container');
        scrollables.forEach(element => {
            let startX, scrollLeft;

            element.addEventListener('touchstart', (e) => {
                startX = e.touches[0].pageX - element.offsetLeft;
                scrollLeft = element.scrollLeft;
            });

            element.addEventListener('touchmove', (e) => {
                if (!startX) return;
                e.preventDefault();
                const x = e.touches[0].pageX - element.offsetLeft;
                const walk = (x - startX) * 2;
                element.scrollLeft = scrollLeft - walk;
            });
        });
    }

    /**
     * Mejorar navegación móvil
     */
    function improveNavigation() {
        const navMenu = document.querySelector('.nav-menu');
        const hamburger = document.querySelector('.nav-hamburger');
        const navLinks = document.querySelectorAll('.nav-link');

        if (hamburger && navMenu) {
            // Cerrar menú al tocar fuera
            document.addEventListener('touchstart', function(e) {
                if (navMenu.classList.contains('active')) {
                    if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
                        navMenu.classList.remove('active');
                        hamburger.classList.remove('active');
                    }
                }
            });

            // Cerrar menú al seleccionar un enlace
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        navMenu.classList.remove('active');
                        hamburger.classList.remove('active');
                    }
                });
            });
        }
    }

    /**
     * Mejorar elementos con scroll
     */
    function improveScrollableElements() {
        const scrollables = document.querySelectorAll('.table-responsive, .modal-body, .menu-filters');

        scrollables.forEach(element => {
            // Agregar indicador visual de scroll disponible
            if (element.scrollWidth > element.clientWidth) {
                element.classList.add('has-horizontal-scroll');

                // Crear indicador de scroll
                const indicator = document.createElement('div');
                indicator.className = 'scroll-indicator';
                indicator.innerHTML = '← Desliza →';
                element.appendChild(indicator);

                // Ocultar indicador después del primer scroll
                element.addEventListener('scroll', function() {
                    if (this.scrollLeft > 10) {
                        indicator.style.display = 'none';
                    }
                }, { once: true });
            }

            // Momentum scrolling en iOS
            element.style.webkitOverflowScrolling = 'touch';
        });
    }

    /**
     * Prevenir zoom accidental
     */
    function preventAccidentalZoom() {
        let lastTouchEnd = 0;

        document.addEventListener('touchend', function(e) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Prevenir zoom en inputs (iOS)
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                if (isMobile) {
                    document.querySelector('meta[name="viewport"]').setAttribute('content',
                        'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0');
                }
            });

            input.addEventListener('blur', function() {
                if (isMobile) {
                    document.querySelector('meta[name="viewport"]').setAttribute('content',
                        'width=device-width, initial-scale=1.0');
                }
            });
        });
    }

    /**
     * Tablas responsive mejoradas
     */
    function initResponsiveTables() {
        const tables = document.querySelectorAll('.table');

        tables.forEach(table => {
            // Envolver tabla si no está envuelta
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }

            // En móvil, permitir vista de cards alternativa
            if (window.innerWidth <= 768) {
                createCardView(table);
            }
        });

        // Toggle entre vista tabla y cards
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('toggle-table-view')) {
                const tableWrapper = e.target.closest('.table-wrapper');
                if (tableWrapper) {
                    tableWrapper.classList.toggle('show-cards');
                    e.target.textContent = tableWrapper.classList.contains('show-cards') ?
                        '📊 Vista Tabla' : '📇 Vista Tarjetas';
                }
            }
        });
    }

    /**
     * Crear vista de cards para tablas
     */
    function createCardView(table) {
        const wrapper = table.closest('.table-responsive');
        if (!wrapper || wrapper.querySelector('.table-cards')) return;

        const cards = document.createElement('div');
        cards.className = 'table-cards mobile-cards';

        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const card = document.createElement('div');
            card.className = 'table-card mobile-card';

            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    const field = document.createElement('div');
                    field.className = 'card-field';
                    field.innerHTML = `
                        <span class="field-label">${headers[index]}:</span>
                        <span class="field-value">${cell.innerHTML}</span>
                    `;
                    card.appendChild(field);
                }
            });

            cards.appendChild(card);
        });

        // Agregar botón de toggle
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-sm toggle-table-view';
        toggleBtn.textContent = '📇 Vista Tarjetas';
        wrapper.parentNode.insertBefore(toggleBtn, wrapper);

        // Agregar contenedor de cards
        wrapper.parentNode.insertBefore(cards, wrapper.nextSibling);

        // Wrapper general
        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'table-wrapper';
        wrapper.parentNode.insertBefore(tableWrapper, toggleBtn);
        tableWrapper.appendChild(toggleBtn);
        tableWrapper.appendChild(wrapper);
        tableWrapper.appendChild(cards);
    }

    /**
     * Smooth scroll mejorado
     */
    function initSmoothScroll() {
        // Solo si el navegador no lo soporta nativamente
        if (!('scrollBehavior' in document.documentElement.style)) {
            const links = document.querySelectorAll('a[href^="#"]');

            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').slice(1);
                    const target = document.getElementById(targetId);

                    if (target) {
                        const offset = target.offsetTop - 80; // Compensar header fijo
                        window.scrollTo({
                            top: offset,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        }
    }

    /**
     * Mejoras en formularios
     */
    function initFormEnhancements() {
        // Auto-resize para textareas
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        // Validación visual en tiempo real
        const inputs = document.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });

        // Mostrar/ocultar contraseña
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(input => {
            const wrapper = document.createElement('div');
            wrapper.className = 'password-wrapper';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'password-toggle';
            toggleBtn.innerHTML = '👁️';
            wrapper.appendChild(toggleBtn);

            toggleBtn.addEventListener('click', function() {
                if (input.type === 'password') {
                    input.type = 'text';
                    this.innerHTML = '🙈';
                } else {
                    input.type = 'password';
                    this.innerHTML = '👁️';
                }
            });
        });
    }

    /**
     * Mejoras en modales
     */
    function initModalEnhancements() {
        const modals = document.querySelectorAll('.modal');

        modals.forEach(modal => {
            // Cerrar modal con swipe down
            if (isTouchDevice) {
                let startY = 0;
                let currentY = 0;

                modal.addEventListener('touchstart', function(e) {
                    startY = e.touches[0].pageY;
                });

                modal.addEventListener('touchmove', function(e) {
                    currentY = e.touches[0].pageY;
                    const deltaY = currentY - startY;

                    // Si el swipe es hacia abajo y es significativo
                    if (deltaY > 100 && startY < 100) {
                        const modalContent = modal.querySelector('.modal-content');
                        if (modalContent) {
                            modalContent.style.transform = `translateY(${deltaY}px)`;
                            modalContent.style.opacity = 1 - (deltaY / 300);
                        }
                    }
                });

                modal.addEventListener('touchend', function(e) {
                    const deltaY = currentY - startY;
                    const modalContent = modal.querySelector('.modal-content');

                    if (deltaY > 150 && startY < 100) {
                        // Cerrar modal
                        if (typeof bootstrap !== 'undefined') {
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        } else {
                            modal.style.display = 'none';
                        }
                    }

                    // Reset estilos
                    if (modalContent) {
                        modalContent.style.transform = '';
                        modalContent.style.opacity = '';
                    }

                    startY = 0;
                    currentY = 0;
                });
            }

            // Trap focus en modal
            modal.addEventListener('shown.bs.modal', function() {
                trapFocus(modal);
            });
        });
    }

    /**
     * Trap focus helper
     */
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select'
        );
        const firstFocusableElement = focusableElements[0];
        const lastFocusableElement = focusableElements[focusableElements.length - 1];

        element.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusableElement) {
                        lastFocusableElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusableElement) {
                        firstFocusableElement.focus();
                        e.preventDefault();
                    }
                }
            }

            if (e.key === 'Escape') {
                element.style.display = 'none';
            }
        });
    }

    // Exportar funciones útiles
    window.MobileEnhancements = {
        isMobile: isMobile,
        isTouchDevice: isTouchDevice,
        initResponsiveTables: initResponsiveTables,
        improveScrollableElements: improveScrollableElements
    };

})();