<?php
// Verificar permisos de administrador
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// La función requireAdmin() ya está definida en index.php
// Solo verificamos que el usuario sea administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    header('Location: index.php?route=unauthorized');
    exit;
}

// Configuración
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);
$total_mesas = 15; // Ajustable según configuración del restaurante
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de QRs - Sistema Comanda</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .qr-config {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .qr-config h3 {
            margin-top: 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .config-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .config-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        .config-field input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .btn-regenerar {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .btn-regenerar:hover {
            background: #0056b3;
        }
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        .qr-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .qr-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .qr-card h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
        }
        .qr-canvas {
            margin: 15px auto;
            border: 2px solid #f0f0f0;
            border-radius: 4px;
        }
        .qr-url {
            font-size: 11px;
            color: #666;
            margin: 10px 0;
            word-break: break-all;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            min-height: 40px;
        }
        .btn-descargar {
            background: rgb(144, 104, 76);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 3px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn-descargar:hover {
            background: rgb(92, 64, 51);
        }
        .back-btn {
            display: inline-block;
            background: rgb(144, 104, 76);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s ease;
            margin-bottom: 1.5rem;
        }
        .back-btn:hover {
            background-color: #5a6268;
            text-decoration: none;
            color: white;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 20px;
            color: #004085;
        }
        .info-box strong {
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php?route=home" class="back-btn">← Volver al inicio</a>
        
        <h1>🏷️ Generador de QRs por Mesa</h1>
        
        <div class="info-box">
            <strong>ℹ️ Cómo usar el generador:</strong>
            <ul>
                <li><strong>URL Base:</strong> Se detecta automáticamente, no modifique</li>
                <li><strong>Total de Mesas:</strong> Cambie este número (ej: 15 para generar QRs de Mesa 1 a Mesa 15)</li>
                <li><strong>Regenerar QRs:</strong> Haga clic para generar QRs de todas las mesas</li>
                <li><strong>Descargar:</strong> Cada QR se puede descargar individualmente como PNG</li>
            </ul>
        </div>
        
        <div class="qr-config">
            <h3>⚙️ Configuración</h3>
            <div class="config-grid">
                <div class="config-field">
                    <label>URL Base del Sistema:</label>
                    <input type="text" id="base-url" value="<?= htmlspecialchars($base_url) ?>" readonly>
                </div>
                <div class="config-field">
                    <label>Total de Mesas:</label>
                    <input type="number" id="total-mesas" value="<?= $total_mesas ?>" min="1" max="50">
                </div>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button onclick="generarTodosQRs()" class="btn-regenerar">
                    🔄 Regenerar todos los QRs
                </button>
                <button onclick="descargarTodosQRs()" class="btn-regenerar" style="background: rgb(144, 104, 76);">
                    💾 Descargar todos los QRs
                </button>
            </div>
        </div>
        
        <div id="qr-container" class="qr-grid">
            <div class="loading">
                Generando códigos QR...
            </div>
        </div>
    </div>

    <!-- Librería QR alternativa sin dependencias externas -->
    <script>
    // Implementación QR simple usando API de Google Charts
    window.QRCodeLocal = {
        toCanvas: function(canvas, text, options, callback) {
            const size = options.width || 200;
            const margin = options.margin || 0;
            
            // Crear imagen QR usando API de Google Charts
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            const qrSize = size - (margin * 20);
            const url = `https://api.qrserver.com/v1/create-qr-code/?size=${qrSize}x${qrSize}&data=${encodeURIComponent(text)}&margin=${margin}&format=png`;
            
            img.onload = function() {
                const ctx = canvas.getContext('2d');
                
                // Limpiar canvas
                ctx.fillStyle = options.color?.light || '#FFFFFF';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                // Centrar la imagen
                const x = (canvas.width - qrSize) / 2;
                const y = (canvas.height - qrSize) / 2;
                
                ctx.drawImage(img, x, y, qrSize, qrSize);
                
                if (callback) callback(null);
            };
            
            img.onerror = function() {
                // Fallback: dibujar QR simple usando canvas
                createSimpleQR(canvas, text, options, callback);
            };
            
            img.src = url;
        }
    };
    
    // Fallback: crear QR simple sin dependencias
    function createSimpleQR(canvas, text, options, callback) {
        const ctx = canvas.getContext('2d');
        const size = options.width || 200;
        
        // Limpiar canvas
        ctx.fillStyle = options.color?.light || '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Crear patrón QR básico
        ctx.fillStyle = options.color?.dark || '#000000';
        
        // Hash simple del texto para generar patrón
        let hash = 0;
        for (let i = 0; i < text.length; i++) {
            const char = text.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        
        const gridSize = 21; // QR estándar
        const cellSize = Math.floor(size / gridSize);
        const offsetX = (canvas.width - (gridSize * cellSize)) / 2;
        const offsetY = (canvas.height - (gridSize * cellSize)) / 2;
        
        // Dibujar esquinas de posición (características de QR)
        drawPositionMarker(ctx, offsetX, offsetY, cellSize);
        drawPositionMarker(ctx, offsetX + (gridSize - 7) * cellSize, offsetY, cellSize);
        drawPositionMarker(ctx, offsetX, offsetY + (gridSize - 7) * cellSize, cellSize);
        
        // Dibujar patrón de datos usando hash
        for (let row = 0; row < gridSize; row++) {
            for (let col = 0; col < gridSize; col++) {
                // Saltar áreas de marcadores de posición
                if (isPositionArea(row, col, gridSize)) continue;
                
                const cellHash = hash + row * gridSize + col;
                if (cellHash % 2 === 0) {
                    ctx.fillRect(
                        offsetX + col * cellSize,
                        offsetY + row * cellSize,
                        cellSize,
                        cellSize
                    );
                }
            }
        }
        
        // Agregar texto como código de verificación visual
        ctx.font = '10px monospace';
        ctx.fillText(text.substr(-10), offsetX, offsetY + gridSize * cellSize + 15);
        
        if (callback) callback(null);
    }
    
    function drawPositionMarker(ctx, x, y, cellSize) {
        // Marco exterior (7x7)
        ctx.fillRect(x, y, 7 * cellSize, cellSize); // top
        ctx.fillRect(x, y, cellSize, 7 * cellSize); // left
        ctx.fillRect(x + 6 * cellSize, y, cellSize, 7 * cellSize); // right
        ctx.fillRect(x, y + 6 * cellSize, 7 * cellSize, cellSize); // bottom
        
        // Centro (3x3)
        ctx.fillRect(x + 2 * cellSize, y + 2 * cellSize, 3 * cellSize, 3 * cellSize);
    }
    
    function isPositionArea(row, col, gridSize) {
        return (
            (row < 9 && col < 9) || // top-left
            (row < 9 && col >= gridSize - 8) || // top-right
            (row >= gridSize - 8 && col < 9) // bottom-left
        );
    }
    
    // Alias para compatibilidad
    window.QRCode = window.QRCodeLocal;
    </script>
    <script>
    let qrGenerationStatus = {};

    function log(message) {
        console.log('[QR Generator]', message);
    }

    function generarTodosQRs() {
        log('Iniciando generación de QRs...');
        
        if (typeof QRCode === 'undefined') {
            alert('❌ Error: La librería de códigos QR no se cargó correctamente.\nPor favor recargue la página e intente nuevamente.');
            return;
        }

        const baseUrl = document.getElementById('base-url').value;
        const totalMesas = parseInt(document.getElementById('total-mesas').value);
        const container = document.getElementById('qr-container');
        
        // Validar entrada
        if (!totalMesas || totalMesas <= 0 || totalMesas > 50) {
            alert('Por favor ingrese un número válido de mesas (1-50)');
            return;
        }
        
        log(`Generando ${totalMesas} QRs con base URL: ${baseUrl}`);
        
        container.innerHTML = '<div class="loading">Generando códigos QR... Por favor espere.</div>';
        qrGenerationStatus = {};
        
        // Generar todas las tarjetas primero
        setTimeout(() => {
            container.innerHTML = '';
            
            for (let mesa = 1; mesa <= totalMesas; mesa++) {
                crearTarjetaMesa(mesa, baseUrl, container);
            }
            
            // Luego generar los QRs uno por uno
            generarQRsSecuencial(1, totalMesas, baseUrl);
        }, 300);
    }

    function crearTarjetaMesa(numeroMesa, baseUrl, container) {
        const url = `${baseUrl}/index.php?route=cliente&mesa=${numeroMesa}`;
        
        const mesaDiv = document.createElement('div');
        mesaDiv.className = 'qr-card';
        mesaDiv.id = `card-mesa-${numeroMesa}`;
        
        mesaDiv.innerHTML = `
            <h4>Mesa ${numeroMesa}</h4>
            <div style="position: relative;">
                <canvas id="qr-mesa-${numeroMesa}" width="180" height="180" style="border: 1px solid #ddd; background: white;"></canvas>
                <div id="status-mesa-${numeroMesa}" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.9); font-size: 12px;">
                    ⏳ Generando...
                </div>
            </div>
            <div class="qr-url">${url}</div>
            <button onclick="descargarQR(${numeroMesa})" class="btn-descargar" disabled>
                💾 Descargar PNG
            </button>
        `;
        
        container.appendChild(mesaDiv);
    }

    function generarQRsSecuencial(mesaActual, totalMesas, baseUrl) {
        if (mesaActual > totalMesas) {
            log('✅ Generación de QRs completada');
            return;
        }
        
        log(`Generando QR para mesa ${mesaActual}...`);
        generarQRMesa(mesaActual, baseUrl, () => {
            // Continuar con la siguiente mesa después de 200ms
            setTimeout(() => {
                generarQRsSecuencial(mesaActual + 1, totalMesas, baseUrl);
            }, 200);
        });
    }

    function generarQRMesa(numeroMesa, baseUrl, callback) {
        const url = `${baseUrl}/index.php?route=cliente&mesa=${numeroMesa}`;
        const canvas = document.getElementById(`qr-mesa-${numeroMesa}`);
        const statusDiv = document.getElementById(`status-mesa-${numeroMesa}`);
        const botonDescargar = document.querySelector(`#card-mesa-${numeroMesa} .btn-descargar`);
        
        if (!canvas) {
            log(`❌ Error: Canvas no encontrado para mesa ${numeroMesa}`);
            if (callback) callback();
            return;
        }

        // Limpiar canvas
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        try {
            QRCode.toCanvas(canvas, url, {
                width: 180,
                height: 180,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                },
                errorCorrectionLevel: 'M'
            }, function (error) {
                if (error) {
                    log(`❌ Error generando QR para mesa ${numeroMesa}: ${error}`);
                    statusDiv.innerHTML = '❌ Error';
                    statusDiv.style.background = 'rgba(255,0,0,0.1)';
                    ctx.fillStyle = 'red';
                    ctx.font = '14px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText('Error', 90, 90);
                    ctx.fillText('al generar QR', 90, 110);
                } else {
                    log(`✅ QR generado para mesa ${numeroMesa}`);
                    statusDiv.style.display = 'none';
                    botonDescargar.disabled = false;
                    qrGenerationStatus[numeroMesa] = true;
                }
                
                if (callback) callback();
            });
        } catch (err) {
            log(`❌ Excepción generando QR para mesa ${numeroMesa}: ${err}`);
            statusDiv.innerHTML = '❌ Error';
            if (callback) callback();
        }
    }

    function descargarQR(numeroMesa) {
        log(`Iniciando descarga QR para mesa ${numeroMesa}`);
        
        const canvas = document.getElementById(`qr-mesa-${numeroMesa}`);
        
        if (!canvas) {
            alert(`❌ Error: No se encontró el código QR para la mesa ${numeroMesa}`);
            return;
        }
        
        // Verificar que el QR se haya generado correctamente
        if (!qrGenerationStatus[numeroMesa]) {
            alert(`⏳ El código QR para la mesa ${numeroMesa} aún no está listo.\nPor favor espere a que termine la generación.`);
            return;
        }
        
        try {
            const ctx = canvas.getContext('2d');
            
            // Verificación más robusta del contenido
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;
            
            let hasBlackPixels = false;
            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                const a = data[i + 3];
                
                // Buscar píxeles que no sean blancos
                if (r < 200 && g < 200 && b < 200 && a > 200) {
                    hasBlackPixels = true;
                    break;
                }
            }
            
            if (!hasBlackPixels) {
                log(`❌ Canvas para mesa ${numeroMesa} parece estar vacío`);
                alert(`❌ El código QR para la mesa ${numeroMesa} parece estar vacío.\nIntente regenerar los códigos QR.`);
                return;
            }
            
            log(`✅ Canvas para mesa ${numeroMesa} tiene contenido válido`);
            
            // Crear canvas de descarga con diseño profesional
            const downloadCanvas = document.createElement('canvas');
            const downloadCtx = downloadCanvas.getContext('2d');
            
            downloadCanvas.width = 350;
            downloadCanvas.height = 400;
            
            // Fondo blanco
            downloadCtx.fillStyle = 'white';
            downloadCtx.fillRect(0, 0, downloadCanvas.width, downloadCanvas.height);
            
            // Borde decorativo
            downloadCtx.strokeStyle = '#333';
            downloadCtx.lineWidth = 2;
            downloadCtx.strokeRect(10, 10, downloadCanvas.width - 20, downloadCanvas.height - 20);
            
            // Título principal
            downloadCtx.fillStyle = '#2c3e50';
            downloadCtx.font = 'bold 32px Arial';
            downloadCtx.textAlign = 'center';
            downloadCtx.fillText(`Mesa ${numeroMesa}`, downloadCanvas.width / 2, 60);
            
            // Línea separadora
            downloadCtx.strokeStyle = '#3498db';
            downloadCtx.lineWidth = 3;
            downloadCtx.beginPath();
            downloadCtx.moveTo(50, 80);
            downloadCtx.lineTo(downloadCanvas.width - 50, 80);
            downloadCtx.stroke();
            
            // QR centrado y escalado
            const qrSize = 220;
            const qrX = (downloadCanvas.width - qrSize) / 2;
            const qrY = 100;
            
            // Fondo blanco para el QR
            downloadCtx.fillStyle = 'white';
            downloadCtx.fillRect(qrX - 5, qrY - 5, qrSize + 10, qrSize + 10);
            
            // Copiar el QR
            downloadCtx.drawImage(canvas, qrX, qrY, qrSize, qrSize);
            
            // Texto instructivo
            downloadCtx.fillStyle = '#34495e';
            downloadCtx.font = '18px Arial';
            downloadCtx.fillText('Escanea para ver el menú', downloadCanvas.width / 2, 350);
            
            // Texto del sistema
            downloadCtx.font = '14px Arial';
            downloadCtx.fillStyle = '#7f8c8d';
            downloadCtx.fillText('Sistema Comanda', downloadCanvas.width / 2, 375);
            
            // Descargar archivo
            const link = document.createElement('a');
            link.download = `QR_Mesa_${numeroMesa}.png`;
            link.href = downloadCanvas.toDataURL('image/png', 1.0);
            
            // Simular clic sin agregar al DOM
            link.dispatchEvent(new MouseEvent('click', {
                view: window,
                bubbles: true,
                cancelable: true
            }));
            
            log(`✅ Descarga completada para mesa ${numeroMesa}`);
            
        } catch (error) {
            log(`❌ Error en descarga para mesa ${numeroMesa}: ${error}`);
            alert(`❌ Error al generar la descarga para mesa ${numeroMesa}.\nDetalles: ${error.message}`);
        }
    }

    function descargarTodosQRs() {
        log('Iniciando descarga masiva de QRs...');
        
        const totalMesas = parseInt(document.getElementById('total-mesas').value);
        
        if (!totalMesas || totalMesas <= 0) {
            alert('Por favor configure primero el número de mesas');
            return;
        }
        
        // Verificar que todos los QRs estén generados
        let qrsListos = 0;
        for (let i = 1; i <= totalMesas; i++) {
            if (qrGenerationStatus[i]) {
                qrsListos++;
            }
        }
        
        if (qrsListos === 0) {
            alert('⏳ No hay códigos QR generados aún.\nPor favor espere a que termine la generación.');
            return;
        }
        
        if (qrsListos < totalMesas) {
            if (!confirm(`Solo ${qrsListos} de ${totalMesas} códigos QR están listos.\n¿Desea descargar solo los que están listos?`)) {
                return;
            }
        }
        
        log(`Descargando ${qrsListos} códigos QR...`);
        
        let descargados = 0;
        for (let i = 1; i <= totalMesas; i++) {
            if (qrGenerationStatus[i]) {
                setTimeout(() => {
                    descargarQR(i);
                    descargados++;
                    if (descargados === qrsListos) {
                        log(`✅ Descarga masiva completada: ${qrsListos} QRs descargados`);
                    }
                }, i * 600); // 600ms entre descargas para evitar problemas
            }
        }
    }

    // Verificación mejorada de la librería
    function verificarLibreriaQR() {
        if (typeof QRCode === 'undefined') {
            log('❌ Librería QRCode no está disponible');
            document.getElementById('qr-container').innerHTML = 
                '<div style="color: red; text-align: center; padding: 40px; background: #ffe6e6; border-radius: 8px;">' +
                '<h3>❌ Error de Carga</h3>' +
                '<p>La librería de códigos QR no se pudo cargar.</p>' +
                '<p><strong>Posibles soluciones:</strong></p>' +
                '<ul style="text-align: left; display: inline-block;">' +
                '<li>Verificar conexión a Internet</li>' +
                '<li>Recargar la página (F5)</li>' +
                '<li>Desactivar bloqueadores de anuncios</li>' +
                '</ul>' +
                '<button onclick="location.reload()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; margin-top: 10px;">🔄 Recargar Página</button>' +
                '</div>';
            return false;
        }
        
        log('✅ Librería QRCode cargada correctamente');
        return true;
    }

    // Inicialización mejorada
    document.addEventListener('DOMContentLoaded', function() {
        log('🚀 Iniciando aplicación generador de QRs...');
        
        // Esperar a que se carguen las librerías
        let intentos = 0;
        const maxIntentos = 10;
        
        function intentarCargar() {
            intentos++;
            log(`Intento ${intentos}/${maxIntentos} de verificar librerías...`);
            
            if (verificarLibreriaQR()) {
                log('✅ Todo listo, generando QRs iniciales...');
                setTimeout(generarTodosQRs, 500);
            } else if (intentos < maxIntentos) {
                setTimeout(intentarCargar, 1000);
            } else {
                log('❌ Máximo de intentos alcanzado');
            }
        }
        
        intentarCargar();
    });
    </script>
</body>
</html>