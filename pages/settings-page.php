<?php
    $page_title   = "ตั้งค่าระบบ";
    $header_class = "header-settings";
    
    // ตรวจสอบว่าไฟล์มีอยู่จริงไหม
    if (file_exists('includes/topbar.php')) {
        include 'includes/topbar.php';
    }

    // --- 1. ส่วนจัดการ Alert ---
    $alert_html = '';
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $msg = htmlspecialchars($_GET['msg'] ?? '', ENT_QUOTES, 'UTF-8');
        
        $alertType = ($status === 'success') ? 'success' : 'danger';
        $icon      = ($status === 'success') ? 'check-circle' : 'exclamation-circle';
        $title     = ($status === 'success') ? 'ดำเนินการสำเร็จ' : 'เกิดข้อผิดพลาด';

        $alert_html = "
        <div class='alert alert-{$alertType} alert-dismissible fade show shadow-sm border-0 border-start border-5 border-{$alertType} bg-white rounded-4 mb-4' role='alert'>
            <div class='d-flex align-items-center'>
                <div class='me-3 text-{$alertType}'>
                    <i class='fas fa-{$icon} fa-2x'></i>
                </div>
                <div>
                    <h6 class='fw-bold mb-0 text-{$alertType}'>{$title}</h6>
                    <div class='text-muted small'>{$msg}</div>
                </div>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }

    // --- 2. ดึงข้อมูล & Pagination ---
    $users = [];
    $is_admin = (isset($_SESSION['role']) && stripos($_SESSION['role'], 'admin') !== false);
    $current_user_id = $_SESSION['user_id'] ?? 0;

    // 2.1 ตั้งค่า Pagination
    $limit = 10; // แสดง 10 รายการต่อหน้า
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // 2.2 สร้างเงื่อนไข SQL พื้นฐาน
    $whereSQL = "";
    $params = [];

    if (!$is_admin) {
        $whereSQL = " WHERE u.user_id = ? ";
        $params[] = $current_user_id;
    }

    // 2.3 นับจำนวนข้อมูลทั้งหมด (Count)
    $totalRows = 0;
    if (class_exists('CON')) {
        $sqlCount = "SELECT COUNT(*) as total FROM users u $whereSQL";
        $resCount = CON::selectArrayDB($params, $sqlCount);
        $totalRows = $resCount[0]['total'] ?? 0;
    }
    
    // คำนวณจำนวนหน้าทั้งหมด
    $totalPages = ceil($totalRows / $limit);
    if ($totalPages == 0) $totalPages = 1; // บังคับให้เป็น 1 หน้าแม้ไม่มีข้อมูล

    // ปรับหน้าปัจจุบันไม่ให้เกินจำนวนหน้าที่มี
    if ($page > $totalPages) $page = $totalPages;

    // 2.4 ดึงข้อมูลตามหน้า (Query with Limit/Offset)
    $sql = "SELECT u.*, r.role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            $whereSQL 
            ORDER BY u.user_id ASC 
            LIMIT $limit OFFSET $offset";
    
    if (class_exists('CON')) {
        $users = CON::selectArrayDB($params, $sql) ?? [];
    }
?>

