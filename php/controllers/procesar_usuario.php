<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';
    $id_rol = $_POST["id_rol"] ?? '';
    $id_estado_usuario = $_POST["id_estado_usuario"] ?? '';
    $id_usuario = $_POST["id_usuario"] ?? '';

    // Encriptar la contraseña solo si viene algo ingresado
    $password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    if ($accion === "crear") {
        $sql = "INSERT INTO usuario (username, password, id_rol, id_estado_usuario)
                VALUES (?, ?, ?, ?)";
        $params = [$username, $password_hash, $id_rol, $id_estado_usuario];

    } elseif ($accion === "editar" && !empty($id_usuario)) {
        // Si se proporciona una nueva contraseña, actualizarla. Si no, mantener la anterior.
        if (!empty($password)) {
            $sql = "UPDATE usuario SET username = ?, password = ?, id_rol = ?, id_estado_usuario = ?
                    WHERE id_usuario = ?";
            $params = [$username, $password_hash, $id_rol, $id_estado_usuario, $id_usuario];
        } else {
            $sql = "UPDATE usuario SET username = ?, id_rol = ?, id_estado_usuario = ?
                    WHERE id_usuario = ?";
            $params = [$username, $id_rol, $id_estado_usuario, $id_usuario];
        }

    } elseif ($accion === "eliminar" && !empty($id_usuario)) {
        $sql = "DELETE FROM usuario WHERE id_usuario = ?";
        $params = [$id_usuario];

    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_usuarios.php?success=1");
        exit;
    } else {
        echo "Error en la operación:";
        print_r(sqlsrv_errors());
    }
}
?>
