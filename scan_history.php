<?php 
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$history = [];
try {
    if (isset($pdo)) {
        // ดึงประวัติที่ action_by เป็นคนนี้ หรือ line_user_id เป็นคนนี้ (ถ้าผูกบัญชี)
        $sql = "SELECT l.*, d.title, d.document_code 
                FROM document_status_log l
                JOIN documents d ON l.document_id = d.document_id
                WHERE l.action_by = ? 
                ORDER BY l.action_time DESC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการสแกน - EDE System</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <?php $page_title = "ประวัติการทำงานของคุณ"; $header_class = "header-dashboard"; include 'includes/topbar.php'; ?>
        <div class="page-content">
            <h5 class="mb-4 fw-bold text-secondary">**🕒 รายการที่คุณเคยสแกน/อัปเดต**</h5>
            
            <div class="table-responsive rounded-4 shadow-sm border">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 ps-4">เวลา</th>
                            <th class="py-3">เอกสาร</th>
                            <th class="py-3">การดำเนินการ</th>
                            <th class="py-3">อุปกรณ์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history) > 0): foreach ($history as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?php echo date('d/m/Y H:i', strtotime($row['action_time'])); ?></td>
                                <td>
                                    <span class="fw-bold text-primary"><?php echo htmlspecialchars($row['document_code']); ?></span><br>
                                    <small><?php echo htmlspecialchars($row['title']); ?></small>
                                </td>
                                <td><span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                <td class="text-muted small"><i class="fas fa-desktop me-1"></i><?php echo htmlspecialchars($row['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">ยังไม่พบประวัติการทำงานของคุณ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>