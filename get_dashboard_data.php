<?php
// ==========================================
// 1. ZONA DE SEGURIDAD Y CORS
// ==========================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Si el navegador pregunta "¿Puedo pasar?" (OPTIONS), le decimos que sí y cortamos.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==========================================
// 2. CARGA DE SISTEMA
// ==========================================
require_once 'db_connect.php';
require_once 'validate_token.php';

// Validamos Token
$userData = validateToken();
$userId = $userData->id;

try {
    // ---------------------------------------------------
    // QUERY 1: Saldo Total
    // ---------------------------------------------------
    $sqlBalance = "SELECT SUM(current_balance) as total_balance, currency 
                   FROM Accounts 
                   WHERE user_id = ? 
                   GROUP BY currency";
    
    $stmt1 = $conn->prepare($sqlBalance);
    $stmt1->execute([$userId]);
    $balances = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------------------------------
    // QUERY 2: Últimas 5 Transacciones
    // ---------------------------------------------------
    $sqlRecent = "SELECT TOP 5 
                    t.transaction_id, 
                    t.amount, 
                    t.type, 
                    t.description, 
                    FORMAT(t.transaction_date, 'yyyy-MM-dd') as date,
                    c.name as category_name,
                    a.account_name
                  FROM Transactions t
                  INNER JOIN Accounts a ON t.account_id = a.account_id
                  LEFT JOIN Categories c ON t.category_id = c.category_id
                  WHERE a.user_id = ? 
                  ORDER BY t.transaction_date DESC, t.transaction_id DESC";

    $stmt2 = $conn->prepare($sqlRecent);
    $stmt2->execute([$userId]);
    $recentTransactions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------------------------------
    // QUERY 3: Gastos del Mes Actual (Para Gráfico 1)
    // ---------------------------------------------------
    $sqlChart = "SELECT c.name, SUM(t.amount) as total
                 FROM Transactions t
                 JOIN Accounts a ON t.account_id = a.account_id
                 JOIN Categories c ON t.category_id = c.category_id
                 WHERE a.user_id = ? 
                   AND t.type = 'EXPENSE'
                   AND MONTH(t.transaction_date) = MONTH(GETDATE()) 
                   AND YEAR(t.transaction_date) = YEAR(GETDATE())
                 GROUP BY c.name";

    $stmt3 = $conn->prepare($sqlChart);
    $stmt3->execute([$userId]);
    $monthlyChartData = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------------------------------
    // QUERY 4: Gastos de HOY (Para Gráfico 2)
    // ---------------------------------------------------
    // Usamos CAST(... AS DATE) para ignorar la hora y comparar solo el día
    $sqlDailyChart = "SELECT c.name, SUM(t.amount) as total
                      FROM Transactions t
                      JOIN Accounts a ON t.account_id = a.account_id
                      JOIN Categories c ON t.category_id = c.category_id
                      WHERE a.user_id = ? 
                        AND t.type = 'EXPENSE'
                        AND CAST(t.transaction_date AS DATE) = CAST(GETDATE() AS DATE)
                      GROUP BY c.name";

    $stmt4 = $conn->prepare($sqlDailyChart);
    $stmt4->execute([$userId]);
    $dailyChartData = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------------------------------
    // RESPUESTA JSON FINAL
    // ---------------------------------------------------
    echo json_encode([
        "status" => "success",
        "data" => [
            "net_worth" => $balances,
            "recent_activity" => $recentTransactions,
            "monthly_spending_chart" => $monthlyChartData,
            "daily_spending_chart" => $dailyChartData
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener datos: " . $e->getMessage()]);
}
?>