id_empresa<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $nombre = $_POST["nombre"] ?? '';
    $id_empresa = $_POST["id_empresa"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO empresa (nombre) VALUES (?)";
        $params = [$nombre];

    } elseif ($accion === "editar" && !empty($id_empresa)) {
        $sql = "UPDATE empresa SET nombre = ? WHERE id_empresa = ?";
        $params = [$nombre, $id_empresa];

    } elseif ($accion === "eliminar" && !empty($id_empresa)) {
        $sql = "DELETE FROM empresa WHERE id_empresa = ?";
        $params = [$id_empresa];

    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_empresa.php?success=1");
        exit;
    } else {
        echo "Error en la operación:";
        print_r(sqlsrv_errors());
    }
}
?>