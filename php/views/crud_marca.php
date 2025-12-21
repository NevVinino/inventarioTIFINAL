<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de marcas con tipo_marca
$sqlMarcas = "SELECT m.id_marca, m.nombre, m.id_tipo_marca, tm.nombre AS tipo_marca_nombre
     FROM marca m 
     INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca";
$marcas = sqlsrv_query($conn, $sqlMarcas);

// Obtener tipos de marca para el dropdown
$sqlTiposMarca = "SELECT id_tipo_marca, nombre FROM tipo_marca ORDER BY nombre";
$tiposMarca = sqlsrv_query($conn, $sqlTiposMarca);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Marcas por Tipo de Componente</title>
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
                    <h2>Gestión de Marcas por Tipo de Componente</h2>
                    <input type="text" id="buscador" placeholder="Busca en la tabla">
                    <button id="btnNuevo">+ NUEVO</button>
                </div>

                <!-- Contenedor de alertas -->
                <div id="alertaCRUD" style="display:none"></div>

                <table id="tablaMarcas">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nombre</th>
                            <th>Tipo de Componente</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($u = sqlsrv_fetch_array($marcas, SQLSRV_FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($u["nombre"]) ?></td>
                                <td><?= htmlspecialchars($u["tipo_marca_nombre"]) ?></td>
                                <td>
                                    <div class="acciones">
                                        <button type="button" class="btn-icon btn-editar"
                                            data-id="<?= $u['id_marca'] ?>"
                                            data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                                            data-id-tipo-marca="<?= $u['id_tipo_marca'] ?>">
                                            <img src="../../img/editar.png" alt="Editar">
                                        </button>
                                        <form method="POST" action="../controllers/procesar_marca.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta marca?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_marca" value="<?= $u['id_marca'] ?>">
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

                <!-- Modal para crear/editar marca -->
                <div id="modalMarca" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3 id="modal-title">Nueva Marca</h3>
                        <form id="formMarca" method="POST" action="../controllers/procesar_marca.php">
                            <input type="hidden" name="accion" id="accion" value="crear">
                            <input type="hidden" name="id_marca" id="id_marca">

                            <label for="nombre">Nombre:</label>
                            <input type="text" name="nombre" id="nombre" required>
                            
                            <label for="id_tipo_marca">Tipo de Marca:</label>
                            <select name="id_tipo_marca" id="id_tipo_marca" required>
                                <option value="">Seleccione un tipo</option>
                                <?php 
                                // Reset pointer for tipos_marca
                                $tiposMarcaArray = [];
                                while ($tm = sqlsrv_fetch_array($tiposMarca, SQLSRV_FETCH_ASSOC)) {
                                    $tiposMarcaArray[] = $tm;
                                }
                                foreach ($tiposMarcaArray as $tm) { ?>
                                    <option value="<?= $tm['id_tipo_marca'] ?>"><?= htmlspecialchars($tm['nombre']) ?></option>
                                <?php } ?>
                            </select>
                            
                            <button type="submit">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        
        <script src="../../js/admin/crud_marca.js"></script>
        <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>
    </body>
</html>

