<?php
// ==========================================
// 1. CONFIGURACIÓN DE CABECERAS (CORS & JSON) - ¡ESTO FALTABA!
// ==========================================
header("Access-Control-Allow-Origin: *"); // Permite que React (y cualquiera) se conecte
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Permite Login (POST) y Pre-chequeo (OPTIONS)
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo de la petición "preflight" (OPTIONS)
// El navegador pregunta antes de enviar los datos: "¿Puedo pasar?"
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==========================================
// 2. LÓGICA DE LOGIN ORIGINAL
// ==========================================
require 'vendor/autoload.php';
require_once 'db_connect.php';
use Firebase\JWT\JWT;

// TU LLAVE SECRETA (Asegúrate de que sea la misma que usas en validate_token.php)
$secret_key = "TU_SECRET_KEY_SUPER_COMPLEJA_Y_LARGA"; 

$data = json_decode(file_get_contents("php://input"));

// Validación rápida
if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos (email o password)"]);
    exit();
}

try {
    // Buscar usuario
    $sql = "SELECT user_id, username, password_hash FROM Users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data->email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar contraseña
    if ($user && password_verify($data->password, $user['password_hash'])) {
        
        // Crear Token
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // 1 hora
        $payload = array(
            'iss' => 'http://localhost',
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => array(
                'id' => $user['user_id'],
                'username' => $user['username']
            )
        );

        $jwt = JWT::encode($payload, $secret_key, 'HS256');

        echo json_encode([
            "message" => "Login exitoso",
            "token" => $jwt,
            "user" => [ 
                "username" => $user['username'],
                "email" => $data->email 
            ]
        ]);
    } else {
        http_response_code(401); 
        echo json_encode(["error" => "Email o contraseña incorrectos"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error del servidor: " . $e->getMessage()]);
}
?>