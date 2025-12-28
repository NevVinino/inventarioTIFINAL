<?php
// NO incluir verificar_acceso.php aquí - esta página es pública inicialmente
include("../../includes/conexion.php");

$id_activo = $_GET['id'] ?? null;
if (!$id_activo) {
    die("No se proporcionó un ID de activo válido");
}

// Verificar la conexión
if (!$conn) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// Variable para controlar si mostrar información completa
$mostrar_completo = false;
$mensaje_error = '';

// Procesar autenticación de administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_usuario']) && isset($_POST['auth_password'])) {
    $auth_usuario = trim($_POST['auth_usuario']);
    $auth_password = trim($_POST['auth_password']);
    
    if (!empty($auth_usuario) && !empty($auth_password)) {
        // Verificar credenciales de administrador - CORREGIDA para coincidir con login.php
        $sql_auth = "SELECT u.id_usuario, u.username, u.password, r.descripcion AS rol, e.vestado_usuario
                     FROM usuario u
                     JOIN rol r ON u.id_rol = r.id_rol
                     JOIN estado_usuario e ON u.id_estado_usuario = e.id_estado_usuario
                     WHERE u.username = ?";
        
        $stmt_auth = sqlsrv_query($conn, $sql_auth, [$auth_usuario]);
        
        if ($stmt_auth && $row_auth = sqlsrv_fetch_array($stmt_auth, SQLSRV_FETCH_ASSOC)) {
            // Debug: log para verificar qué datos se obtienen
            error_log("Usuario encontrado: " . $row_auth['username'] . ", Rol: " . $row_auth['rol'] . ", Estado: " . $row_auth['vestado_usuario']);
            
            // Verificar que esté habilitado
            if (strtolower($row_auth['vestado_usuario']) === 'habilitado') {
                // Verificar password usando el mismo método que login.php
                if (password_verify($auth_password, $row_auth['password'])) {
                    // Verificar que sea administrador o usuario autorizado
                    if ($row_auth['rol'] === 'admin' || $row_auth['rol'] === 'user') {
                        $mostrar_completo = true;
                        // Establecer datos de sesión temporales para usar en el resto de la página
                        $admin_data = [
                            'id_usuario' => $row_auth['id_usuario'],
                            'username' => $row_auth['username'],
                            'rol' => $row_auth['rol']
                        ];
                        error_log("Acceso autorizado para usuario: " . $row_auth['username'] . " con rol: " . $row_auth['rol']);
                    } else {
                        $mensaje_error = "Solo los usuarios autorizados (admin o user) pueden acceder a la información completa. Su rol actual es: " . $row_auth['rol'];
                        error_log("Usuario con rol insuficiente: " . $row_auth['rol']);
                    }
                } else {
                    $mensaje_error = "Contraseña incorrecta. Verifique sus credenciales.";
                    error_log("Contraseña incorrecta para usuario: " . $auth_usuario);
                }
            } else {
                $mensaje_error = "Su cuenta está deshabilitada. Contacte al administrador del sistema.";
                error_log("Usuario deshabilitado: " . $auth_usuario . " - Estado: " . $row_auth['vestado_usuario']);
            }
        } else {
            $mensaje_error = "Usuario no encontrado o inactivo.";
            error_log("Usuario no encontrado en BD: " . $auth_usuario);
        }
    } else {
        $mensaje_error = "Por favor, complete todos los campos.";
    }
}

// Obtener información básica del activo (siempre visible)
$sql_basico = "
SELECT 
    a.tipo_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN l.nombreEquipo
        WHEN a.tipo_activo = 'PC' THEN pc.nombreEquipo
        WHEN a.tipo_activo = 'Servidor' THEN s.nombreEquipo
        ELSE 'Activo sin nombre'
    END as nombreEquipo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN l.modelo
        WHEN a.tipo_activo = 'PC' THEN pc.modelo
        WHEN a.tipo_activo = 'Servidor' THEN s.modelo
        ELSE 'No especificado'
    END as modelo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN m_l.nombre
        WHEN a.tipo_activo = 'PC' THEN m_pc.nombre
        WHEN a.tipo_activo = 'Servidor' THEN m_s.nombre
        ELSE 'No especificado'
    END as marca
FROM activo a
LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
LEFT JOIN pc pc ON a.id_pc = pc.id_pc
LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
LEFT JOIN marca m_l ON l.id_marca = m_l.id_marca
LEFT JOIN marca m_pc ON pc.id_marca = m_pc.id_marca
LEFT JOIN marca m_s ON s.id_marca = m_s.id_marca
WHERE a.id_activo = ?
";

$stmt_basico = sqlsrv_query($conn, $sql_basico, [$id_activo]);
if (!$stmt_basico || !($activo_basico = sqlsrv_fetch_array($stmt_basico, SQLSRV_FETCH_ASSOC))) {
    die("Activo no encontrado");
}

$activo_completo = null;

