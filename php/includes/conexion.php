<?php
// Configurar zona horaria de Lima, Perú
date_default_timezone_set('America/Lima');

$serverName = "DESKTOP-3NIIFTR"; // tu servidor local
$connectionOptions = array(
    "Database" => "InventarioTI",
    "Uid" => "sa", // usuario SQL Server
    "PWD" => "72653250", // tu contraseña
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("❌ Error de conexión: " . print_r(sqlsrv_errors(), true));
}

//Para probar conexión
// if (!$conn) {
//     die("❌ Error de conexión: " . print_r(sqlsrv_errors(), true));
// } else {
//     echo "✅ Conexión exitosa a la base de datos.";
// }
?>