<div class="page-content">
    <?php echo $alert_html; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold text-secondary mb-0">⚙️ จัดการผู้ใช้งาน</h5>
        
        <?php if ($is_admin): ?>
        <a href="<?php echo SITE_URL; ?>/user" class="btn btn-success rounded-pill px-4 shadow-sm" style="background-color: #00E676; border:none; color:black; font-weight: bold;">
            <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้งานใหม่
        </a>
        <?php endif; ?>
    </div>

    <div class="table-responsive rounded-4 shadow-sm border">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light text-center border-bottom">
                <tr>
                    <th class="py-3 bg-light text-secondary">ชื่อ-สกุล</th>
                    <th class="py-3 bg-light text-secondary">แผนก</th>
                    <th class="py-3 bg-light text-secondary">สิทธิ์</th>
                    <th class="py-3 bg-light text-secondary" width="150">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): 
                        $userId     = $user['user_id'] ?? 0;
                        $fullname   = htmlspecialchars($user['fullname'] ?? '', ENT_QUOTES, 'UTF-8');
                        $username   = htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8');
                        $department = htmlspecialchars($user['department'] ?? '-', ENT_QUOTES, 'UTF-8');
                        $roleNameRaw = $user['role_name'] ?? '';
                        $roleName   = htmlspecialchars($roleNameRaw, ENT_QUOTES, 'UTF-8');

                        $badgeColor = 'bg-secondary';
                        if (stripos($roleNameRaw, 'admin') !== false) $badgeColor = 'bg-primary';
                        if (stripos($roleNameRaw, 'staff') !== false) $badgeColor = 'bg-info text-dark';
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?php echo $fullname; ?></div>
                            <div class="small text-muted"><i class="fas fa-user-circle me-1"></i><?php echo $username; ?></div>
                        </td>
                        <td class="text-center text-secondary"><?php echo $department; ?></td>
                        <td class="text-center"><span class="badge rounded-pill <?php echo $badgeColor; ?> px-3 py-2"><?php echo $roleName; ?></span></td>
                        <td class="text-center">
                            
                        <a href="<?php echo SITE_URL; ?>/user/<?php echo $userId; ?>" 
                        class="btn btn-sm btn-light rounded-pill border me-1 text-primary" 
                        title="แก้ไข">
                        <i class="fas fa-edit"></i>
                        </a>
                            
                            <?php if ($is_admin): ?>
                            <a href="#" class="btn btn-sm btn-light rounded-pill border text-danger js-delete-user" 
                               data-id="<?php echo $userId; ?>" 
                               data-username="<?php echo $username; ?>" 
                               title="ลบ">
                               <i class="fas fa-trash-alt"></i>
                            </a>
                            <?php endif; ?>

                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="fas fa-users-slash fa-3x mb-3 opacity-50"></i><br>
                            ยังไม่มีข้อมูลผู้ใช้งานในระบบ
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination pagination-sm">
            
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link rounded-start-pill border-0 <?php echo ($page <= 1) ? 'bg-light text-muted' : 'bg-white text-primary shadow-sm'; ?>" 
                   href="<?php echo ($page > 1) ? '?page='.($page-1) : '#'; ?>">
                   <i class="fas fa-chevron-left me-1"></i> ก่อนหน้า
                </a>
            </li>

            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link border-0 mx-1 rounded-circle d-flex align-items-center justify-content-center <?php echo ($page == $i) ? 'shadow-sm' : 'text-secondary bg-transparent'; ?>" 
                       style="width: 35px; height: 35px; <?php echo ($page == $i) ? 'background-color: var(--color-settings, #0d6efd);' : ''; ?>"
                       href="?page=<?php echo $i; ?>">
                       <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                <a class="page-link rounded-end-pill border-0 <?php echo ($page >= $totalPages) ? 'bg-light text-muted' : 'bg-white text-primary shadow-sm'; ?>" 
                   href="<?php echo ($page < $totalPages) ? '?page='.($page+1) : '#'; ?>">
                   ถัดไป <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </li>

        </ul>
    </nav>
    <div class="text-center text-muted small mt-2">
        หน้า <?php echo $page; ?> จาก <?php echo $totalPages; ?> (ทั้งหมด <?php echo number_format($totalRows); ?> รายการ)
    </div>
</div>

<script nonce="<?php echo isset($nonce) ? $nonce : ''; ?>">
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.js-delete-user');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault(); 
            
            const userId = this.getAttribute('data-id');
            const username = this.getAttribute('data-username');
            
            if (confirm("คุณต้องการลบผู้ใช้ '" + username + "' ใช่หรือไม่?\nการกระทำนี้ไม่สามารถเรียกคืนได้")) {
                window.location.href = '<?php echo SITE_URL; ?>/api/delete_user.php?id=' + userId;
            }
        });
    });
});
</script>