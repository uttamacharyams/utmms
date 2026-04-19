<?php
header("Content-Type: application/json");

// ---------------- DB CONNECTION ----------------
$conn = new mysqli("localhost", "ms", "ms", "ms");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// GET POST DATA — support both JSON and form-encoded bodies
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (!isset($input['proposal_id'], $input['user_id'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

$proposalId = (int) $input['proposal_id'];
$userId     = (int) $input['user_id'];

try {

    // Fetch the proposal
    $sql = "SELECT id, sender_id, receiver_id, status FROM proposals WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proposalId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Proposal not found"]);
        exit;
    }

    $proposal = $result->fetch_assoc();

    if ($proposal['status'] !== 'pending') {
        echo json_encode(["success" => false, "message" => "Proposal already processed"]);
        exit;
    }

    // Authorization: sender or receiver may reject
    if ($proposal['sender_id'] != $userId && $proposal['receiver_id'] != $userId) {
        echo json_encode(["success" => false, "message" => "You are not authorized to reject this proposal"]);
        exit;
    }

    // Update status
    $updateSql  = "UPDATE proposals SET status = 'rejected' WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $proposalId);

    if (!$updateStmt->execute()) {
        echo json_encode(["success" => false, "message" => "Failed to reject proposal"]);
        exit;
    }

    // Notify the other party (optional, silently ignore failures)
    $otherUserId = ($proposal['sender_id'] == $userId)
        ? $proposal['receiver_id']
        : $proposal['sender_id'];

    try {
        $notifSql = "INSERT INTO notifications (user_id, title, message, type, reference_id, created_at)
                     VALUES (?, 'Proposal Rejected', 'Your proposal has been rejected', 'proposal', ?, NOW())";
        $notifStmt = $conn->prepare($notifSql);
        $notifStmt->bind_param("ii", $otherUserId, $proposalId);
        $notifStmt->execute();
    } catch (Exception $e) {
        // Ignore notification failure
    }

    echo json_encode(["success" => true, "message" => "Proposal rejected successfully"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error", "debug" => $e->getMessage()]);
}

$conn->close();
?>
