<?php
session_start();
include("../includes/conexion.php");

// Verificar que sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

// Verificar la conexión
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Función para obtener historial de asignaciones con filtros
function obtenerHistorialAsignaciones($conn, $filtros = [], $pagina = 1, $limite = 25) {
    $offset = ($pagina - 1) * $limite;
    
    // Construir WHERE dinámico basado en filtros
    $where_conditions = [];
    $params = [];
    
    if (!empty($filtros['numero_serial'])) {
        $where_conditions[] = "(
            (a.tipo_activo = 'Laptop' AND l.numeroSerial LIKE ?) OR
            (a.tipo_activo = 'PC' AND pc.numeroSerial LIKE ?) OR
            (a.tipo_activo = 'Servidor' AND s.numeroSerial LIKE ?)
        )";
        $search_serial = '%' . $filtros['numero_serial'] . '%';
        $params[] = $search_serial;
        $params[] = $search_serial;
        $params[] = $search_serial;
    }
    
    if (!empty($filtros['nombre_activo'])) {
        $where_conditions[] = "(
            (a.tipo_activo = 'Laptop' AND l.nombreEquipo LIKE ?) OR
            (a.tipo_activo = 'PC' AND pc.nombreEquipo LIKE ?) OR
            (a.tipo_activo = 'Servidor' AND s.nombreEquipo LIKE ?)
        )";
        $search_nombre = '%' . $filtros['nombre_activo'] . '%';
        $params[] = $search_nombre;
        $params[] = $search_nombre;
        $params[] = $search_nombre;
    }
    
    if (!empty($filtros['persona'])) {
        $where_conditions[] = "(p.nombre LIKE ? OR p.apellido LIKE ? OR CONCAT(p.nombre, ' ', p.apellido) LIKE ?)";
        $search_persona = '%' . $filtros['persona'] . '%';
        $params[] = $search_persona;
        $params[] = $search_persona;
        $params[] = $search_persona;
    }
    
    if (!empty($filtros['tipo_activo'])) {
        $where_conditions[] = "a.tipo_activo = ?";
        $params[] = $filtros['tipo_activo'];
    }
    
    if (!empty($filtros['estado_asignacion'])) {
        if ($filtros['estado_asignacion'] === 'Activo') {
            $where_conditions[] = "asig.fecha_retorno IS NULL";
        } else {
            $where_conditions[] = "asig.fecha_retorno IS NOT NULL";
        }
    }
    
    if (!empty($filtros['fecha_desde'])) {
        $where_conditions[] = "asig.fecha_asignacion >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    
    if (!empty($filtros['fecha_hasta'])) {
        $where_conditions[] = "asig.fecha_asignacion <= ?";
        $params[] = $filtros['fecha_hasta'];
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Consulta principal
    $sql = "
        SELECT 
            asig.id_asignacion,
            asig.fecha_asignacion,
            asig.fecha_retorno,
            asig.observaciones,
            a.id_activo,
            a.tipo_activo,
            -- Información del activo según tipo
            CASE 
                WHEN a.tipo_activo = 'Laptop' THEN l.nombreEquipo
                WHEN a.tipo_activo = 'PC' THEN pc.nombreEquipo
                WHEN a.tipo_activo = 'Servidor' THEN s.nombreEquipo
            END as nombre_activo,
            CASE 
                WHEN a.tipo_activo = 'Laptop' THEN l.numeroSerial
                WHEN a.tipo_activo = 'PC' THEN pc.numeroSerial
                WHEN a.tipo_activo = 'Servidor' THEN s.numeroSerial
            END as numero_serial,
            CASE 
                WHEN a.tipo_activo = 'Laptop' THEN l.modelo
                WHEN a.tipo_activo = 'PC' THEN pc.modelo
                WHEN a.tipo_activo = 'Servidor' THEN s.modelo
            END as modelo_activo,
            -- Información de la persona
            CONCAT(p.nombre, ' ', p.apellido) as persona_nombre,
            p.correo as email,
            p.celular as telefono,
            ar.nombre as area_nombre,
            emp.nombre as empresa_nombre,
            -- Información adicional
            u.username as usuario_asigno,
            -- Calcular duración
            CASE 
                WHEN asig.fecha_retorno IS NOT NULL THEN 
                    DATEDIFF(day, asig.fecha_asignacion, asig.fecha_retorno)
                ELSE 
                    DATEDIFF(day, asig.fecha_asignacion, GETDATE())
            END as dias_asignacion,
            -- Estado de asignación
            CASE 
                WHEN asig.fecha_retorno IS NOT NULL THEN 'Retornado'
                ELSE 'Activo'
            END as estado_asignacion
        FROM asignacion asig
        INNER JOIN activo a ON asig.id_activo = a.id_activo
        LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
        LEFT JOIN pc pc ON a.id_pc = pc.id_pc
        LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
        INNER JOIN persona p ON asig.id_persona = p.id_persona
        LEFT JOIN area ar ON p.id_area = ar.id_area
        LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
        LEFT JOIN usuario u ON asig.id_usuario = u.id_usuario
        $where_clause
        ORDER BY asig.fecha_asignacion DESC
        OFFSET $offset ROWS FETCH NEXT $limite ROWS ONLY
    ";
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        throw new Exception("Error en consulta: " . print_r(sqlsrv_errors(), true));
    }
    
    $resultados = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Formatear fechas
        if ($row['fecha_asignacion'] instanceof DateTime) {
            $row['fecha_asignacion_formato'] = $row['fecha_asignacion']->format('d/m/Y');
        }
        if ($row['fecha_retorno'] instanceof DateTime) {
            $row['fecha_retorno_formato'] = $row['fecha_retorno']->format('d/m/Y');
        } else {
            $row['fecha_retorno_formato'] = null;
        }
        
        $resultados[] = $row;
    }
    
    // Obtener total de registros para paginación
    $sql_count = "
        SELECT COUNT(*) as total
        FROM asignacion asig
        INNER JOIN activo a ON asig.id_activo = a.id_activo
        LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
        LEFT JOIN pc pc ON a.id_pc = pc.id_pc
        LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
        INNER JOIN persona p ON asig.id_persona = p.id_persona
        LEFT JOIN area ar ON p.id_area = ar.id_area
        LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
        $where_clause
    ";
    
    $stmt_count = sqlsrv_query($conn, $sql_count, $params);
    $total_registros = 0;
    if ($stmt_count && $row_count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC)) {
        $total_registros = $row_count['total'];
    }
    
    return [
        'datos' => $resultados,
        'total' => $total_registros,
        'pagina' => $pagina,
        'limite' => $limite,
        'total_paginas' => ceil($total_registros / $limite)
    ];
}

