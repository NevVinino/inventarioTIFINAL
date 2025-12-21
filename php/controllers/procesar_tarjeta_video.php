<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $modelo = $_POST["modelo"] ?? '';
    $memoria = $_POST["memoria"] ?? '';
    $tipo_memoria = $_POST["tipo_memoria"] ?? '';
    $interfaz = $_POST["interfaz"] ?? '';
    $puertos = $_POST["puertos"] ?? '';
    $serial_number = $_POST["serial_number"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $id_tarjeta_video = $_POST["id_tarjeta_video"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO tarjeta_video (modelo, memoria, tipo_memoria, interfaz, puertos, serial_number, id_marca) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$modelo, $memoria, $tipo_memoria, $interfaz, $puertos, $serial_number, $id_marca];
    } elseif ($accion === "editar" && !empty($id_tarjeta_video)) {
        $sql = "UPDATE tarjeta_video SET modelo = ?, memoria = ?, tipo_memoria = ?, interfaz = ?, puertos = ?, serial_number = ?, id_marca = ? WHERE id_tarjeta_video = ?";
        $params = [$modelo, $memoria, $tipo_memoria, $interfaz, $puertos, $serial_number, $id_marca, $id_tarjeta_video];
    } elseif ($accion === "eliminar" && !empty($id_tarjeta_video)) {
        $sql = "DELETE FROM tarjeta_video WHERE id_tarjeta_video = ?";
        $params = [$id_tarjeta_video];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_tarjeta_video.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}
?>
