<?php
require_once '../lib/routeros_api.class.php';
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Ruta del archivo JSON
$json_file = '../views/mikrotik_data.json';

// Leer el contenido actual del archivo JSON
$routers = [];
if (file_exists($json_file)) {
    $routers = json_decode(file_get_contents($json_file), true);
}

// Obtener los datos de la sesión del usuario
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$host = $_SESSION['host'];

// Inicializar el objeto RouterosAPI
$router = new RouterosAPI();

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $action_type = $_POST['action_type'];
    $ip = $_POST['ip'] ?? '';
    $interface = $_POST['interface'] ?? '';
    $ip_id = $_POST['ip_id'] ?? '';

    if ($router->connect($host, $username, $password)) {
        if ($action_type === 'add_address' && !is_valid_ip($ip)) {
            $_SESSION['message'] = 'Dirección IP no válida.';
            header("Location: agregar_ips.php");
            exit();
        }

        if ($action_type === 'add_address') {
            // Agregar dirección IP
            $router->comm('/ip/address/add', [
                'address' => $ip,
                'interface' => $interface,
            ]);
            $_SESSION['message'] = 'Dirección IP agregada correctamente.';
        } elseif ($action_type === 'delete_address') {
            // Eliminar dirección IP
            $router->comm('/ip/address/remove', [
                '.id' => $ip_id,
            ]);
            $_SESSION['message'] = 'Dirección IP eliminada correctamente.';
        }

        $router->disconnect();
    } else {
        $_SESSION['message'] = 'Error al conectar a MikroTik.';
    }
    header("Location: agregar_ips.php");
    exit();
}

// Obtener las interfaces y direcciones IP configuradas en MikroTik
$interfaces = [];
$ip_addresses = [];

if ($router->connect($host, $username, $password)) {
    // Obtener la lista de interfaces
    $interfaces = $router->comm('/interface/print');

    // Obtener la lista de direcciones IP configuradas
    $ip_addresses = $router->comm('/ip/address/print');

    $router->disconnect();
} else {
    $_SESSION['message'] = 'Error al conectar a MikroTik.';
}

// Función para validar direcciones IP y CIDR
function is_valid_ip($ip) {
    return preg_match('/^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/', $ip) && valid_ip_range($ip);
}

function valid_ip_range($ip) {
    if (strpos($ip, '/') !== false) {
        list($ip_addr, $cidr) = explode('/', $ip);
        if ($cidr < 0 || $cidr > 32) {
            return false;
        }
    } else {
        $cidr = null;
    }

    $parts = explode('.', $ip_addr);
    if (count($parts) !== 4) {
        return false;
    }
    foreach ($parts as $part) {
        if ($part < 0 || $part > 255) {
            return false;
        }
    }

    return true;
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

include('sidebar.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Dirección IP</title>
    <link rel="stylesheet" href="../assets/css/agregar_ips.css">
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h1>Agregar Dirección IP</h1>
            <form method="POST" action="agregar_ips.php">
                <input type="hidden" name="action_type" value="add_address">
                <div class="input-group">
                    <label for="ip">Dirección IP</label>
                    <input type="text" id="ip" name="ip" placeholder="Ej: 192.168.88.10 o 192.168.88.0/24" required>
                </div>
                <div class="input-group">
                    <label for="interface">Interfaz</label>
                    <select id="interface" name="interface">
                        <?php foreach ($interfaces as $interface): ?>
                            <option value="<?php echo $interface['name']; ?>"><?php echo $interface['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-agregar">Agregar IP</button>
            </form>

            <h2>Direcciones IP Configuradas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Dirección</th>
                        <th>Red</th>
                        <th>Interfaz</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ip_addresses as $address): ?>
                        <tr>
                            <td><?php echo $address['address']; ?></td>
                            <td><?php echo $address['network']; ?></td>
                            <td><?php echo $address['interface']; ?></td>
                            <td>
                                <!-- Botón de eliminar -->
                                <form method="POST" action="agregar_ips.php" style="display:inline;">
                                    <input type="hidden" name="action_type" value="delete_address">
                                    <input type="hidden" name="ip_id" value="<?php echo $address['.id']; ?>">
                                    <button type="submit" class="btn-eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar esta IP?');">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($message): ?>
            <div class="modal" id="myModal">
                <div class="modal-content">
                    <span class="close-button" onclick="closeModal()">&times;</span>
                    <p><?php echo $message; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <script>
            function closeModal() {
                document.getElementById("myModal").style.display = "none";
            }
            window.onload = function() {
                <?php if ($message): ?>
                    document.getElementById("myModal").style.display = "block";
                <?php endif; ?>
            }
        </script>
    </div>
</body>
</html>