// Función para obtener estadísticas
function obtenerEstadisticas($conn, $filtros = []) {
    $where_conditions = [];
    $params = [];
    
    // Aplicar los mismos filtros que en la consulta principal
    if (!empty($filtros['numero_serial'])) {
        $where_conditions[] = "(
            (a.tipo_activo = 'Laptop' AND l.numeroSerial LIKE ?) OR
            (a.tipo_activo = 'PC' AND pc.numeroSerial LIKE ?) OR
            (a.tipo_activo = 'Servidor' AND s.numeroSerial LIKE ?)
        )";
        $search_serial = '%' . $filtros['numero_serial'] . '%';
        $params[] = $search_serial;
        $params[] = $search_serial;
        $params[] = $search_serial;
    }
    
    if (!empty($filtros['nombre_activo'])) {
        $where_conditions[] = "(
            (a.tipo_activo = 'Laptop' AND l.nombreEquipo LIKE ?) OR
            (a.tipo_activo = 'PC' AND pc.nombreEquipo LIKE ?) OR
            (a.tipo_activo = 'Servidor' AND s.nombreEquipo LIKE ?)
        )";
        $search_nombre = '%' . $filtros['nombre_activo'] . '%';
        $params[] = $search_nombre;
        $params[] = $search_nombre;
        $params[] = $search_nombre;
    }
    
    if (!empty($filtros['persona'])) {
        $where_conditions[] = "(p.nombre LIKE ? OR p.apellido LIKE ? OR CONCAT(p.nombre, ' ', p.apellido) LIKE ?)";
        $search_persona = '%' . $filtros['persona'] . '%';
        $params[] = $search_persona;
        $params[] = $search_persona;
        $params[] = $search_persona;
    }
    
    if (!empty($filtros['tipo_activo'])) {
        $where_conditions[] = "a.tipo_activo = ?";
        $params[] = $filtros['tipo_activo'];
    }
    
    if (!empty($filtros['fecha_desde'])) {
        $where_conditions[] = "asig.fecha_asignacion >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    
    if (!empty($filtros['fecha_hasta'])) {
        $where_conditions[] = "asig.fecha_asignacion <= ?";
        $params[] = $filtros['fecha_hasta'];
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    $sql = "
        SELECT 
            COUNT(*) as total_asignaciones,
            SUM(CASE WHEN asig.fecha_retorno IS NULL THEN 1 ELSE 0 END) as asignaciones_activas,
            SUM(CASE WHEN asig.fecha_retorno IS NOT NULL THEN 1 ELSE 0 END) as asignaciones_retornadas,
            AVG(
                CASE 
                    WHEN asig.fecha_retorno IS NOT NULL THEN 
                        DATEDIFF(day, asig.fecha_asignacion, asig.fecha_retorno)
                    ELSE 
                        DATEDIFF(day, asig.fecha_asignacion, GETDATE())
                END
            ) as promedio_dias
        FROM asignacion asig
        INNER JOIN activo a ON asig.id_activo = a.id_activo
        LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
        LEFT JOIN pc pc ON a.id_pc = pc.id_pc
        LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
        INNER JOIN persona p ON asig.id_persona = p.id_persona
        $where_clause
    ";
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        throw new Exception("Error obteniendo estadísticas: " . print_r(sqlsrv_errors(), true));
    }
    
    $estadisticas = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    return [
        'total_asignaciones' => (int)($estadisticas['total_asignaciones'] ?? 0),
        'asignaciones_activas' => (int)($estadisticas['asignaciones_activas'] ?? 0),
        'asignaciones_retornadas' => (int)($estadisticas['asignaciones_retornadas'] ?? 0),
        'promedio_dias' => round($estadisticas['promedio_dias'] ?? 0, 1)
    ];
}

