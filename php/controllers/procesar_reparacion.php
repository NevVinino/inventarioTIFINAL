<?php
include("../includes/conexion.php");

    // Si es una consulta para obtener cambios de hardware
    if (isset($_GET['action']) && $_GET['action'] === 'get_cambios_hardware') {
        $id_reparacion = $_GET['id_reparacion'] ?? '';
        
        if (empty($id_reparacion)) {
            echo json_encode(['error' => 'ID de reparación requerido']);
            exit;
        }
        
        try {
            $sql = "SELECT 
                        ch.id_cambio_hardware,
                        tc.nombre_tipo_cambio as tipo_cambio,
                        CASE 
                            WHEN ch.id_procesador IS NOT NULL THEN 'Procesador'
                            WHEN ch.id_ram IS NOT NULL THEN 'RAM'
                            WHEN ch.id_almacenamiento IS NOT NULL THEN 'Almacenamiento'
                            WHEN ch.id_tarjeta_video IS NOT NULL THEN 'Tarjeta de Video'
                            WHEN ch.id_procesador_generico IS NOT NULL THEN 'Procesador'
                            WHEN ch.id_ram_generico IS NOT NULL THEN 'RAM'
                            WHEN ch.id_almacenamiento_generico IS NOT NULL THEN 'Almacenamiento'
                            WHEN ch.id_tarjeta_video_generico IS NOT NULL THEN 'Tarjeta de Video'
                            ELSE 'N/A'
                        END as tipo_componente,
                        CASE 
                            WHEN ch.id_procesador IS NOT NULL THEN CONCAT(ISNULL(m1.nombre + ' ', ''), p.modelo, ISNULL(' ' + p.generacion, ''))
                            WHEN ch.id_ram IS NOT NULL THEN CONCAT(r.capacidad, ISNULL(' ' + r.tipo, ''), ISNULL(' ' + m2.nombre, ''))
                            WHEN ch.id_almacenamiento IS NOT NULL THEN CONCAT(a.capacidad, ISNULL(' ' + a.tipo, ''), ISNULL(' ' + m3.nombre, ''))
                            WHEN ch.id_tarjeta_video IS NOT NULL THEN CONCAT(ISNULL(m4.nombre + ' ', ''), tv.modelo, ISNULL(' ' + tv.memoria, ''))
                            WHEN ch.id_procesador_generico IS NOT NULL THEN CONCAT(pg.modelo, ISNULL(' ' + pg.generacion, ''))
                            WHEN ch.id_ram_generico IS NOT NULL THEN rg.capacidad
                            WHEN ch.id_almacenamiento_generico IS NOT NULL THEN CONCAT(ag.capacidad, ISNULL(' ' + ag.tipo, ''))
                            WHEN ch.id_tarjeta_video_generico IS NOT NULL THEN CONCAT(tvg.modelo, ISNULL(' ' + tvg.memoria, ''))
                            ELSE 'N/A'
                        END as componente_nuevo,
                        ISNULL(ch.componente_retirado, 'N/A') as componente_retirado,
                        ISNULL(ch.costo, 0) as costo,
                        ISNULL(ch.motivo, 'N/A') as motivo,
                        ch.fecha
                    FROM cambio_hardware ch
                    LEFT JOIN tipo_cambio tc ON ch.id_tipo_cambio = tc.id_tipo_cambio
                    LEFT JOIN procesador p ON ch.id_procesador = p.id_procesador
                    LEFT JOIN marca m1 ON p.id_marca = m1.id_marca
                    LEFT JOIN RAM r ON ch.id_ram = r.id_ram
                    LEFT JOIN marca m2 ON r.id_marca = m2.id_marca
                    LEFT JOIN almacenamiento a ON ch.id_almacenamiento = a.id_almacenamiento
                    LEFT JOIN marca m3 ON a.id_marca = m3.id_marca
                    LEFT JOIN tarjeta_video tv ON ch.id_tarjeta_video = tv.id_tarjeta_video
                    LEFT JOIN marca m4 ON tv.id_marca = m4.id_marca
                    LEFT JOIN procesador_generico pg ON ch.id_procesador_generico = pg.id_procesador_generico
                    LEFT JOIN RAM_generico rg ON ch.id_ram_generico = rg.id_ram_generico
                    LEFT JOIN almacenamiento_generico ag ON ch.id_almacenamiento_generico = ag.id_almacenamiento_generico
                    LEFT JOIN tarjeta_video_generico tvg ON ch.id_tarjeta_video_generico = tvg.id_tarjeta_video_generico
                    WHERE ch.id_reparacion = ?
                    ORDER BY ch.fecha DESC";
            
            $stmt = sqlsrv_prepare($conn, $sql, [$id_reparacion]);
            
            if (!$stmt || !sqlsrv_execute($stmt)) {
                throw new Exception("Error consultando cambios: " . print_r(sqlsrv_errors(), true));
            }
            
            $cambios = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir fecha a string si es objeto DateTime
                if ($row['fecha'] instanceof DateTime) {
                    $row['fecha'] = $row['fecha']->format('d/m/Y');
                }
                $cambios[] = $row;
            }
            
            echo json_encode($cambios);
            exit;
            
        } catch (Exception $e) {
            error_log("Error obteniendo cambios de hardware: " . $e->getMessage());
            echo json_encode(['error' => 'Error consultando cambios: ' . $e->getMessage()]);
            exit;
        }
    }

    // Si es una acción para eliminar cambio de hardware
    if (isset($_POST['action']) && $_POST['action'] === 'eliminar_cambio_hardware') {
        $id_cambio_hardware = $_POST['id_cambio_hardware'] ?? '';
        
        if (empty($id_cambio_hardware)) {
            echo json_encode(['success' => false, 'error' => 'ID de cambio requerido']);
            exit;
        }
        
        try {
            // Iniciar transacción
            sqlsrv_begin_transaction($conn);
            
            // Verificar que el cambio existe antes de eliminar
            $sqlVerificar = "SELECT id_cambio_hardware, id_activo, id_tipo_cambio FROM cambio_hardware WHERE id_cambio_hardware = ?";
            $stmtVerificar = sqlsrv_prepare($conn, $sqlVerificar, [$id_cambio_hardware]);
            
            if (!$stmtVerificar || !sqlsrv_execute($stmtVerificar)) {
                throw new Exception("Error verificando cambio de hardware");
            }
            
            $cambio = sqlsrv_fetch_array($stmtVerificar, SQLSRV_FETCH_ASSOC);
            if (!$cambio) {
                throw new Exception("Cambio de hardware no encontrado");
            }
            
            // Eliminar el cambio de hardware
            $sqlEliminar = "DELETE FROM cambio_hardware WHERE id_cambio_hardware = ?";
            $stmtEliminar = sqlsrv_prepare($conn, $sqlEliminar, [$id_cambio_hardware]);
            
            if (!$stmtEliminar || !sqlsrv_execute($stmtEliminar)) {
                throw new Exception("Error eliminando cambio de hardware: " . print_r(sqlsrv_errors(), true));
            }
            
            // Confirmar transacción
            sqlsrv_commit($conn);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cambio de hardware eliminado correctamente'
            ]);
            exit;
            
        } catch (Exception $e) {
            // Revertir transacción
            sqlsrv_rollback($conn);
            
            error_log("Error eliminando cambio de hardware: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Error eliminando cambio: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Si es una consulta para obtener slots disponibles para instalación
    if (isset($_GET['action']) && $_GET['action'] === 'get_slots_disponibles') {
        $id_activo = $_GET['id_activo'] ?? '';
        $tipo_componente = $_GET['tipo'] ?? '';
        
        if (empty($id_activo) || empty($tipo_componente)) {
            echo json_encode(['error' => 'ID de activo y tipo de componente requeridos']);
            exit;
        }
        
        try {
            // Mapear tipo de componente a tipo de slot
            $tipoSlotMap = [
                'procesador' => 'PROCESADOR',
                'ram' => 'RAM',
                'almacenamiento' => 'ALMACENAMIENTO', 
                'tarjeta_video' => 'TARJETA_VIDEO'
            ];
            
            $tipoSlot = $tipoSlotMap[$tipo_componente] ?? '';
            if (empty($tipoSlot)) {
                echo json_encode(['error' => 'Tipo de componente no válido']);
                exit;
            }
            
            // Obtener slots disponibles para este activo y tipo de componente
            $sql = "SELECT 
                        sa.id_slot,
                        sa.tipo_slot,
                        sa.estado
                    FROM slot_activo sa
                    WHERE sa.id_activo = ? 
                    AND sa.tipo_slot = ? 
                    AND sa.estado = 'disponible'
                    ORDER BY sa.id_slot";
            
            $stmt = sqlsrv_prepare($conn, $sql, [$id_activo, $tipoSlot]);
            
            if (!$stmt || !sqlsrv_execute($stmt)) {
                throw new Exception("Error consultando slots disponibles: " . print_r(sqlsrv_errors(), true));
            }
            
            $slots = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $slots[] = $row;
            }
            
            echo json_encode($slots);
            exit;
            
        } catch (Exception $e) {
            error_log("Error obteniendo slots disponibles: " . $e->getMessage());
            echo json_encode(['error' => 'Error consultando slots: ' . $e->getMessage()]);
            exit;
        }
    }// Endpoint de prueba para verificar conexión
if (isset($_GET['action']) && $_GET['action'] == 'test_connection') {
    try {
        // Verificar si la conexión existe
        if (!$conn) {
            throw new Exception("Conexión no establecida");
        }
        
        // Hacer una consulta simple
        $sql = "SELECT COUNT(*) as count FROM tipo_cambio";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en consulta de prueba: " . print_r(sqlsrv_errors(), true));
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Conexión OK',
            'count_tipos_cambio' => $row['count']
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Error de conexión',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

// NUEVO: Endpoint de prueba para verificar componentes
if (isset($_GET['action']) && $_GET['action'] == 'test_components') {
    try {
        $counts = [];
        
        // Contar componentes genéricos
        $tables_genericos = ['procesador_generico', 'RAM_generico', 'almacenamiento_generico', 'tarjeta_video_generico'];
        foreach ($tables_genericos as $table) {
            $sql = "SELECT COUNT(*) as count FROM $table";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                $counts[$table] = $row['count'];
            }
        }
        
        // Contar componentes detallados
        $tables_detallados = ['procesador', 'RAM', 'almacenamiento', 'tarjeta_video'];
        foreach ($tables_detallados as $table) {
            $sql = "SELECT COUNT(*) as count FROM $table";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                $counts[$table] = $row['count'];
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Conteo de componentes',
            'counts' => $counts
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Error verificando componentes',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

// Endpoint específico para obtener tipos de cambio
if (isset($_GET['action']) && $_GET['action'] == 'get_tipos_cambio') {
    try {
        $sql = "SELECT id_tipo_cambio, nombre_tipo_cambio FROM tipo_cambio ORDER BY nombre_tipo_cambio";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en consulta: " . print_r(sqlsrv_errors(), true));
        }
        
        $tipos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $tipos[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($tipos);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Error obteniendo tipos de cambio',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

// Endpoint específico para obtener lugares de reparación
if (isset($_GET['action']) && $_GET['action'] == 'get_lugares') {
    try {
        $sql = "SELECT id_lugar, nombre_lugar FROM lugar_reparacion ORDER BY nombre_lugar";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en consulta: " . print_r(sqlsrv_errors(), true));
        }
        
        $lugares = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $lugares[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($lugares);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Error obteniendo lugares',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

// Endpoint específico para obtener estados de reparación
if (isset($_GET['action']) && $_GET['action'] == 'get_estados') {
    try {
        $sql = "SELECT id_estado_reparacion, nombre_estado FROM estado_reparacion ORDER BY nombre_estado";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en consulta: " . print_r(sqlsrv_errors(), true));
        }
        
        $estados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $estados[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($estados);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Error obteniendo estados',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

// NUEVO: Endpoint para eliminar cambio de hardware (DEBE IR ANTES del bloque POST principal)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_cambio_hardware') {
    $id_cambio_hardware = (int)($_POST['id_cambio_hardware'] ?? 0);
    
    if ($id_cambio_hardware <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ID de cambio de hardware inválido']);
        exit;
    }
    
    try {
        sqlsrv_begin_transaction($conn);
        
        // Verificar que el cambio existe
        $sql_verificar = "SELECT id_cambio_hardware FROM cambio_hardware WHERE id_cambio_hardware = ?";
        $stmt_verificar = sqlsrv_query($conn, $sql_verificar, [$id_cambio_hardware]);
        
        if (!$stmt_verificar || !sqlsrv_fetch_array($stmt_verificar)) {
            throw new Exception("El cambio de hardware no existe");
        }
        
        // Eliminar el cambio de hardware
        $sql_eliminar = "DELETE FROM cambio_hardware WHERE id_cambio_hardware = ?";
        $stmt_eliminar = sqlsrv_query($conn, $sql_eliminar, [$id_cambio_hardware]);
        
        if (!$stmt_eliminar) {
            throw new Exception("Error eliminando cambio de hardware: " . print_r(sqlsrv_errors(), true));
        }
        
        sqlsrv_commit($conn);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Cambio de hardware eliminado correctamente'
        ]);
        exit;
        
    } catch (Exception $e) {
        sqlsrv_rollback($conn);
        error_log("Error eliminando cambio de hardware: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // CORREGIDO: Manejar guardado de cambios de hardware con estructura de tabla corregida
    if ($accion === "guardar_cambio_hardware") {
        $id_reparacion = $_POST["id_reparacion"] ?? '';
        $id_activo = $_POST["id_activo"] ?? '';
        $id_tipo_cambio = $_POST["id_tipo_cambio"] ?? '';
        $tipo_componente = $_POST["tipo_componente"] ?? '';
        $componente_actual = $_POST["componente_actual"] ?? '';
        $tipo_nuevo_componente = $_POST["tipo_nuevo_componente"] ?? '';
        $costo = (isset($_POST["costo"]) && $_POST["costo"] !== '') ? $_POST["costo"] : null;
        $motivo = $_POST["motivo"] ?? '';
        
        // NUEVO: Validaciones más robustas
        if (empty($id_reparacion) || empty($id_activo) || empty($id_tipo_cambio) || empty($tipo_componente) || empty($tipo_nuevo_componente)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
            exit;
        }
        
        try {
            sqlsrv_begin_transaction($conn);
            
            // MEJORADO: Validación específica para tipo de cambio
            if ($id_tipo_cambio === '1' || $id_tipo_cambio === '3') { // Reemplazo o Retiro
                if (empty($componente_actual)) {
                    $tipoTexto = $id_tipo_cambio === '1' ? 'reemplazo' : 'retiro';
                    throw new Exception("Para un $tipoTexto debe seleccionar el componente actual");
                }
                
                // CORREGIDO: Verificar que el slot existe, pertenece al activo Y está ocupado
                $sql_verificar = "SELECT estado FROM slot_activo WHERE id_slot = ? AND id_activo = ?";
                $stmt_verificar = sqlsrv_query($conn, $sql_verificar, [$componente_actual, $id_activo]);
                $row_verificar = sqlsrv_fetch_array($stmt_verificar, SQLSRV_FETCH_ASSOC);
                
                if (!$row_verificar) {
                    throw new Exception("El slot seleccionado no existe o no pertenece a este activo");
                }
                
                // NUEVO: Validar que el slot esté ocupado para reemplazo/retiro
                if ($row_verificar['estado'] !== 'ocupado') {
                    $tipoTexto = $id_tipo_cambio === '1' ? 'reemplazar' : 'retirar';
                    throw new Exception("No se puede $tipoTexto un slot disponible. Solo se pueden $tipoTexto componentes que estén actualmente instalados.");
                }
                
                error_log("DEBUG: Validando slot ocupado - ID Slot: $componente_actual, ID Activo: $id_activo, Estado: " . $row_verificar['estado']);
                
            } elseif ($id_tipo_cambio === '2') { // Adición
                if (empty($componente_actual)) {
                    throw new Exception("Para una adición debe seleccionar un slot disponible");
                }
                
                // NUEVO: Verificar que el slot existe, pertenece al activo Y está disponible
                $sql_verificar = "SELECT estado FROM slot_activo WHERE id_slot = ? AND id_activo = ?";
                $stmt_verificar = sqlsrv_query($conn, $sql_verificar, [$componente_actual, $id_activo]);
                $row_verificar = sqlsrv_fetch_array($stmt_verificar, SQLSRV_FETCH_ASSOC);
                
                if (!$row_verificar) {
                    throw new Exception("El slot seleccionado no existe o no pertenece a este activo");
                }
                
                // NUEVO: Validar que el slot esté disponible para adición
                if ($row_verificar['estado'] !== 'disponible') {
                    throw new Exception("No se puede agregar un componente a un slot ocupado. Solo se pueden usar slots disponibles para adición.");
                }
                
                error_log("DEBUG: Validando slot disponible - ID Slot: $componente_actual, ID Activo: $id_activo, Estado: " . $row_verificar['estado']);
            }
            
            // NUEVO: Validar costo si se proporciona
            if ($costo !== null && $costo < 0) {
                throw new Exception("El costo no puede ser negativo");
            }
            
            // CORREGIDO: Obtener información del componente actual para TODOS los tipos de cambio
            $componente_retirado_descripcion = '';
            
            if ($componente_actual) {
                // NUEVO: Verificar el estado del slot seleccionado
                $sql_verificar_slot = "SELECT estado FROM slot_activo WHERE id_slot = ?";
                $stmt_verificar_slot = sqlsrv_query($conn, $sql_verificar_slot, [$componente_actual]);
                $row_slot = sqlsrv_fetch_array($stmt_verificar_slot, SQLSRV_FETCH_ASSOC);
                
                if ($row_slot['estado'] === 'disponible') {
                    $componente_retirado_descripcion = 'Slot disponible - Sin componente previo';
                } else {
                    // Solo para slots ocupados, obtener la descripción del componente actual
                    $sql_actual = "
                        SELECT sa.tipo_slot,
                               CASE sa.tipo_slot
                                   WHEN 'PROCESADOR' THEN 
                                       CASE 
                                           WHEN sap.id_procesador IS NOT NULL THEN CONCAT(ISNULL(mp.nombre + ' ', ''), p.modelo)
                                           WHEN sap.id_procesador_generico IS NOT NULL THEN pg.modelo
                                       END
                                   WHEN 'RAM' THEN 
                                       CASE 
                                           WHEN sar.id_ram IS NOT NULL THEN CONCAT(r.capacidad, ISNULL(' ' + r.tipo, ''))
                                           WHEN sar.id_ram_generico IS NOT NULL THEN rg.capacidad
                                       END
                                   WHEN 'ALMACENAMIENTO' THEN 
                                       CASE 
                                           WHEN saa.id_almacenamiento IS NOT NULL THEN CONCAT(st.capacidad, ISNULL(' ' + st.tipo, ''))
                                           WHEN saa.id_almacenamiento_generico IS NOT NULL THEN CONCAT(sg.capacidad, ISNULL(' ' + sg.tipo, ''))
                                       END
                                   WHEN 'TARJETA_VIDEO' THEN 
                                       CASE 
                                           WHEN satv.id_tarjeta_video IS NOT NULL THEN CONCAT(tv.modelo, ISNULL(' ' + tv.memoria, ''))
                                           WHEN satv.id_tarjeta_video_generico IS NOT NULL THEN CONCAT(tvg.modelo, ISNULL(' ' + tvg.memoria, ''))
                                       END
                               END as descripcion
                        FROM slot_activo sa
                        LEFT JOIN slot_activo_procesador sap ON sa.id_slot = sap.id_slot
                        LEFT JOIN procesador p ON sap.id_procesador = p.id_procesador
                        LEFT JOIN marca mp ON p.id_marca = mp.id_marca
                        LEFT JOIN procesador_generico pg ON sap.id_procesador_generico = pg.id_procesador_generico
                        LEFT JOIN slot_activo_ram sar ON sa.id_slot = sar.id_slot
                        LEFT JOIN RAM r ON sar.id_ram = r.id_ram
                        LEFT JOIN RAM_generico rg ON sar.id_ram_generico = rg.id_ram_generico
                        LEFT JOIN slot_activo_almacenamiento saa ON sa.id_slot = saa.id_slot
                        LEFT JOIN almacenamiento st ON saa.id_almacenamiento = st.id_almacenamiento
                        LEFT JOIN almacenamiento_generico sg ON saa.id_almacenamiento_generico = sg.id_almacenamiento_generico
                        LEFT JOIN slot_activo_tarjeta_video satv ON sa.id_slot = satv.id_slot
                        LEFT JOIN tarjeta_video tv ON satv.id_tarjeta_video = tv.id_tarjeta_video
                        LEFT JOIN tarjeta_video_generico tvg ON satv.id_tarjeta_video_generico = tvg.id_tarjeta_video_generico
                        WHERE sa.id_slot = ?";
                    
                    $stmt_actual = sqlsrv_query($conn, $sql_actual, [$componente_actual]);
                    if ($stmt_actual && $row_actual = sqlsrv_fetch_array($stmt_actual, SQLSRV_FETCH_ASSOC)) {
                        $componente_retirado_descripcion = $row_actual['descripcion'] ?? 'Componente desconocido';
                    }
                }
            } else {
                // Para otros tipos de cambio sin componente seleccionado
                $descripcion_tipo_cambio = [
                    '2' => 'Adición de nuevo componente',
                    '3' => 'Retiro de componente',
                    '4' => 'Actualización de componente'
                ];
                $componente_retirado_descripcion = $descripcion_tipo_cambio[$id_tipo_cambio] ?? 'Cambio de componente';
            }
            
            // CORREGIDO: Procesar el nuevo componente según el tipo y crear referencias correctas
            $nuevo_componente_id = null;
            $nuevo_componente_tabla = '';
            $nuevo_componente_descripcion = '';
            
            // Variables para las columnas específicas en cambio_hardware
            $id_procesador_cambio = null;
            $id_ram_cambio = null;
            $id_almacenamiento_cambio = null;
            $id_tarjeta_video_cambio = null;
            
            // NUEVO: Variables para componentes genéricos en cambio_hardware
            $id_procesador_generico_cambio = null;
            $id_ram_generico_cambio = null;
            $id_almacenamiento_generico_cambio = null;
            $id_tarjeta_video_generico_cambio = null;
            
            switch ($tipo_nuevo_componente) {
                case 'existente':
                    $componente_existente = $_POST["id_componente_existente"] ?? '';
                    if (empty($componente_existente)) {
                        throw new Exception("Debe seleccionar un componente existente");
                    }
                    
                    if (!preg_match('/^(generico|detallado)_(\d+)$/', $componente_existente, $matches)) {
                        throw new Exception("Formato de componente existente inválido");
                    }
                    
                    $tipo_comp = $matches[1];
                    $id_comp = $matches[2];
                    $nuevo_componente_id = $id_comp;
                    
                    // CORREGIDO: Obtener descripción y configurar IDs para AMBOS tipos (genérico y detallado)
                    $sql_desc = '';
                    switch ($tipo_componente) {
                        case 'procesador':
                            if ($tipo_comp === 'generico') {
                                $sql_desc = "SELECT modelo as descripcion FROM procesador_generico WHERE id_procesador_generico = ?";
                                $nuevo_componente_tabla = 'id_procesador_generico';
                                $id_procesador_generico_cambio = $nuevo_componente_id; // NUEVO: Asignar para genéricos
                            } else {
                                $sql_desc = "SELECT CONCAT(ISNULL(m.nombre + ' ', ''), p.modelo) as descripcion FROM procesador p LEFT JOIN marca m ON p.id_marca = m.id_marca WHERE p.id_procesador = ?";
                                $nuevo_componente_tabla = 'id_procesador';
                                $id_procesador_cambio = $nuevo_componente_id;
                            }
                            break;
                        case 'ram':
                            if ($tipo_comp === 'generico') {
                                $sql_desc = "SELECT capacidad as descripcion FROM RAM_generico WHERE id_ram_generico = ?";
                                $nuevo_componente_tabla = 'id_ram_generico';
                                $id_ram_generico_cambio = $nuevo_componente_id; // NUEVO: Asignar para genéricos
                            } else {
                                $sql_desc = "SELECT CONCAT(r.capacidad, ISNULL(' ' + r.tipo, '')) as descripcion FROM RAM r WHERE r.id_ram = ?";
                                $nuevo_componente_tabla = 'id_ram';
                                $id_ram_cambio = $nuevo_componente_id;
                            }
                            break;
                        case 'almacenamiento':
                            if ($tipo_comp === 'generico') {
                                $sql_desc = "SELECT CONCAT(capacidad, ISNULL(' ' + tipo, '')) as descripcion FROM almacenamiento_generico WHERE id_almacenamiento_generico = ?";
                                $nuevo_componente_tabla = 'id_almacenamiento_generico';
                                $id_almacenamiento_generico_cambio = $nuevo_componente_id; // NUEVO: Asignar para genéricos
                            } else {
                                $sql_desc = "SELECT CONCAT(a.capacidad, ISNULL(' ' + a.tipo, '')) as descripcion FROM almacenamiento a WHERE a.id_almacenamiento = ?";
                                $nuevo_componente_tabla = 'id_almacenamiento';
                                $id_almacenamiento_cambio = $nuevo_componente_id;
                            }
                            break;
                        case 'tarjeta_video':
                            if ($tipo_comp === 'generico') {
                                $sql_desc = "SELECT CONCAT(modelo, ISNULL(' ' + memoria, '')) as descripcion FROM tarjeta_video_generico WHERE id_tarjeta_video_generico = ?";
                                $nuevo_componente_tabla = 'id_tarjeta_video_generico';
                                $id_tarjeta_video_generico_cambio = $nuevo_componente_id; // NUEVO: Asignar para genéricos
                            } else {
                                $sql_desc = "SELECT CONCAT(tv.modelo, ISNULL(' ' + tv.memoria, '')) as descripcion FROM tarjeta_video tv WHERE tv.id_tarjeta_video = ?";
                                $nuevo_componente_tabla = 'id_tarjeta_video';
                                $id_tarjeta_video_cambio = $nuevo_componente_id;
                            }
                            break;
                    }
                    
                    $stmt_desc = sqlsrv_query($conn, $sql_desc, [$nuevo_componente_id]);
                    if ($stmt_desc && $row_desc = sqlsrv_fetch_array($stmt_desc, SQLSRV_FETCH_ASSOC)) {
                        $nuevo_componente_descripcion = $row_desc['descripcion'] ?? 'Componente';
                    } else {
                        throw new Exception("No se encontró el componente seleccionado");
                    }
                    break;
                    
                default:
                    throw new Exception("Solo se permite usar componentes existentes");
            }
            
            // CORREGIDO: Registrar cambio de hardware con TODAS las columnas (detallados Y genéricos)
            $sql_cambio = "INSERT INTO cambio_hardware (
                id_activo, id_reparacion, 
                id_procesador, id_ram, id_almacenamiento, id_tarjeta_video,
                id_procesador_generico, id_ram_generico, id_almacenamiento_generico, id_tarjeta_video_generico,
                id_tipo_cambio, fecha, motivo, costo, componente_retirado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?, ?)";
            
            $params_cambio = [
                $id_activo, 
                $id_reparacion, 
                // Componentes detallados
                $id_procesador_cambio,
                $id_ram_cambio,
                $id_almacenamiento_cambio,
                $id_tarjeta_video_cambio,
                // NUEVO: Componentes genéricos
                $id_procesador_generico_cambio,
                $id_ram_generico_cambio,
                $id_almacenamiento_generico_cambio,
                $id_tarjeta_video_generico_cambio,
                // Otros campos
                $id_tipo_cambio, 
                $motivo, 
                $costo, 
                $componente_retirado_descripcion
            ];
            
            $stmt_cambio = sqlsrv_query($conn, $sql_cambio, $params_cambio);
            
            if (!$stmt_cambio) {
                throw new Exception("Error registrando cambio de hardware: " . print_r(sqlsrv_errors(), true));
            }
            
            // NUEVO: Log detallado para verificar qué se guardó
            error_log("SUCCESS: Cambio registrado - Detallados: [P:$id_procesador_cambio, R:$id_ram_cambio, A:$id_almacenamiento_cambio, TV:$id_tarjeta_video_cambio] - Genéricos: [P:$id_procesador_generico_cambio, R:$id_ram_generico_cambio, A:$id_almacenamiento_generico_cambio, TV:$id_tarjeta_video_generico_cambio]");
            
            // CORREGIDO: Actualizar el slot del activo SIEMPRE que tengamos componente_actual y nuevo_componente_id
            if ($componente_actual && $nuevo_componente_id && $nuevo_componente_tabla) {
                // Limpiar slot actual - eliminar de todas las tablas de slots para ese slot específico
                $sqls_limpiar = [
                    "DELETE FROM slot_activo_procesador WHERE id_slot = ?",
                    "DELETE FROM slot_activo_ram WHERE id_slot = ?",
                    "DELETE FROM slot_activo_almacenamiento WHERE id_slot = ?",
                    "DELETE FROM slot_activo_tarjeta_video WHERE id_slot = ?"
                ];
                
                foreach ($sqls_limpiar as $sql_limpiar) {
                    $stmt_limpiar = sqlsrv_query($conn, $sql_limpiar, [$componente_actual]);
                    if (!$stmt_limpiar) {
                        error_log("Warning: Error limpiando slot: " . print_r(sqlsrv_errors(), true));
                    }
                }
                
                // Insertar nuevo componente en el slot correspondiente
                $tabla_slot = '';
                switch ($tipo_componente) {
                    case 'procesador':
                        $tabla_slot = 'slot_activo_procesador';
                        break;
                    case 'ram':
                        $tabla_slot = 'slot_activo_ram';
                        break;
                    case 'almacenamiento':
                        $tabla_slot = 'slot_activo_almacenamiento';
                        break;
                    case 'tarjeta_video':
                        $tabla_slot = 'slot_activo_tarjeta_video';
                        break;
                }
                
                if ($tabla_slot && $nuevo_componente_tabla) {
                    $sql_insertar = "INSERT INTO $tabla_slot (id_slot, $nuevo_componente_tabla) VALUES (?, ?)";
                    $stmt_insertar = sqlsrv_query($conn, $sql_insertar, [$componente_actual, $nuevo_componente_id]);
                    
                    if (!$stmt_insertar) {
                        throw new Exception("Error actualizando slot: " . print_r(sqlsrv_errors(), true));
                    }
                    
                    // NUEVO: Actualizar estado del slot a 'ocupado'
                    $sql_update_estado = "UPDATE slot_activo SET estado = 'ocupado' WHERE id_slot = ?";
                    $stmt_update_estado = sqlsrv_query($conn, $sql_update_estado, [$componente_actual]);
                    
                    if (!$stmt_update_estado) {
                        error_log("Warning: Error actualizando estado del slot: " . print_r(sqlsrv_errors(), true));
                    }
                    
                    error_log("SUCCESS: Slot actualizado - Tabla: $tabla_slot, Columna: $nuevo_componente_tabla, ID: $nuevo_componente_id, Slot: $componente_actual");
                }
            }
            
            sqlsrv_commit($conn);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Cambio de hardware guardado correctamente',
                'data' => [
                    'id_cambio' => $nuevo_componente_id,
                    'descripcion_nuevo' => $nuevo_componente_descripcion,
                    'descripcion_retirado' => $componente_retirado_descripcion,
                    'debug' => [
                        'slot_actualizado' => ($componente_actual && $id_tipo_cambio === '1'),
                        'tabla_slot' => $tabla_slot ?? 'N/A',
                        'columna' => $nuevo_componente_tabla,
                        'id_componente' => $nuevo_componente_id,
                        'cambio_hardware_detallados' => [
                            'procesador' => $id_procesador_cambio,
                            'ram' => $id_ram_cambio,
                            'almacenamiento' => $id_almacenamiento_cambio,
                            'tarjeta_video' => $id_tarjeta_video_cambio
                        ],
                        'cambio_hardware_genericos' => [
                            'procesador_generico' => $id_procesador_generico_cambio,
                            'ram_generico' => $id_ram_generico_cambio,
                            'almacenamiento_generico' => $id_almacenamiento_generico_cambio,
                            'tarjeta_video_generico' => $id_tarjeta_video_generico_cambio
                        ]
                    ]
                ]
            ]);
            exit;
            
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            error_log("Error guardando cambio de hardware: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    // Variables comunes para otras acciones
    $id_activo = $_POST["id_activo"] ?? '';
    $id_lugar_reparacion = $_POST["id_lugar_reparacion"] ?? '';
    $id_estado_reparacion = $_POST["id_estado_reparacion"] ?? '';
    $id_proveedor = !empty($_POST["id_proveedor"]) ? $_POST["id_proveedor"] : null;
    $descripcion = $_POST["descripcion"] ?? '';
    
    // CORREGIDO: Manejar costo para permitir el valor 0
    $costo = null;
    if (isset($_POST["costo"]) && $_POST["costo"] !== '') {
        $costo = floatval($_POST["costo"]);
        // Validar que el costo no sea negativo
        if ($costo < 0) {
            die("❌ Error: El costo no puede ser negativo.");
        }
    }
    
    // CORREGIDO: Manejar tiempo_inactividad para permitir el valor 0 y validar negativos
    $tiempo_inactividad = null;
    if (isset($_POST["tiempo_inactividad"]) && $_POST["tiempo_inactividad"] !== '') {
        $tiempo_inactividad = (int)$_POST["tiempo_inactividad"];
        // Validar que el tiempo de inactividad no sea negativo
        if ($tiempo_inactividad < 0) {
            die("❌ Error: El tiempo de inactividad no puede ser negativo.");
        }
    }
    
    // CORREGIDO: Mejorar manejo de fechas para SQL Server
    $fecha = $_POST["fecha"] ?? '';
    if (!empty($fecha)) {
        // Log de debug para ver qué fecha recibimos
        error_log("DEBUG: Fecha recibida del formulario: " . $fecha);
        
        // Validar formato de fecha Y-m-d
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            die("❌ Error: Formato de fecha inválido. Use YYYY-MM-DD");
        }
        
        // Crear objeto DateTime desde la fecha recibida
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        
        if ($fechaObj === false) {
            error_log("DEBUG: Error al crear DateTime desde: " . $fecha);
            die("❌ Error: No se pudo procesar la fecha proporcionada.");
        }
        
        // Validar que la fecha no sea futura
        $hoy = new DateTime();
        $hoy->setTime(0, 0, 0); // Establecer a medianoche para comparar solo fechas
        
        if ($fechaObj > $hoy) {
            die("❌ Error: La fecha de reparación no puede ser posterior a hoy.");
        }
        
        // NUEVO: Formatear la fecha específicamente para SQL Server usando el formato ISO 8601 completo
        $fecha_sql = $fechaObj->format('Y-m-d\TH:i:s.v');
        
        error_log("DEBUG: Fecha formateada para SQL Server: " . $fecha_sql);
        
        // Alternativamente, usar formato simple que SQL Server entienda mejor
        $fecha = $fechaObj->format('Y-m-d');
        
        error_log("DEBUG: Fecha final para enviar a BD: " . $fecha);
    }
    
    $id_reparacion = $_POST["id_reparacion"] ?? '';
    $id_usuario = $_SESSION['id_usuario'] ?? 1;

    if ($accion === "crear") {
        // CORREGIDO: Validaciones adicionales para crear
        if (empty($fecha)) {
            die("❌ Error: La fecha es obligatoria.");
        }
        
        // NUEVO: Usar parámetros con tipo específico para SQL Server y agregar proveedor
        $sql = "INSERT INTO reparacion (id_usuario, id_activo, id_lugar_reparacion, id_estado_reparacion, id_proveedor, fecha, descripcion, costo, tiempo_inactividad) 
                VALUES (?, ?, ?, ?, ?, CAST(? AS DATE), ?, ?, ?)";
        $params = [$id_usuario, $id_activo, $id_lugar_reparacion, $id_estado_reparacion, $id_proveedor, $fecha, $descripcion, $costo, $tiempo_inactividad];
        
        error_log("DEBUG CREAR: SQL = " . $sql);
        error_log("DEBUG CREAR: Parámetros = " . print_r($params, true));
        
    } elseif ($accion === "editar" && !empty($id_reparacion)) {
        // CORREGIDO: Validaciones adicionales para editar
        if (empty($fecha)) {
            die("❌ Error: La fecha es obligatoria.");
        }
        
        // NUEVO: Usar CAST para asegurar el tipo correcto en SQL Server y agregar proveedor
        $sql = "UPDATE reparacion 
                SET id_activo = ?, id_lugar_reparacion = ?, id_estado_reparacion = ?, id_proveedor = ?, fecha = CAST(? AS DATE), descripcion = ?, costo = ?, tiempo_inactividad = ?
                WHERE id_reparacion = ?";
        $params = [$id_activo, $id_lugar_reparacion, $id_estado_reparacion, $id_proveedor, $fecha, $descripcion, $costo, $tiempo_inactividad, $id_reparacion];
        
        error_log("DEBUG EDITAR: SQL = " . $sql);
        error_log("DEBUG EDITAR: Parámetros = " . print_r($params, true));
        
    } elseif ($accion === "eliminar" && !empty($id_reparacion)) {
        // Primero eliminar cambios de hardware relacionados
        $sqlCambios = "DELETE FROM cambio_hardware WHERE id_reparacion = ?";
        $stmtCambios = sqlsrv_query($conn, $sqlCambios, [$id_reparacion]);
        
        // Luego eliminar la reparación
        $sql = "DELETE FROM reparacion WHERE id_reparacion = ?";
        $params = [$id_reparacion];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        error_log("DEBUG: Operación exitosa para acción: " . $accion);
        header("Location: ../views/crud_reparacion.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        error_log("DEBUG: Error en SQL: " . print_r($errors, true));
        echo "Error en la operación:<br>";
        print_r($errors);
    }
}

// Endpoint para obtener componentes por tipo
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action']) && $_GET['action'] === 'get_componentes') {
    $tipo = $_GET['tipo'] ?? '';
    
    error_log("=== DEBUG GET_COMPONENTES ===");
    error_log("Tipo solicitado: " . $tipo);
    
    if (empty($tipo)) {
        error_log("ERROR: Tipo de componente no especificado");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Tipo de componente no especificado']);
        exit;
    }
    
    try {
        $componentes = [];
        
        switch ($tipo) {
            case 'procesador':
                error_log("Consultando procesadores...");
                // Componentes detallados
                $sql_detallados = "
                    SELECT 
                        p.id_procesador as id, 
                        CONCAT(ISNULL(m.nombre + ' ', ''), p.modelo, ISNULL(' ' + p.generacion, '')) as descripcion,
                        'detallado' as tipo
                    FROM procesador p 
                    LEFT JOIN marca m ON p.id_marca = m.id_marca
                    ORDER BY descripcion";
                
                $stmt_detallados = sqlsrv_query($conn, $sql_detallados);
                if ($stmt_detallados) {
                    $count_detallados = 0;
                    while ($row = sqlsrv_fetch_array($stmt_detallados, SQLSRV_FETCH_ASSOC)) {
                        $componentes[] = $row;
                        $count_detallados++;
                    }
                    error_log("Procesadores detallados encontrados: " . $count_detallados);
                } else {
                    error_log("Error en consulta procesadores detallados: " . print_r(sqlsrv_errors(), true));
                }
                
                // Componentes genéricos
                $sql_genericos = "
                    SELECT 
                        id_procesador_generico as id, 
                        CONCAT(modelo, ISNULL(' ' + generacion, '')) as descripcion,
                        'generico' as tipo
                    FROM procesador_generico
                    ORDER BY descripcion";
                
                $stmt_genericos = sqlsrv_query($conn, $sql_genericos);
                if ($stmt_genericos) {
                    $count_genericos = 0;
                    while ($row = sqlsrv_fetch_array($stmt_genericos, SQLSRV_FETCH_ASSOC)) {
                        $componentes[] = $row;
                        $count_genericos++;
                    }
                    error_log("Procesadores genéricos encontrados: " . $count_genericos);
                } else {
                    error_log("Error en consulta procesadores genéricos: " . print_r(sqlsrv_errors(), true));
                }
                break;
                
            case 'ram':
                error_log("Consultando memorias RAM...");
                // RAM detalladas
                $sql_detallados = "
                    SELECT 
                        r.id_ram as id, 
                        CONCAT(r.capacidad, ISNULL(' ' + r.tipo, ''), ISNULL(' ' + m.nombre, '')) as descripcion,
                        'detallado' as tipo
                    FROM RAM r 
                    LEFT JOIN marca m ON r.id_marca = m.id_marca
                    ORDER BY descripcion";
                
                $stmt_detallados = sqlsrv_query($conn, $sql_detallados);
                if ($stmt_detallados) {
                    $count_detallados = 0;
                    while ($row = sqlsrv_fetch_array($stmt_detallados, SQLSRV_FETCH_ASSOC)) {
                        $componentes[] = $row;
                        $count_detallados++;
                    }
                    error_log("RAMs detalladas encontradas: " . $count_detallados);
                } else {
                    error_log("Error en consulta RAMs detalladas: " . print_r(sqlsrv_errors(), true));
                }
                
                // RAM genéricas
                $sql_genericos = "
                    SELECT 
                        id_ram_generico as id, 
                        capacidad as descripcion,
                        'generico' as tipo
                    FROM RAM_generico
                    ORDER BY descripcion";
                
                $stmt_genericos = sqlsrv_query($conn, $sql_genericos);
                if ($stmt_genericos) {
                    $count_genericos = 0;
                    while ($row = sqlsrv_fetch_array($stmt_genericos, SQLSRV_FETCH_ASSOC)) {
                        $componentes[] = $row;
                        $count_genericos++;
                    }
                    error_log("RAMs genéricas encontradas: " . $count_genericos);
                } else {
                    error_log("Error en consulta RAMs genéricas: " . print_r(sqlsrv_errors(), true));
                }
                break;
                
            case 'almacenamiento':
                error_log("Consultando almacenamiento...");
                // Almacenamiento detallado
                $sql_detallados = "
                    SELECT 
                        a.id_almacenamiento as id, 
                        CONCAT(a.capacidad, ISNULL(' ' + a.tipo, ''), ISNULL(' ' + m.nombre, '')) as descripcion,
                        'detallado' as tipo
                    FROM almacenamiento a 
                    LEFT JOIN marca m ON a.id_marca = m.id_marca
                    ORDER BY descripcion";
                
                $stmt_detallados = sqlsrv_query($conn, $sql_detallados);
                if ($stmt_detallados) {
                    $count_detallados = 0;
                    while ($row = sqlsrv_fetch_array($stmt_detallados, SQLSRV_FETCH_ASSOC)) {
                        $componentes[] = $row;
                        $count_detallados++;
                    }
                    error_log("Almacenamiento detallado encontrado: " . $count_detallados);
                } else {
                    error_log("Error en consulta almacenamiento detallado: " . print_r(sqlsrv_errors(), true));
                }
                
                // Almacenamiento genérico
                $sql_genericos = "
                    SELECT 
                        id_almacenamiento_generico as id, 
                        CONCAT(capacidad, ISNULL(' ' + tipo, '')) as descripcion,
                        'generico' as tipo
                    FROM almacenamiento_generico
                    ORDER BY descripcion";
                
                $stmt_genericos = sqlsrv_query($conn, $sql_genericos);
                if ($stmt_genericos) {
                    $count_genericos = 0;
                    while ($row = sqlsrv_fetch_array($stmt_genericos, SQLSRV_FETCH_ASSOC)) {
                        $componentes[] = $row;
                        $count_genericos++;
                    }
                    error_log("Almacenamiento genérico encontrado: " . $count_genericos);
                } else {
                    error_log("Error en consulta almacenamiento genérico: " . print_r(sqlsrv_errors(), true));
                }
                break;
                
            case 'tarjeta_video':
                error_log("Consultando tarjetas de video...");
                // Tarjetas de video detalladas
                $sql_detallados = "
                    SELECT 
                        tv.id_tarjeta_video as id, 
                        CONCAT(ISNULL(m.nombre + ' ', ''), tv.modelo, ISNULL(' ' + tv.memoria, ''), ISNULL(' ' + tv.tipo_memoria, '')) as descripcion,
                        'detallado' as tipo
                    FROM tarjeta_video tv
                    LEFT JOIN marca m ON tv.id_marca = m.id_marca
                    ORDER BY descripcion";
                
                $stmt_detallados = sqlsrv_query($conn, $sql_detallados);
                if ($stmt_detallados) {
                    $count_detallados = 0;
                    while ($row = sqlsrv_fetch_array($stmt_detallados, SQLSRV_FETCH_ASSOC)) {
                        $componentes[] = $row;
                        $count_detallados++;
                    }
                    error_log("Tarjetas de video detalladas encontradas: " . $count_detallados);
                } else {
                    error_log("Error en consulta tarjetas de video detalladas: " . print_r(sqlsrv_errors(), true));
                }
                
                // Tarjetas de video genéricas
                $sql_genericos = "
                    SELECT 
                        id_tarjeta_video_generico as id, 
                        CONCAT(modelo, ISNULL(' ' + memoria, '')) as descripcion,
                        'generico' as tipo
                    FROM tarjeta_video_generico
                    ORDER BY descripcion";
                
                $stmt_genericos = sqlsrv_query($conn, $sql_genericos);
                if ($stmt_genericos) {
                    $count_genericos = 0;
                    while ($row = sqlsrv_fetch_array($stmt_genericos, SQLSRV_FETCH_ASSOC)) {
                        $componentes[] = $row;
                        $count_genericos++;
                    }
                    error_log("Tarjetas de video genéricas encontradas: " . $count_genericos);
                } else {
                    error_log("Error en consulta tarjetas de video genéricas: " . print_r(sqlsrv_errors(), true));
                }
                break;
                
            default:
                throw new Exception("Tipo de componente no soportado: $tipo");
        }
        
        error_log("Total componentes encontrados: " . count($componentes));
        error_log("Componentes: " . print_r($componentes, true));
        
        header('Content-Type: application/json');
        echo json_encode($componentes);
        exit;
        
    } catch (Exception $e) {
        error_log("Error obteniendo componentes para reparaciones: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Error obteniendo componentes: ' . $e->getMessage()
        ]);
        exit;
    }
}

// NUEVO: Endpoint para obtener componentes actuales de un activo específico
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action']) && $_GET['action'] === 'get_componentes_activo') {
    $id_activo = (int)($_GET['id_activo'] ?? 0);
    $tipo = $_GET['tipo'] ?? '';
    
    if (empty($tipo) || $id_activo <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
        exit;
    }
    
    try {
        // NUEVO: Log de debug
        error_log("DEBUG: Obteniendo slots para activo $id_activo, tipo $tipo");
        
        $componentes = [];
        
        // Mapear tipo de componente a tipo de slot
        $tipo_slot_map = [
            'procesador' => 'PROCESADOR',
            'ram' => 'RAM',
            'almacenamiento' => 'ALMACENAMIENTO',
            'tarjeta_video' => 'TARJETA_VIDEO'
        ];
        
        if (!isset($tipo_slot_map[$tipo])) {
            throw new Exception("Tipo de componente no válido: $tipo");
        }
        
        $tipo_slot = $tipo_slot_map[$tipo];
        
        // NUEVO: Verificar primero que el activo existe
        $sql_verificar_activo = "SELECT COUNT(*) as count FROM activo WHERE id_activo = ?";
        $stmt_verificar_activo = sqlsrv_query($conn, $sql_verificar_activo, [$id_activo]);
        $row_activo = sqlsrv_fetch_array($stmt_verificar_activo, SQLSRV_FETCH_ASSOC);
        
        if ($row_activo['count'] == 0) {
            throw new Exception("El activo no existe");
        }
        
        error_log("DEBUG: Activo verificado, buscando slots de tipo $tipo_slot");
        
        // CORREGIDO: Consultar TODOS los slots del activo para el tipo específico (ocupados Y disponibles)
        switch ($tipo) {
            case 'procesador':
                $sql = "
                    SELECT 
                        sa.id_slot,
                        sa.estado,
                        CASE 
                            WHEN sa.estado = 'disponible' THEN 'Slot disponible'
                            WHEN sap.id_procesador IS NOT NULL THEN 
                                CONCAT(ISNULL(m.nombre + ' ', ''), p.modelo, ISNULL(' ' + p.generacion, ''))
                            WHEN sap.id_procesador_generico IS NOT NULL THEN 
                                CONCAT(pg.modelo, ISNULL(' ' + pg.generacion, ''))
                            ELSE 'Slot con componente desconocido'
                        END as descripcion,
                        CASE 
                            WHEN sa.estado = 'disponible' THEN 'disponible'
                            WHEN sap.id_procesador IS NOT NULL THEN 'detallado'
                            WHEN sap.id_procesador_generico IS NOT NULL THEN 'generico'
                            ELSE 'desconocido'
                        END as tipo,
                        CASE 
                            WHEN sap.id_procesador IS NOT NULL THEN sap.id_procesador
                            WHEN sap.id_procesador_generico IS NOT NULL THEN sap.id_procesador_generico
                            ELSE NULL
                        END as componente_id
                    FROM slot_activo sa
                    LEFT JOIN slot_activo_procesador sap ON sa.id_slot = sap.id_slot
                    LEFT JOIN procesador p ON sap.id_procesador = p.id_procesador
                    LEFT JOIN marca m ON p.id_marca = m.id_marca
                    LEFT JOIN procesador_generico pg ON sap.id_procesador_generico = pg.id_procesador_generico
                    WHERE sa.id_activo = ? AND sa.tipo_slot = ? AND sa.estado = 'ocupado'
                    ORDER BY sa.id_slot";
                break;
                
            case 'ram':
                $sql = "
                    SELECT 
                        sa.id_slot,
                        sa.estado,
                        CASE 
                            WHEN sa.estado = 'disponible' THEN 'Slot disponible'
                            WHEN sar.id_ram IS NOT NULL THEN 
                                CONCAT(r.capacidad, ISNULL(' ' + r.tipo, ''), ISNULL(' (' + mr.nombre + ')', ''))
                            WHEN sar.id_ram_generico IS NOT NULL THEN 
                                rg.capacidad
                            ELSE 'Slot con componente desconocido'
                        END as descripcion,
                        CASE 
                            WHEN sa.estado = 'disponible' THEN 'disponible'
                            WHEN sar.id_ram IS NOT NULL THEN 'detallado'
                            WHEN sar.id_ram_generico IS NOT NULL THEN 'generico'
                            ELSE 'desconocido'
                        END as tipo,
                        CASE 
                            WHEN sar.id_ram IS NOT NULL THEN sar.id_ram
                            WHEN sar.id_ram_generico IS NOT NULL THEN sar.id_ram_generico
                            ELSE NULL
                        END as componente_id
                    FROM slot_activo sa
                    LEFT JOIN slot_activo_ram sar ON sa.id_slot = sar.id_slot
                    LEFT JOIN RAM r ON sar.id_ram = r.id_ram
                    LEFT JOIN marca mr ON r.id_marca = mr.id_marca
                    LEFT JOIN RAM_generico rg ON sar.id_ram_generico = rg.id_ram_generico
                    WHERE sa.id_activo = ? AND sa.tipo_slot = ? AND sa.estado = 'ocupado'
                    ORDER BY sa.id_slot";
                break;
                
            case 'almacenamiento':
                $sql = "
                    SELECT 
                        sa.id_slot,
                        sa.estado,
                        CASE 
                            WHEN sa.estado = 'disponible' THEN 'Slot disponible'
                            WHEN saa.id_almacenamiento IS NOT NULL THEN 
                                CONCAT(st.capacidad, ISNULL(' ' + st.tipo, ''), ISNULL(' (' + ms.nombre + ')', ''))
                            WHEN saa.id_almacenamiento_generico IS NOT NULL THEN 
                                CONCAT(sg.capacidad, ISNULL(' ' + sg.tipo, ''))
                            ELSE 'Slot con componente desconocido'
                        END as descripcion,
                        CASE 
                            WHEN sa.estado = 'disponible' THEN 'disponible'
                            WHEN saa.id_almacenamiento IS NOT NULL THEN 'detallado'
                            WHEN saa.id_almacenamiento_generico IS NOT NULL THEN 'generico'
                            ELSE 'desconocido'
                        END as tipo,
                        CASE 
                            WHEN saa.id_almacenamiento IS NOT NULL THEN saa.id_almacenamiento
                            WHEN saa.id_almacenamiento_generico IS NOT NULL THEN saa.id_almacenamiento_generico
                            ELSE NULL
                        END as componente_id
                    FROM slot_activo sa
                    LEFT JOIN slot_activo_almacenamiento saa ON sa.id_slot = saa.id_slot
                    LEFT JOIN almacenamiento st ON saa.id_almacenamiento = st.id_almacenamiento
                    LEFT JOIN marca ms ON st.id_marca = ms.id_marca
                    LEFT JOIN almacenamiento_generico sg ON saa.id_almacenamiento_generico = sg.id_almacenamiento_generico
                    WHERE sa.id_activo = ? AND sa.tipo_slot = ? AND sa.estado = 'ocupado'
                    ORDER BY sa.id_slot";
                break;
                
            case 'tarjeta_video':
                $sql = "
                    SELECT 
                        sa.id_slot,
                        sa.estado,
                        CASE 
                            WHEN sa.estado = 'disponible' THEN 'Slot disponible'
                            WHEN satv.id_tarjeta_video IS NOT NULL THEN 
                                CONCAT(ISNULL(mtv.nombre + ' ', ''), tv.modelo, ISNULL(' ' + tv.memoria, ''))
                            WHEN satv.id_tarjeta_video_generico IS NOT NULL THEN 
                                CONCAT(tvg.modelo, ISNULL(' ' + tvg.memoria, ''))
                            ELSE 'Slot con componente desconocido'
                        END as descripcion,
                        CASE 
                            WHEN sa.estado = 'disponible' THEN 'disponible'
                            WHEN satv.id_tarjeta_video IS NOT NULL THEN 'detallado'
                            WHEN satv.id_tarjeta_video_generico IS NOT NULL THEN 'generico'
                            ELSE 'desconocido'
                        END as tipo,
                        CASE 
                            WHEN satv.id_tarjeta_video IS NOT NULL THEN satv.id_tarjeta_video
                            WHEN satv.id_tarjeta_video_generico IS NOT NULL THEN satv.id_tarjeta_video_generico
                            ELSE NULL
                        END as componente_id
                    FROM slot_activo sa
                    LEFT JOIN slot_activo_tarjeta_video satv ON sa.id_slot = satv.id_slot
                    LEFT JOIN tarjeta_video tv ON satv.id_tarjeta_video = tv.id_tarjeta_video
                    LEFT JOIN marca mtv ON tv.id_marca = mtv.id_marca
                    LEFT JOIN tarjeta_video_generico tvg ON satv.id_tarjeta_video_generico = tvg.id_tarjeta_video_generico
                    WHERE sa.id_activo = ? AND sa.tipo_slot = ? AND sa.estado = 'ocupado'
                    ORDER BY sa.id_slot";
                break;
                
            default:
                throw new Exception("Tipo de componente no soportado: $tipo");
        }
        
        $stmt = sqlsrv_query($conn, $sql, [$id_activo, $tipo_slot]);
        
        if ($stmt === false) {
            $errors = print_r(sqlsrv_errors(), true);
            error_log("DEBUG: Error en consulta: $errors");
            throw new Exception("Error en consulta de componentes actuales: $errors");
        }
        
        $count = 0;
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $count++;
            $componentes[] = [
                'id_slot' => $row['id_slot'],
                'descripcion' => $row['descripcion'],
                'tipo' => $row['tipo'],
                'componente_id' => $row['componente_id'],
                'estado' => $row['estado']
            ];
            
            error_log("DEBUG: Slot encontrado - ID: {$row['id_slot']}, Estado: {$row['estado']}, Descripción: {$row['descripcion']}");
        }
        
        error_log("DEBUG: Total de slots encontrados: $count");
        
        header('Content-Type: application/json');
        echo json_encode($componentes);
        exit;
        
    } catch (Exception $e) {
        error_log("ERROR obteniendo componentes actuales del activo: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Error obteniendo componentes del activo: ' . $e->getMessage()
        ]);
        exit;
    }
}

// CORREGIDO: Endpoint para obtener cambios de hardware con AMBOS tipos de componentes
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action']) && $_GET['action'] === 'get_cambios_hardware') {
    $id_reparacion = (int)($_GET['id_reparacion'] ?? 0);
    
    if ($id_reparacion <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ID de reparación inválido']);
        exit;
    }
    
    try {
        $sql = "
            SELECT 
                ch.id_cambio_hardware,
                ch.fecha,
                ch.motivo,
                ch.costo,
                ch.componente_retirado,
                tc.nombre_tipo_cambio,
                -- Determinar tipo y descripción del componente nuevo (AMBOS tipos)
                CASE 
                    WHEN ch.id_procesador IS NOT NULL THEN 'Procesador (Detallado)'
                    WHEN ch.id_procesador_generico IS NOT NULL THEN 'Procesador (Genérico)'
                    WHEN ch.id_ram IS NOT NULL THEN 'RAM (Detallada)'
                    WHEN ch.id_ram_generico IS NOT NULL THEN 'RAM (Genérica)'
                    WHEN ch.id_almacenamiento IS NOT NULL THEN 'Almacenamiento (Detallado)'
                    WHEN ch.id_almacenamiento_generico IS NOT NULL THEN 'Almacenamiento (Genérico)'
                    WHEN ch.id_tarjeta_video IS NOT NULL THEN 'Tarjeta de Video (Detallada)'
                    WHEN ch.id_tarjeta_video_generico IS NOT NULL THEN 'Tarjeta de Video (Genérica)'
                    ELSE 'Desconocido'
                END as tipo_componente,
                -- CORREGIDO: Mostrar información del componente nuevo (detallados Y genéricos)
                CASE 
                    -- Componentes detallados
                    WHEN ch.id_procesador IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(ISNULL(m.nombre + ' ', ''), p.modelo) FROM procesador p LEFT JOIN marca m ON p.id_marca = m.id_marca WHERE p.id_procesador = ch.id_procesador), 'Procesador detallado ID: ' + CAST(ch.id_procesador as VARCHAR))
                    WHEN ch.id_ram IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(r.capacidad, ISNULL(' ' + r.tipo, '')) FROM RAM r WHERE r.id_ram = ch.id_ram), 'RAM detallada ID: ' + CAST(ch.id_ram as VARCHAR))
                    WHEN ch.id_almacenamiento IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(a.capacidad, ISNULL(' ' + a.tipo, '')) FROM almacenamiento a WHERE a.id_almacenamiento = ch.id_almacenamiento), 'Almacenamiento detallado ID: ' + CAST(ch.id_almacenamiento as VARCHAR))
                    WHEN ch.id_tarjeta_video IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(tv.modelo, ISNULL(' ' + tv.memoria, '')) FROM tarjeta_video tv WHERE tv.id_tarjeta_video = ch.id_tarjeta_video), 'Tarjeta Video detallada ID: ' + CAST(ch.id_tarjeta_video as VARCHAR))
                    -- NUEVO: Componentes genéricos
                    WHEN ch.id_procesador_generico IS NOT NULL THEN 
                        ISNULL((SELECT modelo FROM procesador_generico WHERE id_procesador_generico = ch.id_procesador_generico), 'Procesador genérico ID: ' + CAST(ch.id_procesador_generico as VARCHAR))
                    WHEN ch.id_ram_generico IS NOT NULL THEN 
                        ISNULL((SELECT capacidad FROM RAM_generico WHERE id_ram_generico = ch.id_ram_generico), 'RAM genérica ID: ' + CAST(ch.id_ram_generico as VARCHAR))
                    WHEN ch.id_almacenamiento_generico IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(capacidad, ISNULL(' ' + tipo, '')) FROM almacenamiento_generico WHERE id_almacenamiento_generico = ch.id_almacenamiento_generico), 'Almacenamiento genérico ID: ' + CAST(ch.id_almacenamiento_generico as VARCHAR))
                    WHEN ch.id_tarjeta_video_generico IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(modelo, ISNULL(' ' + memoria, '')) FROM tarjeta_video_generico WHERE id_tarjeta_video_generico = ch.id_tarjeta_video_generico), 'Tarjeta Video genérica ID: ' + CAST(ch.id_tarjeta_video_generico as VARCHAR))
                    ELSE 'Sin componente registrado'
                END as componente_nuevo,
                -- Mostrar fecha formateada
                FORMAT(ch.fecha, 'dd/MM/yyyy') as fecha_formateada
            FROM cambio_hardware ch
            LEFT JOIN tipo_cambio tc ON ch.id_tipo_cambio = tc.id_tipo_cambio
            WHERE ch.id_reparacion = ?
            ORDER BY ch.fecha DESC
        ";
        
        $stmt = sqlsrv_query($conn, $sql, [$id_reparacion]);
        
        if ($stmt === false) {
            throw new Exception("Error en consulta de cambios de hardware: " . print_r(sqlsrv_errors(), true));
        }
        
        $cambios = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear fecha si es un objeto DateTime
            if ($row['fecha'] instanceof DateTime) {
                $row['fecha'] = $row['fecha']->format('Y-m-d');
            }
            $cambios[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($cambios);
        exit;
        
    } catch (Exception $e) {
        error_log("Error obteniendo cambios de hardware: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Error obteniendo cambios de hardware: ' . $e->getMessage()
        ]);
        exit;
    }
}

