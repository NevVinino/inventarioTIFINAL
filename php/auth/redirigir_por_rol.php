<?php
session_start();

// Construir la ruta base del proyecto
$currentDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = dirname($currentDir); // Desde /php/auth/ ir a /php/

if (!isset($_SESSION["rol"])) {
    header("Location: " . $currentDir . "/iniciarsesion.php?error=sin_rol");
    exit;
}

switch ($_SESSION["rol"]) {
    case "admin":
        header("Location: " . $basePath . "/views/vista_admin.php");
        break;
    case "user":
        header("Location: " . $basePath . "/views/user/vista_user.php");
        break;
    default:
        header("Location: " . $currentDir . "/iniciarsesion.php?error=rol_no_valido");
        break;
}
exit;