// Función para obtener detalle de una asignación específica
function obtenerDetalleAsignacion($conn, $id_asignacion) {
    $sql = "
        SELECT 
            asig.*,
            a.id_activo,
            a.tipo_activo,
            -- Información completa del activo
            CASE 
                WHEN a.tipo_activo = 'Laptop' THEN l.nombreEquipo
                WHEN a.tipo_activo = 'PC' THEN pc.nombreEquipo
                WHEN a.tipo_activo = 'Servidor' THEN s.nombreEquipo
            END as nombre_activo,
            CASE 
                WHEN a.tipo_activo = 'Laptop' THEN l.numeroSerial
                WHEN a.tipo_activo = 'PC' THEN pc.numeroSerial
                WHEN a.tipo_activo = 'Servidor' THEN s.numeroSerial
            END as numero_serial,
            CASE 
                WHEN a.tipo_activo = 'Laptop' THEN l.modelo
                WHEN a.tipo_activo = 'PC' THEN pc.modelo
                WHEN a.tipo_activo = 'Servidor' THEN s.modelo
            END as modelo_activo,
            CASE 
                WHEN a.tipo_activo = 'Laptop' THEN m_l.nombre
                WHEN a.tipo_activo = 'PC' THEN m_pc.nombre
                WHEN a.tipo_activo = 'Servidor' THEN m_s.nombre
            END as marca_activo,
            -- Información completa de la persona
            p.nombre as persona_nombre,
            p.apellido as persona_apellido,
            p.correo as email,
            p.celular as telefono,
            l_p.localidad_nombre,
            ar.nombre as area_nombre,
            emp.nombre as empresa_nombre,
            sp.situacion as situacion_personal,
            tp.nombre_tipo_persona,
            CONCAT(jefe.nombre, ' ', jefe.apellido) as jefe_inmediato,
            -- Usuario que asignó
            u.username as usuario_asigno,
            -- Duración calculada
            CASE 
                WHEN asig.fecha_retorno IS NOT NULL THEN 
                    DATEDIFF(day, asig.fecha_asignacion, asig.fecha_retorno)
                ELSE 
                    DATEDIFF(day, asig.fecha_asignacion, GETDATE())
            END as dias_asignacion,
            -- Estado
            CASE 
                WHEN asig.fecha_retorno IS NOT NULL THEN 'Retornado'
                ELSE 'Activo'
            END as estado_asignacion
        FROM asignacion asig
        INNER JOIN activo a ON asig.id_activo = a.id_activo
        LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
        LEFT JOIN pc pc ON a.id_pc = pc.id_pc
        LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
        LEFT JOIN marca m_l ON l.id_marca = m_l.id_marca
        LEFT JOIN marca m_pc ON pc.id_marca = m_pc.id_marca
        LEFT JOIN marca m_s ON s.id_marca = m_s.id_marca
        INNER JOIN persona p ON asig.id_persona = p.id_persona
        LEFT JOIN localidad l_p ON p.id_localidad = l_p.id_localidad
        LEFT JOIN area ar ON p.id_area = ar.id_area
        LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
        LEFT JOIN situacion_personal sp ON p.id_situacion_personal = sp.id_situacion
        LEFT JOIN tipo_persona tp ON p.id_tipo_persona = tp.id_tipo_persona
        LEFT JOIN persona jefe ON p.jefe_inmediato = jefe.id_persona
        LEFT JOIN usuario u ON asig.id_usuario = u.id_usuario
        WHERE asig.id_asignacion = ?
    ";
    
    $stmt = sqlsrv_query($conn, $sql, [$id_asignacion]);
    
    if ($stmt === false) {
        throw new Exception("Error obteniendo detalle: " . print_r(sqlsrv_errors(), true));
    }
    
    $detalle = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if ($detalle) {
        // Formatear fechas
        if ($detalle['fecha_asignacion'] instanceof DateTime) {
            $detalle['fecha_asignacion_formato'] = $detalle['fecha_asignacion']->format('d/m/Y H:i');
        }
        if ($detalle['fecha_retorno'] instanceof DateTime) {
            $detalle['fecha_retorno_formato'] = $detalle['fecha_retorno']->format('d/m/Y H:i');
        }
    }
    
    return $detalle;
}

