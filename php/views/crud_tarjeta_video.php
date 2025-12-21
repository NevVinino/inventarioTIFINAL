<?php
include("../includes/conexion.php");
$solo_admin = true; 
include("../includes/verificar_acceso.php");

//Obtener lista de Tarjetas de Video
$sqlTarjetas = "SELECT tv.id_tarjeta_video, tv.modelo, tv.memoria, tv.tipo_memoria, tv.interfaz, tv.puertos, tv.serial_number, m.nombre as marca, tv.id_marca
     FROM tarjeta_video tv
     INNER JOIN marca m ON tv.id_marca = m.id_marca";
$tarjetas = sqlsrv_query($conn, $sqlTarjetas);

// Obtener marcas filtradas por tipo "Tarjeta de Video"
$sqlMarcasTarjeta = "SELECT m.*, tm.nombre as tipo_marca_nombre 
                     FROM marca m 
                     INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca 
                     WHERE LOWER(tm.nombre) = 'tarjeta de video' OR LOWER(tm.nombre) = 'tarjeta video' OR LOWER(tm.nombre) = 'gpu'";
$marcasTarjeta = sqlsrv_query($conn, $sqlMarcasTarjeta);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Tarjetas de Video</title>
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
                <h2>Tarjetas de Video</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaTarjetas">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Modelo</th>
                        <th>Marca</th>
                        <th>Memoria</th>
                        <th>Tipo Memoria</th>
                        <th>Interfaz</th>
                        <th>Puertos</th>
                        <th>Serial Number</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($tv = sqlsrv_fetch_array($tarjetas, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $tv["modelo"] ?></td>
                            <td><?= $tv["marca"] ?></td>
                            <td><?= $tv["memoria"] ?></td>
                            <td><?= $tv["tipo_memoria"] ?></td>
                            <td><?= $tv["interfaz"] ?></td>
                            <td><?= $tv["puertos"] ?></td>
                            <td><?= $tv["serial_number"] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $tv['id_tarjeta_video'] ?>"
                                        data-modelo="<?= htmlspecialchars($tv['modelo']) ?>"
                                        data-id-marca="<?= htmlspecialchars($tv['id_marca']) ?>"
                                        data-memoria="<?= htmlspecialchars($tv['memoria']) ?>"
                                        data-tipo-memoria="<?= htmlspecialchars($tv['tipo_memoria']) ?>"
                                        data-interfaz="<?= htmlspecialchars($tv['interfaz']) ?>"
                                        data-puertos="<?= htmlspecialchars($tv['puertos']) ?>"
                                        data-serial="<?= htmlspecialchars($tv['serial_number']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_tarjeta_video.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta tarjeta de video?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_tarjeta_video" value="<?= $tv['id_tarjeta_video'] ?>">
                                        <button type="submit" class="btn-icon">
                                            <img src="../../img/eliminar.png" alt="Eliminar">
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Modal para crear/editar Tarjeta de Video -->
            <div id="modalTarjeta" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Nueva Tarjeta de Video</h2>
                    <form method="POST" action="../controllers/procesar_tarjeta_video.php" id="formTarjeta">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_tarjeta_video" id="id_tarjeta_video">

                        <label>Modelo:</label>
                        <input type="text" name="modelo" id="modelo" required>

                        <label>Marca:</label>
                        <select name="id_marca" id="id_marca" required>
                            <option value="">Seleccione una marca...</option>
                            <?php 
                            // Obtener marcas de Tarjeta de Video para el dropdown
                            $marcasTarjetaArray = [];
                            while ($marca = sqlsrv_fetch_array($marcasTarjeta, SQLSRV_FETCH_ASSOC)) {
                                $marcasTarjetaArray[] = $marca;
                            }
                            
                            if (empty($marcasTarjetaArray)) { ?>
                                <option value="">No hay marcas de tipo "Tarjeta de Video" disponibles</option>
                            <?php } else {
                                foreach ($marcasTarjetaArray as $marca) { ?>
                                    <option value="<?= $marca['id_marca'] ?>"><?= htmlspecialchars($marca['nombre']) ?></option>
                                <?php }
                            } ?>
                        </select>

                        <label>Memoria:</label>
                        <input type="text" name="memoria" id="memoria" required>

                        <label>Tipo de Memoria:</label>
                        <input type="text" name="tipo_memoria" id="tipo_memoria">

                        <label>Interfaz:</label>
                        <input type="text" name="interfaz" id="interfaz">

                        <label>Puertos:</label>
                        <input type="text" name="puertos" id="puertos">

                        <label>Serial Number:</label>
                        <input type="text" name="serial_number" id="serial_number">

                        <button type="submit" id="btn-Guardar">Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Script para debug -->
        <script>
            // Cargar marcas de Tarjeta de Video para JavaScript
            <?php 
            // Reset query para JavaScript
            $marcasTarjeta2 = sqlsrv_query($conn, "SELECT m.*, tm.nombre as tipo_marca_nombre 
                                                   FROM marca m 
                                                   INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca 
                                                   WHERE LOWER(tm.nombre) = 'tarjeta de video' OR LOWER(tm.nombre) = 'tarjeta video' OR LOWER(tm.nombre) = 'gpu'");
            $marcasArray = [];
            while ($m = sqlsrv_fetch_array($marcasTarjeta2, SQLSRV_FETCH_ASSOC)) {
                $marcasArray[] = $m;
            }
            ?>
            window.marcasTarjeta = <?= json_encode($marcasArray) ?>;
            
            console.log('=== DEBUG TARJETA DE VIDEO ===');
            console.log('Marcas de Tarjeta de Video cargadas:', window.marcasTarjeta);
            
            if (window.marcasTarjeta.length === 0) {
                console.warn('⚠️ No se encontraron marcas de tipo "Tarjeta de Video"');
                console.log('💡 Asegúrate de tener:');
                console.log('1. Un tipo_marca con nombre "tarjeta de video", "tarjeta video" o "gpu" en la tabla tipo_marca');
                console.log('2. Marcas asociadas a ese tipo_marca en la tabla marca');
            }
        </script>

        <script src="../../js/admin/crud_tarjeta_video.js"></script>
            <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>
        
        </main>
    </body>
</html>
