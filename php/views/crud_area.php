<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener roles, estados y empresas
    // no tiene relaciones con otras tablas, por lo que no es necesario hacer joins

// Obtener lista de usuarios
$sqlAreas = "SELECT id_area, nombre FROM area";
$areas = sqlsrv_query($conn, $sqlAreas);
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Gestión de Áreas</title>
        <link rel="stylesheet" href="../../css/admin/admin_main.css">
            <style>
                .alerta-error {
                    background-color: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                    padding: 12px 20px;
                    margin: 20px auto;
                    width: 80%;
                    text-align: center;
                    border-radius: 5px;
                    font-weight: bold;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    transition: opacity 0.5s ease;
                }

            </style>
    </head>

    <body>
        <!-- Incluir Header -->
        <?php include('includes/header.php'); ?>

        <!-- Incluir Sidebar -->
        <?php include('includes/sidebar.php'); ?>

    

        <!-- Alerta de errores -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alerta-error" id="mensajeError">
                <?php if ($_GET['error'] === 'area_en_uso'): ?>
                    No se puede eliminar esta área porque está asignada a una o más personas.
                <?php else: ?>
                    Error al eliminar el registro.
                <?php endif; ?>
            </div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Ocultar después de 5 segundos
                    setTimeout(() => {
                        const alerta = document.getElementById("mensajeError");
                        if (alerta) alerta.style.display = "none";
                    }, 10000);

                    // Limpiar el parámetro ?error de la URL
                    if (history.replaceState) {
                        const url = new URL(window.location);
                        url.searchParams.delete('error');
                        window.history.replaceState({}, document.title, url.pathname + url.search);
                    }
                    });
            </script>
        <?php endif; ?>

        <main class="main-content" id="mainContent">
            <!-- Flecha -->
            <a href="vista_admin.php" class="back-button">
                <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
            </a>
            <div class="main-container"> 

                <div class="top-bar">
                    <h2>Crear Areas</h2>
                    <input type="text" id="buscador" placeholder="Buscar areas">
                    <button id="btnNuevo">+ NUEVO</button>
                </div>

                <table id="tablaAreas">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nombre</th>                
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($u = sqlsrv_fetch_array($areas, SQLSRV_FETCH_ASSOC)) { ?>
                        
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $u['nombre'] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $u['id_area'] ?>"
                                        data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>

                                    <form method="POST" action="../controllers/procesar_area.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta area?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_area" value="<?= $u['id_area'] ?>">
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
        <div id="modalArea" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title">Crear Área</h2>
                <form id="formArea" method="POST" action="../controllers/procesar_area.php">
                    <input type="hidden" name="accion" id="accion" value="crear">
                    <input type="hidden" name="id_area" id="id_area">

                    <label for="nombre">Nombre del Área:</label>
                    <input type="text" id="nombre" name="nombre" required>

                    <button type="submit">Guardar</button>
                </form>
        
            </div>
        </div>     

        <script src="../../js/admin/crud_area.js"></script>
        <!-- Incluir JavaScript del Sidebar -->
        <script src="../../js/admin/sidebar.js"></script>
    </body>

</html>
