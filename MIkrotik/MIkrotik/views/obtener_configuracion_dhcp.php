<?php
session_start();

$username = $_SESSION['username'];
$password = $_SESSION['password'];
$host = $_SESSION['host'];
$dhcp_entries = [];

require_once '../lib/routeros_api.class.php';
$router = new RouterosAPI();

if ($router->connect($host, $username, $password)) {
    $dhcp_entries = $router->comm('/ip/dhcp-server/print');
    $router->disconnect();
}

?>

<table class="tabla-dhcp">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Interfaz</th>
            <th>Rango de Direcciones</th>
            <th>Tiempo de Concesión</th>
            <th>Pool de Direcciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($dhcp_entries)): ?>
            <?php foreach ($dhcp_entries as $entry): ?>
                <tr>
                    <td><?php echo $entry['.id']; ?></td>
                    <td><?php echo $entry['name']; ?></td>
                    <td><?php echo $entry['interface']; ?></td>
                    <td><?php echo $entry['address-pool']; ?></td>
                    <td><?php echo $entry['lease-time']; ?></td>
                    <td><?php echo $entry['address-pool']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No hay configuraciones de DHCP disponibles.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
