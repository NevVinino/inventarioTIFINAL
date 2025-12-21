<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Consultar datos relacionados y verificar errores
$tipo = sqlsrv_query($conn, "SELECT * FROM tipo_persona");
if ($tipo === false) {
    die("Error al consultar tipos de persona: " . print_r(sqlsrv_errors(), true));
}

$situaciones = sqlsrv_query($conn, "SELECT * FROM situacion_personal");
if ($situaciones === false) {
    die("Error al consultar situaciones: " . print_r(sqlsrv_errors(), true));
}

$localidades = sqlsrv_query($conn, "SELECT * FROM localidad");
if ($localidades === false) {
    die("Error al consultar localidades: " . print_r(sqlsrv_errors(), true));
}

$areas = sqlsrv_query($conn, "SELECT * FROM area");
if ($areas === false) {
    die("Error al consultar áreas: " . print_r(sqlsrv_errors(), true));
}

$empresas = sqlsrv_query($conn, "SELECT * FROM empresa");
if ($empresas === false) {
    die("Error al consultar empresas: " . print_r(sqlsrv_errors(), true));
}

// Consulta de personas
$sql = "SELECT 
            p.*, 
            CONCAT(j.nombre, ' ', j.apellido) AS jefe_nombre,
            tipo_j.nombre_tipo_persona AS tipo_jefe_nombre,
            t.nombre_tipo_persona AS tipo,
            s.situacion AS situacion,
            l.localidad_nombre AS localidad,
            a.nombre AS area_nombre,
            e.nombre AS empresa_nombre
        FROM persona p
        LEFT JOIN persona j ON p.jefe_inmediato = j.id_persona
        LEFT JOIN tipo_persona tipo_j ON j.id_tipo_persona = tipo_j.id_tipo_persona
        JOIN tipo_persona t ON p.id_tipo_persona = t.id_tipo_persona
        JOIN situacion_personal s ON p.id_situacion_personal = s.id_situacion
        JOIN localidad l ON p.id_localidad = l.id_localidad
        JOIN area a ON p.id_area = a.id_area
        JOIN empresa e ON p.id_empresa = e.id_empresa";

$personas = sqlsrv_query($conn, $sql);
if ($personas === false) {
    die("Error al consultar personas: " . print_r(sqlsrv_errors(), true));
}

