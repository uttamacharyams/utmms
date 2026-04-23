<?php
/**
 * app_settings.php
 *
 * Public GET endpoint that returns the global application call-tone settings.
 * Used by the Flutter admin app on startup to sync the currently active call
 * tone from the server.
 *
 * GET (no parameters required)
 *
 * Response:
 *   {
 *     "success": true,
 *     "data": {
 *       "call_tone_id":         "classic",
 *       "custom_call_tone_url":  "",
 *       "custom_call_tone_name": ""
 *     }
 *   }
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── DB credentials ────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'ms');
define('DB_USER', 'ms');
define('DB_PASS', 'ms');

// ── Connect ───────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Return sensible defaults if DB is unreachable so the app still starts
    echo json_encode([
        'success' => true,
        'data'    => [
            'call_tone_id'          => 'default',
            'custom_call_tone_url'  => '',
            'custom_call_tone_name' => '',
        ],
    ]);
    exit;
}

try {
    // Ensure table exists before querying
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_settings (
            `setting_key`   VARCHAR(100) NOT NULL PRIMARY KEY,
            `setting_value` TEXT,
            updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Fetch all relevant keys in one query
    $stmt = $pdo->prepare(
        "SELECT setting_key, setting_value FROM app_settings
         WHERE setting_key IN ('call_tone_id', 'custom_call_tone_url', 'custom_call_tone_name')"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'call_tone_id'          => $settings['call_tone_id']          ?? 'default',
            'custom_call_tone_url'  => $settings['custom_call_tone_url']  ?? '',
            'custom_call_tone_name' => $settings['custom_call_tone_name'] ?? '',
        ],
    ]);

} catch (PDOException $e) {
    error_log('app_settings error: ' . $e->getMessage());
    // Return defaults on error so the app can still start
    echo json_encode([
        'success' => true,
        'data'    => [
            'call_tone_id'          => 'default',
            'custom_call_tone_url'  => '',
            'custom_call_tone_name' => '',
        ],
    ]);
}