// Procesar solicitudes
try {
    header('Content-Type: application/json; charset=utf-8');
    
    $metodo = $_SERVER['REQUEST_METHOD'];
    $accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'obtener_historial':
            $filtros = [];
            $pagina = (int)($_GET['pagina'] ?? 1);
            $limite = (int)($_GET['limite'] ?? 25);
            
            // Obtener filtros del GET
            if (!empty($_GET['numero_serial'])) {
                $filtros['numero_serial'] = trim($_GET['numero_serial']);
            }
            if (!empty($_GET['nombre_activo'])) {
                $filtros['nombre_activo'] = trim($_GET['nombre_activo']);
            }
            if (!empty($_GET['persona'])) {
                $filtros['persona'] = trim($_GET['persona']);
            }
            if (!empty($_GET['tipo_activo'])) {
                $filtros['tipo_activo'] = $_GET['tipo_activo'];
            }
            if (!empty($_GET['estado_asignacion'])) {
                $filtros['estado_asignacion'] = $_GET['estado_asignacion'];
            }
            if (!empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            if (!empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            $resultado = obtenerHistorialAsignaciones($conn, $filtros, $pagina, $limite);
            echo json_encode($resultado);
            break;
            
        case 'obtener_estadisticas':
            $filtros = [];
            
            // Obtener filtros del GET para estadísticas
            if (!empty($_GET['numero_serial'])) {
                $filtros['numero_serial'] = trim($_GET['numero_serial']);
            }
            if (!empty($_GET['nombre_activo'])) {
                $filtros['nombre_activo'] = trim($_GET['nombre_activo']);
            }
            if (!empty($_GET['persona'])) {
                $filtros['persona'] = trim($_GET['persona']);
            }
            if (!empty($_GET['tipo_activo'])) {
                $filtros['tipo_activo'] = $_GET['tipo_activo'];
            }
            if (!empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            if (!empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            $estadisticas = obtenerEstadisticas($conn, $filtros);
            echo json_encode($estadisticas);
            break;
            
        case 'obtener_detalle':
            $id_asignacion = (int)($_GET['id_asignacion'] ?? 0);
            
            if ($id_asignacion <= 0) {
                throw new Exception("ID de asignación inválido");
            }
            
            $detalle = obtenerDetalleAsignacion($conn, $id_asignacion);
            
            if (!$detalle) {
                throw new Exception("Asignación no encontrada");
            }
            
            echo json_encode($detalle);
            break;
            
        default:
            throw new Exception("Acción no válida");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
