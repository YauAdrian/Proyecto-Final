<?php
session_start(); 

// Verificar que el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MikroTik</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css"> <!-- Enlace al archivo CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome para los íconos -->
</head>
<body>
    <!-- Sidebar con las opciones del menú -->
    <div class="sidebar">
        <div class="logo-container">
            <h2><i class="fas fa-network-wired"></i> MikroTik</h2>
        </div>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="gestion_usuarios.php"><i class="fas fa-users"></i> Gestión de Usuarios</a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
            <li><a href="#" id="agregar-ips"><i class="fas fa-network-wired"></i> Agregar IPs</a></li>
            <li><a href="agregar_ancho_banda.php"><i class="fas fa-tachometer-alt"></i> Ancho de Banda</a></li>
            <li><a href="terminal.php"><i class="fas fa-terminal"></i> Terminal</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
        </ul>
    </div>

    <!-- Contenido principal del dashboard -->
    <div class="main-content">
        <div class="header">
            <h1>Bienvenido, <?php echo $_SESSION['username']; ?></h1>
            <p>Selecciona una opción del menú para continuar.</p>
        </div>
        <div class="cards-container">
            <div class="card">
                <i class="fas fa-users"></i>
                <h3>Gestión de Usuarios</h3>
                <p>Administra los usuarios conectados a la red.</p>
            </div>
            <div class="card">
                <i class="fas fa-cog"></i>
                <h3>Configuración</h3>
                <p>Modifica las configuraciones del sistema.</p>
            </div>
            <div class="card">
                <i class="fas fa-network-wired"></i>
                <h3>Agregar IPs</h3>
                <p>Gestiona y añade nuevas IPs a la red.</p>
            </div>
            <div class="card">
                <i class="fas fa-tachometer-alt"></i>
                <h3>Ancho de Banda</h3>
                <p>Monitorea el uso del ancho de banda.</p>
            </div>
        </div>
    </div>

    <!-- Overlay para el minidashboard -->
    <div class="overlay" id="overlay"></div>

    <!-- Minidashboard para agregar IPs -->
    <div class="minidashboard" id="minidashboard">
        <h2>Opciones de IP</h2>
        <button onclick="agregarAddress()">Agregar Address</button>
        <button onclick="agregarFirewallNAT()">Agregar Firewall NAT</button>
        <button onclick="agregarGateway()">Agregar Gateway</button>
        <button onclick="agregarDNS()">Agregar DNS</button>
        <button onclick="agregarDHCP()">Agregar DHCP</button>
        <button onclick="cerrarMinidashboard()">Cerrar</button>
    </div>

    <script>
        // Función para mostrar el minidashboard
        document.getElementById("agregar-ips").onclick = function(event) {
            event.preventDefault(); // Evitar el comportamiento predeterminado del enlace
            document.getElementById("minidashboard").style.display = "block"; // Mostrar minidashboard
            document.getElementById("overlay").style.display = "block"; // Mostrar overlay
        };

        // Función para cerrar el minidashboard
        function cerrarMinidashboard() {
            document.getElementById("minidashboard").style.display = "none"; // Ocultar minidashboard
            document.getElementById("overlay").style.display = "none"; // Ocultar overlay
        }

        // Funciones para manejar opciones
        function agregarAddress() {
            window.location.href = 'agregar_ips.php'; // Redirigir a agregar_ips.php
        }

        function agregarFirewallNAT() {
            window.location.href = 'agregar_nat.php';
        }

        function agregarGateway() {
            window.location.href = 'agregar_ruta.php';
        }

        function agregarDNS() {
            window.location.href = 'agregar_DNS.php';
        }

        function agregarDHCP() {
            window.location.href = 'configurar_dhcp.php';
        }
    </script>
</body>
</html>











