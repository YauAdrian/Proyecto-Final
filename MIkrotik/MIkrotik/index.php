<?php
session_start(); // Inicia la sesión

// Redirige al dashboard si el usuario ya ha iniciado sesión
if (isset($_SESSION['username'])) {
    header("Location: views/dashboard.php");
    exit();
}

// Redirige a la página de inicio de sesión
header("Location: views/login.php");
exit();
