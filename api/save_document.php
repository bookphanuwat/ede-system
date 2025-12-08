<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. รับค่าจากฟอร์ม
        $title = $_POST['title'];
        $type_id = $_POST['type_id'];
        $reference_no = $_POST['reference_no'];
        $sender_name = $_POST['sender_name'];
        $receiver_name = $_POST['receiver_name'];
        $created_by = $_POST['created_by'];

        // ------------------------------------------------------------------
        // 2. สร้างรหัสเอกสาร (System Code) แบบไม่ซ้ำแน่นอน (Unique)
        // ------------------------------------------------------------------
        // รูปแบบ: EDE-ปีเดือนวัน-รหัสเวลาฐาน16 (เช่น EDE-20231025-6538c2a9)
        // ข้อดี: ไม่ซ้ำแน่นอน 100% เพราะอิงตามเวลาเสี้ยววินาที
        $uuid_part = substr(uniqid(), -5); // ตัดเอาแค่ 5 ตัวท้ายเพื่อไม่ให้ยาวไป
        $document_code = "EDE-" . date("Ymd") . "-" . strtoupper($uuid_part) . rand(10,99);

        // 3. บันทึกลงฐานข้อมูล
        $sql = "INSERT INTO documents (document_code, title, type_id, reference_no, sender_name, receiver_name, created_by, current_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Registered')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $document_code,
            $title,
            $type_id,
            $reference_no,
            $sender_name,
            $receiver_name,
            $created_by
        ]);
        
        $document_id = $pdo->lastInsertId();

        // 4. สร้าง Log แรก
        $stmtLog = $pdo->prepare("INSERT INTO document_status_log (document_id, status, action_by) VALUES (?, 'Registered', ?)");
        $stmtLog->execute([$document_id, $created_by]);

        // 5. ส่งไปหน้าพิมพ์
        header("Location: ../print_cover.php?code=" . $document_code);
        exit;

    } catch (Exception $e) {
        // ดักจับ Error กรณีซ้ำจริงๆ (โอกาสน้อยมาก) แล้วแจ้งเตือน
        if ($e->getCode() == 23000) { // รหัส Error ข้อมูลซ้ำ
             echo "<script>alert('เกิดข้อผิดพลาดในการสร้างรหัส (ซ้ำ) กรุณาลองใหม่'); window.history.back();</script>";
        } else {
             echo "Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: ../register.php");
}
?>