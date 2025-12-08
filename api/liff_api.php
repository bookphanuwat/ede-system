<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if (!isset($pdo)) throw new Exception("Database connection failed");

    // --- 1. ค้นหาเอกสาร ---
    if ($action === 'search') {
        $keyword = $_GET['keyword'] ?? '';
        if (empty($keyword)) throw new Exception("ระบุคำค้นหา");

        $sql = "SELECT d.*, dt.type_name 
                FROM documents d
                LEFT JOIN document_type dt ON d.type_id = dt.type_id
                WHERE d.document_code LIKE ? OR d.title LIKE ?
                ORDER BY d.created_at DESC LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$keyword%", "%$keyword%"]);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // --- 2. ประวัติการสแกน (ของคนนั้น) ---
    else if ($action === 'history') {
        $line_id = $_GET['line_id'] ?? '';
        if (empty($line_id)) throw new Exception("No Line ID");

        $sql = "SELECT l.*, d.title, d.document_code 
                FROM document_status_log l
                JOIN documents d ON l.document_id = d.document_id
                WHERE l.line_user_id_action = ?
                ORDER BY l.action_time DESC LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$line_id]);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // --- 3. ดึงสถานะ (Updated: ตามคนสร้างเอกสาร) ---
    else if ($action === 'get_statuses') {
        // รับ ID ของคนสร้างเอกสาร (Creator) ที่ส่งมาจากหน้าบ้าน
        $creator_id = $_GET['creator_id'] ?? 0;

        // ดึงสถานะ: เอาของส่วนกลาง (NULL) + ของคนสร้างเอกสารนี้ ($creator_id)
        $sql = "SELECT * FROM document_statuses WHERE created_by IS NULL";
        $params = [];

        if ($creator_id > 0) {
            $sql .= " OR created_by = ?";
            $params[] = $creator_id;
        }
        
        $sql .= " ORDER BY status_id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $statuses]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>