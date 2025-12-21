<?php
session_start();
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT u.id_usuario, u.username, u.password, r.descripcion AS rol, e.vestado_usuario
            FROM usuario u
            JOIN rol r ON u.id_rol = r.id_rol
            JOIN estado_usuario e ON u.id_estado_usuario = e.id_estado_usuario
            WHERE u.username = ?";

    $stmt = sqlsrv_query($conn, $sql, [$username]);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (password_verify($password, $row["password"])) {
            // Verificar si está habilitado
            if (strtolower($row["vestado_usuario"]) === 'habilitado') {
                $_SESSION["id_usuario"] = $row["id_usuario"];   // <--- AÑADIDO para registrar usuario responsable
                $_SESSION["username"] = $row["username"];
                $_SESSION["rol"] = $row["rol"];

                if ($row["rol"] === "admin") {
                    header("Location: ../views/vista_admin.php");
                    exit;
                } elseif ($row["rol"] === "user") {
                    header("Location: ../views/user/vista_user.php");
                    exit;
                }
            } else {
                // Usuario está deshabilitado
                header("Location: iniciarsesion.php?error=deshabilitado");
                exit;
            }
        }
    }

    // Usuario o contraseña incorrectos
    header("Location: iniciarsesion.php?error=credenciales");
    exit;
}
