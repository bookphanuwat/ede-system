<?php
require_once '../config/db.php';

// ฟังก์ชันหา IP Address
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED'])) $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR'])) $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED'])) $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR'])) $ipaddress = $_SERVER['REMOTE_ADDR'];
    return $ipaddress;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_code = $_POST['doc_code'] ?? '';
    $new_status = $_POST['status'] ?? 'Received';
    $next_receiver = $_POST['receiver_name'] ?? '';
    $line_user_id = $_POST['line_user_id'] ?? '';
    
    // รับข้อมูล Log เพิ่มเติม
    $device_info = $_POST['device_info'] ?? 'Unknown';
    $display_name = $_POST['display_name'] ?? 'Unknown User';
    $picture_url = $_POST['picture_url'] ?? '';
    $ip_address = get_client_ip();

    if (empty($doc_code)) die("Error: No Code");

    try {
        $pdo->beginTransaction();

        // 1. หา ID เอกสาร
        $stmt = $pdo->prepare("SELECT document_id FROM documents WHERE document_code = ?");
        $stmt->execute([$doc_code]);
        $doc = $stmt->fetch();
        if (!$doc) throw new Exception("ไม่พบเอกสาร");
        $doc_id = $doc['document_id'];

        // 2. อัปเดตสถานะหลัก
        $sql = "UPDATE documents SET current_status = ?";
        $params = [$new_status];
        if (!empty($next_receiver)) {
            $sql .= ", receiver_name = ?";
            $params[] = $next_receiver;
        }
        $sql .= " WHERE document_id = ?";
        $params[] = $doc_id;
        
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute($params);

        // 3. บันทึก Log แบบละเอียด
        // หา user_id ในระบบถ้ามี (Optional)
        $u_stmt = $pdo->prepare("SELECT user_id FROM users WHERE line_user_id = ?");
        $u_stmt->execute([$line_user_id]);
        $user = $u_stmt->fetch();
        $action_by = $user ? $user['user_id'] : NULL;

        $log_note = !empty($next_receiver) ? "ส่งต่อให้: $next_receiver" : "อัปเดตสถานะ";

        $sqlLog = "INSERT INTO document_status_log 
                   (document_id, status, action_by, line_user_id_action, location_note, 
                    ip_address, device_info, actor_name_snapshot, actor_pic_snapshot) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                   
        $stmtLog = $pdo->prepare($sqlLog);
        $stmtLog->execute([
            $doc_id, $new_status, $action_by, $line_user_id, $log_note,
            $ip_address, $device_info, $display_name, $picture_url
        ]);

        $pdo->commit();
        echo "Success";

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }
}
?>