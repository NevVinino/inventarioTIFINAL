<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de almacenamientos genéricos
$sqlAlmacenamientos = "SELECT id_almacenamiento_generico, capacidad, tipo FROM almacenamiento_generico ORDER BY capacidad, tipo";
$almacenamientos = sqlsrv_query($conn, $sqlAlmacenamientos);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Almacenamiento Genérico</title>
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
                    <h2>Almacenamiento Genérico</h2>
                    <input type="text" id="buscador" placeholder="Busca en la tabla">
                    <button id="btnNuevo">+ NUEVO</button>
                </div>

                <table id="tablaAlmacenamientos">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Capacidad</th>
                            <th>Tipo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($a = sqlsrv_fetch_array($almacenamientos, SQLSRV_FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($a["capacidad"]) ?></td>
                                <td><?= htmlspecialchars($a["tipo"] ?? '') ?></td>
                                <td>
                                    <div class="acciones">
                                        <button type="button" class="btn-icon btn-editar"
                                            data-id="<?= $a['id_almacenamiento_generico'] ?>"
                                            data-capacidad="<?= htmlspecialchars($a['capacidad']) ?>"
                                            data-tipo="<?= htmlspecialchars($a['tipo'] ?? '') ?>">
                                            <img src="../../img/editar.png" alt="Editar">
                                        </button>
                                        <form method="POST" action="../controllers/procesar_almacenamiento_generico.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este almacenamiento genérico?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_almacenamiento_generico" value="<?= $a['id_almacenamiento_generico'] ?>">
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

                <!-- Modal para crear/editar almacenamiento genérico -->
                <div id="modalAlmacenamiento" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3 id="modal-title">Nuevo Almacenamiento Genérico</h3>
                        <form id="formAlmacenamiento" method="POST" action="../controllers/procesar_almacenamiento_generico.php">
                            <input type="hidden" name="accion" id="accion" value="crear">
                            <input type="hidden" name="id_almacenamiento_generico" id="id_almacenamiento_generico">

                            <label for="capacidad">Capacidad:</label>
                            <input type="text" name="capacidad" id="capacidad" required placeholder="Ej: 256 GB, 512 GB, 1 TB">
                            
                            <label for="tipo">Tipo:</label>
                            <input type="text" name="tipo" id="tipo" placeholder="Ej: SSD, HDD">
                            
                            <button type="submit">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
            <script src="../../js/admin/crud_almacenamiento_generico.js"></script>
            <!-- Incluir JavaScript del Sidebar -->
            <script src="../../js/admin/sidebar.js"></script>
    </body>
</html>
