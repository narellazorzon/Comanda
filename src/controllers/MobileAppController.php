<?php
// src/controllers/MobileAppController.php
namespace App\Controllers;

use App\Config\Database;
use PDO;

/**
 * Controlador para la aplicación móvil de mozos
 *
 * Este controlador maneja todas las operaciones relacionadas con la app móvil,
 * incluyendo autenticación, pedidos y notificaciones.
 */
class MobileAppController
{
    /**
     * Página principal de la app móvil
     */
    public function index()
    {
        // Verificar si es un dispositivo móvil
        $isMobile = $this->isMobileDevice();

        if (!$isMobile) {
            // Si no es móvil, redirigir al login normal
            header('Location: index.php?route=login');
            exit;
        }

        // Si no está logueado, mostrar login de la app
        if (!isset($_SESSION['usuario'])) {
            $this->showMobileLogin();
            return;
        }

        // Verificar que sea mozo
        if ($_SESSION['usuario']['rol'] !== 'mozo') {
            session_destroy();
            $this->showMobileLogin();
            return;
        }

        // Cargar vista principal de la app
        $idMozo = $_SESSION['usuario']['id_usuario'];
        $pedidos = $this->getPedidosActivosByMozo($idMozo);

        require __DIR__ . '/../views/mobile/app.php';
    }

    /**
     * API para login desde la app móvil
     */
    public function apiLogin()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['email']) || !isset($input['password'])) {
            echo json_encode(['success' => false, 'error' => 'Email y contraseña requeridos']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("
                SELECT u.*, m.numero as mesa_asignada
                FROM usuarios u
                LEFT JOIN mesas m ON u.id_usuario = m.id_mozo AND m.estado = 'ocupada'
                WHERE u.email = ? AND u.rol = 'mozo' AND u.estado = 'activo'
            ");
            $stmt->execute([$input['email']]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($input['password'], $usuario['contrasena'])) {
                // Iniciar sesión
                $_SESSION['usuario'] = [
                    'id_usuario' => $usuario['id_usuario'],
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'email' => $usuario['email'],
                    'rol' => $usuario['rol']
                ];

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id_usuario' => $usuario['id_usuario'],
                        'nombre' => $usuario['nombre'],
                        'apellido' => $usuario['apellido'],
                        'mesa_asignada' => $usuario['mesa_asignada']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
        }
    }

    /**
     * API para logout desde la app móvil
     */
    public function apiLogout()
    {
        header('Content-Type: application/json');

        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Sesión cerrada']);
    }

    /**
     * Obtiene pedidos activos para un mozo
     */
    public function apiPedidosActivos()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        try {
            $idMozo = $_SESSION['usuario']['id_usuario'];
            $pedidos = $this->getPedidosActivosByMozo($idMozo);

            echo json_encode(['success' => true, 'data' => $pedidos]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Envía notificación push a los mozos
     */
    public function sendNotification($idMozo, $titulo, $mensaje, $data = [])
    {
        // Aquí se implementaría la lógica para enviar notificaciones push
        // Por ahora, solo guardamos en la base de datos para futuras implementaciones
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("
            INSERT INTO notificaciones_mozos (id_mozo, titulo, mensaje, data, leida, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([
            $idMozo,
            $titulo,
            $mensaje,
            json_encode($data)
        ]);

        return true;
    }

    /**
     * Obtiene notificaciones no leídas para un mozo
     */
    public function apiNotificaciones()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        try {
            $idMozo = $_SESSION['usuario']['id_usuario'];
            $db = (new Database())->getConnection();

            $stmt = $db->prepare("
                SELECT * FROM notificaciones_mozos
                WHERE id_mozo = ? AND leida = 0
                ORDER BY created_at DESC
            ");
            $stmt->execute([$idMozo]);
            $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Marcar como leídas
            $stmt = $db->prepare("
                UPDATE notificaciones_mozos SET leida = 1
                WHERE id_mozo = ? AND leida = 0
            ");
            $stmt->execute([$idMozo]);

            echo json_encode(['success' => true, 'data' => $notificaciones]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Actualiza el estado de un pedido desde la app
     */
    public function apiActualizarEstadoPedido()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['usuario'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['id_pedido']) || !isset($input['nuevo_estado'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        try {
            $idPedido = (int)$input['id_pedido'];
            $nuevoEstado = $input['nuevo_estado'];
            $idMozo = $_SESSION['usuario']['id_usuario'];

            // Verificar que el pedido pertenezca al mozo
            if (!$this->verifyPedidoMozo($idPedido, $idMozo)) {
                echo json_encode(['success' => false, 'error' => 'No autorizado']);
                exit;
            }

            // Actualizar estado
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
            $result = $stmt->execute([$nuevoEstado, $idPedido]);

            if ($result) {
                // Enviar notificación si el pedido está listo
                if ($nuevoEstado === 'listo_para_servir') {
                    $this->sendNotification(
                        $idMozo,
                        'Pedido Listo',
                        "El pedido #$idPedido está listo para servir",
                        ['id_pedido' => $idPedido]
                    );
                }

                echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Muestra el formulario de login para la app móvil
     */
    private function showMobileLogin()
    {
        require __DIR__ . '/../views/mobile/login.php';
    }

    /**
     * Verifica si el dispositivo es móvil
     */
    private function isMobileDevice()
    {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
    }

    /**
     * Obtiene pedidos activos por mozo
     */
    private function getPedidosActivosByMozo($idMozo)
    {
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("
            SELECT p.*, m.numero as numero_mesa,
                   GROUP_CONCAT(
                       CONCAT(c.nombre, ' (', dp.cantidad, ')')
                       SEPARATOR ', '
                   ) as items
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
            LEFT JOIN carta c ON dp.id_item = c.id_item
            WHERE p.id_mozo = ? AND p.estado NOT IN ('cerrado', 'cancelado')
            GROUP BY p.id_pedido
            ORDER BY p.fecha_hora ASC
        ");
        $stmt->execute([$idMozo]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un pedido pertenece a un mozo
     */
    private function verifyPedidoMozo($idPedido, $idMozo)
    {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT id_mozo FROM pedidos WHERE id_pedido = ?");
        $stmt->execute([$idPedido]);
        $pedidoMozo = $stmt->fetchColumn();

        return $pedidoMozo == $idMozo;
    }
}