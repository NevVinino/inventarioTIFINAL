<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $nombre = $_POST["nombre"] ?? '';
    $RUC = $_POST["RUC"] ?? '';
    $telefono = $_POST["telefono"] ?? '';
    $email = $_POST["email"] ?? '';
    $direccion = $_POST["direccion"] ?? '';
    $ciudad = $_POST["ciudad"] ?? '';
    $pais = $_POST["pais"] ?? '';
    $id_proveedor = $_POST["id_proveedor"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO proveedor (nombre, RUC, telefono, email, direccion, ciudad, pais) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$nombre, $RUC, $telefono, $email, $direccion, $ciudad, $pais];
    } elseif ($accion === "editar" && !empty($id_proveedor)) {
        $sql = "UPDATE proveedor SET nombre = ?, RUC = ?, telefono = ?, email = ?, direccion = ?, ciudad = ?, pais = ? WHERE id_proveedor = ?";
        $params = [$nombre, $RUC, $telefono, $email, $direccion, $ciudad, $pais, $id_proveedor];
    } elseif ($accion === "eliminar" && !empty($id_proveedor)) {
        $sql = "DELETE FROM proveedor WHERE id_proveedor = ?";
        $params = [$id_proveedor];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_proveedor.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if (strpos($errors[0]['message'], 'REFERENCE constraint') !== false) {
            header("Location: ../views/crud_proveedor.php?error=No se puede eliminar este proveedor porque está siendo usado en un cambio de hardware.");
        } else {
            header("Location: ../views/crud_proveedor.php?error=Error al eliminar el registro.");
        }
        exit;
    }
}