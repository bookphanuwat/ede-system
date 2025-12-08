<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    // --- 1. ดึงรายชื่อสถานะ (GET) ---
    if ($action === 'list') {
        // ดึงสถานะของระบบ (NULL) + สถานะที่ User คนนี้สร้างเอง
        $sql = "SELECT * FROM document_statuses 
                WHERE created_by IS NULL OR created_by = ? 
                ORDER BY created_by ASC, status_id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $statuses]);
    }

    // --- 2. เพิ่มสถานะใหม่ (POST) ---
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
        $name = trim($_POST['status_name']);
        $color = $_POST['color_class'] ?? 'secondary';

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'ระบุชื่อสถานะ']);
            exit;
        }

        // เช็คซ้ำ
        $check = $pdo->prepare("SELECT COUNT(*) FROM document_statuses WHERE status_name = ? AND (created_by IS NULL OR created_by = ?)");
        $check->execute([$name, $user_id]);
        if ($check->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'ชื่อสถานะนี้มีอยู่แล้ว']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO document_statuses (status_name, color_class, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $color, $user_id]);

        echo json_encode(['success' => true, 'message' => 'เพิ่มสถานะเรียบร้อย']);
    }

    // --- 3. ลบสถานะ (DELETE/GET) ---
    elseif ($action === 'delete') {
        $id = $_GET['id'];
        
        // ลบได้เฉพาะอันที่ตัวเองสร้าง (created_by = user_id) ห้ามลบของระบบ (NULL)
        $stmt = $pdo->prepare("DELETE FROM document_statuses WHERE status_id = ? AND created_by = ?");
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'ลบเรียบร้อย']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ลบไม่ได้ (อาจเป็นสถานะของระบบ)']);
        }
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>