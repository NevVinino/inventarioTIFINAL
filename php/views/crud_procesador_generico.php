<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de procesadores genéricos
$sqlProcesadores = "SELECT id_procesador_generico, modelo, generacion FROM procesador_generico ORDER BY modelo";
$procesadores = sqlsrv_query($conn, $sqlProcesadores);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Procesadores Genéricos</title>
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
                    <h2>Procesadores Genéricos</h2>
                    <input type="text" id="buscador" placeholder="Busca en la tabla">
                    <button id="btnNuevo">+ NUEVO</button>
                </div>

                <table id="tablaProcesadores">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Modelo</th>
                            <th>Generación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($p = sqlsrv_fetch_array($procesadores, SQLSRV_FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($p["modelo"]) ?></td>
                                <td><?= htmlspecialchars($p["generacion"] ?? '') ?></td>
                                <td>
                                    <div class="acciones">
                                        <button type="button" class="btn-icon btn-editar"
                                            data-id="<?= $p['id_procesador_generico'] ?>"
                                            data-modelo="<?= htmlspecialchars($p['modelo']) ?>"
                                            data-generacion="<?= htmlspecialchars($p['generacion'] ?? '') ?>">
                                            <img src="../../img/editar.png" alt="Editar">
                                        </button>
                                        <form method="POST" action="../controllers/procesar_procesador_generico.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este procesador genérico?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_procesador_generico" value="<?= $p['id_procesador_generico'] ?>">
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

                <!-- Modal para crear/editar procesador genérico -->
                <div id="modalProcesador" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3 id="modal-title">Nuevo Procesador Genérico</h3>
                        <form id="formProcesador" method="POST" action="../controllers/procesar_procesador_generico.php">
                            <input type="hidden" name="accion" id="accion" value="crear">
                            <input type="hidden" name="id_procesador_generico" id="id_procesador_generico">

                            <label for="modelo">Modelo:</label>
                            <input type="text" name="modelo" id="modelo" required>
                            
                            <label for="generacion">Generación:</label>
                            <input type="text" name="generacion" id="generacion">
                            
                            <button type="submit">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <script src="../../js/admin/crud_procesador_generico.js"></script>
        <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>
    </body>
</html>
