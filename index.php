<?php
session_start();
// print_r($_SESSION); // Comment ไว้เพื่อความสวยงาม
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// ดึงข้อมูล User (ถ้าจำเป็นต้องใช้)
$user_role = $_SESSION['role'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เมนูหลัก - EDE System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        /* สไตล์พิเศษสำหรับหน้าเมนูหลัก */
        .menu-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border: none;
            height: 100%;
        }
        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
        }
        .menu-icon-box {
            width: 80px; height: 80px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem; color: white;
        }
        /* เพิ่มสีพื้นหลังสำหรับ header-menu ถ้ายังไม่มีใน style.css */
        .header-menu {
            background: linear-gradient(45deg, #6c757d, #495057); /* สีเทาแบบ Secondary */
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper w-100 d-flex flex-column">
        
        <!-- เรียกใช้ Topbar -->
        <?php 
            $page_title = "เมนูหลัก (Main Menu)";
            $header_class = "header-menu"; // กำหนด class ใหม่สำหรับหน้าเมนู
            include 'includes/topbar.php'; 
        ?>

        <!-- Page Content -->
        <div class="page-content bg-light flex-grow-1">
            <div class="container py-4">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-secondary">ยินดีต้อนรับสู่ระบบ EDE</h2>
                    <p class="text-muted">กรุณาเลือกเมนูที่ต้องการใช้งาน</p>
                </div>

                <div class="row g-4 justify-content-center">

                    <!-- 1. Dashboard -->
                    <div class="col-md-6 col-lg-3">
                        <a href="dashboard.php" class="text-decoration-none">
                            <div class="card menu-card shadow-sm rounded-4 p-4 text-center">
                                <div class="menu-icon-box shadow-sm" style="background: var(--color-dashboard, #0d6efd);">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <h5 class="fw-bold text-dark">Dashboard</h5>
                                <p class="text-muted small">ภาพรวมและสถิติ</p>
                            </div>
                        </a>
                    </div>

                    <!-- 2. ลงทะเบียน -->
                    <div class="col-md-6 col-lg-3">
                        <a href="register.php" class="text-decoration-none">
                            <div class="card menu-card shadow-sm rounded-4 p-4 text-center">
                                <div class="menu-icon-box shadow-sm" style="background: var(--color-register, #198754);">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <h5 class="fw-bold text-dark">ลงทะเบียน</h5>
                                <p class="text-muted small">สร้างเอกสารใหม่</p>
                            </div>
                        </a>
                    </div>

                    <!-- 3. ติดตามเอกสาร -->
                    <div class="col-md-6 col-lg-3">
                        <a href="tracking.php" class="text-decoration-none">
                            <div class="card menu-card shadow-sm rounded-4 p-4 text-center">
                                <div class="menu-icon-box shadow-sm" style="background: var(--color-tracking, #0dcaf0);">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h5 class="fw-bold text-dark">ติดตามเอกสาร</h5>
                                <p class="text-muted small">ค้นหาและตรวจสอบ</p>
                            </div>
                        </a>
                    </div>

                    <!-- 4. รายงาน -->
                    <div class="col-md-6 col-lg-3">
                        <a href="report.php" class="text-decoration-none">
                            <div class="card menu-card shadow-sm rounded-4 p-4 text-center">
                                <div class="menu-icon-box shadow-sm" style="background: var(--color-report, #ffc107);">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h5 class="fw-bold text-dark">รายงาน</h5>
                                <p class="text-muted small">สรุปยอดประจำเดือน</p>
                            </div>
                        </a>
                    </div>

                    <!-- 5. ตั้งค่าระบบ (ผู้ใช้งาน) -->
                    <div class="col-md-6 col-lg-3">
                        <a href="settings.php" class="text-decoration-none">
                            <div class="card menu-card shadow-sm rounded-4 p-4 text-center">
                                <div class="menu-icon-box shadow-sm" style="background: var(--color-settings, #6c757d);">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h5 class="fw-bold text-dark">ตั้งค่าระบบ</h5>
                                <p class="text-muted small">จัดการผู้ใช้งาน</p>
                            </div>
                        </a>
                    </div>

                    <!-- 6. ประวัติการสแกน -->
                    <div class="col-md-6 col-lg-3">
                        <a href="scan_history.php" class="text-decoration-none">
                            <div class="card menu-card shadow-sm rounded-4 p-4 text-center">
                                <div class="menu-icon-box shadow-sm" style="background: #fd7e14;">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h5 class="fw-bold text-dark">ประวัติการสแกน</h5>
                                <p class="text-muted small">ดูประวัติการดำเนินการ</p>
                            </div>
                        </a>
                    </div>

                    <!-- 7. ตั้งค่า Workflow -->
                    <div class="col-md-6 col-lg-3">
                        <a href="workflow_settings.php" class="text-decoration-none">
                            <div class="card menu-card shadow-sm rounded-4 p-4 text-center">
                                <div class="menu-icon-box shadow-sm" style="background: #6f42c1;">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <h5 class="fw-bold text-dark">ตั้งค่า Workflow</h5>
                                <p class="text-muted small">กำหนดหมวดหมู่สถานะ</p>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>