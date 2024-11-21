<?php
session_start(); // Inicia la sesión para poder acceder a las variables de sesión

// Destruir todas las variables de sesión
$_SESSION = array(); // Limpia el array de sesiones

// Si se desea destruir la sesión completamente, también se puede usar:
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir al login
header("Location: /index.php");
exit();