// Si está autenticado como administrador, obtener información completa
if ($mostrar_completo) {
    $tipo_activo = $activo_basico['tipo_activo'];
    
    // Usar la misma lógica que detalle_activo.php para obtener información completa
    if ($tipo_activo === 'Laptop') {
        $sql = "
        SELECT 
            a.*,
            l.nombreEquipo,
            l.modelo,
            l.numeroSerial,
            l.mac,
            l.numeroIP,
            l.fechaCompra,
            l.garantia,
            l.precioCompra,
            l.antiguedad,
            l.ordenCompra,
            l.estadoGarantia,
            l.observaciones,
            ea.vestado_activo AS estado,
            m.nombre AS marca,
            emp_activo.nombre AS empresa_activo,
            asi.fecha_asignacion,
            asi.fecha_retorno,
            asi.observaciones AS obs_asignacion,
            p.nombre AS persona_nombre,
            p.apellido AS persona_apellido,
            ar.nombre AS area_nombre,
            emp.nombre AS empresa_persona,
            u2.username AS usuario_asignacion,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN pr.modelo IS NOT NULL THEN 
                            ISNULL(pr.modelo, '') + 
                            CASE WHEN pr.generacion IS NOT NULL THEN ' ' + pr.generacion ELSE '' END
                        WHEN prg.modelo IS NOT NULL THEN 
                            ISNULL(prg.modelo, '') + 
                            CASE WHEN prg.generacion IS NOT NULL THEN ' ' + prg.generacion ELSE '' END
                        ELSE 'Sin procesador'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_procesador sap ON sa.id_slot = sap.id_slot
                LEFT JOIN procesador pr ON sap.id_procesador = pr.id_procesador
                LEFT JOIN procesador_generico prg ON sap.id_procesador_generico = prg.id_procesador_generico
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'Laptop' AND sa.tipo_slot = 'PROCESADOR' AND sa.estado = 'ocupado'
            ) as cpu_desc,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN r.capacidad IS NOT NULL THEN 
                            ISNULL(r.capacidad, '') + 
                            CASE WHEN mr.nombre IS NOT NULL THEN ' - ' + mr.nombre ELSE ' - Sin marca' END
                        WHEN rg.capacidad IS NOT NULL THEN 
                            ISNULL(rg.capacidad, '') + ' (Genérico)'
                        ELSE 'Sin RAM'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_ram sar ON sa.id_slot = sar.id_slot
                LEFT JOIN ram r ON sar.id_ram = r.id_ram
                LEFT JOIN ram_generico rg ON sar.id_ram_generico = rg.id_ram_generico
                LEFT JOIN marca mr ON r.id_marca = mr.id_marca
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'Laptop' AND sa.tipo_slot = 'RAM' AND sa.estado = 'ocupado'
            ) as ram_desc,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN s.capacidad IS NOT NULL THEN 
                            ISNULL(s.capacidad, '') + ' - ' + ISNULL(s.tipo, '') + 
                            CASE WHEN ms.nombre IS NOT NULL THEN ' - ' + ms.nombre ELSE ' - Sin marca' END
                        WHEN sg.capacidad IS NOT NULL THEN 
                            ISNULL(sg.capacidad, '') + 
                            CASE WHEN sg.tipo IS NOT NULL THEN ' - ' + sg.tipo ELSE '' END + ' (Genérico)'
                        ELSE 'Sin almacenamiento'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_almacenamiento saa ON sa.id_slot = saa.id_slot
                LEFT JOIN almacenamiento s ON saa.id_almacenamiento = s.id_almacenamiento
                LEFT JOIN almacenamiento_generico sg ON saa.id_almacenamiento_generico = sg.id_almacenamiento_generico
                LEFT JOIN marca ms ON s.id_marca = ms.id_marca
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'Laptop' AND sa.tipo_slot = 'ALMACENAMIENTO' AND sa.estado = 'ocupado'
            ) as storage_desc
        FROM activo a
        INNER JOIN laptop l ON a.id_laptop = l.id_laptop
        LEFT JOIN estado_activo ea ON l.id_estado_activo = ea.id_estado_activo
        LEFT JOIN marca m ON l.id_marca = m.id_marca
        LEFT JOIN empresa emp_activo ON l.id_empresa = emp_activo.id_empresa
        LEFT JOIN asignacion asi ON a.id_activo = asi.id_activo AND (asi.fecha_retorno IS NULL OR asi.fecha_retorno > GETDATE())
        LEFT JOIN usuario u2 ON asi.id_usuario = u2.id_usuario
        LEFT JOIN persona p ON asi.id_persona = p.id_persona
        LEFT JOIN area ar ON p.id_area = ar.id_area
        LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
        WHERE a.id_activo = ? AND a.tipo_activo = 'Laptop'";
    } elseif ($tipo_activo === 'PC') {
        $sql = "
        SELECT 
            a.*,
            pc.nombreEquipo,
            pc.modelo,
            pc.numeroSerial,
            pc.mac,
            pc.numeroIP,
            pc.fechaCompra,
            pc.garantia,
            pc.precioCompra,
            pc.antiguedad,
            pc.ordenCompra,
            pc.estadoGarantia,
            pc.observaciones,
            ea.vestado_activo AS estado,
            m.nombre AS marca,
            emp_activo.nombre AS empresa_activo,
            asi.fecha_asignacion,
            asi.fecha_retorno,
            asi.observaciones AS obs_asignacion,
            p.nombre AS persona_nombre,
            p.apellido AS persona_apellido,
            ar.nombre AS area_nombre,
            emp.nombre AS empresa_persona,
            u2.username AS usuario_asignacion,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN pr.modelo IS NOT NULL THEN 
                            ISNULL(pr.modelo, '') + 
                            CASE WHEN pr.generacion IS NOT NULL THEN ' ' + pr.generacion ELSE '' END
                        WHEN prg.modelo IS NOT NULL THEN 
                            ISNULL(prg.modelo, '') + 
                            CASE WHEN prg.generacion IS NOT NULL THEN ' ' + prg.generacion ELSE '' END
                        ELSE 'Sin procesador'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_procesador sap ON sa.id_slot = sap.id_slot
                LEFT JOIN procesador pr ON sap.id_procesador = pr.id_procesador
                LEFT JOIN procesador_generico prg ON sap.id_procesador_generico = prg.id_procesador_generico
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'PC' AND sa.tipo_slot = 'PROCESADOR' AND sa.estado = 'ocupado'
            ) as cpu_desc,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN r.capacidad IS NOT NULL THEN 
                            ISNULL(r.capacidad, '') + 
                            CASE WHEN mr.nombre IS NOT NULL THEN ' - ' + mr.nombre ELSE ' - Sin marca' END
                        WHEN rg.capacidad IS NOT NULL THEN 
                            ISNULL(rg.capacidad, '') + ' (Genérico)'
                        ELSE 'Sin RAM'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_ram sar ON sa.id_slot = sar.id_slot
                LEFT JOIN ram r ON sar.id_ram = r.id_ram
                LEFT JOIN ram_generico rg ON sar.id_ram_generico = rg.id_ram_generico
                LEFT JOIN marca mr ON r.id_marca = mr.id_marca
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'PC' AND sa.tipo_slot = 'RAM' AND sa.estado = 'ocupado'
            ) as ram_desc,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN s.capacidad IS NOT NULL THEN 
                            ISNULL(s.capacidad, '') + ' - ' + ISNULL(s.tipo, '') + 
                            CASE WHEN ms.nombre IS NOT NULL THEN ' - ' + ms.nombre ELSE ' - Sin marca' END
                        WHEN sg.capacidad IS NOT NULL THEN 
                            ISNULL(sg.capacidad, '') + 
                            CASE WHEN sg.tipo IS NOT NULL THEN ' - ' + sg.tipo ELSE '' END + ' (Genérico)'
                        ELSE 'Sin almacenamiento'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_almacenamiento saa ON sa.id_slot = saa.id_slot
                LEFT JOIN almacenamiento s ON saa.id_almacenamiento = s.id_almacenamiento
                LEFT JOIN almacenamiento_generico sg ON saa.id_almacenamiento_generico = sg.id_almacenamiento_generico
                LEFT JOIN marca ms ON s.id_marca = ms.id_marca
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'PC' AND sa.tipo_slot = 'ALMACENAMIENTO' AND sa.estado = 'ocupado'
            ) as storage_desc
        FROM activo a
        INNER JOIN pc pc ON a.id_pc = pc.id_pc
        LEFT JOIN estado_activo ea ON pc.id_estado_activo = ea.id_estado_activo
        LEFT JOIN marca m ON pc.id_marca = m.id_marca
        LEFT JOIN empresa emp_activo ON pc.id_empresa = emp_activo.id_empresa
        LEFT JOIN asignacion asi ON a.id_activo = asi.id_activo AND (asi.fecha_retorno IS NULL OR asi.fecha_retorno > GETDATE())
        LEFT JOIN usuario u2 ON asi.id_usuario = u2.id_usuario
        LEFT JOIN persona p ON asi.id_persona = p.id_persona
        LEFT JOIN area ar ON p.id_area = ar.id_area
        LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
        WHERE a.id_activo = ? AND a.tipo_activo = 'PC'";
    } elseif ($tipo_activo === 'Servidor') {
        $sql = "
        SELECT 
            a.*,
            s.nombreEquipo,
            s.modelo,
            s.numeroSerial,
            s.mac,
            s.numeroIP,
            s.fechaCompra,
            s.garantia,
            s.precioCompra,
            s.antiguedad,
            s.ordenCompra,
            s.estadoGarantia,
            s.observaciones,
            ea.vestado_activo AS estado,
            m.nombre AS marca,
            emp_activo.nombre AS empresa_activo,
            asi.fecha_asignacion,
            asi.fecha_retorno,
            asi.observaciones AS obs_asignacion,
            p.nombre AS persona_nombre,
            p.apellido AS persona_apellido,
            ar.nombre AS area_nombre,
            emp.nombre AS empresa_persona,
            u2.username AS usuario_asignacion,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN pr.modelo IS NOT NULL THEN 
                            ISNULL(pr.modelo, '') + 
                            CASE WHEN pr.generacion IS NOT NULL THEN ' ' + pr.generacion ELSE '' END
                        WHEN prg.modelo IS NOT NULL THEN 
                            ISNULL(prg.modelo, '') + 
                            CASE WHEN prg.generacion IS NOT NULL THEN ' ' + prg.generacion ELSE '' END
                        ELSE 'Sin procesador'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_procesador sap ON sa.id_slot = sap.id_slot
                LEFT JOIN procesador pr ON sap.id_procesador = pr.id_procesador
                LEFT JOIN procesador_generico prg ON sap.id_procesador_generico = prg.id_procesador_generico
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'Servidor' AND sa.tipo_slot = 'PROCESADOR' AND sa.estado = 'ocupado'
            ) as cpu_desc,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN r.capacidad IS NOT NULL THEN 
                            ISNULL(r.capacidad, '') + 
                            CASE WHEN mr.nombre IS NOT NULL THEN ' - ' + mr.nombre ELSE ' - Sin marca' END
                        WHEN rg.capacidad IS NOT NULL THEN 
                            ISNULL(rg.capacidad, '') + ' (Genérico)'
                        ELSE 'Sin RAM'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_ram sar ON sa.id_slot = sar.id_slot
                LEFT JOIN ram r ON sar.id_ram = r.id_ram
                LEFT JOIN ram_generico rg ON sar.id_ram_generico = rg.id_ram_generico
                LEFT JOIN marca mr ON r.id_marca = mr.id_marca
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'Servidor' AND sa.tipo_slot = 'RAM' AND sa.estado = 'ocupado'
            ) as ram_desc,
            (
                SELECT STRING_AGG(
                    CASE 
                        WHEN st.capacidad IS NOT NULL THEN 
                            ISNULL(st.capacidad, '') + ' - ' + ISNULL(st.tipo, '') + 
                            CASE WHEN ms.nombre IS NOT NULL THEN ' - ' + ms.nombre ELSE ' - Sin marca' END
                        WHEN stg.capacidad IS NOT NULL THEN 
                            ISNULL(stg.capacidad, '') + 
                            CASE WHEN stg.tipo IS NOT NULL THEN ' - ' + stg.tipo ELSE '' END + ' (Genérico)'
                        ELSE 'Sin almacenamiento'
                    END, ', '
                )
                FROM slot_activo sa
                LEFT JOIN slot_activo_almacenamiento saa ON sa.id_slot = saa.id_slot
                LEFT JOIN almacenamiento st ON saa.id_almacenamiento = st.id_almacenamiento
                LEFT JOIN almacenamiento_generico stg ON saa.id_almacenamiento_generico = stg.id_almacenamiento_generico
                LEFT JOIN marca ms ON st.id_marca = ms.id_marca
                WHERE sa.id_activo = a.id_activo AND sa.tipo_activo = 'Servidor' AND sa.tipo_slot = 'ALMACENAMIENTO' AND sa.estado = 'ocupado'
            ) as storage_desc
        FROM activo a
        INNER JOIN servidor s ON a.id_servidor = s.id_servidor
        LEFT JOIN estado_activo ea ON s.id_estado_activo = ea.id_estado_activo
        LEFT JOIN marca m ON s.id_marca = m.id_marca
        LEFT JOIN empresa emp_activo ON s.id_empresa = emp_activo.id_empresa
        LEFT JOIN asignacion asi ON a.id_activo = asi.id_activo AND (asi.fecha_retorno IS NULL OR asi.fecha_retorno > GETDATE())
        LEFT JOIN usuario u2 ON asi.id_usuario = u2.id_usuario
        LEFT JOIN persona p ON asi.id_persona = p.id_persona
        LEFT JOIN area ar ON p.id_area = ar.id_area
        LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
        WHERE a.id_activo = ? AND a.tipo_activo = 'Servidor'";
    } else {
        die("Tipo de activo no soportado: " . htmlspecialchars($tipo_activo));
    }

    $stmt = sqlsrv_query($conn, $sql, [$id_activo]);
    if ($stmt) {
        $activo_completo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
    
    // Obtener reparaciones y cambios de hardware si está autenticado
    if ($activo_completo) {
        // Reparaciones
        $sql_reparaciones = "
            SELECT r.*, 
                   CASE 
                       WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                       WHEN a.id_pc IS NOT NULL THEN ISNULL(pc.nombreEquipo, 'Sin nombre') 
                       WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                       ELSE 'Activo sin tipo'
                   END as nombre_equipo,
                   ISNULL(lr.nombre_lugar, 'Sin lugar') as nombre_lugar,
                   ISNULL(er.nombre_estado, 'Sin estado') as nombre_estado
            FROM reparacion r
            INNER JOIN activo a ON r.id_activo = a.id_activo
            LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
            LEFT JOIN pc pc ON a.id_pc = pc.id_pc
            LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
            LEFT JOIN lugar_reparacion lr ON r.id_lugar_reparacion = lr.id_lugar
            LEFT JOIN estado_reparacion er ON r.id_estado_reparacion = er.id_estado_reparacion
            WHERE r.id_activo = ?
            ORDER BY r.fecha DESC
        ";
        $stmt_reparaciones = sqlsrv_query($conn, $sql_reparaciones, [$id_activo]);
        $reparaciones = [];
        while ($row = sqlsrv_fetch_array($stmt_reparaciones, SQLSRV_FETCH_ASSOC)) {
            if ($row['fecha'] instanceof DateTime) {
                $row['fecha'] = $row['fecha']->format('d/m/Y');
            }
            $reparaciones[] = $row;
        }

        // Cambios de hardware
        $sql_cambios = "
            SELECT 
                ch.*, 
                tc.nombre_tipo_cambio,
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
                CASE 
                    WHEN ch.id_procesador IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(ISNULL(m.nombre + ' ', ''), p.modelo) FROM procesador p LEFT JOIN marca m ON p.id_marca = m.id_marca WHERE p.id_procesador = ch.id_procesador), 'Procesador detallado ID: ' + CAST(ch.id_procesador as VARCHAR))
                    WHEN ch.id_ram IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(r.capacidad, ISNULL(' ' + r.tipo, '')) FROM RAM r WHERE r.id_ram = ch.id_ram), 'RAM detallada ID: ' + CAST(ch.id_ram as VARCHAR))
                    WHEN ch.id_almacenamiento IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(a.capacidad, ISNULL(' ' + a.tipo, '')) FROM almacenamiento a WHERE a.id_almacenamiento = ch.id_almacenamiento), 'Almacenamiento detallado ID: ' + CAST(ch.id_almacenamiento as VARCHAR))
                    WHEN ch.id_tarjeta_video IS NOT NULL THEN 
                        ISNULL((SELECT CONCAT(tv.modelo, ISNULL(' ' + tv.memoria, '')) FROM tarjeta_video tv WHERE tv.id_tarjeta_video = ch.id_tarjeta_video), 'Tarjeta Video detallada ID: ' + CAST(ch.id_tarjeta_video as VARCHAR))
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
                FORMAT(ch.fecha, 'dd/MM/yyyy') as fecha_formateada
            FROM cambio_hardware ch
            LEFT JOIN tipo_cambio tc ON ch.id_tipo_cambio = tc.id_tipo_cambio
            WHERE ch.id_activo = ?
            ORDER BY ch.fecha DESC
        ";
        $stmt_cambios = sqlsrv_query($conn, $sql_cambios, [$id_activo]);
        $cambios_hardware = [];
        while ($row = sqlsrv_fetch_array($stmt_cambios, SQLSRV_FETCH_ASSOC)) {
            $cambios_hardware[] = $row;
        }

        // NUEVO: Historial completo de asignaciones (Trazabilidad)
        $sql_trazabilidad = "
            SELECT 
                asig.id_asignacion,
                asig.fecha_asignacion,
                asig.fecha_retorno,
                asig.observaciones,
                CONCAT(p.nombre, ' ', p.apellido) as persona_nombre,
                p.correo as email,
                p.celular as telefono,
                l.localidad_nombre,
                ar.nombre as area_nombre,
                e.nombre as empresa_nombre,
                sp.situacion as situacion_personal,
                tp.nombre_tipo_persona,
                CONCAT(jefe.nombre, ' ', jefe.apellido) as jefe_inmediato,
                u.username as usuario_asigno,
                -- Calcular duración de la asignación
                CASE 
                    WHEN asig.fecha_retorno IS NOT NULL THEN 
                        DATEDIFF(day, asig.fecha_asignacion, asig.fecha_retorno)
                    ELSE 
                        DATEDIFF(day, asig.fecha_asignacion, GETDATE())
                END as dias_asignacion,
                -- Estado de la asignación
                CASE 
                    WHEN asig.fecha_retorno IS NOT NULL THEN 'Retornado'
                    ELSE 'Activo'
                END as estado_asignacion
            FROM asignacion asig
            INNER JOIN persona p ON asig.id_persona = p.id_persona
            LEFT JOIN localidad l ON p.id_localidad = l.id_localidad
            LEFT JOIN area ar ON p.id_area = ar.id_area
            LEFT JOIN empresa e ON p.id_empresa = e.id_empresa
            LEFT JOIN situacion_personal sp ON p.id_situacion_personal = sp.id_situacion
            LEFT JOIN tipo_persona tp ON p.id_tipo_persona = tp.id_tipo_persona
            LEFT JOIN persona jefe ON p.jefe_inmediato = jefe.id_persona
            LEFT JOIN usuario u ON asig.id_usuario = u.id_usuario
            WHERE asig.id_activo = ?
            ORDER BY asig.fecha_asignacion DESC
        ";
        $stmt_trazabilidad = sqlsrv_query($conn, $sql_trazabilidad, [$id_activo]);
        $historial_asignaciones = [];
        while ($row = sqlsrv_fetch_array($stmt_trazabilidad, SQLSRV_FETCH_ASSOC)) {
            // Formatear fechas
            if ($row['fecha_asignacion'] instanceof DateTime) {
                $row['fecha_asignacion_formato'] = $row['fecha_asignacion']->format('d/m/Y');
            } else {
                $row['fecha_asignacion_formato'] = $row['fecha_asignacion'] ? date('d/m/Y', strtotime($row['fecha_asignacion'])) : 'No especificada';
            }
            
            if ($row['fecha_retorno'] instanceof DateTime) {
                $row['fecha_retorno_formato'] = $row['fecha_retorno']->format('d/m/Y');
            } else {
                $row['fecha_retorno_formato'] = $row['fecha_retorno'] ? date('d/m/Y', strtotime($row['fecha_retorno'])) : null;
            }
            
            $historial_asignaciones[] = $row;
        }
    }
}

