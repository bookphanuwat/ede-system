<?php 
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$is_admin = (stripos($_SESSION['role'], 'admin') !== false);
$user_dept = $_SESSION['department'] ?? '';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-t');

$report_data = [];

try {
    if (isset($pdo)) {
        // SQL หาแผนก
        $sql_dept = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != ''";
        
        // ถ้าไม่ใช่ Admin ให้ดูได้เฉพาะแผนกตัวเอง
        if (!$is_admin && !empty($user_dept)) {
            $sql_dept .= " AND department = '$user_dept'";
        }

        $stmt_dept = $pdo->query($sql_dept);
        $departments = $stmt_dept->fetchAll(PDO::FETCH_COLUMN);

        foreach ($departments as $dept) {
            // A. ส่งออก (Count)
            $sql_sent = "SELECT COUNT(*) FROM documents d 
                         JOIN users u ON d.created_by = u.user_id 
                         WHERE u.department = ? 
                         AND DATE(d.created_at) BETWEEN ? AND ?";
            $stmt_sent = $pdo->prepare($sql_sent);
            $stmt_sent->execute([$dept, $start_date, $end_date]);
            $sent_count = $stmt_sent->fetchColumn();

            // B. รับเข้า (Count)
            $sql_recv = "SELECT COUNT(*) FROM documents d 
                         JOIN users u ON d.receiver_name = u.fullname 
                         WHERE u.department = ? 
                         AND DATE(d.created_at) BETWEEN ? AND ?";
            $stmt_recv = $pdo->prepare($sql_recv);
            $stmt_recv->execute([$dept, $start_date, $end_date]);
            $recv_count = $stmt_recv->fetchColumn();

            $report_data[] = [
                'department' => $dept,
                'sent' => $sent_count,
                'received' => $recv_count
            ];
        }
    }
} catch (PDOException $e) { $error_msg = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปผล - EDE System</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <?php $page_title = "รายงานสรุปผล"; $header_class = "header-report"; include 'includes/topbar.php'; ?>

        <div class="page-content">
            <h5 class="mb-4 fw-bold text-secondary">**📊 สรุปการรับ-ส่งเอกสารตามหน่วยงาน** <?php echo $is_admin ? '(ทั้งหมด)' : "(เฉพาะแผนก $user_dept)"; ?></h5>

            <form method="GET" action="report.php" class="row justify-content-center mb-5">
                <div class="col-md-9 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2 bg-light p-3 rounded-pill shadow-sm border">
                        <span class="fw-bold text-secondary"><i class="far fa-calendar-alt"></i> ช่วงเวลา:</span>
                        <input type="date" name="start_date" class="form-control rounded-pill border-0 custom-input py-2" style="max-width: 160px;" value="<?php echo $start_date; ?>">
                        <span class="text-muted">ถึง</span>
                        <input type="date" name="end_date" class="form-control rounded-pill border-0 custom-input py-2" style="max-width: 160px;" value="<?php echo $end_date; ?>">
                        <button type="submit" class="btn btn-danger rounded-circle shadow-sm" style="width: 40px; height: 40px;"><i class="fas fa-search"></i></button>
                        <a href="report.php" class="btn btn-secondary rounded-circle shadow-sm" style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-sync-alt"></i></a>
                    </div>
                </div>
            </form>

            <?php if(isset($error_msg)): ?><div class="alert alert-danger text-center"><?php echo $error_msg; ?></div><?php endif; ?>

            <div class="table-responsive rounded-4 shadow-sm border mx-auto bg-white" style="max-width: 900px;">
                <table id="reportTable" class="table table-hover mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 bg-light text-secondary">แผนก / หน่วยงาน</th>
                            <th class="py-3 bg-light text-success">📤 ส่งออก (รายการ)</th>
                            <th class="py-3 bg-light text-primary">📥 รับเข้า (รายการ)</th>
                            <th class="py-3 bg-light text-secondary">รวมทั้งหมด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($report_data) > 0): ?>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td class="text-start ps-5 fw-bold text-secondary"><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td class="text-success fw-bold" style="font-size: 1.1rem;"><?php echo number_format($row['sent']); ?></td>
                                    <td class="text-primary fw-bold" style="font-size: 1.1rem;"><?php echo number_format($row['received']); ?></td>
                                    <td class="text-secondary fw-bold"><?php echo number_format($row['sent'] + $row['received']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-secondary fw-bold">
                                <td class="text-end pe-3">รวมทั้งสิ้น</td>
                                <td class="text-success"><?php echo number_format(array_sum(array_column($report_data, 'sent'))); ?></td>
                                <td class="text-primary"><?php echo number_format(array_sum(array_column($report_data, 'received'))); ?></td>
                                <td><?php echo number_format(array_sum(array_column($report_data, 'sent')) + array_sum(array_column($report_data, 'received'))); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr><td colspan="4" class="py-5 text-muted">ไม่พบข้อมูล</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- ปุ่ม Export (ใช้ JS เดิมที่มีอยู่) -->
            <div class="text-end mt-4 mx-auto" style="max-width: 900px;">
                <button onclick="window.print()" class="btn btn-outline-dark border-0 fw-bold rounded-pill px-4"><i class="fas fa-print me-2"></i>พิมพ์</button>
                <button onclick="exportTableToExcel('reportTable', 'Report')" class="btn btn-success border-0 fw-bold rounded-pill px-4 ms-2" style="background-color: #1D6F42;"><i class="fas fa-file-excel me-2"></i>Export</button>
            </div>
        </div>
    </div>
</div>
<script>function exportTableToExcel(tableID, filename = ''){ /* ...JS Code เดิม... */ }</script>
</body>
</html>