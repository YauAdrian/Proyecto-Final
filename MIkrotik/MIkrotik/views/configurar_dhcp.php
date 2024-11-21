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
$dhcp_entries = [];
$interfaces = [];

require_once '../lib/routeros_api.class.php';
$router = new RouterosAPI();

// Intentar conectar a MikroTik
if ($router->connect($host, $username, $password)) {
    // Obtener los DHCP servers configurados y las interfaces disponibles
    $dhcp_entries = $router->comm('/ip/dhcp-server/print');
    $interfaces = $router->comm('/interface/print');
    $dhcp_networks = $router->comm('/ip/dhcp-server/network/print');
    $dhcp_pools = $router->comm('/ip/pool/print');

   // Procesar el formulario de eliminación de Redes DHCP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'delete_dhcp_network') {
    $network_id = $_POST['network_id'];

    // Verificar si el ID de la red a eliminar está configurado
    if ($network_id) {
        // Ejecutar el comando de eliminación de red DHCP en MikroTik
        $delete_network_result = $router->comm('/ip/dhcp-server/network/remove', [
            '.id' => $network_id,
        ]);

        // Verificar si ocurrió un error en la eliminación
        if (isset($delete_network_result['!trap'])) {
            $_SESSION['message'] = 'Error al eliminar la red DHCP: ' . htmlspecialchars($network_id);
        } else {
            $_SESSION['message'] = 'Red DHCP eliminada correctamente.';
        }

        // Recargar las configuraciones actualizadas
        $dhcp_networks = $router->comm('/ip/dhcp-server/network/print');
    }
}


    // Procesar el formulario de eliminación de Pool
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'delete_pool') {
        $pool_id = $_POST['pool_id'];

        // Verificar si el ID del pool a eliminar está configurado
        if ($pool_id) {
            // Ejecutar el comando de eliminación de pool en MikroTik
            $delete_pool_result = $router->comm('/ip/pool/remove', [
                '.id' => $pool_id,
            ]);

            // Verificar si ocurrió un error en la eliminación
            if (isset($delete_pool_result['!trap'])) {
                $_SESSION['message'] = 'Error al eliminar el pool de direcciones: ' . htmlspecialchars($pool_id);
            } else {
                $_SESSION['message'] = 'Pool de direcciones eliminado correctamente.';
            }

            // Volver a cargar los pools después de la eliminación
            $dhcp_pools = $router->comm('/ip/pool/print');
        }
    }

    // Procesar el formulario de eliminación de DHCP y sus redes
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'delete_dhcp') {
        $dhcp_name = $_POST['dhcp_name'];
        $network_address = $_POST['network_address'];

        // Comprobar si el nombre de DHCP a eliminar está configurado
        if ($dhcp_name) {
            // Ejecutar el comando para eliminar el DHCP en MikroTik
            $delete_dhcp_result = $router->comm('/ip/dhcp-server/remove', [
                '.id' => $dhcp_name,
            ]);
            // Verificar si ocurrió un error en la eliminación
            if (isset($delete_dhcp_result['!trap']) || isset($delete_network_result['!trap'])) {
                $_SESSION['message'] = 'Error al eliminar el servidor DHCP o la red asociada.';
            } else {
                $_SESSION['message'] = 'Servidor DHCP y red asociados eliminados correctamente.';
            }

            // Recargar las configuraciones actualizadas
            $dhcp_entries = $router->comm('/ip/dhcp-server/print');
        }
    }

    // Procesar el formulario de configuración de DHCP
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'setup_dhcp') {
        $interface = $_POST['interface'];
        $address_pool = $_POST['address_pool'];
        $dns_server = $_POST['dns_server'];
        $gateway = $_POST['gateway'];
        $network = $_POST['network'];
        $address_range = $_POST['address_range'];
        $lease_time = $_POST['lease_time'];

        // Crear el pool de direcciones solo si no existe
        $existingPools = $router->comm('/ip/pool/print');
        $poolExists = array_filter($existingPools, fn($pool) => $pool['name'] === $address_pool);
        
        if (!$poolExists) {
            $pool_result = $router->comm('/ip/pool/add', [
                'name' => $address_pool,
                'ranges' => $address_range,
            ]);
        }

        // Configurar la red DHCP solo si no existe
        $existingNetworks = $router->comm('/ip/dhcp-server/network/print');
        $networkExists = array_filter($existingNetworks, fn($net) => $net['address'] === $network);

        if (!$networkExists) {
            $network_result = $router->comm('/ip/dhcp-server/network/add', [
                'address' => $network,
                'gateway' => $gateway,
                'dns-server' => $dns_server,
            ]);
        }

        // Crear el servidor DHCP solo si no existe
        $dhcpExists = array_filter($dhcp_entries, fn($dhcp) => $dhcp['name'] === 'DHCP_' . $address_pool);
        
        if (!$dhcpExists) {
            $dhcp_result = $router->comm('/ip/dhcp-server/add', [
                'name' => 'DHCP_' . $address_pool,
                'interface' => $interface,
                'address-pool' => $address_pool,
                'lease-time' => $lease_time,
            ]);
        }

        // Comprobar si se produjeron errores en la creación de configuraciones
        $errorMessages = [];

        if (isset($pool_result['!trap'])) {
            $errorMessages[] = 'Error al crear el pool de direcciones.';
        }
        if (isset($network_result['!trap'])) {
            $errorMessages[] = 'Error al configurar la red DHCP.';
        }
        if (isset($dhcp_result['!trap'])) {
            $errorMessages[] = 'Error al crear el servidor DHCP.';
        }

        // Guardar el mensaje de éxito o error en la sesión
        if (empty($errorMessages)) {
            $_SESSION['message'] = 'Servidor DHCP configurado correctamente en la interfaz ' . htmlspecialchars($interface) . '.';
        } else {
            $_SESSION['message'] = implode(' ', $errorMessages);
        }

        

        // Volver a cargar los DHCP servers configurados después de agregar uno nuevo
        $dhcp_entries = $router->comm('/ip/dhcp-server/print'); 
        $dhcp_pools = $router->comm('/ip/pool/print');
        $dhcp_networks = $router->comm('/ip/dhcp-server/network/print');
        
    }

    // Desconectar del router
    $router->disconnect();
} else {
    $_SESSION['message'] = 'No se pudo conectar a MikroTik. Verifica la dirección IP, el usuario y la contraseña.';
}