// Función para calcular la antigüedad en formato legible
function calcularAntiguedadLegible($dias) {
    if (!$dias) return 'No especificado';
    
    $años = floor($dias / 365);
    $meses = floor(($dias % 365) / 30);
    $diasRestantes = $dias % 30;
    
    $partes = [];
    if ($años > 0) {
        $partes[] = $años . ' año' . ($años > 1 ? 's' : '');
    }
    if ($meses > 0) {
        $partes[] = $meses . ' mes' . ($meses > 1 ? 'es' : '');
    }
    if ($diasRestantes > 0 || empty($partes)) {
        $partes[] = $diasRestantes . ' día' . ($diasRestantes != 1 ? 's' : '');
    }
    
    return implode(', ', $partes);
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Información del Activo informático - ID <?= htmlspecialchars($id_activo) ?></title>
        <link rel="stylesheet" href="../../../css/user/vista_user.css">
        <style>
            .auth-container {
                background: #f8f9fa;
                border: 2px solid #007bff;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
            }
            .auth-form {
                display: inline-block;
                text-align: left;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .auth-form input {
                width: 100%;
                padding: 10px;
                margin: 8px 0;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .auth-form button {
                width: 100%;
                background: #007bff;
                color: white;
                padding: 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }
            .auth-form button:hover {
                background: #0056b3;
            }
            .error-message {
                color: #dc3545;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                padding: 10px;
                border-radius: 4px;
                margin: 10px 0;
            }
            .info-basica {
                background: #e7f3ff;
                border: 1px solid #b3d9ff;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .acceso-restringido {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: center;
            }
            /* NUEVO: Estilos para la trazabilidad */
            .trazabilidad-item {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                margin-bottom: 15px;
                padding: 15px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .trazabilidad-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #e9ecef;
            }
            .trazabilidad-persona {
                font-weight: bold;
                color: #007bff;
                font-size: 16px;
            }
            .trazabilidad-estado {
                padding: 4px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .trazabilidad-estado.activo {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .trazabilidad-estado.retornado {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .trazabilidad-fechas {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 15px;
                margin-bottom: 15px;
            }
            .fecha-item {
                text-align: center;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 6px;
            }
            .fecha-label {
                font-size: 12px;
                color: #6c757d;
                text-transform: uppercase;
                margin-bottom: 5px;
            }
            .fecha-valor {
                font-weight: bold;
                color: #495057;
            }
            .trazabilidad-detalles {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-top: 15px;
            }
            .detalle-seccion {
                background: #f8f9fa;
                padding: 12px;
                border-radius: 6px;
            }
            .detalle-seccion h5 {
                margin: 0 0 10px 0;
                color: #495057;
                font-size: 14px;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 5px;
            }
            .detalle-seccion p {
                margin: 5px 0;
                font-size: 13px;
                color: #6c757d;
            }
            .detalle-seccion p strong {
                color: #495057;
            }
            .observaciones-trazabilidad {
                grid-column: 1 / -1;
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 6px;
                padding: 12px;
                margin-top: 10px;
            }
            .observaciones-trazabilidad h5 {
                margin: 0 0 8px 0;
                color: #856404;
            }
            .observaciones-trazabilidad p {
                margin: 0;
                color: #856404;
                font-style: italic;
            }
            .sin-asignaciones {
                text-align: center;
                padding: 40px;
                color: #6c757d;
                background: #f8f9fa;
                border-radius: 8px;
                border: 2px dashed #dee2e6;
            }
            .sin-asignaciones img {
                width: 64px;
                height: 64px;
                opacity: 0.5;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <header>
            <div class="usuario-info">
                <h1>Información del Activo informático
                    <?php if ($mostrar_completo): ?>
                        <span class="rol"><?= htmlspecialchars(ucfirst($admin_data['rol'])) ?>: <?= htmlspecialchars($admin_data['username']) ?></span>
                    <?php else: ?>
                        <span class="rol">Vista Pública</span>
                    <?php endif; ?>
                </h1>
                <div class="id-activo">ID Activo: <?= htmlspecialchars($id_activo) ?></div>
            </div>
        </header>

        <div class="container">
            <div class="header">
                <h1 class="title">Activo informático del Sistema de Inventario TI</h1>
            </div>

            <!-- Información básica (siempre visible) -->
            <div class="info-basica">
                <h2>Información Básica del Equipo</h2>
                <div class="detalle">
                    <span class="label">Nombre del Equipo:</span>
                    <span class="value"><?= htmlspecialchars($activo_basico['nombreEquipo'] ?? 'No especificado') ?></span>
                </div>
                <div class="detalle">
                    <span class="label">Tipo:</span>
                    <span class="value"><?= htmlspecialchars($activo_basico['tipo_activo']) ?></span>
                </div>
                <div class="detalle">
                    <span class="label">Marca/Modelo:</span>
                    <span class="value">
                        <?= htmlspecialchars($activo_basico['marca'] ?? 'No especificado') ?> / 
                        <?= htmlspecialchars($activo_basico['modelo'] ?? 'No especificado') ?>
                    </span>
                </div>
            </div>

            <?php if (!$mostrar_completo): ?>
                <!-- Formulario de autenticación -->
                <div class="acceso-restringido">
                    <h3>⚠️ Información Detallada Restringida</h3>
                    <p>Para ver la información completa del activo, necesita autenticarse como usuario autorizado (Admin o User).</p>
                </div>

                <div class="auth-container">
                    <h3>Acceso de Usuario Autorizado</h3>
                    <p>Ingrese sus credenciales para ver toda la información:</p>
                    
                    <?php if ($mensaje_error): ?>
                        <div class="error-message">
                            <?= htmlspecialchars($mensaje_error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form">
                        <div>
                            <label for="auth_usuario">Usuario:</label>
                            <input type="text" id="auth_usuario" name="auth_usuario" required 
                                   placeholder="Ingrese su usuario"
                                   value="<?= htmlspecialchars($_POST['auth_usuario'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="auth_password">Contraseña:</label>
                            <input type="password" id="auth_password" name="auth_password" required 
                                   placeholder="Ingrese su contraseña">
                        </div>
                        <button type="submit">🔓 Ver Información Completa</button>
                    </form>
                </div>

            <?php else: ?>
                <!-- Información completa (solo visible después de autenticación) -->
                <?php if ($activo_completo): ?>
                    <?php $antiguedadLegible = calcularAntiguedadLegible($activo_completo['antiguedad']); ?>
                    
                    <div class="section-title">Detalles de Asignación</div>

                    <div class="detalle">
                        <span class="label">Asistente TI registro asignación:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['usuario_asignacion'] ?? 'No asignado') ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Persona Asignada:</span>
                        <span class="value">
                            <?= htmlspecialchars($activo_completo['persona_nombre'] ?? '') ?> 
                            <?= htmlspecialchars($activo_completo['persona_apellido'] ?? '') ?>
                        </span>
                    </div>

                    <div class="detalle">
                        <span class="label">Área de la Persona:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['area_nombre'] ?? 'No especificado') ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Empresa de la Persona:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['empresa_persona'] ?? 'No especificado') ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Fecha de Asignación:</span>
                        <span class="value"><?= $activo_completo['fecha_asignacion'] ? $activo_completo['fecha_asignacion']->format('d/m/Y') : 'No asignado' ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Fecha de Retorno:</span>
                        <span class="value"><?= $activo_completo['fecha_retorno'] ? $activo_completo['fecha_retorno']->format('d/m/Y') : 'Sin retorno programado' ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Observaciones de Asignación:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['obs_asignacion'] ?? 'Sin observaciones') ?></span>
                    </div>

                    <div class="section-title">Información del Activo informático</div>
                    
                    <div class="detalle">
                        <span class="label">Empresa del Activo:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['empresa_activo'] ?? 'No especificado') ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Estado:</span>
                        <span class="value estado-wrapper">
                            <span class="estado estado-<?= strtolower($activo_completo['estado']) ?>">
                                <?= htmlspecialchars($activo_completo['estado']) ?>
                            </span>
                        </span>
                    </div>

                    <div class="detalle">
                        <span class="label">Número Serial:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['numeroSerial'] ?? 'No especificado') ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Especificaciones:</span>
                        <div class="value">
                            <div>CPU: <?= htmlspecialchars($activo_completo['cpu_desc'] ?? 'No especificado') ?></div>
                            <div>RAM: <?= htmlspecialchars($activo_completo['ram_desc'] ?? 'No especificado') ?></div>
                            <div>Almacenamiento: <?= htmlspecialchars($activo_completo['storage_desc'] ?? 'No especificado') ?></div>
                        </div>
                    </div>

                    <div class="detalle">
                        <span class="label">MAC:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['mac'] ?? 'No especificado') ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">IP:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['numeroIP'] ?? 'No especificado') ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Observaciones del activo:</span>
                        <span class="value observaciones"><?= nl2br(htmlspecialchars($activo_completo['observaciones'] ?? 'Sin observaciones')) ?></span>
                    </div>

                    <div class="section-title">Detalles de Compra</div>
                    <div class="detalle">
                        <span class="label">Fecha de Compra:</span>
                        <span class="value"><?= $activo_completo['fechaCompra'] ? $activo_completo['fechaCompra']->format('d/m/Y') : 'No especificado' ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Precio de Compra:</span>
                        <span class="value">$<?= number_format($activo_completo['precioCompra'] ?? 0, 2) ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Orden de Compra:</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['ordenCompra'] ?? 'No especificado') ?></span>
                    </div>

                    <div class="section-title">Garantía</div>
                    <div class="detalle">
                        <span class="label">Fecha de Garantía:</span>
                        <span class="value"><?= $activo_completo['garantia'] ? $activo_completo['garantia']->format('d/m/Y') : 'No especificado' ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Estado de Garantía:</span>
                        <span class="value garantia-<?= strtolower($activo_completo['estadoGarantia'] ?? '') ?>">
                            <?= htmlspecialchars($activo_completo['estadoGarantia'] ?? 'No especificado') ?>
                        </span>
                    </div>

                    <div class="detalle">
                        <span class="label">Antigüedad (días):</span>
                        <span class="value"><?= htmlspecialchars($activo_completo['antiguedad'] ?? 'No especificado') ?></span>
                    </div>

                    <div class="detalle">
                        <span class="label">Antigüedad:</span>
                        <span class="value antiguedad-legible"><?= $antiguedadLegible ?></span>
                    </div>

                    <!-- NUEVA SECCIÓN: Trazabilidad del Activo -->
                    <div class="section-title">📋 Trazabilidad del Activo - Historial de Asignaciones</div>
                    <?php if (isset($historial_asignaciones) && count($historial_asignaciones) > 0): ?>
                        <div style="margin-bottom: 20px; padding: 15px; background: #e7f3ff; border-radius: 8px; border: 1px solid #b3d9ff;">
                            <p style="margin: 0; color: #0056b3; font-weight: bold;">
                                📊 Total de asignaciones registradas: <?= count($historial_asignaciones) ?>
                            </p>
                        </div>
                        
                        <?php foreach ($historial_asignaciones as $index => $asignacion): ?>
                            <div class="trazabilidad-item">
                                <div class="trazabilidad-header">
                                    <div>
                                        <div class="trazabilidad-persona">
                                            👤 <?= htmlspecialchars($asignacion['persona_nombre']) ?>
                                        </div>
                                        <small style="color: #6c757d;">Asignación #<?= $index + 1 ?></small>
                                    </div>
                                    <div class="trazabilidad-estado <?= strtolower($asignacion['estado_asignacion']) ?>">
                                        <?= $asignacion['estado_asignacion'] ?>
                                    </div>
                                </div>
                                
                                <div class="trazabilidad-fechas">
                                    <div class="fecha-item">
                                        <div class="fecha-label">📅 Fecha Asignación</div>
                                        <div class="fecha-valor"><?= $asignacion['fecha_asignacion_formato'] ?></div>
                                    </div>
                                    <div class="fecha-item">
                                        <div class="fecha-label">📅 Fecha Retorno</div>
                                        <div class="fecha-valor">
                                            <?= $asignacion['fecha_retorno_formato'] ?: '<span style="color: #28a745; font-weight: bold;">En uso</span>' ?>
                                        </div>
                                    </div>
                                    <div class="fecha-item">
                                        <div class="fecha-label">⏱️ Duración</div>
                                        <div class="fecha-valor">
                                            <?= $asignacion['dias_asignacion'] ?> días
                                            <?php if ($asignacion['estado_asignacion'] === 'Activo'): ?>
                                                <br><small style="color: #28a745;">(En curso)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="trazabilidad-detalles">
                                    <div class="detalle-seccion">
                                        <h5>👤 Datos de la Persona</h5>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($asignacion['email'] ?: 'No registrado') ?></p>
                                        <p><strong>Teléfono:</strong> <?= htmlspecialchars($asignacion['telefono'] ?: 'No registrado') ?></p>
                                        <p><strong>Localidad:</strong> <?= htmlspecialchars($asignacion['localidad_nombre'] ?: 'No registrada') ?></p>
                                        <p><strong>Área:</strong> <?= htmlspecialchars($asignacion['area_nombre'] ?: 'No registrada') ?></p>
                                    </div>
                                    
                                    <div class="detalle-seccion">
                                        <h5>🏢 Información Organizacional</h5>
                                        <p><strong>Empresa:</strong> <?= htmlspecialchars($asignacion['empresa_nombre'] ?: 'No registrada') ?></p>
                                        <p><strong>Situación:</strong> <?= htmlspecialchars($asignacion['situacion_personal'] ?: 'No registrada') ?></p>
                                        <p><strong>Tipo:</strong> <?= htmlspecialchars($asignacion['nombre_tipo_persona'] ?: 'No registrado') ?></p>
                                        <p><strong>Jefe Inmediato:</strong> <?= htmlspecialchars($asignacion['jefe_inmediato'] ?: 'No registrado') ?></p>
                                    </div>
                                    
                                    <div class="detalle-seccion">
                                        <h5>⚙️ Información de Gestión</h5>
                                        <p><strong>Usuario que asignó:</strong> <?= htmlspecialchars($asignacion['usuario_asigno'] ?: 'No registrado') ?></p>
                                        <p><strong>ID Asignación:</strong> #<?= htmlspecialchars($asignacion['id_asignacion']) ?></p>
                                    </div>
                                    
                                    <?php if (!empty($asignacion['observaciones'])): ?>
                                        <div class="observaciones-trazabilidad">
                                            <h5>📝 Observaciones</h5>
                                            <p><?= nl2br(htmlspecialchars($asignacion['observaciones'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Resumen estadístico -->
                        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                            <h4 style="margin: 0 0 10px 0; color: #495057;">📈 Resumen de Trazabilidad</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div style="text-align: center; padding: 10px; background: white; border-radius: 6px;">
                                    <strong style="color: #007bff;">Total Asignaciones</strong><br>
                                    <span style="font-size: 24px; font-weight: bold; color: #007bff;"><?= count($historial_asignaciones) ?></span>
                                </div>
                                <div style="text-align: center; padding: 10px; background: white; border-radius: 6px;">
                                    <strong style="color: #28a745;">Asignaciones Activas</strong><br>
                                    <span style="font-size: 24px; font-weight: bold; color: #28a745;">
                                        <?= count(array_filter($historial_asignaciones, function($a) { return $a['estado_asignacion'] === 'Activo'; })) ?>
                                    </span>
                                </div>
                                <div style="text-align: center; padding: 10px; background: white; border-radius: 6px;">
                                    <strong style="color: #dc3545;">Asignaciones Retornadas</strong><br>
                                    <span style="font-size: 24px; font-weight: bold; color: #dc3545;">
                                        <?= count(array_filter($historial_asignaciones, function($a) { return $a['estado_asignacion'] === 'Retornado'; })) ?>
                                    </span>
                                </div>
                                <div style="text-align: center; padding: 10px; background: white; border-radius: 6px;">
                                    <strong style="color: #6f42c1;">Tiempo Total de Uso</strong><br>
                                    <span style="font-size: 20px; font-weight: bold; color: #6f42c1;">
                                        <?= array_sum(array_column($historial_asignaciones, 'dias_asignacion')) ?> días
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="detalle">No hay asignaciones registradas para este activo.</div>
                    <?php endif; ?>

                    <!-- Sección de Reparaciones -->
                    <div class="section-title">Historial de Reparaciones</div>
                    <?php if (isset($reparaciones) && count($reparaciones) > 0): ?>
                        <table class="tabla-reparaciones">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Lugar</th>
                                    <th>Estado</th>
                                    <th>Descripción</th>
                                    <th>Costo</th>
                                    <th>Días Inactividad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reparaciones as $rep): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rep['fecha']) ?></td>
                                        <td><?= htmlspecialchars($rep['nombre_lugar']) ?></td>
                                        <td><?= htmlspecialchars($rep['nombre_estado']) ?></td>
                                        <td><?= htmlspecialchars($rep['descripcion'] ?? '') ?></td>
                                        <td class="celda-costo"><?= $rep['costo'] !== null ? '$ ' . number_format($rep['costo'], 2) : '-' ?></td>
                                        <td><?= $rep['tiempo_inactividad'] !== null ? (int)$rep['tiempo_inactividad'] : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="detalle">No hay reparaciones registradas para este activo.</div>
                    <?php endif; ?>

                    <!-- Sección de Cambios de Hardware -->
                    <div class="section-title">Cambios de Hardware</div>
                    <?php if (isset($cambios_hardware) && count($cambios_hardware) > 0): ?>
                        <table class="tabla-cambios-hardware">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo de Cambio</th>
                                    <th>Tipo de Componente</th>
                                    <th>Componente Nuevo</th>
                                    <th>Componente Retirado</th>
                                    <th>Costo</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cambios_hardware as $ch): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ch['fecha_formateada'] ?? $ch['fecha']) ?></td>
                                        <td><?= htmlspecialchars($ch['nombre_tipo_cambio'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($ch['tipo_componente'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($ch['componente_nuevo'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($ch['componente_retirado'] ?? '-') ?></td>
                                        <td class="celda-costo"><?= $ch['costo'] !== null ? '$ ' . number_format($ch['costo'], 2) : '-' ?></td>
                                        <td><?= htmlspecialchars($ch['motivo'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="detalle">No hay cambios de hardware registrados para este activo.</div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 30px; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px;">
                        <h3>✅ Acceso Autorizado</h3>
                        <p>Información completa mostrada para: <strong><?= htmlspecialchars($admin_data['username']) ?></strong> (Rol: <strong><?= htmlspecialchars(ucfirst($admin_data['rol'])) ?></strong>)</p>
                        <form method="GET" style="display: inline;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($id_activo) ?>">
                            <button type="submit" style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                                🔒 Cerrar Sesión
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="error-message">
                        Error al cargar la información completa del activo.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </body>
</html>
