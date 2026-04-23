<?php
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include "../DB_connection.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $task_id = $data['task_id'] ?? 0;
    $new_status = $data['status'] ?? '';
    
    // Only valid statuses
    if (in_array($new_status, ['pending', 'in_progress', 'completed'])) {
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $res = $stmt->execute([$new_status, $task_id]);
        if($res) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update status']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
    }
}
?>
