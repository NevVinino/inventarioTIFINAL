<?php
session_start();
include("../../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['id_asignacion'])) {
    try {
        $id_asignacion = $_GET['id_asignacion'];
        
        // Obtener información de la asignación
        $sql = "
        SELECT 
            asig.id_activo,
            act.tipo_activo,
            CASE 
                WHEN act.tipo_activo = 'Laptop' THEN lap.id_laptop
                WHEN act.tipo_activo = 'PC' THEN pc.id_pc
                WHEN act.tipo_activo = 'Servidor' THEN srv.id_servidor
            END as id_equipo
        FROM asignacion asig
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
        
        // Crear rutas de documentos con nueva estructura (CORREGIDA - desde pdf_asignacion)
        $carpeta_base = "../../../pdf";  // Desde pdf_asignacion hacia raíz
        $carpeta_asignacion = $carpeta_base . "/asignacion";
        $carpeta_activo = $carpeta_asignacion . "/activo_" . $datos['id_activo'] . "_" . strtolower($datos['tipo_activo']) . "_" . $datos['id_equipo'];
        
        $documentos = [
            'sin_firma' => [
                'existe' => false,
                'url' => ''
            ],
            'con_firma' => [
                'existe' => false,
                'url' => ''
            ]
        ];
        
        // Verificar documento sin firma - buscar por patrón de nombre
        $subcarpeta_sin_firma = $carpeta_activo . "/sinFirma";
        if (is_dir($subcarpeta_sin_firma)) {
            $archivos_sin_firma = array_diff(scandir($subcarpeta_sin_firma), ['.', '..']);
            foreach ($archivos_sin_firma as $archivo) {
                if (strpos($archivo, "asignacion_" . $id_asignacion . "_") === 0 && 
                    strpos($archivo, "_sinFirma.html") !== false) {
                    $documentos['sin_firma']['existe'] = true;
                    $documentos['sin_firma']['url'] = "pdf/asignacion/activo_" . $datos['id_activo'] . "_" . strtolower($datos['tipo_activo']) . "_" . $datos['id_equipo'] . "/sinFirma/" . $archivo;
                    break;
                }
            }
        }
        
        // Verificar documento con firma - buscar por patrón de nombre
        $subcarpeta_con_firma = $carpeta_activo . "/conFirma";
        if (is_dir($subcarpeta_con_firma)) {
            $archivos_con_firma = array_diff(scandir($subcarpeta_con_firma), ['.', '..']);
            foreach ($archivos_con_firma as $archivo) {
                if (strpos($archivo, "asignacion_" . $id_asignacion . "_") === 0 && 
                    strpos($archivo, "_conFirma.pdf") !== false) {
                    $documentos['con_firma']['existe'] = true;
                    $documentos['con_firma']['url'] = "pdf/asignacion/activo_" . $datos['id_activo'] . "_" . strtolower($datos['tipo_activo']) . "_" . $datos['id_equipo'] . "/conFirma/" . $archivo;
                    break;
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'documentos' => $documentos]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
}
?>
