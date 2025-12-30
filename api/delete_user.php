<?php
// 1. ตั้งค่า Session และ Security
ini_set('display_errors', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
session_start();

// 2. ตรวจสอบสิทธิ์ (Security Check)
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || stripos($_SESSION['role'], 'admin') === false) {
    header("Location: ../login.php");
    exit;
}

// 3. เชื่อมต่อฐานข้อมูล
// 3.1 เรียก config เพื่อใช้ $pdo (สำหรับ Transaction/Update/Delete)
require_once '../config/db.php'; 

$configPath = realpath(__DIR__ . '/../../dv-config.php');
if (file_exists($configPath)) {
    require_once $configPath;
    if (defined('DEV_PATH')) {
        require_once DEV_PATH . '/classes/db.class.v2.php';
        require_once DEV_PATH . '/functions/global.php';
    }
}

// 4. รับค่า ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: ../settings/");
    exit;
}

try {
    // --- ส่วนตรรกะ (Logic Checks) ใช้ CON ---

    // 4.1 ห้ามลบตัวเอง
    if ($id == $_SESSION['user_id']) {
        throw new Exception("ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้");
    }

    // 4.2 ป้องกันการลบ Admin คนสุดท้าย
    // ใช้ CON::selectArrayDB ดึงข้อมูล
    $sqlRole = "SELECT r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
    // หมายเหตุ: สังเกตว่า CON ของคุณรับ parameter เป็น (array $params, string $sql)
    $resultUser = CON::selectArrayDB([$id], $sqlRole); 
    $targetUser = $resultUser[0] ?? null;

    if ($targetUser && stripos($targetUser['role_name'], 'admin') !== false) {
        // ถ้าระบุว่าเป็น Admin ให้เช็คจำนวน Admin ทั้งหมดในระบบ
        $sqlCount = "SELECT COUNT(*) as c FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE r.role_name LIKE '%Admin%'";
        $resultCount = CON::selectArrayDB([], $sqlCount);
        $adminCount = $resultCount[0]['c'] ?? 0;

        if ($adminCount <= 1) {
            throw new Exception("ไม่สามารถลบได้ เนื่องจากเป็นผู้ดูแลระบบ (Admin) คนสุดท้ายของระบบ");
        }
    }

    // --- ส่วนเปลี่ยนแปลงข้อมูล (Write Operations) ใช้ $pdo เพื่อ Transaction ---
    $pdo->beginTransaction();

    // 5. ปลดชื่อออกจากประวัติ (Set NULL)
    // 5.1 ตาราง document_status_log
    $stmt = $pdo->prepare("UPDATE document_status_log SET action_by = NULL WHERE action_by = ?");
    $stmt->execute([$id]);

    // 5.2 ตาราง documents
    $stmt = $pdo->prepare("UPDATE documents SET created_by = NULL WHERE created_by = ?");
    $stmt->execute([$id]);

    // 6. ลบ User
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$id]);

    // ยืนยันการทำงาน
    $pdo->commit();
    
    header("Location: ../settings/?status=success&msg=" . urlencode('ลบผู้ใช้งานเรียบร้อยแล้ว'));
    exit;

} catch (Exception $e) {
    // ถ้ายกเลิกกลางคัน ให้ย้อนกลับค่าเดิม
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $errorMsg = $e->getMessage();
    
    // เช็ค Error SQL กรณีติด Constraint
    if ($e instanceof PDOException && $e->getCode() == '23000') {
        $errorMsg = 'ไม่สามารถลบได้ เนื่องจากข้อมูลนี้ถูกใช้งานอยู่ในระบบ (Foreign Key Constraint)';
    }

    header("Location: ../settings/?status=error&msg=" . urlencode($errorMsg));
    exit;
}
?>