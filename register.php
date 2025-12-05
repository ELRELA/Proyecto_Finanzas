<?php
// register.php
header("Content-Type: application/json");
require_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username, $data->email, $data->password)) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos"]);
    exit();
}

// 1. Validar Email
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["error" => "Email inválido"]);
    exit();
}

// 2. Hashear el Password (NIVEL COMERCIAL)
// PASSWORD_DEFAULT usa el algoritmo más fuerte disponible en tu versión de PHP.
$passwordHash = password_hash($data->password, PASSWORD_DEFAULT);

try {
    // 3. Insertar en SQL Server
    $sql = "INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data->username, $data->email, $passwordHash]);

    http_response_code(201);
    echo json_encode(["message" => "Usuario registrado exitosamente"]);

} catch (PDOException $e) {
    // Código 23000 suele ser violación de llave única (email repetido)
    if ($e->getCode() == 23000) {
        http_response_code(409); // Conflict
        echo json_encode(["error" => "El usuario o email ya existe"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error interno"]);
    }
}
?>