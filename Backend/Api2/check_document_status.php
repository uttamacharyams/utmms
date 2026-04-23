<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host     = 'localhost';
$dbname   = 'ms';
$username = 'ms';
$password = 'ms';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Return one row per uploaded document for this user, including per-doc status.
    $stmt = $pdo->prepare("
        SELECT
            documenttype,
            status,
            reject_reason
        FROM user_documents
        WHERE userid = :user_id
        ORDER BY created_at ASC
    ");
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $documents = [];
    foreach ($rows as $row) {
        $documents[] = [
            'documenttype'  => $row['documenttype'],
            'status'        => $row['status'],
            'reject_reason' => $row['reject_reason'] ?? '',
        ];
    }

    echo json_encode([
        'success'   => true,
        'documents' => $documents,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>