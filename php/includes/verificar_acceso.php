<?php
// Iniciar sesión solo si no hay sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Construir la ruta base del proyecto dinámicamente
$currentDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $currentDir;

// Ajustar la ruta base dependiendo de desde dónde se incluye este archivo
if (strpos($currentDir, '/views') !== false) {
    // Si estamos en /php/views/ o subdirectorios
    $basePath = preg_replace('/\/views.*$/', '', $currentDir);
} elseif (strpos($currentDir, '/auth') !== false) {
    // Si estamos en /php/auth/
    $basePath = dirname($currentDir);
}

// Verificar que el usuario haya iniciado sesión correctamente
if (
    empty($_SESSION["id_usuario"]) ||
    empty($_SESSION["username"]) ||
    empty($_SESSION["rol"])
) {
    header("Location: " . $basePath . "/auth/iniciarsesion.php?error=no_autenticado");
    exit;
}

// Control de acceso exclusivo para administradores
if (isset($solo_admin) && $solo_admin === true && $_SESSION["rol"] !== "admin") {
    header("Location: " . $basePath . "/auth/iniciarsesion.php?error=no_autorizado");
    exit;
}

// Control de acceso exclusivo para usuarios
if (isset($solo_user) && $solo_user === true && $_SESSION["rol"] !== "user") {
    header("Location: " . $basePath . "/auth/iniciarsesion.php?error=no_autorizado");
    exit;
}
