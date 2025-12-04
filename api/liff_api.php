<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if (!isset($pdo)) throw new Exception("Database connection failed");

    // --- ฟังก์ชัน 1: ค้นหาเอกสาร ---
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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $results]);
    }

    // --- ฟังก์ชัน 2: ดึงประวัติส่วนตัว (ตาม Line ID) ---
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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $results]);
    }

    // --- ฟังก์ชัน 3: ดูรายละเอียดเอกสาร + Timeline ---
    else if ($action === 'detail') {
        $code = $_GET['code'] ?? '';
        
        // ข้อมูลเอกสาร
        $stmt = $pdo->prepare("SELECT * FROM documents WHERE document_code = ?");
        $stmt->execute([$code]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) throw new Exception("ไม่พบเอกสาร");

        // Timeline
        $stmt_log = $pdo->prepare("SELECT l.*, u.fullname FROM document_status_log l LEFT JOIN users u ON l.action_by = u.user_id WHERE l.document_id = ? ORDER BY l.action_time DESC");
        $stmt_log->execute([$doc['document_id']]);
        $logs = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'doc' => $doc, 'logs' => $logs]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>