<?php
header("Content-Type: application/json");

// DB CONNECTION
$conn = new mysqli("localhost", "ms", "ms", "ms");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// REQUIRED PARAMS — support both JSON and form-encoded bodies
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (!isset($input['user_id']) || !isset($input['proposal_id'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

$user_id     = intval($input['user_id']);
$proposal_id = intval($input['proposal_id']);

// Verify proposal belongs to user and is in a deletable state
$checkStmt = $conn->prepare(
    "SELECT id FROM proposals
     WHERE id = ?
       AND (sender_id = ? OR receiver_id = ?)
       AND status IN ('pending', 'rejected')
     LIMIT 1"
);
$checkStmt->bind_param("iii", $proposal_id, $user_id, $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Proposal not found or cannot be deleted"]);
    exit;
}

// Delete proposal
$deleteStmt = $conn->prepare("DELETE FROM proposals WHERE id = ?");
$deleteStmt->bind_param("i", $proposal_id);

if ($deleteStmt->execute()) {
    echo json_encode(["success" => true, "message" => "Proposal deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to delete proposal"]);
}

$conn->close();
?>
