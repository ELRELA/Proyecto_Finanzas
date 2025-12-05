<?php
// validate_token.php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "TU_SECRET_KEY_SUPER_COMPLEJA_Y_LARGA"; // La misma del login

function validateToken() {
    global $secret_key;
    
    // 1. Obtener los headers
    $headers = apache_request_headers();
    $authHeader = null;

    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) { // A veces llega en minúscula
        $authHeader = $headers['authorization'];
    }

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(["error" => "Acceso denegado. Token no proporcionado."]);
        exit();
    }

    // El formato es "Bearer <token>"
    $arr = explode(" ", $authHeader);
    $jwt = $arr[1] ?? '';

    try {
        // 2. Decodificar y validar firma
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        
        // 3. Retornar los datos del usuario para usarlos en la app
        return $decoded->data;

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Acceso denegado. Token inválido o expirado."]);
        exit();
    }
}
?>