<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        h1 {
            color: #2c3e50;
        }

        .btn {
            padding: 12px 25px;
            background-color: #2980b9;
            color: white;
            text-decoration: none;
            font-size: 16px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #1f6393;
        }
    </style>
</head>
<body>

    <h1>Bienvenido al Sistema</h1>
    <a href="/php/auth/iniciarsesion.php" class="btn">Iniciar Sesión</a>

</body>
</html>
