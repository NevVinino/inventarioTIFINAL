<?php
session_start();
include("../../includes/conexion.php");

$respuesta = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $accion = $_POST["accion"] ?? '';
        $id_asignacion = $_POST["id_asignacion"] ?? '';
        
        if ($accion === 'subir_firmado' && !empty($id_asignacion)) {
            
            // Verificar que se subió un archivo
            if (!isset($_FILES['documento_firmado']) || $_FILES['documento_firmado']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No se ha seleccionado un archivo válido.");
            }
            
            $archivo = $_FILES['documento_firmado'];
            
            // Validar tipo de archivo
            $tipo_archivo = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            if ($tipo_archivo !== 'pdf') {
                throw new Exception("Solo se permiten archivos PDF.");
            }
            
            // Obtener información de la asignación para crear la ruta (incluyendo el nombre de la persona)
            $sql = "
            SELECT 
                asig.id_activo,
                act.tipo_activo,
                CONCAT(p.nombre, ' ', p.apellido) as persona_nombre,
                CASE 
                    WHEN act.tipo_activo = 'Laptop' THEN lap.id_laptop
                    WHEN act.tipo_activo = 'PC' THEN pc.id_pc
                    WHEN act.tipo_activo = 'Servidor' THEN srv.id_servidor
                END as id_equipo
            FROM asignacion asig
            INNER JOIN persona p ON asig.id_persona = p.id_persona
            INNER JOIN activo act ON asig.id_activo = act.id_activo
            LEFT JOIN laptop lap ON act.id_laptop = lap.id_laptop
            LEFT JOIN pc pc ON act.id_pc = pc.id_pc
            LEFT JOIN servidor srv ON act.id_servidor = srv.id_servidor
            WHERE asig.id_asignacion = ?";
            
            $stmt = sqlsrv_query($conn, $sql, [$id_asignacion]);
            $datos = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if (!$datos) {
                throw new Exception("Asignación no encontrada.");
            }
            
            // Crear estructura de carpetas mejorada
            $carpeta_base = "../../../pdf";
            $carpeta_asignacion = $carpeta_base . "/asignacion";
            $carpeta_activo = $carpeta_asignacion . "/activo_" . $datos['id_activo'] . "_" . strtolower($datos['tipo_activo']) . "_" . $datos['id_equipo'];
            $carpeta_con_firma = $carpeta_activo . "/conFirma";
            
            // Crear carpetas si no existen
            if (!file_exists($carpeta_con_firma)) {
                mkdir($carpeta_con_firma, 0755, true);
            }
            
            // Limpiar el nombre de la persona para el archivo
            $nombre_persona_limpio = preg_replace('/[^A-Za-z0-9_.-]/', '_', $datos['persona_nombre']);
            
            // Nombre del archivo con firma incluyendo el nombre de la persona
            $nombre_archivo = "asignacion_" . $id_asignacion . "_" . $nombre_persona_limpio . "_" . date('Y-m-d') . "_conFirma.pdf";
            $ruta_destino = $carpeta_con_firma . "/" . $nombre_archivo;
            
            // Mover archivo subido
            if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                $respuesta['success'] = true;
                $respuesta['message'] = 'Documento firmado subido exitosamente';
                $respuesta['url_documento'] = "../../../pdf/asignacion/activo_" . $datos['id_activo'] . "_" . strtolower($datos['tipo_activo']) . "_" . $datos['id_equipo'] . "/conFirma/" . $nombre_archivo;
            } else {
                throw new Exception("Error al guardar el archivo.");
            }
            
        } else {
            throw new Exception("Acción no válida o datos incompletos.");
        }
        
    } catch (Exception $e) {
        $respuesta['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

header('Location: ../../views/crud_asignacion.php');
exit;
?>
