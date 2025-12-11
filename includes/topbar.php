<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ดึงข้อมูล User (Fallback ถ้าไม่มี session)
$current_user = $_SESSION['fullname'] ?? 'Admin System';
$user_role = $_SESSION['role'] ?? 'Administrator';

// กำหนดไอคอนตามหน้า (เพิ่ม header-menu เข้าไปเพื่อให้แสดงไอคอนเมนูหลัก)
$icon_map = [
    'header-dashboard' => 'fas fa-home',
    'header-register'  => 'fas fa-edit',
    'header-tracking'  => 'fas fa-search',
    'header-report'    => 'fas fa-chart-bar',
    'header-settings'  => 'fas fa-cog',
    'header-workflow'  => 'fas fa-project-diagram', // เพิ่มไอคอนสำหรับ Workflow
    'header-menu'      => 'fas fa-th-large'
];

// ถ้าไม่มีใน map ให้ใช้ไอคอนกระดาษ (file)
$icon = $icon_map[$header_class] ?? 'fas fa-file';
?>

<!-- Topbar HTML -->
<div class="top-header <?php echo $header_class; ?> d-flex justify-content-between align-items-center px-4 py-3 shadow-sm text-white">
    <!-- ชื่อหน้าและไอคอน -->
    <div class="d-flex align-items-center">
        <i class="<?php echo $icon; ?> fa-lg me-3"></i>
        <h4 class="mb-0 fw-bold"><?php echo $page_title; ?></h4>
    </div>

    <!-- โปรไฟล์มุมขวา -->
    <div class="dropdown">
        <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="text-end me-3 d-none d-md-block">
                <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo $current_user; ?></div>
                <small style="font-size: 0.75rem; opacity: 0.9;"><?php echo ucfirst($user_role); ?></small>
            </div>
            <div class="bg-white text-primary rounded-circle d-flex justify-content-center align-items-center fw-bold shadow-sm" style="width: 45px; height: 45px; font-size: 1.2rem; color: #555 !important;">
                <?php echo mb_substr($current_user, 0, 1); ?>
            </div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end mt-2">
            <li><h6 class="dropdown-header">จัดการบัญชี</h6></li>
            <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
        </ul>
    </div>
</div>