<?php
session_start();

// Incluir el archivo de verificación de acceso que ya tienes
$solo_admin = true;
include("../../includes/verificar_acceso.php");

include("../../includes/conexion.php");

// Configurar zona horaria de Lima, Perú
date_default_timezone_set('America/Lima');

// Configurar respuesta JSON
header('Content-Type: application/json; charset=utf-8');

function listarArchivos() {
    // Usar la misma ruta que en generar_pdf_historial_asignacion.php
    $carpeta_base = "../../../pdf";
    $carpeta_historial = $carpeta_base . "/historial_asignaciones";
    $archivos = [];
    
    if (!is_dir($carpeta_historial)) {
        return ['archivos' => []];
    }
    
    // Obtener todas las subcarpetas reportes_YYYY-MM
    $carpetas = glob($carpeta_historial . '/reportes_*', GLOB_ONLYDIR);
    
    foreach ($carpetas as $carpeta) {
        $nombre_carpeta = basename($carpeta);
        
        // Buscar archivos PDF y HTML en la carpeta
        $archivos_pdf = glob($carpeta . '/*.pdf');
        $archivos_html = glob($carpeta . '/*.html');
        $todos_archivos = array_merge($archivos_pdf, $archivos_html);
        
        foreach ($todos_archivos as $archivo) {
            if (is_file($archivo)) {
                $info_archivo = [
                    'nombre' => basename($archivo),
                    'ruta_completa' => $archivo,
                    'ruta_relativa' => str_replace('../../../', '', $archivo),
                    'tamaño' => filesize($archivo),
                    'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($archivo)),
                    'carpeta' => $nombre_carpeta,
                    'extension' => strtolower(pathinfo($archivo, PATHINFO_EXTENSION))
                ];
                
                $archivos[] = $info_archivo;
            }
        }
    }
    
    // Ordenar por fecha de modificación (más recientes primero)
    usort($archivos, function($a, $b) {
        return strtotime($b['fecha_modificacion']) - strtotime($a['fecha_modificacion']);
    });
    
    return ['archivos' => $archivos];
}

function eliminarArchivo($ruta_archivo) {
    // Sanitizar la ruta para seguridad
    $ruta_completa = "../../../" . $ruta_archivo;
    
    // Verificar que el archivo esté dentro de la carpeta permitida
    $ruta_real = realpath($ruta_completa);
    $carpeta_permitida = realpath("../../../pdf/historial_asignaciones");
    
    if (!$ruta_real || !$carpeta_permitida || strpos($ruta_real, $carpeta_permitida) !== 0) {
        throw new Exception("Ruta de archivo no válida o no permitida");
    }
    
    if (!file_exists($ruta_real)) {
        throw new Exception("El archivo no existe");
    }
    
    if (!is_file($ruta_real)) {
        throw new Exception("La ruta no corresponde a un archivo");
    }
    
    // Verificar que sea un archivo PDF o HTML
    $extension = strtolower(pathinfo($ruta_real, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'html'])) {
        throw new Exception("Solo se pueden eliminar archivos PDF o HTML");
    }
    
    if (unlink($ruta_real)) {
        return ['success' => true, 'message' => 'Archivo eliminado exitosamente'];
    } else {
        throw new Exception("No se pudo eliminar el archivo");
    }
}

// Procesar solicitud
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Listar archivos
        $accion = $_GET['accion'] ?? '';
        
        if ($accion === 'listar') {
            $resultado = listarArchivos();
            echo json_encode($resultado);
        } else {
            echo json_encode(['error' => 'Acción no válida']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Eliminar archivo
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['accion'])) {
            throw new Exception('Datos de entrada no válidos');
        }
        
        if ($input['accion'] === 'eliminar') {
            if (!isset($input['ruta_archivo']) || empty($input['ruta_archivo'])) {
                throw new Exception('Ruta de archivo no especificada');
            }
            
            $resultado = eliminarArchivo($input['ruta_archivo']);
            echo json_encode($resultado);
        } else {
            echo json_encode(['error' => 'Acción no válida']);
        }
        
    } else {
        echo json_encode(['error' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
