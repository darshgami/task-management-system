<?php
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include "../DB_connection.php";

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $task_id = $data['task_id'] ?? 0;
    $user_id = $_SESSION['id'];

    if ($action === 'start') {
        // Start a new timer log
        $stmt = $conn->prepare("INSERT INTO task_time_logs (task_id, user_id, start_time) VALUES (?, ?, NOW())");
        $stmt->execute([$task_id, $user_id]);
        $log_id = $conn->lastInsertId();
        
        // Update task status to in_progress if it was pending
        $stmtStatus = $conn->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ? AND status = 'pending'");
        $stmtStatus->execute([$task_id]);

        echo json_encode(['success' => true, 'log_id' => $log_id, 'message' => 'Timer started']);
    } elseif ($action === 'stop') {
        $log_id = $data['log_id'] ?? 0;
        
        // Stop timer, calculate duration
        $stmt = $conn->prepare("UPDATE task_time_logs SET end_time = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, start_time, NOW()) WHERE id = ? AND user_id = ? AND end_time IS NULL");
        $stmt->execute([$log_id, $user_id]);

        // Add duration to total_task time
        $stmtTime = $conn->prepare("SELECT duration_seconds FROM task_time_logs WHERE id = ?");
        $stmtTime->execute([$log_id]);
        $res = $stmtTime->fetch(PDO::FETCH_ASSOC);
        
        if ($res && $res['duration_seconds']) {
            $addTime = $conn->prepare("UPDATE tasks SET total_time = total_time + ? WHERE id = ?");
            $addTime->execute([$res['duration_seconds'], $task_id]);
            echo json_encode(['success' => true, 'message' => 'Timer stopped', 'added_seconds' => $res['duration_seconds']]);
        } else {
            echo json_encode(['error' => 'Log not found or already stopped']);
        }
    }
}
?>
