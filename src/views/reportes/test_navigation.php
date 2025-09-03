<?php 
session_start();

// Verificar autenticación y permisos
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    header('Location: ../../public/unauthorized.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Usar las mismas variables de color del dashboard principal */
:root {
  --background: #f7f1e1; /* beige muy claro */
  --surface: #eee0be; /* blanco para cartas y tablas */
  --primary:rgb(95, 74, 38); /* beige medio */
  --secondary: #a1866f; /* marrón suave */
  --accent: #eee0be; /* tonalidad intermedia */
  --text: #3f3f3f; /* gris oscuro para texto */
  --text-light: #ffffff; /* texto claro sobre fondo oscuro */
}

body {
    background-color: var(--background);
    font-family: "Segoe UI", Tahoma, sans-serif;
    margin: 0;
    padding: 0;
    color: var(--text);
}

nav {
    background-color: var(--primary);
    padding: 0.75rem 1rem;
    border-bottom: 2px solid var(--secondary);
}

nav a {
    color: var(--text);
    text-decoration: none;
    margin-right: 1rem;
    font-weight: 600;
}

nav a:hover {
    color: var(--secondary);
}

main {
    max-width: 960px;
    margin: 1.5rem auto;
    padding: 0 1rem;
}

.test-section {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.test-section h2 {
    color: var(--secondary);
    margin-bottom: 1rem;
}

.test-links {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.test-link {
    background: var(--secondary);
    color: var(--text-light);
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s ease;
}

.test-link:hover {
    background-color: #8b5e46;
    text-decoration: none;
    color: var(--text-light);
}
</style>

<div class="test-section">
    <h2>🧪 Prueba de Navegación</h2>
    <p>Esta página está en la carpeta <strong>reportes/</strong> y debería permitir navegar correctamente a todas las secciones.</p>
    
    <div class="test-links">
        <a href="../index.php" class="test-link">🏠 Ir a Inicio</a>
        <a href="../cme_mesas.php" class="test-link">🪑 Ir a Mesas</a>
        <a href="../cme_pedidos.php" class="test-link">📋 Ir a Pedidos</a>
        <a href="../cme_mozos.php" class="test-link">👥 Ir a Mozos</a>
        <a href="../cme_carta.php" class="test-link">🍽️ Ir a Carta</a>
        <a href="index.php" class="test-link">📊 Ir a Reportes</a>
    </div>
    
    <p><strong>Estado actual:</strong> Estás en la carpeta reportes como administrador.</p>
    <p><strong>Usuario:</strong> <?= htmlspecialchars($_SESSION['user']['nombre'] ?? 'N/A') ?></p>
    <p><strong>Rol:</strong> <?= htmlspecialchars($_SESSION['user']['rol'] ?? 'N/A') ?></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
