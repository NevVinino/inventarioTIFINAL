<!-- Header Admin -->
<header>
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <span class="hamburger-icon">☰</span>
        </button>
        <div class="usuario-info">
            <h1><?= htmlspecialchars($_SESSION["username"]) ?> 
                <span class="rol">(<?= $_SESSION["rol"] ?>)</span>
                <img src="../../img/aguila.png" alt="Águila" class="aguila-badge">
            </h1>
        </div>
    </div>
    <div class="avatar-contenedor">
        <!-- <img src="../../img/tenor.gif" alt="Avatar" class="avatar"> -->
        <a class="logout" href="../auth/logout.php">Cerrar sesión</a>
    </div>
</header>