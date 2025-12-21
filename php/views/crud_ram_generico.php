<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de RAM genéricos
$sqlRAMs = "SELECT id_ram_generico, capacidad FROM RAM_generico ORDER BY capacidad";
$rams = sqlsrv_query($conn, $sqlRAMs);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de RAM Genérico</title>
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
                <h2>RAM Genérico</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaRAMs">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Capacidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($r = sqlsrv_fetch_array($rams, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($r["capacidad"]) ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $r['id_ram_generico'] ?>"
                                        data-capacidad="<?= htmlspecialchars($r['capacidad']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_ram_generico.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta RAM genérica?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_ram_generico" value="<?= $r['id_ram_generico'] ?>">
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

            <!-- Modal para crear/editar RAM genérico -->
            <div id="modalRAM" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3 id="modal-title">Nueva RAM Genérica</h3>
                    <form id="formRAM" method="POST" action="../controllers/procesar_ram_generico.php">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_ram_generico" id="id_ram_generico">

                        <label for="capacidad">Capacidad:</label>
                        <input type="text" name="capacidad" id="capacidad" required placeholder="Ej: 8GB, 16GB, 32GB">
                        
                        <button type="submit">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
        <script src="../../js/admin/crud_ram_generico.js"></script>
        <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>
    </body>
</html>
