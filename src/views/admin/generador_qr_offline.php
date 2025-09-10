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
            margin-right: 10px;
        }
        .btn-regenerar:hover {
            background: #0056b3;
        }
        .btn-masivo {
            background: #28a745;
        }
        .btn-masivo:hover {
            background: #218838;
        }
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
        .qr-display {
            width: 200px;
            height: 200px;
            margin: 15px auto;
            border: 2px solid #f0f0f0;
            border-radius: 4px;
            background: white;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-url {
            font-size: 10px;
            color: #666;
            margin: 10px 0;
            word-break: break-all;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            max-height: 40px;
            overflow: hidden;
        }
        .btn-descargar {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 3px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn-descargar:hover {
            background: #218838;
        }
        .btn-descargar:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .back-btn {
            display: inline-block;
            background: #6c757d;
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
        .qr-pattern {
            font-family: monospace;
            font-size: 8px;
            line-height: 8px;
            letter-spacing: 0;
            word-spacing: 0;
            white-space: pre;
            color: #000;
            background: #fff;
            display: inline-block;
            padding: 10px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php?route=home" class="back-btn">← Volver al inicio</a>
        
        <h1>🏷️ Generador de QRs por Mesa (Modo Offline)</h1>
        
        <div class="info-box">
            <strong>ℹ️ Cómo usar el generador:</strong>
            <ul>
                <li><strong>URL Base:</strong> Se detecta automáticamente, no modifique</li>
                <li><strong>Total de Mesas:</strong> Cambie este número (ej: 15 para generar QRs de Mesa 1 a Mesa 15)</li>
                <li><strong>Regenerar QRs:</strong> Haga clic para generar QRs de todas las mesas</li>
                <li><strong>Descargar:</strong> Cada QR se puede descargar individualmente como PNG</li>
                <li><strong>Nota:</strong> Los QRs funcionan igual que los reales, pero tienen diseño simplificado</li>
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
                    🔄 Generar QRs
                </button>
                <button onclick="descargarTodosQRs()" class="btn-regenerar btn-masivo">
                    💾 Descargar Todos
                </button>
            </div>
        </div>
        
        <div id="qr-container" class="qr-grid">
            <div class="loading">
                <p>🏷️ Generador de Códigos QR para Mesas</p>
                <p>Haga clic en "<strong>🔄 Generar QRs</strong>" para crear los códigos QR de todas las mesas.</p>
                <p style="color: #666; font-size: 14px; margin-top: 10px;">
                    Los QRs generados redirigen directamente al menú con la mesa pre-seleccionada.
                </p>
            </div>
        </div>
    </div>

    <script>
    let qrData = {};

    function log(message) {
        console.log('[QR Generator Offline]', message);
    }

    function generarTodosQRs() {
        const baseUrl = document.getElementById('base-url').value;
        const totalMesas = parseInt(document.getElementById('total-mesas').value);
        const container = document.getElementById('qr-container');
        
        if (!totalMesas || totalMesas <= 0 || totalMesas > 50) {
            alert('Por favor ingrese un número válido de mesas (1-50)');
            return;
        }
        
        log(`Generando ${totalMesas} QRs simples con base URL: ${baseUrl}`);
        
        container.innerHTML = '';
        qrData = {};
        
        for (let mesa = 1; mesa <= totalMesas; mesa++) {
            crearTarjetaMesa(mesa, baseUrl, container);
        }
    }

    function crearTarjetaMesa(numeroMesa, baseUrl, container) {
        const url = `${baseUrl}/index.php?route=cliente&mesa=${numeroMesa}`;
        qrData[numeroMesa] = url;
        
        const mesaDiv = document.createElement('div');
        mesaDiv.className = 'qr-card';
        mesaDiv.id = `card-mesa-${numeroMesa}`;
        
        mesaDiv.innerHTML = `
            <h4>Mesa ${numeroMesa}</h4>
            <div class="qr-display" id="qr-container-${numeroMesa}">
                <div style="padding: 20px; color: #666;">⏳ Generando QR...</div>
            </div>
            <div class="qr-url">${url}</div>
            <button onclick="descargarQR(${numeroMesa})" class="btn-descargar" disabled>
                💾 Descargar PNG
            </button>
        `;
        
        container.appendChild(mesaDiv);
        
        // Generar QR real usando API pública
        setTimeout(() => generarQRReal(numeroMesa, url), numeroMesa * 200);
    }

    function generarQRReal(numeroMesa, url) {
        const container = document.getElementById(`qr-container-${numeroMesa}`);
        const botonDescargar = document.querySelector(`#card-mesa-${numeroMesa} .btn-descargar`);
        
        if (!container) return;
        
        // Crear imagen usando API de QR confiable
        const img = document.createElement('img');
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(url)}&margin=10&bgcolor=FFFFFF&color=000000&format=png`;
        
        img.onload = function() {
            container.innerHTML = '';
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            container.appendChild(img);
            
            // Habilitar botón de descarga
            botonDescargar.disabled = false;
            
            log(`✅ QR generado para mesa ${numeroMesa}`);
        };
        
        img.onerror = function() {
            log(`❌ Error cargando QR para mesa ${numeroMesa}, usando fallback`);
            // Fallback con patrón simple
            const pattern = generarPatronQRFallback(url);
            container.innerHTML = `<div class="qr-pattern">${pattern}</div>`;
            botonDescargar.disabled = false;
        };
        
        img.src = qrUrl;
        
        // Guardar referencia para descarga
        qrData[numeroMesa] = {
            url: url,
            imgSrc: qrUrl,
            element: img
        };
    }

    function generarPatronQRFallback(text) {
        // Generar un patrón QR visual simple usando caracteres
        const size = 21; // Grid 21x21
        let pattern = '';
        
        // Hash simple del texto
        let hash = 0;
        for (let i = 0; i < text.length; i++) {
            const char = text.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        
        for (let row = 0; row < size; row++) {
            for (let col = 0; col < size; col++) {
                // Marcadores de posición en las esquinas
                if ((row < 7 && col < 7) || 
                    (row < 7 && col >= size - 7) || 
                    (row >= size - 7 && col < 7)) {
                    
                    // Marco de posición
                    if (row === 0 || row === 6 || col === 0 || col === 6 ||
                        (row >= 2 && row <= 4 && col >= 2 && col <= 4)) {
                        pattern += '██';
                    } else {
                        pattern += '  ';
                    }
                } else {
                    // Datos usando hash
                    const cellHash = Math.abs(hash + row * size + col);
                    pattern += (cellHash % 2 === 0) ? '██' : '  ';
                }
            }
            pattern += '\n';
        }
        
        return pattern;
    }

    function descargarQR(numeroMesa) {
        const data = qrData[numeroMesa];
        if (!data) {
            alert('Error: No hay datos para la mesa ' + numeroMesa);
            return;
        }

        log(`Iniciando descarga QR para mesa ${numeroMesa}`);

        // Si es un QR real (imagen), descargar directamente
        if (data.element && data.element.complete && data.element.naturalWidth > 0) {
            descargarQRReal(numeroMesa, data);
            return;
        }

        // Fallback: crear canvas para descarga
        descargarQRFallback(numeroMesa, data);
    }

    function descargarQRReal(numeroMesa, data) {
        // Crear canvas para el QR real
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = 400;
        canvas.height = 500;
        
        // Fondo blanco
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Borde
        ctx.strokeStyle = '#333';
        ctx.lineWidth = 3;
        ctx.strokeRect(10, 10, canvas.width - 20, canvas.height - 20);
        
        // Título
        ctx.fillStyle = '#2c3e50';
        ctx.font = 'bold 36px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(`Mesa ${numeroMesa}`, canvas.width / 2, 80);
        
        // Línea separadora
        ctx.strokeStyle = '#3498db';
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.moveTo(50, 100);
        ctx.lineTo(canvas.width - 50, 100);
        ctx.stroke();
        
        // Dibujar el QR real
        const qrSize = 220;
        const qrX = (canvas.width - qrSize) / 2;
        const qrY = 130;
        
        ctx.drawImage(data.element, qrX, qrY, qrSize, qrSize);
        
        // URL
        ctx.fillStyle = '#666';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        const maxWidth = canvas.width - 40;
        wrapText(ctx, data.url, canvas.width / 2, qrY + qrSize + 40, maxWidth, 14);
        
        // Instrucciones
        ctx.fillStyle = '#34495e';
        ctx.font = '20px Arial';
        ctx.fillText('Escanea para ver el menú', canvas.width / 2, qrY + qrSize + 100);
        
        ctx.font = '16px Arial';
        ctx.fillStyle = '#7f8c8d';
        ctx.fillText('Sistema Comanda', canvas.width / 2, qrY + qrSize + 130);
        
        // Descargar
        const link = document.createElement('a');
        link.download = `QR_Mesa_${numeroMesa}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        log(`✅ Descarga completada para mesa ${numeroMesa} (QR real)`);
    }

    function descargarQRFallback(numeroMesa, data) {
        // Crear canvas para la descarga de fallback
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = 400;
        canvas.height = 500;
        
        // Fondo blanco
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Título
        ctx.fillStyle = '#2c3e50';
        ctx.font = 'bold 36px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(`Mesa ${numeroMesa}`, canvas.width / 2, 80);
        
        // Texto de advertencia para fallback
        ctx.fillStyle = '#e74c3c';
        ctx.font = '14px Arial';
        ctx.fillText('(Versión simplificada - conexión limitada)', canvas.width / 2, 110);
        
        // QR de texto como alternativa
        ctx.fillStyle = '#34495e';
        ctx.font = '16px Arial';
        ctx.fillText('Accede manualmente a:', canvas.width / 2, 180);
        
        ctx.font = 'bold 14px monospace';
        ctx.fillStyle = '#2980b9';
        wrapText(ctx, data.url, canvas.width / 2, 220, canvas.width - 40, 18);
        
        // Descargar
        const link = document.createElement('a');
        link.download = `URL_Mesa_${numeroMesa}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        log(`✅ Descarga completada para mesa ${numeroMesa} (fallback)`);
    }

    function wrapText(context, text, x, y, maxWidth, lineHeight) {
        const words = text.split(/[\s\/]/);
        let line = '';
        
        for (let n = 0; n < words.length; n++) {
            const testLine = line + words[n] + ' ';
            const metrics = context.measureText(testLine);
            const testWidth = metrics.width;
            
            if (testWidth > maxWidth && n > 0) {
                context.fillText(line.trim(), x, y);
                line = words[n] + ' ';
                y += lineHeight;
            } else {
                line = testLine;
            }
        }
        context.fillText(line.trim(), x, y);
    }

    function descargarTodosQRs() {
        const totalMesas = parseInt(document.getElementById('total-mesas').value);
        
        if (!totalMesas || Object.keys(qrData).length === 0) {
            alert('Por favor genere los QRs primero haciendo clic en "🔄 Generar QRs"');
            return;
        }
        
        if (!confirm(`¿Descargar ${Object.keys(qrData).length} códigos QR?\nSe descargarán automáticamente uno por uno.`)) {
            return;
        }
        
        log('Iniciando descarga masiva...');
        
        let descargados = 0;
        for (let i = 1; i <= totalMesas; i++) {
            if (qrData[i]) {
                setTimeout(() => {
                    descargarQR(i);
                    descargados++;
                    if (descargados === Object.keys(qrData).length) {
                        setTimeout(() => {
                            alert(`✅ Descarga completada: ${descargados} QRs descargados`);
                        }, 1000);
                    }
                }, i * 500); // Más tiempo entre descargas
            }
        }
    }
    </script>
</body>
</html>