<?php
include("../includes/conexion.php");

// Controlador específico para cambios de hardware

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    error_log("=== PROCESAR CAMBIO HARDWARE ===");
    error_log("POST: " . print_r($_POST, true));
    
    // Si es una consulta de estructura
    if (isset($_POST['action']) && $_POST['action'] === 'check_structure') {
        $sqlCheck = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'cambio_hardware' ORDER BY ORDINAL_POSITION";
        $stmtCheck = sqlsrv_query($conn, $sqlCheck);
        
        $columnas = [];
        if ($stmtCheck) {
            while ($row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
                $columnas[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'columnas' => $columnas
        ]);
        exit;
    }
    
    // Si es una acción para eliminar cambio de hardware
    if (isset($_POST['action']) && $_POST['action'] === 'eliminar_cambio_hardware') {
        $id_cambio_hardware = $_POST['id_cambio_hardware'] ?? '';
        
        if (empty($id_cambio_hardware)) {
            echo json_encode(['success' => false, 'error' => 'ID de cambio requerido']);
            exit;
        }
        
        try {
            // Eliminar el cambio de hardware
            $sqlEliminar = "DELETE FROM cambio_hardware WHERE id_cambio_hardware = ?";
            $stmtEliminar = sqlsrv_prepare($conn, $sqlEliminar, [$id_cambio_hardware]);
            
            if (!$stmtEliminar || !sqlsrv_execute($stmtEliminar)) {
                throw new Exception("Error eliminando cambio de hardware: " . print_r(sqlsrv_errors(), true));
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Cambio de hardware eliminado correctamente'
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log("Error eliminando cambio de hardware: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Error eliminando cambio: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Obtener datos del formulario
    $id_reparacion = $_POST['id_reparacion'] ?? '';
    $id_activo = $_POST['id_activo'] ?? '';
    $id_tipo_cambio = $_POST['id_tipo_cambio'] ?? '';
    $tipo_componente = $_POST['tipo_componente'] ?? '';
    $id_componente_existente = $_POST['id_componente_existente'] ?? '';
    $componente_actual = $_POST['componente_actual'] ?? '';
    $costo = $_POST['costo'] ?? 0;
    $motivo = $_POST['motivo'] ?? '';
    
    // Validar datos requeridos - diferentes para cada tipo de cambio
    if (empty($id_reparacion) || empty($id_tipo_cambio) || empty($tipo_componente)) {
        echo json_encode([
            'success' => false, 
            'error' => 'Faltan datos básicos requeridos'
        ]);
        exit;
    }
    
    // Para retiro (tipo 3) no se requiere componente existente
    if ($id_tipo_cambio != '3' && empty($id_componente_existente)) {
        echo json_encode([
            'success' => false, 
            'error' => 'Se requiere seleccionar un componente para reemplazo o instalación'
        ]);
        exit;
    }
    
    // Para retiro, verificar que se haya seleccionado componente a retirar
    if ($id_tipo_cambio == '3' && empty($componente_actual)) {
        echo json_encode([
            'success' => false, 
            'error' => 'Debe seleccionar el componente a retirar'
        ]);
        exit;
    }
    
    // Parsear el componente existente (formato: tipo_id) - solo si NO es retiro
    $tipo_nuevo_componente = '';
    $id_nuevo_componente = '';
    
    if ($id_tipo_cambio != '3') {
        // Solo para reemplazo e instalación
        if (empty($id_componente_existente)) {
            echo json_encode([
                'success' => false, 
                'error' => 'Se requiere seleccionar un componente para reemplazo o instalación'
            ]);
            exit;
        }
        
        $partes = explode('_', $id_componente_existente);
        if (count($partes) !== 2) {
            echo json_encode([
                'success' => false, 
                'error' => 'Formato de componente inválido'
            ]);
            exit;
        }
        
        $tipo_nuevo_componente = $partes[0]; // 'generico' o 'detallado'
        $id_nuevo_componente = $partes[1];
        
        error_log("Componente parseado: tipo=$tipo_nuevo_componente, id=$id_nuevo_componente, tipo_componente=$tipo_componente");
    } else {
        // Para retiro no hay componente nuevo
        error_log("Tipo RETIRO: no se parsea componente existente");
    }
    
    // Preparar campos específicos según el tipo de componente
    $campos_especificos = [];
    $valores_especificos = [];
    
    // Solo procesar componente específico si NO es retiro
    if ($id_tipo_cambio != '3' && !empty($id_componente_existente)) {
        switch ($tipo_componente) {
            case 'procesador':
                if ($tipo_nuevo_componente === 'generico') {
                    $campos_especificos[] = 'id_procesador_generico';
                    $valores_especificos[] = $id_nuevo_componente;
                } else {
                    $campos_especificos[] = 'id_procesador';
                    $valores_especificos[] = $id_nuevo_componente;
                }
                break;
                
            case 'ram':
                if ($tipo_nuevo_componente === 'generico') {
                    $campos_especificos[] = 'id_ram_generico';
                    $valores_especificos[] = $id_nuevo_componente;
                } else {
                    $campos_especificos[] = 'id_ram';
                    $valores_especificos[] = $id_nuevo_componente;
                }
                break;
                
            case 'almacenamiento':
                if ($tipo_nuevo_componente === 'generico') {
                    $campos_especificos[] = 'id_almacenamiento_generico';
                    $valores_especificos[] = $id_nuevo_componente;
                } else {
                    $campos_especificos[] = 'id_almacenamiento';
                    $valores_especificos[] = $id_nuevo_componente;
                }
                break;
                
            case 'tarjeta_video':
                if ($tipo_nuevo_componente === 'generico') {
                    $campos_especificos[] = 'id_tarjeta_video_generico';
                    $valores_especificos[] = $id_nuevo_componente;
                } else {
                    $campos_especificos[] = 'id_tarjeta_video';
                    $valores_especificos[] = $id_nuevo_componente;
                }
                break;
                
            default:
                echo json_encode([
                    'success' => false, 
                    'error' => 'Tipo de componente no válido'
                ]);
                exit;
        }
    } else if ($id_tipo_cambio == '3') {
        error_log("Tipo RETIRO: no se procesan campos específicos de componente nuevo");
    }
    
    try {
        // DEBUG: Verificar estructura de la tabla
        $sqlCheck = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'cambio_hardware'";
        $stmtCheck = sqlsrv_query($conn, $sqlCheck);
        
        if ($stmtCheck) {
            $columnas = [];
            while ($row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
                $columnas[] = $row['COLUMN_NAME'];
            }
            error_log("Columnas disponibles en cambio_hardware: " . implode(', ', $columnas));
        }
        
        // Iniciar transacción
        sqlsrv_begin_transaction($conn);
        
        // PASO 1: Obtener información del componente retirado si es reemplazo/retiro
        $componente_retirado_desc = '';
        if (($id_tipo_cambio == '1' || $id_tipo_cambio == '3') && !empty($componente_actual)) {
            // Es reemplazo o retiro, obtener descripción del componente actual
            $componente_retirado_desc = obtenerDescripcionComponenteActual($conn, $componente_actual, $tipo_componente);
        }
        
        // PASO 2: Insertar el registro en cambio_hardware
        
        // Construir SQL dinámicamente con el campo específico del componente
        $campos_sql = "id_activo, id_reparacion, id_tipo_cambio, fecha, motivo, costo, componente_retirado";
        $valores_sql = "?, ?, ?, GETDATE(), ?, ?, ?";
        $params = [$id_activo, $id_reparacion, $id_tipo_cambio, $motivo, $costo, $componente_retirado_desc];
        
        // Agregar campo específico del componente
        if (!empty($campos_especificos)) {
            $campos_sql .= ", " . $campos_especificos[0];
            $valores_sql .= ", ?";
            $params[] = $valores_especificos[0];
        }
        
        $sql = "INSERT INTO cambio_hardware ($campos_sql) VALUES ($valores_sql)";
        
        error_log("SQL a ejecutar: $sql");
        error_log("Parámetros: " . print_r($params, true));
        
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error insertando cambio de hardware: " . print_r(sqlsrv_errors(), true));
        }
        
        // PASO 3: Actualizar los slots del activo según el tipo de cambio
        error_log("=== ACTUALIZANDO SLOTS DEL ACTIVO ===");
        error_log("ID Activo: $id_activo");
        error_log("Tipo Cambio: $id_tipo_cambio");
        error_log("Tipo Componente: $tipo_componente");
        error_log("Componente Actual/Slot: $componente_actual");
        
        if ($id_tipo_cambio == '3') {
            // Para retiro, solo usar los datos necesarios
            $resultado_slots = actualizarSlotsActivo($conn, $id_activo, $id_tipo_cambio, $tipo_componente, '', '', $componente_actual);
        } else {
            // Para reemplazo e instalación, usar todos los datos
            $resultado_slots = actualizarSlotsActivo($conn, $id_activo, $id_tipo_cambio, $tipo_componente, $tipo_nuevo_componente, $id_nuevo_componente, $componente_actual);
        }
        
        if (!$resultado_slots['success']) {
            throw new Exception("Error actualizando slots: " . $resultado_slots['error']);
        }
        
        error_log("✅ Slots actualizados correctamente");
        
        // Confirmar transacción
        sqlsrv_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cambio de hardware guardado correctamente'
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción
        sqlsrv_rollback($conn);
        
        error_log("Error en cambio de hardware: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error guardando el cambio: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
}

// =========================================================
// FUNCIONES AUXILIARES
// =========================================================

// Función para obtener descripción del componente actual
function obtenerDescripcionComponenteActual($conn, $id_slot, $tipo_componente) {
    try {
        switch ($tipo_componente) {
            case 'procesador':
                $sql = "SELECT 
                            CASE 
                                WHEN sap.id_procesador IS NOT NULL THEN CONCAT(ISNULL(m.nombre + ' ', ''), p.modelo, ISNULL(' ' + p.generacion, ''))
                                WHEN sap.id_procesador_generico IS NOT NULL THEN CONCAT(pg.modelo, ISNULL(' ' + pg.generacion, ''))
                                ELSE 'Procesador desconocido'
                            END as descripcion
                        FROM slot_activo_procesador sap
                        LEFT JOIN procesador p ON sap.id_procesador = p.id_procesador
                        LEFT JOIN marca m ON p.id_marca = m.id_marca
                        LEFT JOIN procesador_generico pg ON sap.id_procesador_generico = pg.id_procesador_generico
                        WHERE sap.id_slot = ?";
                break;
                
            case 'ram':
                $sql = "SELECT 
                            CASE 
                                WHEN sar.id_ram IS NOT NULL THEN CONCAT(r.capacidad, ISNULL(' ' + r.tipo, ''), ISNULL(' ' + m.nombre, ''))
                                WHEN sar.id_ram_generico IS NOT NULL THEN rg.capacidad
                                ELSE 'RAM desconocida'
                            END as descripcion
                        FROM slot_activo_ram sar
                        LEFT JOIN RAM r ON sar.id_ram = r.id_ram
                        LEFT JOIN marca m ON r.id_marca = m.id_marca
                        LEFT JOIN RAM_generico rg ON sar.id_ram_generico = rg.id_ram_generico
                        WHERE sar.id_slot = ?";
                break;
                
            case 'almacenamiento':
                $sql = "SELECT 
                            CASE 
                                WHEN saa.id_almacenamiento IS NOT NULL THEN CONCAT(a.capacidad, ISNULL(' ' + a.tipo, ''), ISNULL(' ' + m.nombre, ''))
                                WHEN saa.id_almacenamiento_generico IS NOT NULL THEN CONCAT(ag.capacidad, ISNULL(' ' + ag.tipo, ''))
                                ELSE 'Almacenamiento desconocido'
                            END as descripcion
                        FROM slot_activo_almacenamiento saa
                        LEFT JOIN almacenamiento a ON saa.id_almacenamiento = a.id_almacenamiento
                        LEFT JOIN marca m ON a.id_marca = m.id_marca
                        LEFT JOIN almacenamiento_generico ag ON saa.id_almacenamiento_generico = ag.id_almacenamiento_generico
                        WHERE saa.id_slot = ?";
                break;
                
            case 'tarjeta_video':
                $sql = "SELECT 
                            CASE 
                                WHEN satv.id_tarjeta_video IS NOT NULL THEN CONCAT(ISNULL(m.nombre + ' ', ''), tv.modelo, ISNULL(' ' + tv.memoria, ''))
                                WHEN satv.id_tarjeta_video_generico IS NOT NULL THEN CONCAT(tvg.modelo, ISNULL(' ' + tvg.memoria, ''))
                                ELSE 'Tarjeta de video desconocida'
                            END as descripcion
                        FROM slot_activo_tarjeta_video satv
                        LEFT JOIN tarjeta_video tv ON satv.id_tarjeta_video = tv.id_tarjeta_video
                        LEFT JOIN marca m ON tv.id_marca = m.id_marca
                        LEFT JOIN tarjeta_video_generico tvg ON satv.id_tarjeta_video_generico = tvg.id_tarjeta_video_generico
                        WHERE satv.id_slot = ?";
                break;
                
            default:
                return 'Componente desconocido';
        }
        
        $stmt = sqlsrv_prepare($conn, $sql, [$id_slot]);
        if ($stmt && sqlsrv_execute($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row ? $row['descripcion'] : 'Componente no encontrado';
        }
        
        return 'Error consultando componente';
        
    } catch (Exception $e) {
        error_log("Error obteniendo descripción componente: " . $e->getMessage());
        return 'Error obteniendo componente';
    }
}

// Función para actualizar slots del activo
function actualizarSlotsActivo($conn, $id_activo, $id_tipo_cambio, $tipo_componente, $tipo_nuevo_componente, $id_nuevo_componente, $componente_actual) {
    try {
        error_log("Actualizando slots - Activo: $id_activo, Tipo cambio: $id_tipo_cambio, Componente: $tipo_componente");
        
        // Mapear nombres de tipo_componente a tipo_slot
        $tipo_slot_map = [
            'procesador' => 'PROCESADOR',
            'ram' => 'RAM', 
            'almacenamiento' => 'ALMACENAMIENTO',
            'tarjeta_video' => 'TARJETA_VIDEO'
        ];
        
        $tipo_slot = $tipo_slot_map[$tipo_componente] ?? '';
        if (empty($tipo_slot)) {
            return ['success' => false, 'error' => 'Tipo de componente no válido para slots'];
        }
        
        switch ($id_tipo_cambio) {
            case '1': // Reemplazo
                return reemplazarComponenteSlot($conn, $componente_actual, $tipo_componente, $tipo_nuevo_componente, $id_nuevo_componente);
                
            case '2': // Instalación  
                // Para instalación, usar el slot específico seleccionado por el usuario
                return instalarComponenteSlot($conn, $componente_actual, $tipo_componente, $tipo_nuevo_componente, $id_nuevo_componente);
                
            case '3': // Retiro
                return retirarComponenteSlot($conn, $componente_actual, $tipo_componente);
                
            default:
                return ['success' => false, 'error' => 'Tipo de cambio no válido'];
        }
        
    } catch (Exception $e) {
        error_log("Error en actualizarSlotsActivo: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para reemplazar componente en slot
function reemplazarComponenteSlot($conn, $id_slot, $tipo_componente, $tipo_nuevo_componente, $id_nuevo_componente) {
    try {
        $tabla_slot = "slot_activo_" . $tipo_componente;
        
        // Limpiar slot actual
        $sqlLimpiar = "UPDATE $tabla_slot SET 
                       id_{$tipo_componente} = NULL, 
                       id_{$tipo_componente}_generico = NULL 
                       WHERE id_slot = ?";
        
        $stmt = sqlsrv_prepare($conn, $sqlLimpiar, [$id_slot]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error limpiando slot");
        }
        
        // Asignar nuevo componente
        if ($tipo_nuevo_componente === 'generico') {
            $sqlNuevo = "UPDATE $tabla_slot SET id_{$tipo_componente}_generico = ? WHERE id_slot = ?";
        } else {
            $sqlNuevo = "UPDATE $tabla_slot SET id_{$tipo_componente} = ? WHERE id_slot = ?";
        }
        
        $stmt = sqlsrv_prepare($conn, $sqlNuevo, [$id_nuevo_componente, $id_slot]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error asignando nuevo componente");
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para instalar componente en slot específico
function instalarComponenteSlot($conn, $id_slot_seleccionado, $tipo_componente, $tipo_nuevo_componente, $id_nuevo_componente) {
    try {
        error_log("=== INSTALAR COMPONENTE EN SLOT ===");
        error_log("Slot seleccionado: $id_slot_seleccionado");
        error_log("Tipo componente: $tipo_componente");
        error_log("Tipo nuevo: $tipo_nuevo_componente");
        error_log("ID nuevo: $id_nuevo_componente");
        
        // Verificar que el slot esté disponible
        $sqlVerificar = "SELECT estado FROM slot_activo WHERE id_slot = ?";
        $stmt = sqlsrv_prepare($conn, $sqlVerificar, [$id_slot_seleccionado]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error verificando estado del slot");
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) {
            throw new Exception("Slot no encontrado");
        }
        
        if ($row['estado'] !== 'disponible') {
            throw new Exception("El slot seleccionado no está disponible");
        }
        
        // PASO 1: Marcar slot como ocupado en slot_activo
        $sqlOcupar = "UPDATE slot_activo SET estado = 'ocupado' WHERE id_slot = ?";
        $stmt = sqlsrv_prepare($conn, $sqlOcupar, [$id_slot_seleccionado]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error marcando slot como ocupado");
        }
        error_log("✅ Slot marcado como ocupado en slot_activo");
        
        // PASO 2: Crear/actualizar registro en la tabla específica del componente
        $tabla_slot = "slot_activo_" . $tipo_componente;
        
        // Verificar si ya existe el registro en la tabla específica
        $sqlExiste = "SELECT COUNT(*) as count FROM $tabla_slot WHERE id_slot = ?";
        $stmt = sqlsrv_prepare($conn, $sqlExiste, [$id_slot_seleccionado]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error verificando existencia en tabla específica");
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $existe = $row['count'] > 0;
        
        if ($existe) {
            // Si existe, hacer UPDATE
            if ($tipo_nuevo_componente === 'generico') {
                $sqlAsignar = "UPDATE $tabla_slot SET 
                               id_{$tipo_componente} = NULL,
                               id_{$tipo_componente}_generico = ? 
                               WHERE id_slot = ?";
            } else {
                $sqlAsignar = "UPDATE $tabla_slot SET 
                               id_{$tipo_componente} = ?,
                               id_{$tipo_componente}_generico = NULL 
                               WHERE id_slot = ?";
            }
            error_log("🔄 Actualizando registro existente en $tabla_slot");
        } else {
            // Si no existe, hacer INSERT
            if ($tipo_nuevo_componente === 'generico') {
                $sqlAsignar = "INSERT INTO $tabla_slot (id_slot, id_{$tipo_componente}_generico) VALUES (?, ?)";
            } else {
                $sqlAsignar = "INSERT INTO $tabla_slot (id_slot, id_{$tipo_componente}) VALUES (?, ?)";
            }
            error_log("🆕 Creando nuevo registro en $tabla_slot");
        }
        
        $stmt = sqlsrv_prepare($conn, $sqlAsignar, [$id_slot_seleccionado, $id_nuevo_componente]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error asignando componente al slot: " . print_r(sqlsrv_errors(), true));
        }
        error_log("✅ Componente asignado correctamente en $tabla_slot");
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("❌ Error en instalarComponenteSlot: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para retirar componente de slot
function retirarComponenteSlot($conn, $id_slot, $tipo_componente) {
    try {
        error_log("=== RETIRAR COMPONENTE DE SLOT ===");
        error_log("Slot: $id_slot");
        error_log("Tipo componente: $tipo_componente");
        
        $tabla_slot = "slot_activo_" . $tipo_componente;
        
        // PASO 1: Eliminar el componente de la tabla específica
        // Verificar si existe el registro antes de eliminarlo
        $sqlVerificar = "SELECT COUNT(*) as count FROM $tabla_slot WHERE id_slot = ?";
        $stmt = sqlsrv_prepare($conn, $sqlVerificar, [$id_slot]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error verificando existencia en tabla específica");
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $existe = $row['count'] > 0;
        
        if ($existe) {
            // Si existe, ELIMINAR el registro completamente de la tabla específica
            $sqlEliminar = "DELETE FROM $tabla_slot WHERE id_slot = ?";
            
            $stmt = sqlsrv_prepare($conn, $sqlEliminar, [$id_slot]);
            if (!$stmt || !sqlsrv_execute($stmt)) {
                throw new Exception("Error eliminando registro de $tabla_slot: " . print_r(sqlsrv_errors(), true));
            }
            error_log("✅ Registro eliminado completamente de $tabla_slot");
        } else {
            error_log("⚠️ No había registro en $tabla_slot para el slot $id_slot");
        }
        
        // PASO 2: Marcar slot como disponible en slot_activo
        $sqlLiberar = "UPDATE slot_activo SET estado = 'disponible' WHERE id_slot = ?";
        $stmt = sqlsrv_prepare($conn, $sqlLiberar, [$id_slot]);
        if (!$stmt || !sqlsrv_execute($stmt)) {
            throw new Exception("Error liberando slot: " . print_r(sqlsrv_errors(), true));
        }
        error_log("✅ Slot marcado como disponible en slot_activo");
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("❌ Error en retirarComponenteSlot: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

?>