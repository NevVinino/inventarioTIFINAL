<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener roles, estados
$roles = sqlsrv_query($conn, "SELECT * FROM rol");
$estados = sqlsrv_query($conn, "SELECT * FROM estado_usuario");

// Obtener lista de usuarios
$sqlUsuarios = "SELECT u.id_usuario, u.username, r.id_rol, r.descripcion AS rol, 
                       e.id_estado_usuario, e.vestado_usuario
                FROM usuario u
                JOIN rol r ON u.id_rol = r.id_rol
                JOIN estado_usuario e ON u.id_estado_usuario = e.id_estado_usuario";

$usuarios = sqlsrv_query($conn, $sqlUsuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
</head>
<body>
    <!-- Incluir Header -->
    <?php include('includes/header.php'); ?>

    <!-- Incluir Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <!-- Contenido principal -->
    <main class="main-content" id="mainContent">
        <a href="vista_admin.php" class="back-button">
            <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
        </a>

        <div class="main-container">
            <div class="top-bar">
                <h2>Usuarios</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($u = sqlsrv_fetch_array($usuarios, SQLSRV_FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= $u['username'] ?></td>
                        <td><?= $u['rol'] ?></td>
                        <td><?= $u['vestado_usuario'] ?></td>
                        <td>
                            <div class="acciones">
                                <button type="button" class="btn-icon btn-editar"
                                    data-id="<?= $u['id_usuario'] ?>"
                                    data-username="<?= htmlspecialchars($u['username']) ?>"
                                    data-id_rol="<?= $u['id_rol'] ?>"
                                    data-id_estado="<?= $u['id_estado_usuario'] ?>">
                                    <img src="../../img/editar.png" alt="Editar">
                                </button>
                                <form method="POST" action="../controllers/procesar_usuario.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este usuario?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
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
    </main>    <!-- Modal para Crear o Editar -->
    <div id="modalUsuario" class="modal">
        <div class="modal-content"> 
            <span class="close">&times;</span>
            <h2 id="modal-title">Crear nuevo usuario</h2>
            <form method="POST" action="../controllers/procesar_usuario.php" id="formUsuario">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id_usuario" id="id_usuario">

                <label>Usuario:</label>
                <input type="text" name="username" id="username" required>

                <label>Password:</label>
                <input type="password" name="password" id="password" required>

                <label>Rol:</label>
                <select name="id_rol" id="id_rol" required>
                    <?php while ($r = sqlsrv_fetch_array($roles, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?= $r['id_rol'] ?>"><?= $r['descripcion'] ?></option>
                    <?php } ?>
                </select>

                <label>Estado:</label>
                <select name="id_estado_usuario" id="id_estado_usuario" required>
                    <?php while ($e = sqlsrv_fetch_array($estados, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?= $e['id_estado_usuario'] ?>"><?= $e['vestado_usuario'] ?></option>
                    <?php } ?>
                </select>

                <button type="submit" id="btnGuardar">Guardar</button>
            </form>
        </div>
    </div>

    <script src="../../js/admin/crud_usuario.js"></script>
        <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>
</body>
</html>
