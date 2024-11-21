<?php
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

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    require_once '../lib/routeros_api.class.php';
    $router = new RouterosAPI();
    
    $action_type = $_POST['action_type'];

    if ($router->connect($host, $username, $password)) {
        if ($action_type === 'add_dns') {
            $dns_servers = $_POST['dns_servers'] ?? '';
            $dns_array = explode(',', $dns_servers);
            $valid_dns = true;
            foreach ($dns_array as $dns) {
                if (!filter_var(trim($dns), FILTER_VALIDATE_IP)) {
                    $valid_dns = false;
                    break;
                }
            }

            if (!$valid_dns) {
                $_SESSION['message'] = 'Dirección de servidor DNS no válida.';
                header("Location: agregar_DNS.php");
                exit();
            }

            // Configurar DNS
            $router->comm('/ip/dns/set', [
                'servers' => $dns_servers, 
                'allow-remote-requests' => 'yes',
            ]);
            $_SESSION['message'] = 'Servidor DNS agregado correctamente y se permite solicitudes remotas.';
        } elseif ($action_type === 'delete_dns' && isset($_POST['dns_to_delete'])) {
            // Eliminar DNS
            $dns_to_delete = $_POST['dns_to_delete'];
            $current_dns_servers = $router->comm('/ip/dns/print')[0]['servers'] ?? '';
            $dns_list = array_filter(explode(',', $current_dns_servers), fn($dns) => trim($dns) !== $dns_to_delete);
            $router->comm('/ip/dns/set', [
                'servers' => implode(',', $dns_list),
            ]);
            $_SESSION['message'] = 'Servidor DNS eliminado correctamente.';
        }
        $router->disconnect();
    } else {
        $_SESSION['message'] = 'Error al conectar a MikroTik.';
    }
    header("Location: agregar_DNS.php");
    exit();
}

// Obtener los servidores DNS actuales
$dns_servers = [];
require_once '../lib/routeros_api.class.php';
$router = new RouterosAPI();

if ($router->connect($host, $username, $password)) {
    $dns_response = $router->comm('/ip/dns/print');
    $router->disconnect();

    if (!empty($dns_response)) {
        foreach ($dns_response as $dns) {
            if (isset($dns['servers'])) {
                $dns_servers = explode(',', $dns['servers']);
            }
        }
    }
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
    <title>Agregar DNS</title>
    <link rel="stylesheet" href="../assets/css/agregar_DNS.css">
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h1>Agregar Servidor DNS</h1>
            <form method="POST" action="agregar_DNS.php">
                <input type="hidden" name="action_type" value="add_dns">
                <div class="input-group">
                    <label for="dns_servers">Servidores DNS (separados por coma)</label>
                    <input type="text" id="dns_servers" name="dns_servers" placeholder="Ej: 8.8.8.8, 8.8.4.4" required>
                </div>
                <button type="submit" class="btn-agregar">Agregar DNS</button>
            </form>
        </div>

        <div class="container">
            <h2>Servidores DNS Actuales</h2>
            <table>
                <thead>
                    <tr>
                        <th>Servidor DNS</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dns_servers)): ?>
                        <?php foreach ($dns_servers as $dns): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(trim($dns)); ?></td>
                                <td>
                                    <form method="POST" action="agregar_DNS.php" style="display:inline;">
                                        <input type="hidden" name="action_type" value="delete_dns">
                                        <input type="hidden" name="dns_to_delete" value="<?php echo htmlspecialchars(trim($dns)); ?>">
                                        <button type="submit" class="btn-eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">No hay servidores DNS configurados.</td>
                        </tr>
                    <?php endif; ?>
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




