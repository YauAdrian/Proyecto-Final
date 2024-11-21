<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Incluir la biblioteca de MikroTik API
require_once '../lib/routeros_api.class.php';

// Obtener los datos del formulario
$host = $_POST['host'] ?? null;
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

if ($host && $username && $password) {
    $api = new RouterosAPI();

    // Intentar conectar a la API de MikroTik
    if ($api->connect($host, $username, $password)) {
        // Conexión exitosa, puedes realizar las operaciones necesarias
        $_SESSION['host'] = $host; // Guardar la IP en la sesión si es necesario

        // Redirigir al dashboard o a la página que desees
        header("Location: dashboard.php");
        exit();
    } else {
        // Manejar el error de conexión
        echo "No se pudo conectar a la API de MikroTik. Verifica tus credenciales.";
    }

    // Desconectar al final
    $api->disconnect();
} else {
    echo "Faltan datos para conectarse.";
}