// Mostrar el mensaje si existe
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

include('sidebar.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar DHCP</title>
    <link rel="stylesheet" href="../assets/css/configurar_dhcp.css">
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h1>Configurar Servidor DHCP</h1>
            <form id="dhcpForm" method="POST">
                <input type="hidden" name="action_type" value="setup_dhcp">
                <div class="input-group">
                    <label for="interface">Interfaz</label>
                    <select id="interface" name="interface" required>
                        <option value="">Seleccione una interfaz</option>
                        <?php foreach ($interfaces as $interface): ?>
                            <option value="<?php echo htmlspecialchars($interface['name']); ?>"><?php echo htmlspecialchars($interface['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="network">Red DHCP</label>
                    <input type="text" id="network" name="network" placeholder="Ej: 192.168.100.0/24" required>
                </div>
                <div class="input-group">
                    <label for="address_pool">Nombre del Pool de Direcciones</label>
                    <input type="text" id="address_pool" name="address_pool" placeholder="Ej: dhcp_pool" required>
                </div>
                <div class="input-group">
                    <label for="address_range">Rango de Direcciones</label>
                    <input type="text" id="address_range" name="address_range" placeholder="Ej: 192.168.100.10-192.168.100.100" required>
                </div>
                <div class="input-group">
                    <label for="dns_server">Servidor DNS</label>
                    <input type="text" id="dns_server" name="dns_server" placeholder="Ej: 8.8.8.8" required>
                </div>
                <div class="input-group">
                    <label for="gateway">Gateway</label>
                    <input type="text" id="gateway" name="gateway" placeholder="Ej: 192.168.100.1" required>
                </div>
                <div class="input-group">
                    <label for="lease_time">Tiempo de Conexión</label>
                    <input type="text" id="lease_time" name="lease_time" placeholder="Ej: 3d/00:30:00" required>
                </div>
                <button type="submit" class="btn-configurar">Configurar DHCP</button>
            </form>

            <!-- Modal para mostrar el mensaje -->
            <?php if ($message): ?>
                <div class="modal" id="myModal">
                    <div class="modal-content">
                        <span class="close-button" onclick="closeModal()">&times;</span>
                        <p><?php echo htmlspecialchars($message); ?></p>
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

            <!-- Tabla para mostrar las configuraciones actuales del servidor DHCP -->
<h2>Configuraciones del Servidor DHCP</h2>
<div id="tablaDHCP">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Interfaz</th>
                <th>Tiempo de Conexión</th>
                <th>Pool de Direcciones</th>
                <th>Acciones</th> <!-- Nueva columna para las acciones -->
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($dhcp_entries)): ?>
                <?php foreach ($dhcp_entries as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry['name']); ?></td>
                        <td><?php echo htmlspecialchars($entry['interface']); ?></td>
                        <td><?php echo htmlspecialchars($entry['lease-time']); ?></td>
                        <td><?php 
                            // Buscar el pool correspondiente
                            $pool_info = array_filter($dhcp_pools, fn($pool) => $pool['name'] === $entry['address-pool']);
                            $pool_info = reset($pool_info);
                            echo htmlspecialchars($pool_info['ranges'] ?? ''); 
                        ?></td>
                        <!-- Columna para el botón de eliminar -->
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action_type" value="delete_dhcp">
                                <input type="hidden" name="dhcp_name" value="<?php echo htmlspecialchars($entry['name']); ?>">
                                <input type="hidden" name="network_address" value="<?php echo htmlspecialchars($entry['network-id']); ?>">
                                <button type="submit" class="btn-eliminar" aria-label="Eliminar configuración DHCP">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No hay configuraciones de DHCP disponibles.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<!-- Segunda tabla para mostrar información de la red y el pool -->
<h2>Pools de Direcciones DHCP</h2>
<div id="tablaPools">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Rango</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($dhcp_pools)): ?>
                <?php foreach ($dhcp_pools as $pool): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pool['name']); ?></td>
                        <td><?php echo htmlspecialchars($pool['ranges']); ?></td>
                        <td>
                            <form method="POST" action=""> <!-- Cambiar la acción a tu script PHP -->
                                <input type="hidden" name="pool_id" value="<?php echo htmlspecialchars($pool['.id']); ?>">
                                <input type="hidden" name="action_type" value="delete_pool">
                                <button type="submit" class="btn-eliminar" onclick="return confirm('¿Está seguro de que desea eliminar este pool?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No hay pools de direcciones configurados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Segunda tabla para mostrar información de la red y el pool -->
<h2>Información de Redes y Pools de Direcciones</h2>
<div id="tablaRedes">
    <table>
        <thead>
            <tr>
                <th>Red</th>
                <th>Gateway</th>
                <th>Servidor DNS</th>
                <th>Acciones</th> <!-- Nueva columna para las acciones -->
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($dhcp_networks)): ?>
                <?php foreach ($dhcp_networks as $network): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($network['address']); ?></td>
                        <td><?php echo htmlspecialchars($network['gateway']); ?></td>
                        <td><?php echo htmlspecialchars($network['dns-server']); ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="network_id" value="<?php echo htmlspecialchars($network['.id']); ?>">
                                <input type="hidden" name="action_type" value="delete_dhcp_network">
                                <button type="submit" class="btn-eliminar" onclick="return confirm('¿Está seguro de que desea eliminar esta red?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No hay redes DHCP disponibles.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


















