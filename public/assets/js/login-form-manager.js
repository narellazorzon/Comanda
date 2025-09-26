/**
 * Login Form Handler - Sistema Comanda
 *
 * Este módulo maneja toda la lógica de JavaScript para el formulario de login,
 * incluyendo validación, efectos visuales y manejo de errores.
 */

class LoginFormManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupElements();
        this.setupEventListeners();
        this.loadSavedEmail();
    }

    setupElements() {
        this.form = document.getElementById('loginForm');
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this.submitBtn = document.getElementById('submitBtn');
        this.buttonText = document.getElementById('buttonText');
        this.buttonLoader = document.getElementById('buttonLoader');
        this.alertContainer = document.getElementById('alertContainer');
    }

    setupEventListeners() {
        if (!this.form) return;

        // Limpiar errores al escribir
        [this.emailInput, this.passwordInput].forEach(input => {
            input.addEventListener('input', () => {
                const inputWrapper = input.closest('.input-wrapper');
                if (inputWrapper) {
                    inputWrapper.classList.remove('error');
                }
                this.clearAlert();
            });

            input.addEventListener('focus', () => {
                const inputWrapper = input.closest('.input-wrapper');
                if (inputWrapper) {
                    inputWrapper.classList.add('focused');
                }
            });

            input.addEventListener('blur', () => {
                const inputWrapper = input.closest('.input-wrapper');
                if (inputWrapper) {
                    inputWrapper.classList.remove('focused');
                }
            });
        });

        // Prevenir envío múltiple del formulario
        this.form.addEventListener('submit', (e) => {
            this.handleSubmit(e);
        });
    }

    handleSubmit(e) {
        if (this.submitBtn.disabled) {
            e.preventDefault();
            return false;
        }

        // Validar campos antes del envío
        const email = this.emailInput.value.trim();
        const password = this.passwordInput.value;

        if (!email || !password) {
            e.preventDefault();
            this.showErrorAlert('Por favor, completa todos los campos');
            return false;
        }

        if (!this.isValidEmail(email)) {
            e.preventDefault();
            const inputWrapper = this.emailInput.closest('.input-wrapper');
            if (inputWrapper) {
                inputWrapper.classList.add('error');
            }
            this.showErrorAlert('El formato del email no es válido');
            return false;
        }

        // Mostrar estado de carga
        this.submitBtn.disabled = true;
        this.submitBtn.classList.add('loading');
        if (this.buttonText) this.buttonText.style.opacity = '0';
        if (this.buttonLoader) this.buttonLoader.style.opacity = '1';

        // Mantener el email en la URL para persistencia
        const formData = new FormData(this.form);
        formData.append('email_param', email);
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    showErrorAlert(message) {
        if (!this.alertContainer) return;

        this.alertContainer.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Scroll al inicio del contenedor si el alert está fuera de vista
        const rect = this.alertContainer.getBoundingClientRect();
        if (rect.top < 0) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    clearAlert() {
        if (!this.alertContainer) return;
        this.alertContainer.innerHTML = '';
    }

    loadSavedEmail() {
        const urlParams = new URLSearchParams(window.location.search);
        const emailParam = urlParams.get('email');

        if (emailParam && this.emailInput) {
            this.emailInput.value = emailParam;
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.loginFormManager = new LoginFormManager();
});