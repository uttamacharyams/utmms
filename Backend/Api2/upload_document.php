<?php
header("Content-Type: application/json");

// ---------------- DB CONNECTION ----------------
$host   = "localhost";
$dbuser = "ms";
$pass   = "ms";
$dbname = "ms";

$conn = new mysqli($host, $dbuser, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB connect failed"]);
    exit;
}

// ---------------- REQUIRED PARAMS ----------------
$userid       = isset($_POST['userid'])       ? intval($_POST['userid'])       : 0;
$documenttype = isset($_POST['documenttype']) ? trim($_POST['documenttype'])   : '';

if ($userid <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid userid"]);
    exit;
}
if ($documenttype === '') {
    echo json_encode(["status" => "error", "message" => "documenttype is required"]);
    exit;
}

// ---------------- OPTIONAL PARAMS ----------------
$documentidnumber = $_POST['documentidnumber'] ?? null;

// ---------------- FILE UPLOAD ----------------
$photoPath = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

    $folder = "uploads/user_documents/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = "doc_" . $userid . "_" . time() . "." . $ext;
    $filepath = $folder . $filename;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
        $photoPath = $filepath;
    }
}

// ---------------- UPSERT: one row per (userid, documenttype) ----------------
// On duplicate key reset status to pending and clear reject_reason so the
// admin re-reviews the freshly uploaded document.
$sql = "INSERT INTO user_documents
            (userid, documenttype, documentidnumber, photo, status, reject_reason)
        VALUES (?, ?, ?, ?, 'pending', NULL)
        ON DUPLICATE KEY UPDATE
            documentidnumber = VALUES(documentidnumber),
            photo            = IFNULL(VALUES(photo), photo),
            status           = 'pending',
            reject_reason    = NULL,
            updated_at       = NOW()";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $userid, $documenttype, $documentidnumber, $photoPath);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Document uploaded and set to pending review"
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}

$conn->close();
?>
