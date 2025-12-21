<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de empresas
$sqlEmpresas = "SELECT u.id_empresa, u.nombre
        FROM empresa u";
$empresas = sqlsrv_query($conn, $sqlEmpresas);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Empresas</title>
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
                <h2>Empresas</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaEmpresas">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nombre de la Empresa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($u = sqlsrv_fetch_array($empresas, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $u["nombre"] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $u['id_empresa'] ?>"
                                        data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_empresa.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta empresa?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_empresa" value="<?= $u['id_empresa'] ?>">
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
    <!-- Modal para Crear o Editar -->
    <div id="modalEmpresa" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title">Crear nueva empresa</h2>
            <form method="POST" action="../controllers/procesar_empresa.php" id="formEmpresa">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id_empresa" id="id_empresa">

                <label>Nombre de Empresa:</label>
                <input type="text" name="nombre" id="nombre" required>

                <button type="submit" id="btnGuardar">Guardar</button>
            </form>
        </div>
    </div>

    <script src="../../js/admin/crud_empresa.js"></script>
    <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>
</body>
</html>
