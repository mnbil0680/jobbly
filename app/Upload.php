<?php
// **================================================**
// ** File: Upload.php                               **
// ** Responsibility: Handle file uploads            **
// **================================================**

header('Content-Type: application/json');
session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/DB_Ops.php';

$userId = $_SESSION['user_id'];
$type = $_POST['type'] ?? 'photo'; // 'photo' or 'cv'
$file = $_FILES['file'] ?? null;

if (!$file) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$uploadDir = __DIR__ . '/assets/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = $type . '_' . $userId . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $filename;
$relativeUrl = 'assets/uploads/' . $filename;

// Basic validation
$allowedPhoto = ['jpg', 'jpeg', 'png', 'gif'];
$allowedCV = ['pdf', 'doc', 'docx'];

if ($type === 'photo' && !in_array(strtolower($extension), $allowedPhoto)) {
    echo json_encode(['success' => false, 'message' => 'Invalid photo type']);
    exit;
}

if ($type === 'cv' && !in_array(strtolower($extension), $allowedCV)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CV type']);
    exit;
}

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $db = new JobsDatabase();
    $data = [];
    if ($type === 'photo') {
        $data['profile_photo'] = $relativeUrl;
    } else {
        $data['cv_path'] = $relativeUrl;
    }
    
    if ($db->updateUserInfo($userId, $data)) {
        echo json_encode(['success' => true, 'path' => $relativeUrl]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}