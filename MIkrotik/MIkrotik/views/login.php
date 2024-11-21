<?php
session_start(); // Inicia la sesión

// Redirige al dashboard si el usuario ya ha iniciado sesión
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Maneja el inicio de sesión al enviar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $host = $_POST['host']; // Dirección IP ingresada por el usuario
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Requiere la librería API de MikroTik
    require('../lib/routeros_api.class.php'); // Asegúrate de que este archivo está en tu carpeta /lib/

    $API = new RouterosAPI();

    // Conectar a la API y verificar credenciales
    if ($API->connect($host, $username, $password)) {
        $_SESSION['username'] = $username; // Guarda el nombre de usuario en la sesión
        $_SESSION['host'] = $host; // Guarda la IP para su uso posterior
        $_SESSION['password'] = $password;
        header("Location: dashboard.php"); // Redirige al panel de control
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos."; // Mensaje de error si las credenciales son incorrectas
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MikroTik</title>
    <link rel="stylesheet" href="../assets/css/styles.css"> 
</head>
<body>

    <div class="mikrotik-background"></div> <!-- Fondo de imagen -->
    <div class="login-container">
        <img src="../assets/img/mikro.png" alt="MikroTik Logo" class="mikrotik-image"> <!-- Imagen animada -->
        <h1>Iniciar Sesión</h1>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <form method="POST" action="login.php">
            <div class="input-group">
                <label for="host">Dirección IP</label>
                <input type="text" id="host" name="host" required placeholder="Ej: 192.168.88.1">
            </div>
            <div class="input-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required placeholder="Usuario">
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="Contraseña">
            </div>
            <button type="submit">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>

