<?php
// src/controllers/MozoController.php
namespace App\Controllers;

use App\Models\Usuario;
use App\Models\Mesa;

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
        if (!$mozo || $mozo['rol'] !== 'mozo') {
            header('Location: ' . url('mozos', ['error' => 'Mozo no encontrado']));
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
        if (!$mozo || $mozo['rol'] !== 'mozo') {
            header('Location: ' . url('mozos', ['error' => 'Mozo no encontrado']));
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
            $data['contrasenia'] = base64_decode($_POST['nueva_contrasenia']);
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

    public static function delete() {
        self::authorize();
        $id = (int) ($_GET['delete'] ?? 0);
        if ($id > 0) {
            Usuario::delete($id);
        }
        header('Location: ' . url('mozos'));
        exit;
    }
}
