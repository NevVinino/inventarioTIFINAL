<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de Tarjetas de Video genéricas
$sqlTarjetas = "SELECT id_tarjeta_video_generico, modelo, memoria FROM tarjeta_video_generico ORDER BY modelo";
$tarjetas = sqlsrv_query($conn, $sqlTarjetas);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Tarjetas de Video Genéricas</title>
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
                    <h2>Tarjetas de Video Genéricas</h2>
                    <input type="text" id="buscador" placeholder="Busca en la tabla">
                    <button id="btnNuevo">+ NUEVO</button>
                </div>

                <table id="tablaTarjetas">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Modelo</th>
                            <th>Memoria</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($tv = sqlsrv_fetch_array($tarjetas, SQLSRV_FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($tv["modelo"]) ?></td>
                                <td><?= htmlspecialchars($tv["memoria"]) ?></td>
                                <td>
                                    <div class="acciones">
                                        <button type="button" class="btn-icon btn-editar"
                                            data-id="<?= $tv['id_tarjeta_video_generico'] ?>"
                                            data-modelo="<?= htmlspecialchars($tv['modelo']) ?>"
                                            data-memoria="<?= htmlspecialchars($tv['memoria']) ?>">
                                            <img src="../../img/editar.png" alt="Editar">
                                        </button>
                                        <form method="POST" action="../controllers/procesar_tarjeta_video_generico.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta tarjeta de video genérica?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_tarjeta_video_generico" value="<?= $tv['id_tarjeta_video_generico'] ?>">
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

                <!-- Modal para crear/editar Tarjeta de Video genérica -->
                <div id="modalTarjeta" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3 id="modal-title">Nueva Tarjeta de Video Genérica</h3>
                        <form id="formTarjeta" method="POST" action="../controllers/procesar_tarjeta_video_generico.php">
                            <input type="hidden" name="accion" id="accion" value="crear">
                            <input type="hidden" name="id_tarjeta_video_generico" id="id_tarjeta_video_generico">

                            <label for="modelo">Modelo:</label>
                            <input type="text" name="modelo" id="modelo" required placeholder="Ej: GTX 1650, RTX 3060, Radeon RX 580">
                            
                            <label for="memoria">Memoria:</label>
                            <input type="text" name="memoria" id="memoria" placeholder="Ej: 4GB, 8GB">
                            
                            <button type="submit">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        <script src="../../js/admin/crud_tarjeta_video_generico.js"></script>
            <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>
    </body>
</html>
