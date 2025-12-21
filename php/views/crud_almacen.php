<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de almacenes con localidad
$sqlAlmacenes = "SELECT a.id_almacen, a.nombre, a.direccion, a.id_localidad, a.observaciones, l.localidad_nombre AS localidad_nombre
     FROM almacen a 
     INNER JOIN localidad l ON a.id_localidad = l.id_localidad";
$almacenes = sqlsrv_query($conn, $sqlAlmacenes);

// Obtener localidades para el dropdown
$sqlLocalidades = "SELECT id_localidad, localidad_nombre FROM localidad ORDER BY localidad_nombre";
$localidades = sqlsrv_query($conn, $sqlLocalidades);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Almacenes</title>
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
                    <h2>Almacenes</h2>
                    <input type="text" id="buscador" placeholder="Busca en la tabla">
                    <button id="btnNuevo">+ NUEVO</button>
                </div>

                <table id="tablaAlmacenes">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Localidad</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($a = sqlsrv_fetch_array($almacenes, SQLSRV_FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($a["nombre"]) ?></td>
                                <td><?= htmlspecialchars($a["direccion"] ?? '') ?></td>
                                <td><?= htmlspecialchars($a["localidad_nombre"]) ?></td>
                                <td><?= htmlspecialchars($a["observaciones"] ?? '') ?></td>
                                <td>
                                    <div class="acciones">
                                        <button type="button" class="btn-icon btn-editar"
                                            data-id="<?= $a['id_almacen'] ?>"
                                            data-nombre="<?= htmlspecialchars($a['nombre']) ?>"
                                            data-direccion="<?= htmlspecialchars($a['direccion'] ?? '') ?>"
                                            data-id-localidad="<?= $a['id_localidad'] ?>"
                                            data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? '') ?>">
                                            <img src="../../img/editar.png" alt="Editar">
                                        </button>
                                        <form method="POST" action="../controllers/procesar_almacen.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este almacén?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_almacen" value="<?= $a['id_almacen'] ?>">
                                            <button type="submit" class="btn-icon btn-eliminar">
                                                <img src="../../img/eliminar.png" alt="Eliminar">
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <!-- Modal para crear/editar almacén -->
                <div id="modalAlmacen" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3 id="modal-title">Nuevo Almacén</h3>
                        <form id="formAlmacen" method="POST" action="../controllers/procesar_almacen.php">
                            <input type="hidden" name="accion" id="accion" value="crear">
                            <input type="hidden" name="id_almacen" id="id_almacen">

                            <label for="nombre">Nombre:</label>
                            <input type="text" name="nombre" id="nombre" required>
                            
                            <label for="direccion">Dirección:</label>
                            <input type="text" name="direccion" id="direccion">
                            
                            <label for="id_localidad">Localidad:</label>
                            <select name="id_localidad" id="id_localidad" required>
                                <option value="">Seleccione una localidad</option>
                                <?php 
                                // Reset pointer for localidades
                                $localidadesArray = [];
                                while ($l = sqlsrv_fetch_array($localidades, SQLSRV_FETCH_ASSOC)) {
                                    $localidadesArray[] = $l;
                                }
                                foreach ($localidadesArray as $l) { ?>
                                    <option value="<?= $l['id_localidad'] ?>"><?= htmlspecialchars($l['localidad_nombre']) ?></option>
                                <?php } ?>
                            </select>
                            
                            <label for="observaciones">Observaciones:</label>
                            <textarea name="observaciones" id="observaciones" rows="3"></textarea>
                            
                            <button type="submit">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
            <script src="../../js/admin/crud_almacen.js"></script>
            <!-- Incluir JavaScript del Sidebar -->
            <script src="../../js/admin/sidebar.js"></script>
    </body>
</html>
