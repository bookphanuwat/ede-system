<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
require_once '../config/db.php';

// ตรวจสอบสิทธิ์ (ถ้ามีระบบ Login แล้ว ควรเปิดบรรทัดนี้)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') { die("Access Denied"); }

if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    // จัดการเมื่อ ID ไม่ถูกต้อง เช่น redirect กลับ
    header("Location: ../settings.php");
    exit;
}

    try {
        // 1. ห้ามลบตัวเอง
        if (isset($_SESSION['user_id']) && $id == $_SESSION['user_id']) {
            echo "<script>alert('❌ ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้'); window.location.href='../settings.php';</script>";
            exit;
        }

        // 1.5 ป้องกันการลบ Admin คนสุดท้าย
        // ตรวจสอบก่อนว่าผู้ใช้ที่จะลบเป็น Admin หรือไม่
        $stmtRole = $pdo->prepare("SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?");
        $stmtRole->execute([$id]);
        $targetUser = $stmtRole->fetch(PDO::FETCH_ASSOC);

        if ($targetUser && stripos($targetUser['role_name'], 'admin') !== false) {
            // ถ้าระบุว่าเป็น Admin ให้เช็คจำนวน Admin ทั้งหมดในระบบ
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name LIKE '%Admin%'");
            $adminCount = $stmtCount->fetchColumn();

            if ($adminCount <= 1) {
                echo "<script>alert('❌ ไม่สามารถลบได้ เนื่องจากเป็นผู้ดูแลระบบ (Admin) คนสุดท้ายของระบบ'); window.location.href='../settings.php';</script>";
                exit;
            }
        }

        // เริ่มต้น Transaction (เพื่อให้ทำงานต่อเนื่องกัน ถ้าพลาดให้ยกเลิกหมด)
        $pdo->beginTransaction();

        // 2. ปลดชื่อออกจากประวัติการสแกน (document_status_log)
        // เปลี่ยน action_by ให้เป็น NULL แทนการลบแถวประวัติทิ้ง
        $stmt = $pdo->prepare("UPDATE document_status_log SET action_by = NULL WHERE action_by = ?");
        $stmt->execute([$id]);

        // 3. ปลดชื่อออกจากเอกสารที่เคยสร้าง (documents) 
        // เปิดใช้งานส่วนนี้ เพื่อป้องกัน Error ในตาราง documents ด้วย
        $stmt = $pdo->prepare("UPDATE documents SET created_by = NULL WHERE created_by = ?");
        $stmt->execute([$id]);

        // 4. ทำการลบ User
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$id]);

        // ยืนยันการทำงาน
        $pdo->commit();
        
        echo "<script>alert('✅ ลบข้อมูลเรียบร้อยแล้ว'); window.location.href='../settings.php';</script>";

    } catch (PDOException $e) {
        // ถ้ายกเลิกกลางคัน ให้ย้อนกลับค่าเดิม
        $pdo->rollBack();

        // เช็ค Error เฉพาะกรณี (เช่น ติด Foreign Key ของตารางอื่นอีก)
        if ($e->getCode() == '23000') {
            echo "<script>
                alert('⚠️ ไม่สามารถลบได้เนื่องจากติดข้อกำหนดของฐานข้อมูล\\n(กรุณาตั้งค่า Database ให้ action_by/created_by รองรับค่า NULL ก่อน)'); 
                window.location.href='../settings.php';
            </script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: ../settings.php");
}
?>