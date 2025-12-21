<?php
include("../includes/conexion.php");
$solo_admin = true; 
include("../includes/verificar_acceso.php");

//Obtener lista de RAM
$sqlRams = "SELECT r.id_ram, r.capacidad, m.nombre as marca, r.tipo, r.frecuencia, r.serial_number, r.id_marca
     FROM RAM r
     INNER JOIN marca m ON r.id_marca = m.id_marca";
$rams = sqlsrv_query($conn, $sqlRams);

// Obtener marcas filtradas por tipo "RAM"
$sqlMarcasRAM = "SELECT m.*, tm.nombre as tipo_marca_nombre 
                 FROM marca m 
                 INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca 
                 WHERE LOWER(tm.nombre) = 'ram'";
$marcasRAM = sqlsrv_query($conn, $sqlMarcasRAM);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de RAM</title>
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
                <h2>RAM</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaRams">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Capacidad</th>
                        <th>Marca</th>
                        <th>Tipo</th>
                        <th>Frecuencia</th>
                        <th>Serial Number</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($r = sqlsrv_fetch_array($rams, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $r["capacidad"] ?></td>
                            <td><?= $r["marca"] ?></td>
                            <td><?= $r["tipo"] ?></td>
                            <td><?= $r["frecuencia"] ?></td>
                            <td><?= $r["serial_number"] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $r['id_ram'] ?>"
                                        data-capacidad="<?= htmlspecialchars($r['capacidad']) ?>"
                                        data-id-marca="<?= htmlspecialchars($r['id_marca']) ?>"
                                        data-tipo="<?= htmlspecialchars($r['tipo']) ?>"
                                        data-frecuencia="<?= htmlspecialchars($r['frecuencia']) ?>"
                                        data-serial="<?= htmlspecialchars($r['serial_number']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_ram.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta RAM?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_ram" value="<?= $r['id_ram'] ?>">
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

            <!-- Modal para crear/editar RAM -->
            <div id="modalRam" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Nueva RAM</h2>
                    <form method="POST" action="../controllers/procesar_ram.php" id="formRam">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_ram" id="id_ram">

                        <label>Capacidad:</label>
                        <input type="text" name="capacidad" id="capacidad" required>

                        <label>Marca:</label>
                        <select name="id_marca" id="id_marca" required>
                            <option value="">Seleccione una marca...</option>
                            <?php 
                            // Obtener marcas de RAM para el dropdown
                            $marcasRAMArray = [];
                            while ($marca = sqlsrv_fetch_array($marcasRAM, SQLSRV_FETCH_ASSOC)) {
                                $marcasRAMArray[] = $marca;
                            }
                            
                            if (empty($marcasRAMArray)) { ?>
                                <option value="">No hay marcas de tipo "RAM" disponibles</option>
                            <?php } else {
                                foreach ($marcasRAMArray as $marca) { ?>
                                    <option value="<?= $marca['id_marca'] ?>"><?= htmlspecialchars($marca['nombre']) ?></option>
                                <?php }
                            } ?>
                        </select>

                        <label>Tipo:</label>
                        <input type="text" name="tipo" id="tipo">

                        <label>Frecuencia:</label>
                        <input type="text" name="frecuencia" id="frecuencia">

                        <label>Serial Number:</label>
                        <input type="text" name="serial_number" id="serial_number">

                        <button type="submit" id="btn-Guardar">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
        <!-- Script para debug -->
        <script>
            // Cargar marcas de RAM para JavaScript
            <?php 
            // Reset query para JavaScript
            $marcasRAM2 = sqlsrv_query($conn, "SELECT m.*, tm.nombre as tipo_marca_nombre 
                                               FROM marca m 
                                               INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca 
                                               WHERE LOWER(tm.nombre) = 'ram'");
            $marcasArray = [];
            while ($m = sqlsrv_fetch_array($marcasRAM2, SQLSRV_FETCH_ASSOC)) {
                $marcasArray[] = $m;
            }
            ?>
            window.marcasRAM = <?= json_encode($marcasArray) ?>;
            
            console.log('=== DEBUG RAM ===');
            console.log('Marcas de RAM cargadas:', window.marcasRAM);
            
            if (window.marcasRAM.length === 0) {
                console.warn('⚠️ No se encontraron marcas de tipo "RAM"');
                console.log('💡 Asegúrate de tener:');
                console.log('1. Un tipo_marca con nombre "ram" en la tabla tipo_marca');
                console.log('2. Marcas asociadas a ese tipo_marca en la tabla marca');
            }
        </script>

        <script src="../../js/admin/crud_ram.js"></script>
        
        <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>
    </body>
</html>