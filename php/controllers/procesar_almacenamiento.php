<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $tipo = $_POST["tipo"] ?? '';
    $interfaz = $_POST["interfaz"] ?? '';
    $capacidad = $_POST["capacidad"] ?? '';
    $modelo = $_POST["modelo"] ?? '';
    $serial_number = $_POST["serial_number"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $id_almacenamiento = $_POST["id_almacenamiento"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO almacenamiento (tipo, interfaz, capacidad, modelo, serial_number, id_marca) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$tipo, $interfaz, $capacidad, $modelo, $serial_number, $id_marca];
    } elseif ($accion === "editar" && !empty($id_almacenamiento)) {
        $sql = "UPDATE almacenamiento SET tipo = ?, interfaz = ?, capacidad = ?, modelo = ?, serial_number = ?, id_marca = ? WHERE id_almacenamiento = ?";
        $params = [$tipo, $interfaz, $capacidad, $modelo, $serial_number, $id_marca, $id_almacenamiento];
    } elseif ($accion === "eliminar" && !empty($id_almacenamiento)) {
        $sql = "DELETE FROM almacenamiento WHERE id_almacenamiento = ?";
        $params = [$id_almacenamiento];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_almacenamiento.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if (strpos($errors[0]['message'], 'REFERENCE constraint') !== false) {
            header("Location: ../views/crud_almacenamiento.php?error=No se puede eliminar este almacenamiento porque está siendo usado por uno o más activos informáticos.");
        } else {
            header("Location: ../views/crud_almacenamiento.php?error=Error al eliminar el registro.");
        }
        exit;
    }
}