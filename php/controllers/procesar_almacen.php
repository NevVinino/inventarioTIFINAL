<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $nombre = $_POST["nombre"] ?? '';
    $direccion = $_POST["direccion"] ?? '';
    $id_almacen = $_POST["id_almacen"] ?? '';
    $id_localidad = $_POST["id_localidad"] ?? '';
    $observaciones = $_POST["observaciones"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO almacen (nombre, direccion, id_localidad, observaciones) VALUES (?, ?, ?, ?)";
        $params = [$nombre, $direccion, $id_localidad, $observaciones];
    } elseif ($accion === "editar" && !empty($id_almacen)) {
        $sql = "UPDATE almacen SET nombre = ?, direccion = ?, id_localidad = ?, observaciones = ? WHERE id_almacen = ?";
        $params = [$nombre, $direccion, $id_localidad, $observaciones, $id_almacen];
    } elseif ($accion === "eliminar" && !empty($id_almacen)) {
        $sql = "DELETE FROM almacen WHERE id_almacen = ?";
        $params = [$id_almacen];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_almacen.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}
?>