// Obtener lista de jefes (tipo = Gerente o Jefe Area)
$jefes = sqlsrv_query($conn, "
    SELECT p.id_persona, p.nombre + ' ' + p.apellido AS nombre_completo,
    t.nombre_tipo_persona AS nombre_tipo
    FROM persona p
    JOIN tipo_persona t ON p.id_tipo_persona = t.id_tipo_persona
    WHERE t.nombre_tipo_persona IN ('Gerente', 'Jefe Area')
");
if ($jefes === false) {
    die("Error al consultar jefes: " . print_r(sqlsrv_errors(), true));
}

// Reinicializar recursos antes de usarlos en el formulario
sqlsrv_free_stmt($tipo);
sqlsrv_free_stmt($situaciones);
sqlsrv_free_stmt($localidades);
sqlsrv_free_stmt($areas);
sqlsrv_free_stmt($empresas);

$tipo = sqlsrv_query($conn, "SELECT * FROM tipo_persona");
$situaciones = sqlsrv_query($conn, "SELECT * FROM situacion_personal");
$localidades = sqlsrv_query($conn, "SELECT * FROM localidad");
$areas = sqlsrv_query($conn, "SELECT * FROM area");
$empresas = sqlsrv_query($conn, "SELECT * FROM empresa");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Personas</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
</head>
<body>
    <!-- Incluir Header -->
    <?php include('includes/header.php'); ?>

    <!-- Incluir Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content" id="mainContent">

        <a href="vista_admin.php" class="back-button">
            <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
        </a>

        <div class="main-container">
            <div class="top-bar">
                <h2>Personas</h2>
                <input type="text" id="buscador" placeholder="Buscar persona">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaPersonas">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Correo</th>
                        <th>Celular</th>
                        <th>Jefe inmediato</th>
                        <th>Tipo</th>
                        <th>Situación</th>
                        <th>Localidad</th>
                        <th>Área</th>
                        <th>Empresa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php if ($personas !== false) { while ($p = sqlsrv_fetch_array($personas, SQLSRV_FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['apellido']) ?></td>
                        <td><?= htmlspecialchars($p['correo']) ?></td>
                        <td><?= htmlspecialchars($p['celular']) ?></td>
                        <td>
                            <?php
                            if (is_null($p['jefe_inmediato'])) {
                                if (strtolower($p['tipo']) === 'gerente' || strtolower($p['tipo']) === 'Gerente') {
                                    echo "Es gerente";
                                } else {
                                    echo "- Sin jefe -";
                                }
                            } else {
                                echo htmlspecialchars($p['jefe_nombre']) . " (" . htmlspecialchars($p['tipo_jefe_nombre']) . ")";
                            }
                            ?>
                        </td>

                        <td><?= htmlspecialchars($p['tipo']) ?></td>
                        <td><?= htmlspecialchars($p['situacion']) ?></td>
                        <td><?= htmlspecialchars($p['localidad']) ?></td>
                        <td><?= htmlspecialchars($p['area_nombre']) ?></td>
                        <td><?= htmlspecialchars($p['empresa_nombre']) ?></td>
                        <td>
                            <div class="acciones">
                                <button class="btn-icon btn-editar" 
                                        data-id="<?= $p['id_persona'] ?>"
                                        data-nombre="<?= $p['nombre'] ?>"
                                        data-apellido="<?= $p['apellido'] ?>"
                                        data-correo="<?= $p['correo'] ?>"
                                        data-celular="<?= $p['celular'] ?>"
                                        data-jefe="<?= $p['jefe_inmediato'] ?>"
                                        data-tipo="<?= $p['id_tipo_persona'] ?>"
                                        data-situacion="<?= $p['id_situacion_personal'] ?>"
                                        data-localidad="<?= $p['id_localidad'] ?>"
                                        data-area="<?= $p['id_area'] ?>"
                                        data-empresa="<?= $p['id_empresa'] ?>">
                                    <img src="../../img/editar.png" alt="Editar">
                                </button>
                                <form method="POST" action="../controllers/procesar_persona.php" onsubmit="return confirm('¿Eliminar esta persona?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_persona" value="<?= $p['id_persona'] ?>">
                                    <button type="submit" class="btn-icon">
                                        <img src="../../img/eliminar.png" alt="Eliminar">
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>

    </main>

<!-- Modal -->
<div id="modalPersona" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modal-title">Registrar persona</h2>
        <form method="POST" action="../controllers/procesar_persona.php" id="formPersona">
            <input type="hidden" name="accion" id="accion" value="crear">
            <input type="hidden" name="id_persona" id="id_persona">

            <label>Nombre:</label>
            <input type="text" name="nombre" id="nombre" required>

            <label>Apellido:</label>
            <input type="text" name="apellido" id="apellido" required>

            <label>Correo:</label>
            <input type="email" name="correo" id="correo" required>

            <label>Celular:</label>
            <input type="text" name="celular" id="celular">

            <label>Jefe Inmediato:</label>
            <select name="jefe_inmediato" id="jefe_inmediato">
                <option value="">-- Sin jefe --</option>
                <?php sqlsrv_execute($jefes); while ($j = sqlsrv_fetch_array($jefes, SQLSRV_FETCH_ASSOC)) { 
                    $tipo_jefe = isset($j['nombre_tipo']) ? strtolower($j['nombre_tipo']) : '';
                ?>
                    <option value="<?= $j['id_persona'] ?>" data-tipo="<?= $tipo_jefe ?>">
                        <?= $j['nombre_completo'] ?> (<?= $j['nombre_tipo'] ?>)
                    </option>
                <?php } ?>
            </select>

            <label>Tipo:</label>
            <select name="id_tipo_persona" id="id_tipo_persona" required>
                <?php if ($tipo !== false) { while ($s = sqlsrv_fetch_array($tipo, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $s['id_tipo_persona'] ?>"><?= $s['nombre_tipo_persona'] ?></option>
                <?php } } ?>
            </select>

            <label>Situación Personal:</label>
            <select name="id_situacion_personal" id="id_situacion_personal" required>
                <?php if ($situaciones !== false) { while ($s = sqlsrv_fetch_array($situaciones, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $s['id_situacion'] ?>"><?= $s['situacion'] ?></option>
                <?php } } ?>
            </select>

            <label>Localidad:</label>
            <select name="id_localidad" id="id_localidad" required>
                <?php if ($localidades !== false) { while ($l = sqlsrv_fetch_array($localidades, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $l['id_localidad'] ?>"><?= $l['localidad_nombre'] ?></option>
                <?php } } ?>
            </select>

            <label>Área:</label>
            <select name="id_area" id="id_area" required>
                <?php if ($areas !== false) { while ($a = sqlsrv_fetch_array($areas, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $a['id_area'] ?>"><?= $a['nombre'] ?></option>
                <?php } } ?>
            </select>

            <label>Empresa:</label>
            <select name="id_empresa" id="id_empresa" required>
                <?php if ($empresas !== false) { while ($e = sqlsrv_fetch_array($empresas, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $e['id_empresa'] ?>"><?= $e['nombre'] ?></option>
                <?php } } ?>
            </select>

            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

    <script src="../../js/admin/crud_persona.js"></script>
    <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>
</body>
</html>

