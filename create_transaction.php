<?php
// ==========================================
// CONFIGURACIÓN DE CABECERAS (CORS & JSON)
// ==========================================
header("Access-Control-Allow-Origin: *"); // En producción, pon tu dominio específico (ej: https://miapp.com)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo de pre-flight request (OPTIONS) para clientes como React/Angular
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==========================================
// IMPORTACIONES Y SEGURIDAD
// ==========================================
require_once 'db_connect.php';
require_once 'validate_token.php';

// 1. EL GUARDIÁN: Validar Token JWT
// Si el token es inválido o no existe, el script muere aquí automáticamente (exit).
// Si es válido, nos devuelve los datos del usuario desencriptados.
$userData = validateToken(); 
$userIdFromToken = $userData->id; // ESTE ES EL ID EN EL QUE CONFIAMOS

// ==========================================
// OBTENCIÓN Y VALIDACIÓN DE DATOS
// ==========================================
$data = json_decode(file_get_contents("php://input"));

// Validar que lleguen los datos mínimos necesarios
if (
    !isset($data->account_id) || 
    !isset($data->amount) || 
    !isset($data->type)
) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Datos incompletos. Se requiere account_id, amount y type."]);
    exit();
}

// Sanitización y Casting
$accountId = (int) $data->account_id;
$categoryId = isset($data->category_id) ? (int) $data->category_id : null;
$amount = (float) $data->amount;
$type = strtoupper(trim($data->type)); // INCOME, EXPENSE
$description = isset($data->description) ? trim($data->description) : '';

// Validaciones de Lógica de Negocio
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "El monto debe ser mayor a 0."]);
    exit();
}

if (!in_array($type, ['INCOME', 'EXPENSE'])) {
    http_response_code(400);
    echo json_encode(["error" => "Tipo de transacción inválido. Use INCOME o EXPENSE."]);
    exit(); // Nota: Transferencias requieren lógica adicional, lo mantenemos simple aquí.
}

// ==========================================
// LÓGICA DE TRANSACCIÓN ACID (SQL SERVER)
// ==========================================
try {
    // Iniciamos el "Modo Atómico"
    $conn->beginTransaction();

    // ---------------------------------------------------------
    // PASO 1: Verificar propiedad de la cuenta (Seguridad extra)
    // ---------------------------------------------------------
    // Antes de insertar nada, verificamos si la cuenta pertenece al usuario del token.
    $checkSql = "SELECT count(*) as count FROM Accounts WHERE account_id = ? AND user_id = ?";
    $stmtCheck = $conn->prepare($checkSql);
    $stmtCheck->execute([$accountId, $userIdFromToken]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        throw new Exception("La cuenta no existe o no te pertenece.", 403);
    }

    // ---------------------------------------------------------
    // PASO 2: Insertar la transacción histórica
    // ---------------------------------------------------------
    // Nota: Usamos GETDATE() que es nativo de SQL Server
    $sqlInsert = "INSERT INTO Transactions (account_id, category_id, amount, transaction_date, description, type, created_at) 
                  VALUES (?, ?, ?, GETDATE(), ?, ?, GETDATE())";
    
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->execute([
        $accountId,
        $categoryId,
        $amount,
        $description,
        $type
    ]);

    // ---------------------------------------------------------
    // PASO 3: Actualizar el saldo de la cuenta
    // ---------------------------------------------------------
    $balanceAdjustment = ($type === 'EXPENSE') ? -$amount : $amount;

    $sqlUpdate = "UPDATE Accounts 
                  SET current_balance = current_balance + ? 
                  WHERE account_id = ?";
    
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->execute([$balanceAdjustment, $accountId]);

    // Validación final: ¿Se afectó alguna fila?
    if ($stmtUpdate->rowCount() === 0) {
        throw new Exception("Error crítico al actualizar el saldo.");
    }

    // ---------------------------------------------------------
    // COMMIT: Confirmar cambios permanentemente
    // ---------------------------------------------------------
    $conn->commit();

    http_response_code(201); // Created
    echo json_encode([
        "message" => "Transacción registrada exitosamente",
        "details" => [
            "account_id" => $accountId,
            "amount_processed" => $balanceAdjustment,
            "type" => $type
        ]
    ]);

} catch (Exception $e) {
    // ---------------------------------------------------------
    // ROLLBACK: Deshacer todo si hubo error
    // ---------------------------------------------------------
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Manejo de errores diferenciado
    $errorCode = $e->getCode();
    if ($errorCode === 403) {
        http_response_code(403);
        echo json_encode(["error" => $e->getMessage()]);
    } else {
        // En producción, registra $e->getMessage() en un log interno, no lo muestres al usuario
        error_log($e->getMessage()); 
        http_response_code(500);
        echo json_encode(["error" => "Error interno al procesar la transacción."]);
    }
}
?>