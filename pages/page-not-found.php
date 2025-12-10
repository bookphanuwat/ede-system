<?php
// หน้า 404 - ไม่พบหน้าที่ต้องการ
?>
<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="top-header bg-danger bg-gradient d-flex justify-content-between align-items-center px-4 py-3 shadow-sm">
            <div class="d-flex align-items-center text-white">
                <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
                <h4 class="mb-0 fw-bold">404 - ไม่พบหน้าที่ต้องการ</h4>
            </div>
        </div>

        <div class="page-content bg-light">
            <div class="container py-5">
                <div class="text-center">
                    <i class="fas fa-exclamation-circle text-danger" style="font-size: 100px;"></i>
                    <h2 class="fw-bold text-secondary mt-4">ขออภัย! ไม่พบหน้าที่คุณต้องการ</h2>
                    <p class="text-muted">หน้าที่คุณพยายามเข้าถึงไม่มีอยู่ในระบบ</p>
                    <a href="index.php" class="btn btn-primary mt-3"><i class="fas fa-home me-2"></i>กลับสู่หน้าหลัก</a>
                </div>
            </div>
        </div>
    </div>
</div>
