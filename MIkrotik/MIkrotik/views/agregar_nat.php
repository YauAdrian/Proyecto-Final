<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Obtener los datos de la sesión del usuario
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$host = $_SESSION['host'];

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $action_type = $_POST['action_type'];
    $chain = $_POST['chain'] ?? '';
    $action = $_POST['action'] ?? '';
    $interface = $_POST['interface'] ?? '';
    $nat_id = $_POST['nat_id'] ?? '';

    require_once '../lib/routeros_api.class.php';
    $router = new RouterosAPI();

    if ($router->connect($host, $username, $password)) {
        if ($action_type === 'add_nat') {
            // Agregar regla NAT
            $router->comm('/ip/firewall/nat/add', [
                'chain' => $chain,
                'action' => $action,
                'out-interface' => $interface,
            ]);
            $_SESSION['message'] = 'Regla NAT agregada correctamente.';
        } elseif ($action_type === 'delete_nat') {
            // Eliminar regla NAT
            $router->comm('/ip/firewall/nat/remove', [
                '.id' => $nat_id,
            ]);
            $_SESSION['message'] = 'Regla NAT eliminada correctamente.';
        }

        $router->disconnect();
    } else {
        $_SESSION['message'] = 'Error al conectar a MikroTik.';
    }
    header("Location: agregar_nat.php");
    exit();
}

// Obtener las reglas NAT configuradas en MikroTik
$nat_rules = [];
$interfaces = [];

require_once '../lib/routeros_api.class.php';
$router = new RouterosAPI();

if ($router->connect($host, $username, $password)) {
    // Obtener la lista de reglas NAT
    $nat_rules = $router->comm('/ip/firewall/nat/print');

    // Obtener la lista de interfaces
    $interfaces = $router->comm('/interface/print');

    $router->disconnect();
} else {
    $_SESSION['message'] = 'Error al conectar a MikroTik.';
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
    <title>Agregar NAT</title>
    <link rel="stylesheet" href="../assets/css/agregar_ips.css">
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h1>Agregar Regla NAT</h1>
            <form method="POST" action="agregar_nat.php">
                <input type="hidden" name="action_type" value="add_nat">
                <div class="input-group">
                    <label for="chain">Cadena (Chain)</label>
                    <select id="chain" name="chain" required>
                        <option value="srcnat">srcnat</option>
                        <option value="dstnat">dstnat</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="action">Acción (Action)</label>
                    <select id="action" name="action" required>
                        <option value="masquerade">Masquerade</option>
                        <option value="accept">Accept</option>
                        <option value="drop">Drop</option>
                        <!-- Agregar más opciones de acción si es necesario -->
                    </select>
                </div>
                <div class="input-group">
                    <label for="interface">Interfaz de salida (Out Interface)</label>
                    <select id="interface" name="interface" required>
                        <?php foreach ($interfaces as $interface): ?>
                            <option value="<?php echo $interface['name']; ?>"><?php echo $interface['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-agregar">Agregar NAT</button>
            </form>

            <h2>Reglas NAT Configuradas</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cadena</th>
                        <th>Acción</th>
                        <th>Interfaz de salida</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nat_rules as $rule): ?>
                        <tr>
                            <td><?php echo $rule['.id']; ?></td>
                            <td><?php echo $rule['chain']; ?></td>
                            <td><?php echo $rule['action']; ?></td>
                            <td><?php echo $rule['out-interface']; ?></td>
                            <td>
                                <!-- Botón de eliminar -->
                                <form method="POST" action="agregar_nat.php" style="display:inline;">
                                    <input type="hidden" name="action_type" value="delete_nat">
                                    <input type="hidden" name="nat_id" value="<?php echo $rule['.id']; ?>">
                                    <button type="submit" class="btn-eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar esta regla NAT?');">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para mostrar el mensaje -->
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
