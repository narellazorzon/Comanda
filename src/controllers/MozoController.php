<?php
// src/controllers/MozoController.php
namespace App\Controllers;

use App\Models\Usuario;
use App\Models\Mesa;
use App\Models\LlamadoMesa;

function url($route = '', $params = []) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_url = $protocol . '://' . $host . dirname($script_name);
    
    $url = $base_url . '/index.php';
    
    if ($route) {
        $url .= '?route=' . $route;
    }
    
    // Agregar parámetros adicionales
    foreach ($params as $key => $value) {
        $url .= ($route || !empty($params)) ? '&' : '?';
        $url .= urlencode($key) . '=' . urlencode($value);
    }
    
    return $url;
}

// Incluir helpers para las URLs
require_once __DIR__ . '/../config/helpers.php';

class MozoController {
    protected static function authorize() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            header('Location: ' . url('unauthorized'));
            exit;
        }
    }

    public static function index() {
        self::authorize();
        $mozos = Usuario::allByRole('mozo');
        include __DIR__ . '/../views/mozos/index.php';
    }

    public static function create() {
        self::authorize();
        
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id > 0) {
            // Es una actualización
            self::update($id);
        } else {
            // Es una creación nueva
            // Validar que el email no esté duplicado
            if (Usuario::emailExists($_POST['email'])) {
                // Preservar los datos del formulario en la sesión para mostrarlos en el error
                $_SESSION['form_data'] = $_POST;
                header('Location: ' . url('mozos/create', ['error' => 'El email ya está en uso por otro usuario']));
                exit;
            }
            
            // Validar longitud de contraseña
            if (strlen($_POST['contrasenia']) < 8) {
                $_SESSION['form_data'] = $_POST;
                header('Location: ' . url('mozos/create', ['error' => 'La contraseña debe tener al menos 8 caracteres']));
                exit;
            }
            
            $data = $_POST;
            $data['rol'] = 'mozo';
            $data['contrasenia'] = password_hash($data['contrasenia'], PASSWORD_DEFAULT);
            Usuario::create($data);
            
            // Redirección POST-REDIRECT-GET para evitar reenvío de formulario
            header('Location: ' . url('mozos', ['success' => 'Mozo creado exitosamente']));
            exit;
        }
    }

    public static function update(int $id) {
        self::authorize();
        
        $mozo = Usuario::find($id);
        if (!$mozo || !in_array($mozo['rol'], ['mozo', 'administrador'])) {
            header('Location: ' . url('mozos', ['error' => 'Usuario no encontrado']));
            exit;
        }

        // Validar que el email no esté duplicado
        if (Usuario::emailExists($_POST['email'], $id)) {
            header('Location: ' . url('mozos/edit', ['id' => $id, 'error' => 'El email ya está en uso por otro usuario']));
            exit;
        }

        $nuevo_estado = $_POST['estado'] ?? 'activo';
        
        // Si se está inactivando el mozo y tiene mesas asignadas, redirigir a pantalla de reasignación
        if ($nuevo_estado === 'inactivo' && $mozo['estado'] === 'activo') {
            $mesas_asignadas = Mesa::countMesasByMozo($id);
            if ($mesas_asignadas > 0) {
                // Redirigir a pantalla de confirmación con los datos del formulario
                $query_params = [
                    'confirmar_inactivacion' => '1',
                    'id_mozo' => $id,
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'email' => $_POST['email'],
                    'mesas_asignadas' => $mesas_asignadas
                ];
                
                // Si hay contraseña, incluirla (aunque no sea seguro en URL, es temporal)
                if (!empty($_POST['contrasenia'])) {
                    $query_params['nueva_contrasenia'] = base64_encode($_POST['contrasenia']);
                }
                
                header('Location: ' . url('mozos/confirmar-inactivacion', $query_params));
                exit;
            }
        }

        // Preparar datos para actualización
        $data = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'email' => $_POST['email'],
            'estado' => $nuevo_estado
        ];

        // Solo incluir contraseña si se proporcionó
        if (!empty($_POST['contrasenia'])) {
            // Validar longitud de contraseña
            if (strlen($_POST['contrasenia']) < 8) {
                header('Location: ' . url('mozos/edit', ['id' => $id, 'error' => 'La contraseña debe tener al menos 8 caracteres']));
                exit;
            }
            $data['contrasenia'] = $_POST['contrasenia'];
        }

        if (Usuario::update($id, $data)) {
            header('Location: ' . url('mozos', ['success' => 'Mozo actualizado exitosamente']));
        } else {
            header('Location: ' . url('mozos', ['error' => 'Error al actualizar el mozo']));
        }
        exit;
    }

    public static function procesarInactivacion() {
        self::authorize();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('mozos'));
            exit;
        }
        
        $id_mozo = (int) ($_POST['id_mozo'] ?? 0);
        $accion_mesas = $_POST['accion_mesas'] ?? '';
        $nuevo_mozo = !empty($_POST['nuevo_mozo']) ? (int) $_POST['nuevo_mozo'] : null;
        
        $mozo = Usuario::find($id_mozo);
        if (!$mozo || !in_array($mozo['rol'], ['mozo', 'administrador'])) {
            header('Location: ' . url('mozos', ['error' => 'Usuario no encontrado']));
            exit;
        }
        
        // Procesar las mesas según la acción elegida
        if ($accion_mesas === 'reasignar' && $nuevo_mozo) {
            // Reasignar todas las mesas al nuevo mozo
            $mesas = Mesa::getMesasByMozo($id_mozo);
            foreach ($mesas as $mesa) {
                Mesa::asignarMozo($mesa['id_mesa'], $nuevo_mozo);
            }
        } elseif ($accion_mesas === 'liberar') {
            // Liberar todas las mesas (sin mozo asignado)
            $mesas = Mesa::getMesasByMozo($id_mozo);
            foreach ($mesas as $mesa) {
                Mesa::asignarMozo($mesa['id_mesa'], null);
            }
        }
        
        // Actualizar el mozo
        $data = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'email' => $_POST['email'],
            'estado' => 'inactivo'
        ];
        
        // Manejar contraseña si existe
        if (!empty($_POST['nueva_contrasenia'])) {
            $contrasenia_decodificada = base64_decode($_POST['nueva_contrasenia']);
            // Validar longitud de contraseña
            if (strlen($contrasenia_decodificada) < 8) {
                header('Location: ' . url('mozos', ['error' => 'La contraseña debe tener al menos 8 caracteres']));
                exit;
            }
            $data['contrasenia'] = $contrasenia_decodificada;
        }
        
        if (Usuario::update($id_mozo, $data)) {
            $mensaje = 'Mozo inactivado exitosamente';
            if ($accion_mesas === 'reasignar') {
                $nuevo_mozo_info = Usuario::find($nuevo_mozo);
                $mensaje .= ' y sus mesas fueron reasignadas a ' . $nuevo_mozo_info['nombre'] . ' ' . $nuevo_mozo_info['apellido'];
            } elseif ($accion_mesas === 'liberar') {
                $mensaje .= ' y sus mesas fueron liberadas';
            }
            header('Location: ' . url('mozos', ['success' => $mensaje]));
        } else {
            header('Location: ' . url('mozos', ['error' => 'Error al inactivar el mozo']));
        }
        exit;
    }

    public static function inactivar() {
        self::authorize();
        $id = (int) ($_GET['id'] ?? $_GET['inactivar'] ?? 0);
        
        if ($id <= 0) {
            header('Location: ' . url('mozos', ['error' => 'ID de usuario inválido']));
            exit;
        }
        
        // Verificar que el usuario existe y no es administrador
        $usuario = Usuario::find($id);
        if (!$usuario) {
            header('Location: ' . url('mozos', ['error' => 'Usuario no encontrado']));
            exit;
        }
        
        if ($usuario['rol'] === 'administrador') {
            header('Location: ' . url('mozos', ['error' => 'No se puede inactivar un usuario administrador']));
            exit;
        }
        
        // Si ya está inactivo, no hacer nada
        if ($usuario['estado'] === 'inactivo') {
            header('Location: ' . url('mozos', ['error' => 'El mozo ya está inactivo']));
            exit;
        }
        
        // Primero inactivar el mozo (cambiar estado a inactivo)
        $data = [
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'email' => $usuario['email'],
            'estado' => 'inactivo'
        ];
        
        if (!Usuario::update($id, $data)) {
            header('Location: ' . url('mozos', ['error' => 'Error al inactivar el mozo']));
            exit;
        }
        
        // Verificar si el mozo tiene mesas asignadas DESPUÉS de inactivarlo
        $mesas_asignadas = Mesa::countMesasByMozo($id);
        
        if ($mesas_asignadas > 0) {
            // Redirigir a pantalla de confirmación de inactivación con los datos del mozo
            $query_params = [
                'confirmar_inactivacion' => '1',
                'id_mozo' => $id,
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'email' => $usuario['email'],
                'mesas_asignadas' => $mesas_asignadas
            ];
            
            header('Location: ' . url('mozos/confirmar-inactivacion', $query_params));
            exit;
        }
        
        // Si no tiene mesas asignadas, mostrar mensaje de éxito
        header('Location: ' . url('mozos', ['success' => 'Mozo inactivado exitosamente']));
        exit;
    }

    public static function activar() {
        self::authorize();
        $id = (int) ($_GET['id'] ?? $_GET['activar'] ?? 0);
        
        if ($id <= 0) {
            header('Location: ' . url('mozos', ['error' => 'ID de usuario inválido']));
            exit;
        }
        
        // Verificar que el usuario existe y no es administrador
        $usuario = Usuario::find($id);
        if (!$usuario) {
            header('Location: ' . url('mozos', ['error' => 'Usuario no encontrado']));
            exit;
        }
        
        if ($usuario['rol'] === 'administrador') {
            header('Location: ' . url('mozos', ['error' => 'No se puede cambiar el estado de un usuario administrador']));
            exit;
        }
        
        // Si ya está activo, no hacer nada
        if ($usuario['estado'] === 'activo') {
            header('Location: ' . url('mozos', ['error' => 'El mozo ya está activo']));
            exit;
        }
        
        // Verificar si el mozo tiene mesas asignadas y liberarlas automáticamente
        $mesas_asignadas = Mesa::countMesasByMozo($id);
        if ($mesas_asignadas > 0) {
            $mesas = Mesa::getMesasByMozo($id);
            foreach ($mesas as $mesa) {
                Mesa::asignarMozo($mesa['id_mesa'], null);
            }
        }
        
        // Activar el mozo (cambiar estado a activo)
        $data = [
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'email' => $usuario['email'],
            'estado' => 'activo'
        ];
        
        if (Usuario::update($id, $data)) {
            $mensaje = 'Mozo activado exitosamente';
            if ($mesas_asignadas > 0) {
                $mensaje .= ' y sus ' . $mesas_asignadas . ' mesa(s) fueron liberadas automáticamente';
            }
            header('Location: ' . url('mozos', ['success' => $mensaje]));
        } else {
            header('Location: ' . url('mozos', ['error' => 'Error al activar el mozo']));
        }
        exit;
    }

    /**
     * Maneja el llamado de mozo desde el cliente
     */
    public static function llamarMozo() {
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Configurar headers para JSON
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        // Solo permitir POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        try {
            // Obtener datos del JSON
            $raw_input = file_get_contents('php://input');
            $input = json_decode($raw_input, true);
            
            // Log para debug
            error_log("LlamarMozo - Raw input: " . $raw_input);
            error_log("LlamarMozo - Decoded input: " . print_r($input, true));
            
            if (!$input || !isset($input['numero_mesa'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Número de mesa requerido']);
                exit;
            }
            
            $numero_mesa = (int) $input['numero_mesa'];
            error_log("LlamarMozo - Número de mesa: " . $numero_mesa);
            
            // Buscar la mesa por número
            $db = (new \App\Config\Database)->getConnection();
            $stmt = $db->prepare("
                SELECT m.*, 
                       u.nombre as mozo_nombre,
                       u.apellido as mozo_apellido,
                       CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
                FROM mesas m
                LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
                WHERE m.numero = ?
            ");
            $stmt->execute([$numero_mesa]);
            $mesa = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            error_log("LlamarMozo - Mesa encontrada: " . print_r($mesa, true));
            
            if (!$mesa) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Mesa no encontrada']);
                exit;
            }
            
            // Verificar que la mesa tenga un mozo asignado
            if (!$mesa['id_mozo']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Esta mesa no tiene un mozo asignado']);
                exit;
            }
            
            // Verificar si hay un llamado reciente (menos de 3 minutos)
            $stmt_reciente = $db->prepare("
                SELECT id_llamado, hora_solicitud, 
                       TIMESTAMPDIFF(MINUTE, hora_solicitud, NOW()) as minutos_transcurridos
                FROM llamados_mesa 
                WHERE id_mesa = ? AND estado = 'pendiente'
                ORDER BY hora_solicitud DESC 
                LIMIT 1
            ");
            $stmt_reciente->execute([$mesa['id_mesa']]);
            $llamado_reciente = $stmt_reciente->fetch(\PDO::FETCH_ASSOC);
            
            if ($llamado_reciente && $llamado_reciente['minutos_transcurridos'] < 3) {
                $minutos_restantes = 3 - $llamado_reciente['minutos_transcurridos'];
                echo json_encode([
                    'success' => false, 
                    'message' => "Usted ya llamó al mozo hace menos de 3 minutos, por favor aguarde {$minutos_restantes} minuto(s) más",
                    'type' => 'warning'
                ]);
                exit;
            }
            
            // Si hay un llamado anterior (más de 3 minutos), eliminarlo
            if ($llamado_reciente && $llamado_reciente['minutos_transcurridos'] >= 3) {
                $stmt_eliminar = $db->prepare("UPDATE llamados_mesa SET estado = 'completado', hora_atencion = NOW(), atendido_por = NULL WHERE id_llamado = ?");
                $stmt_eliminar->execute([$llamado_reciente['id_llamado']]);
                error_log("LlamarMozo - Llamado anterior marcado como completado: " . $llamado_reciente['id_llamado']);
            }
            
            // Crear el nuevo llamado
            error_log("LlamarMozo - Creando llamado para mesa ID: " . $mesa['id_mesa']);
            $resultado = LlamadoMesa::create($mesa['id_mesa']);
            error_log("LlamarMozo - Resultado del llamado: " . ($resultado ? 'true' : 'false'));
            
            if ($resultado) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Llamado generado correctamente',
                    'mozo' => $mesa['mozo_nombre_completo'],
                    'mesa' => $numero_mesa,
                    'type' => 'success'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al crear el llamado']);
            }
            
        } catch (\Exception $e) {
            error_log("LlamarMozo - Error: " . $e->getMessage());
            error_log("LlamarMozo - Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
        
        exit;
    }
}


