<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de proveedores
$sqlProveedores = "SELECT id_proveedor, nombre, RUC, telefono, email, direccion, ciudad, pais FROM proveedor";
$proveedores = sqlsrv_query($conn, $sqlProveedores);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Gestión de Proveedores</title>
        <link rel="stylesheet" href="../../css/admin/admin_main.css">
    </head>

    <body>
        <!-- Incluir Header -->
        <?php include('includes/header.php'); ?>

        <!-- Incluir Sidebar -->
        <?php include('includes/sidebar.php'); ?>

        <main class="main-content" id="mainContent">
            <!-- Flecha -->
            <a href="vista_admin.php" class="back-button">
                    <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
            </a>
            <div class="main-container">
                <div class="top-bar">
                    <h2>Gestión de Proveedores</h2>
                    <input type="text" id="buscador" placeholder="Buscar proveedor">
                    <button id="btnNuevo">+ NUEVO</button>
                </div>

                <table id="tablaProveedores">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nombre</th>
                            <th>RUC</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Dirección</th>
                            <th>Ciudad</th>
                            <th>País</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($u = sqlsrv_fetch_array($proveedores, SQLSRV_FETCH_ASSOC)) { ?>
                        
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $u['nombre'] ?></td>
                            <td><?= $u['RUC'] ?></td>
                            <td><?= $u['telefono'] ?></td>
                            <td><?= $u['email'] ?></td>
                            <td><?= $u['direccion'] ?></td>
                            <td><?= $u['ciudad'] ?></td>
                            <td><?= $u['pais'] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $u['id_proveedor'] ?>"
                                        data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                                        data-ruc="<?= htmlspecialchars($u['RUC']) ?>"
                                        data-telefono="<?= htmlspecialchars($u['telefono']) ?>"
                                        data-email="<?= htmlspecialchars($u['email']) ?>"
                                        data-direccion="<?= htmlspecialchars($u['direccion']) ?>"
                                        data-ciudad="<?= htmlspecialchars($u['ciudad']) ?>"
                                        data-pais="<?= htmlspecialchars($u['pais']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>

                                    <form method="POST" action="../controllers/procesar_proveedor.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este proveedor?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_proveedor" value="<?= $u['id_proveedor'] ?>">
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
            </div>
        </main>

        <!-- Modal para Crear o Editar Proveedor -->
         <div id="modalProveedor" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modalTitle">Nuevo Proveedor</h2>
                <form id="formProveedor" method="POST" action="../controllers/procesar_proveedor.php">
                    <input type="hidden" name="accion" id="accion" value="crear">
                    <input type="hidden" name="id_proveedor" id="id_proveedor">

                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>

                    <label for="RUC">RUC:</label>
                    <input type="text" id="RUC" name="RUC" required>

                    <label for="telefono">Teléfono:</label>
                    <input type="text" id="telefono" name="telefono">

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email">

                    <label for="direccion">Dirección:</label>
                    <input type="text" id="direccion" name="direccion">

                    <label for="ciudad">Ciudad:</label>
                    <input type="text" id="ciudad" name="ciudad">

                    <label for="pais">País:</label>
                    <input type="text" id="pais" name="pais">

                    <button type="submit" id="btnGuardar">Guardar</button>
                </form>
            </div>

         </div>

         <script src="../../js/admin/crud_proveedor.js"></script>
         <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>

    </body>
</html>