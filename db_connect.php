<?php
// db_connect.php
$serverName = "PCSEBASTIAN\SQLEXPRESS"; // O tu IP/Nombre de servidor
$connectionOptions = array(
    "Database" => "PROYECT_FINANZAS", // Nombre de tu DB en SQL Server
    "Uid" => "sebastianadmin",              // Tu usuario de SQL Server
    "PWD" => "2781241" // Tu contraseña
);

try {
    // Usamos el driver sqlsrv
    $conn = new PDO("sqlsrv:Server=$serverName;Database={$connectionOptions['Database']}", 
                    $connectionOptions['Uid'], 
                    $connectionOptions['PWD']);
    
    // Configuración vital para robustez:
    // 1. Lanzar excepciones en caso de error (no fallar en silencio)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 2. Fetch como array asociativo por defecto
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // En producción, no hagas echo del error exacto al usuario, regístralo en un log.
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión a base de datos"]);
    exit();
}
