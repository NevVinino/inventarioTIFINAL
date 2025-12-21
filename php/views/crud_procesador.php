<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de procesadores
$sqlProcesadores = "SELECT p.id_procesador, p.modelo, m.nombre as marca, m.id_marca, p.generacion, p.nucleos, p.hilos, p.part_number
     FROM procesador p
     INNER JOIN marca m ON p.id_marca = m.id_marca";
$procesadores = sqlsrv_query($conn, $sqlProcesadores);

// Obtener marcas filtradas por tipo "Procesador"
$sqlMarcasProcesador = "SELECT m.*, tm.nombre as tipo_marca_nombre 
                       FROM marca m 
                       INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca 
                       WHERE LOWER(tm.nombre) = 'procesador'";
$marcasProcesador = sqlsrv_query($conn, $sqlMarcasProcesador);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Procesadores</title>
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
                    <h2>Procesadores</h2>
                    <input type="text" id="buscador" placeholder="Busca en la tabla">
                    <button id="btnNuevo">+ NUEVO</button>
                </div>

                <table id="tablaProcesadores">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Generación</th>
                            <th>Núcleos</th>
                            <th>Hilos</th>
                            <th>Part Number</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($p = sqlsrv_fetch_array($procesadores, SQLSRV_FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= $p["modelo"] ?></td>
                                <td><?= $p["marca"] ?></td>
                                <td><?= $p["generacion"] ?></td>
                                <td><?= $p["nucleos"] ?></td>
                                <td><?= $p["hilos"] ?></td>
                                <td><?= $p["part_number"] ?></td>
                                <td>
                                    <div class="acciones">
                                        <button type="button" class="btn-icon btn-editar"
                                            data-id="<?= $p['id_procesador'] ?>"
                                            data-modelo="<?= htmlspecialchars($p['modelo']) ?>"
                                            data-id-marca="<?= htmlspecialchars($p['id_marca']) ?>"
                                            data-generacion="<?= htmlspecialchars($p['generacion']) ?>"
                                            data-nucleos="<?= htmlspecialchars($p['nucleos']) ?>"
                                            data-hilos="<?= htmlspecialchars($p['hilos']) ?>"
                                            data-partnumber="<?= htmlspecialchars($p['part_number']) ?>">
                                            <img src="../../img/editar.png" alt="Editar">
                                        </button>
                                        <form method="POST" action="../controllers/procesar_procesador.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este procesador?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_procesador" value="<?= $p['id_procesador'] ?>">
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

                <!-- Modal para Crear o Editar -->
                <div id="modalProcesador" class="modal">
                    <div class="modal-content"> 
                        <span class="close">&times;</span>
                        <h2 id="modal-title">Crear nuevo Procesador</h2>
                        <form method="POST" action="../controllers/procesar_procesador.php" id="formProcesador">
                            <input type="hidden" name="accion" id="accion" value="crear">
                            <input type="hidden" name="id_procesador" id="id_procesador">

                            <label>Modelo:</label>
                            <input type="text" name="modelo" id="modelo" required>
                            
                            <label>Marca:</label>
                            <select name="id_marca" id="id_marca" required>
                                <option value="">Seleccione una marca...</option>
                                <?php 
                                // Obtener marcas de procesador para el dropdown
                                $marcasProcesadorArray = [];
                                while ($marca = sqlsrv_fetch_array($marcasProcesador, SQLSRV_FETCH_ASSOC)) {
                                    $marcasProcesadorArray[] = $marca;
                                }
                                
                                if (empty($marcasProcesadorArray)) { ?>
                                    <option value="">No hay marcas de tipo "Procesador" disponibles</option>
                                <?php } else {
                                    foreach ($marcasProcesadorArray as $marca) { ?>
                                        <option value="<?= $marca['id_marca'] ?>"><?= htmlspecialchars($marca['nombre']) ?></option>
                                    <?php }
                                } ?>
                            </select>
                            
                            <label>Generación:</label>
                            <input type="text" name="generacion" id="generacion">
                            <label>Núcleos:</label>
                            <input type="number" name="nucleos" id="nucleos">
                            <label>Hilos:</label>
                            <input type="number" name="hilos" id="hilos">
                            <label>Part Number:</label>
                            <input type="text" name="part_number" id="part_number">

                            <button type="submit" id="btnGuardar">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        <!-- Script para debug -->
        <script>
            // Cargar marcas de procesador para JavaScript
            <?php 
            // Reset query para JavaScript
            $marcasProcesador2 = sqlsrv_query($conn, "SELECT m.*, tm.nombre as tipo_marca_nombre 
                                                     FROM marca m 
                                                     INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca 
                                                     WHERE LOWER(tm.nombre) = 'procesador'");
            $marcasArray = [];
            while ($m = sqlsrv_fetch_array($marcasProcesador2, SQLSRV_FETCH_ASSOC)) {
                $marcasArray[] = $m;
            }
            ?>
            window.marcasProcesador = <?= json_encode($marcasArray) ?>;
            
            console.log('=== DEBUG PROCESADOR ===');
            console.log('Marcas de procesador cargadas:', window.marcasProcesador);
            
            if (window.marcasProcesador.length === 0) {
                console.warn('⚠️ No se encontraron marcas de tipo "Procesador"');
                console.log('💡 Asegúrate de tener:');
                console.log('1. Un tipo_marca con nombre "procesador" en la tabla tipo_marca');
                console.log('2. Marcas asociadas a ese tipo_marca en la tabla marca');
            }
        </script>

        <script src="../../js/admin/crud_procesador.js"></script>
        <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>

    </body>
</html>