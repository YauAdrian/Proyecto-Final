<?php
require_once '../lib/routeros_api.class.php';
session_start();

$host = $_SESSION['host'];
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$output = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['command'])) {
    $command = trim($_POST['command']);
    $router = new RouterosAPI();

    // Intentar conectarse al MikroTik
    try {
        $router->connect($host, $username, $password);
        // Comando ping
        if (stripos($command, 'ping') !== false) {
            $parts = explode(' ', $command);
            if (count($parts) === 2) {
                $address = $parts[1];
                $result = $router->comm('/ping', ['address' => $address]);
            } else {
                $output = "Error: formato de comando ping no válido.";
            }
        } else {
            // Ejecutar el comando ingresado
            $result = $router->comm($command);
        }
        
        // Manejar la salida
        if (isset($result['!trap'])) {
            $output .= "Error: " . htmlspecialchars($result['!trap'][0]['message']) . "<br />";
        } else {
            $output .= formatOutput($command, $result);
        }
    } catch (Exception $e) {
        $output = "Error al conectar o ejecutar el comando: " . $e->getMessage();
        error_log("Comando: $command - Error: " . $e->getMessage());
    } finally {
        $router->disconnect(); // Asegurarse de desconectar
    }

    echo json_encode(["output" => nl2br(htmlspecialchars($output))]);
    exit;
}

// Función para formatear la salida según el comando
function formatOutput($command, $result) {
    $output = "";

    // Manejar la salida para el comando de direcciones IP
    if (stripos($command, 'ip address') !== false) {
        $output .= "Flags: D - DYNAMIC<br />Columns: ADDRESS, NETWORK, INTERFACE<br />";
        $output .= "#   ADDRESS            NETWORK        INTERFACE<br />";

        foreach ($result as $index => $row) {
            $flags = isset($row['dynamic']) && $row['dynamic'] ? 'D' : ''; // DYNAMIC flag
            $address = htmlspecialchars($row['address']);
            $network = htmlspecialchars($row['network']);
            $interface = htmlspecialchars($row['interface']);
            $output .= sprintf("%-3d %s %s %s<br />", $index, $flags, $address, $network, $interface);
        }
    } elseif (stripos($command, 'ping') !== false) {
        // Formato para la salida de ping
        if (isset($result['!trap'])) {
            // Manejar caso de error en ping
            $output .= "Error: " . htmlspecialchars($result['!trap'][0]['message']) . "<br />";
        } else {
            // Asumimos que $result es un array de respuestas del ping
            foreach ($result as $row) {
                if (is_array($row)) {
                    // Imprimir solo si es un array
                    $output .= htmlspecialchars(print_r($row, true)) . "<br />";
                } else {
                    $output .= htmlspecialchars($row) . "<br />";
                }
            }
        }
    } else {
        // Para otros comandos, simplemente imprimir la salida
        if (is_array($result)) {
            $output .= htmlspecialchars(print_r($result, true)); // Manejar la salida como array
        } else {
            $output .= htmlspecialchars($result); // Manejar como string
        }
    }

    return $output;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal MikroTik</title>
    <link rel="stylesheet" href="../assets/css/terminal.css">
</head>
<body>
    <div class="container">
        <h1>Terminal MikroTik</h1>
        
        <div id="terminal-output">
            <!-- Aquí aparecerán los resultados de los comandos -->
        </div>

        <!-- Input de la terminal -->
        <form id="terminal-form">
            <label for="command-input">[admin@MikroTik] > </label>
            <input type="text" id="command-input" placeholder="Escribe un comando" autocomplete="off" autofocus>
            <button type="submit" style="display: none;">Enviar</button>
        </form>
    </div>

    <!-- JavaScript inline -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const terminalForm = document.getElementById("terminal-form");
            const commandInput = document.getElementById("command-input");
            const terminalOutput = document.getElementById("terminal-output");

            terminalForm.addEventListener("submit", async (event) => {
                event.preventDefault();
                const command = commandInput.value.trim();

                if (command) {
                    // Mostrar el comando en el terminal antes de enviarlo
                    const userCommand = document.createElement("div");
                    userCommand.textContent = `[admin@MikroTik] > ${command}`;
                    terminalOutput.appendChild(userCommand);

                    // Enviar comando al servidor
                    const response = await fetch("terminal.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({ command: command })
                    });

                    const data = await response.json();

                    // Mostrar el resultado en el terminal
                    const outputResult = document.createElement("div");
                    outputResult.textContent = data.output;
                    terminalOutput.appendChild(outputResult);

                    // Limpiar el input y hacer scroll hacia abajo
                    commandInput.value = "";
                    terminalOutput.scrollTop = terminalOutput.scrollHeight;
                }
            });
        });
    </script>
</body>
</html>