// CORREGIDO: Endpoint para obtener reparaciones (agregar este nuevo endpoint)
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action']) && $_GET['action'] === 'get_reparaciones') {
    try {
        $sql = "SELECT r.*, 
                       CASE 
                           WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                           WHEN a.id_pc IS NOT NULL THEN ISNULL(p.nombreEquipo, 'Sin nombre') 
                           WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                           ELSE 'Activo sin tipo'
                       END as nombre_equipo,
                       ISNULL(a.tipo_activo, 'Sin tipo') as tipo_activo,
                       ISNULL(lr.nombre_lugar, 'Sin lugar') as nombre_lugar,
                       ISNULL(er.nombre_estado, 'Sin estado') as nombre_estado,
                       ISNULL(prov.nombre, '-') as nombre_proveedor,
                       CASE 
                           WHEN asig.id_persona IS NOT NULL THEN CONCAT(per.nombre, ' ', per.apellido)
                           ELSE 'Sin asignar'
                       END as persona_asignada
                FROM reparacion r
                INNER JOIN activo a ON r.id_activo = a.id_activo
                LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
                LEFT JOIN pc p ON a.id_pc = p.id_pc
                LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
                LEFT JOIN lugar_reparacion lr ON r.id_lugar_reparacion = lr.id_lugar
                LEFT JOIN estado_reparacion er ON r.id_estado_reparacion = er.id_estado_reparacion
                LEFT JOIN proveedor prov ON r.id_proveedor = prov.id_proveedor
                LEFT JOIN (
                    SELECT id_activo, id_persona, 
                           ROW_NUMBER() OVER (PARTITION BY id_activo ORDER BY fecha_asignacion DESC) as rn
                    FROM asignacion 
                    WHERE fecha_retorno IS NULL
                ) asig ON a.id_activo = asig.id_activo AND asig.rn = 1
                LEFT JOIN persona per ON asig.id_persona = per.id_persona
                ORDER BY r.fecha DESC";
                
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en consulta de reparaciones: " . print_r(sqlsrv_errors(), true));
        }
        
        $reparaciones = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // CORREGIDO: Formatear fecha si es un objeto DateTime
            if ($row['fecha'] instanceof DateTime) {
                $row['fecha'] = $row['fecha']->format('Y-m-d');
            }
            
            // CORREGIDO: Asegurar que tiempo_inactividad mantenga el valor 0 como número
            if (isset($row['tiempo_inactividad'])) {
                $row['tiempo_inactividad'] = (int)$row['tiempo_inactividad'];
            }
            
            $reparaciones[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($reparaciones);
        exit;
        
    } catch (Exception $e) {
        error_log("Error obteniendo reparaciones: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Error obteniendo reparaciones: ' . $e->getMessage()
        ]);
        exit;
    }
}

// CORREGIDO: Endpoint para obtener activos
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action']) && $_GET['action'] === 'get_activos') {
    try {
        $sql = "SELECT a.id_activo, a.tipo_activo,
                       CASE 
                           WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                           WHEN a.id_pc IS NOT NULL THEN ISNULL(p.nombreEquipo, 'Sin nombre')
                           WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                           ELSE 'Activo sin tipo'
                       END as nombre_equipo
                FROM activo a
                LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
                LEFT JOIN pc p ON a.id_pc = p.id_pc
                LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
                ORDER BY nombre_equipo";
                
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en consulta de activos: " . print_r(sqlsrv_errors(), true));
        }
        
        $activos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $activos[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($activos);
        exit;
        
    } catch (Exception $e) {
        error_log("Error obteniendo activos: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Error obteniendo activos: ' . $e->getMessage()
        ]);
        exit;
    }
}

?>