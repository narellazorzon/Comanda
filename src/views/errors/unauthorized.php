<?php
// src/views/errors/unauthorized.php
?>

<div style="text-align: center; padding: 4rem 2rem;">
    <div style="font-size: 6rem; margin-bottom: 1rem;">🚫</div>
    <h1 style="color: var(--secondary); margin-bottom: 1rem;">Acceso No Autorizado</h1>
    <p style="color: #666; margin-bottom: 2rem; font-size: 1.1rem;">
        No tienes permisos para acceder a esta sección del sistema.
    </p>
    
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="../../public/index.php?route=home" class="button">
            🏠 Ir al Inicio
        </a>
        <a href="../../public/index.php?route=logout" class="button" style="background: var(--accent); color: var(--text);">
            🚪 Cerrar Sesión
        </a>
    </div>
    
    <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; max-width: 500px; margin-left: auto; margin-right: auto;">
        <h3 style="color: var(--secondary); margin-bottom: 0.5rem;">¿Necesitas ayuda?</h3>
        <p style="color: #666; margin: 0; font-size: 0.9rem;">
            Si crees que deberías tener acceso a esta función, contacta al administrador del sistema.
        </p>
    </div>
</div>

