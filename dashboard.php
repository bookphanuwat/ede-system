<?php 
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$stats = [ 'total' => 0, 'success' => 0, 'pending' => 0, 'late' => 0 ];
$recent_docs = [];

// ตรวจสอบสิทธิ์ (Admin หรือ User)
$is_admin = (stripos($_SESSION['role'], 'admin') !== false);
$user_id = $_SESSION['user_id'];

try {
    if (isset($pdo)) {
        // --- 1. สรุปยอด (Stats) ---
        // ถ้าไม่ใช่ Admin ให้เพิ่มเงื่อนไข WHERE created_by = $user_id
        $where_clause = $is_admin ? "" : "WHERE created_by = $user_id";
        
        // (ต้องแยกเงื่อนไขสำหรับแต่ละสถานะ)
        $where_success = $is_admin ? "WHERE current_status = 'Received'" : "WHERE current_status = 'Received' AND created_by = $user_id";
        $where_pending = $is_admin ? "WHERE current_status IN ('Registered', 'Sent')" : "WHERE current_status IN ('Registered', 'Sent') AND created_by = $user_id";
        $where_late    = $is_admin ? "WHERE current_status = 'Late'" : "WHERE current_status = 'Late' AND created_by = $user_id";

        $stats['total']   = $pdo->query("SELECT COUNT(*) FROM documents $where_clause")->fetchColumn();
        $stats['success'] = $pdo->query("SELECT COUNT(*) FROM documents $where_success")->fetchColumn();
        $stats['pending'] = $pdo->query("SELECT COUNT(*) FROM documents $where_pending")->fetchColumn();
        $stats['late']    = $pdo->query("SELECT COUNT(*) FROM documents $where_late")->fetchColumn();

        // --- 2. รายการล่าสุด ---
        $sql = "SELECT d.*, dt.type_name 
                FROM documents d 
                LEFT JOIN document_type dt ON d.type_id = dt.type_id ";
        
        if (!$is_admin) {
            $sql .= " WHERE d.created_by = $user_id ";
        }

        $sql .= " ORDER BY d.created_at DESC LIMIT 10";
        
        $stmt = $pdo->query($sql);
        $recent_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {}

function getStatusBadge($status) {
    switch ($status) {
        case 'Received': return '<span class="badge rounded-pill bg-success">สำเร็จ/ได้รับแล้ว</span>';
        case 'Registered': return '<span class="badge rounded-pill bg-info text-dark">ลงทะเบียนใหม่</span>';
        case 'Sent': return '<span class="badge rounded-pill bg-warning text-dark">กำลังนำส่ง</span>';
        case 'Late': return '<span class="badge rounded-pill bg-danger">ล่าช้า</span>';
        default: return '<span class="badge rounded-pill bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - EDE System</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <?php 
            $page_title = "Dashboard (ภาพรวม)"; 
            $header_class = "header-dashboard"; 
            include 'includes/topbar.php'; 
        ?>

        <div class="page-content">
            <h5 class="mb-4 fw-bold text-secondary">**สรุปสถานะประจำวัน** <?php echo $is_admin ? '(ทั้งหมด)' : '(เฉพาะของคุณ)'; ?></h5>
            <div class="row mb-5 g-4">
                <div class="col-md-3">
                    <div class="p-4 rounded-5 text-center text-white shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #4FC3F7, #29B6F6);">
                        <i class="fas fa-folder-open fa-4x position-absolute" style="opacity:0.2; right:-10px; bottom:-10px;"></i>
                        <h2 class="fw-bold mb-0"><?php echo number_format($stats['total']); ?></h2>
                        <small class="text-white-50">เอกสารทั้งหมด</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-4 rounded-5 text-center text-white shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #81C784, #66BB6A);">
                        <i class="fas fa-check-circle fa-4x position-absolute" style="opacity:0.2; right:-10px; bottom:-10px;"></i>
                        <h2 class="fw-bold mb-0"><?php echo number_format($stats['success']); ?></h2>
                        <small class="text-white-50">สำเร็จ / ได้รับแล้ว</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-4 rounded-5 text-center text-white shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #FFB74D, #FFA726);">
                        <i class="fas fa-clock fa-4x position-absolute" style="opacity:0.2; right:-10px; bottom:-10px;"></i>
                        <h2 class="fw-bold mb-0"><?php echo number_format($stats['pending']); ?></h2>
                        <small class="text-white-50">ค้างส่ง / กำลังส่ง</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-4 rounded-5 text-center text-white shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #E57373, #EF5350);">
                        <i class="fas fa-exclamation-triangle fa-4x position-absolute" style="opacity:0.2; right:-10px; bottom:-10px;"></i>
                        <h2 class="fw-bold mb-0"><?php echo number_format($stats['late']); ?></h2>
                        <small class="text-white-50">ล่าช้า</small>
                    </div>
                </div>
            </div>

            <h5 class="mb-3 fw-bold text-secondary">**รายการเอกสารล่าสุด**</h5>
            <div class="table-responsive rounded-4 shadow-sm border">
                <table class="table table-hover mb-0 align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">เลขทะเบียน</th>
                            <th class="py-3 text-start">เรื่อง</th>
                            <th class="py-3">วันที่สร้าง</th>
                            <th class="py-3">สถานะ</th>
                            <th class="py-3">QR / จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_docs) > 0): ?>
                            <?php foreach ($recent_docs as $doc): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary"><?php echo htmlspecialchars($doc['document_code']); ?></span>
                                    </td>
                                    <td class="text-start">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($doc['type_name'] ?? '-'); ?></small>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php echo getStatusBadge($doc['current_status']); ?>
                                    </td>
                                    <td>
                                        <button onclick="showQRModal('<?php echo htmlspecialchars($doc['document_code']); ?>', '<?php echo htmlspecialchars($doc['title']); ?>')" 
                                                class="btn btn-sm btn-light border rounded-pill shadow-sm text-dark">
                                            <i class="fas fa-qrcode text-success"></i> QR
                                        </button>
                                        <a href="print_cover.php?code=<?php echo $doc['document_code']; ?>" 
                                           class="btn btn-sm btn-light border rounded-circle shadow-sm ms-1" title="พิมพ์ใบปะหน้า">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    ยังไม่มีข้อมูลเอกสาร
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal QR Code -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 bg-light rounded-top-4">
                <h5 class="modal-title fw-bold text-secondary"><i class="fas fa-qrcode me-2"></i>QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <h5 id="modalDocTitle" class="fw-bold mb-1 text-primary">...</h5>
                <small id="modalDocCode" class="text-muted d-block mb-3">...</small>
                <div id="qrcode" class="d-flex justify-content-center my-3"></div>
                <p class="small text-muted mt-3">ใช้แอปพลิเคชันสแกนเพื่ออัปเดตสถานะ</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <a id="btnPrintLink" href="#" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-print me-2"></i>พิมพ์ใบปะหน้า
                </a>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showQRModal(docCode, docTitle) {
        document.getElementById('modalDocCode').innerText = "รหัส: " + docCode;
        document.getElementById('modalDocTitle').innerText = docTitle;
        document.getElementById('btnPrintLink').href = 'print_cover.php?code=' + docCode;
        const qrContainer = document.getElementById("qrcode");
        qrContainer.innerHTML = "";
        new QRCode(qrContainer, { text: docCode, width: 200, height: 200, colorDark : "#000000", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H });
        var myModal = new bootstrap.Modal(document.getElementById('qrModal'));
        myModal.show();
    }
</script>

</body>
</html>