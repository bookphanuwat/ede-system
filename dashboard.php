<?php

// Show all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// เริ่มจับเวลา
$start_time = microtime(true);
$time_logs = [];

$time_logs['session_start'] = microtime(true);
session_start();
$time_logs['session_start'] = microtime(true) - $time_logs['session_start'];

$time_logs['db_connect'] = microtime(true);
require_once 'config/db.php';
$time_logs['db_connect'] = microtime(true) - $time_logs['db_connect'];

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// --- 1. โหลดข้อมูลสีสถานะจาก JSON (NEW) ---
$statusColorMap = [];
$jsonFile = 'data/workflow_data.json'; // Path ไปหาไฟล์ JSON

if (file_exists($jsonFile)) {
    $workflows = json_decode(file_get_contents($jsonFile), true) ?? [];
    foreach ($workflows as $wf) {
        if (!empty($wf['statuses'])) {
            foreach ($wf['statuses'] as $st) {
                // สร้าง Map: ชื่อสถานะ => สี
                $statusColorMap[$st['name']] = $st['color'];
            }
        }
    }
}

// ฟังก์ชันดึง Badge สีที่ถูกต้อง
function getStatusBadge($status) {
    global $statusColorMap;

    // 1. เช็คใน JSON ก่อน
    if (isset($statusColorMap[$status])) {
        $color = $statusColorMap[$status];
    } 
    // 2. ถ้าไม่มีใน JSON ให้ใช้ค่า Default เดิม
    else {
        switch ($status) {
            case 'Received': $color = 'success'; break;
            case 'Registered': $color = 'info'; break;
            case 'Sent': 
            case 'กำลังนำส่ง': $color = 'warning'; break;
            case 'Late': $color = 'danger'; break;
            default: $color = 'secondary'; break;
        }
    }

    // จัดการสีตัวอักษรให้อ่านง่าย (พื้นหลังอ่อน ตัวหนังสือต้องเข้ม)
    $textClass = ($color === 'warning' || $color === 'info' || $color === 'light') ? 'text-dark' : 'text-white';

    return '<span class="badge rounded-pill bg-' . $color . ' ' . $textClass . '">' . htmlspecialchars($status) . '</span>';
}

// --- ส่วนดึงข้อมูล ---
$stats = [ 'total' => 0, 'success' => 0, 'pending' => 0, 'late' => 0 ];
$recent_docs = [];

$is_admin = (stripos($_SESSION['role'], 'admin') !== false);
$user_id = $_SESSION['user_id'];

try {
    if (isset($pdo)) {
        // 1. Stats
        $time_logs['stats_queries'] = microtime(true);
        $where_clause = $is_admin ? "" : "WHERE created_by = $user_id";
        
        // ปรับ Logic การนับยอดตามกลุ่มสีคร่าวๆ (อาจจะไม่แม่นเป๊ะถ้ายูสเซอร์ตั้งสีมั่ว แต่พอใช้ได้)
        // Success = สีเขียว, Pending = สีเหลือง/ฟ้า, Late = สีแดง
        // แต่เพื่อความชัวร์ ใช้ Status มาตรฐานถ้ามี หรือนับรวมๆ
        
        $stats['total']   = $pdo->query("SELECT COUNT(*) FROM documents $where_clause")->fetchColumn();
        
        // ตัวอย่างการนับแบบง่าย (นับเฉพาะ Status มาตรฐานไปก่อน หรือต้องแก้ Query ใหญ๋)
        // เพื่อความรวดเร็วและไม่กระทบ DB เดิม ผมจะคง Query เดิมไว้ก่อน 
        // แนะนำ: ในอนาคตควรมี field 'status_group' ใน DB เพื่อแยกประเภท สำเร็จ/รอ/ยกเลิก
        
        $where_success = $is_admin ? "WHERE current_status = 'Received'" : "WHERE current_status = 'Received' AND created_by = $user_id";
        $where_pending = $is_admin ? "WHERE current_status IN ('Registered', 'Sent')" : "WHERE current_status IN ('Registered', 'Sent') AND created_by = $user_id";
        $where_late    = $is_admin ? "WHERE current_status = 'Late'" : "WHERE current_status = 'Late' AND created_by = $user_id";

        $stats['success'] = $pdo->query("SELECT COUNT(*) FROM documents $where_success")->fetchColumn();
        $stats['pending'] = $pdo->query("SELECT COUNT(*) FROM documents $where_pending")->fetchColumn();
        $stats['late']    = $pdo->query("SELECT COUNT(*) FROM documents $where_late")->fetchColumn();
        
        $time_logs['stats_queries'] = microtime(true) - $time_logs['stats_queries'];

        // 2. Recent Docs
        $time_logs['recent_docs_query'] = microtime(true);
        $sql = "SELECT d.*, dt.type_name
                FROM documents d
                LEFT JOIN document_type dt ON d.type_id = dt.type_id ";
        if (!$is_admin) { $sql .= " WHERE d.created_by = $user_id "; }
        $sql .= " ORDER BY d.created_at DESC LIMIT 10";

        $stmt = $pdo->query($sql);
        $recent_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $time_logs['recent_docs_query'] = microtime(true) - $time_logs['recent_docs_query'];
    }
} catch (PDOException $e) {}

$total_time = microtime(true) - $start_time;

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - EDE System</title>

    <!-- Bootstrap & Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- QR Code Lib -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        .doc-link {
            color: #29B6F6; font-weight: bold; text-decoration: none;
            background: rgba(41, 182, 246, 0.1); padding: 5px 10px; border-radius: 20px; transition: 0.2s;
        }
        .doc-link:hover { background: #29B6F6; color: white; }
        .view-count-badge { font-size: 0.85rem; color: #555; background: #eee; padding: 5px 10px; border-radius: 15px; display: inline-flex; align-items: center; gap: 5px; }
    </style>
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
            <!-- Load Time Display -->
            <div class="alert alert-info rounded-4 mb-4 shadow-sm" style="font-size: 0.85rem;">
                <i class="fas fa-tachometer-alt me-2"></i>
                <strong>เวลาโหลดหน้า:</strong>
                <span id="loadTime">กำลังคำนวณ...</span> วินาที
            </div>

            <!-- Cards สรุปยอด -->
            <h5 class="mb-4 fw-bold text-secondary">**สรุปสถานะประจำวัน** <?php echo $is_admin ? '(ทั้งหมด)' : '(เฉพาะของคุณ)'; ?></h5>
            <div class="row mb-5 g-4">
                <div class="col-md-3"><div class="p-4 rounded-5 text-center text-white shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #4FC3F7, #29B6F6);"><i class="fas fa-folder-open fa-4x position-absolute" style="opacity:0.2; right:-10px; bottom:-10px;"></i><h2 class="fw-bold mb-0"><?php echo number_format($stats['total']); ?></h2><small>เอกสารทั้งหมด</small></div></div>
                <div class="col-md-3"><div class="p-4 rounded-5 text-center text-white shadow-sm" style="background: linear-gradient(135deg, #81C784, #66BB6A);"><i class="fas fa-check-circle fa-4x position-absolute" style="opacity:0.2; right:-10px; bottom:-10px;"></i><h2 class="fw-bold mb-0"><?php echo number_format($stats['success']); ?></h2><small>สำเร็จ</small></div></div>
                <div class="col-md-3"><div class="p-4 rounded-5 text-center text-white shadow-sm" style="background: linear-gradient(135deg, #FFB74D, #FFA726);"><i class="fas fa-clock fa-4x position-absolute" style="opacity:0.2; right:-10px; bottom:-10px;"></i><h2 class="fw-bold mb-0"><?php echo number_format($stats['pending']); ?></h2><small>ค้างส่ง</small></div></div>
                <div class="col-md-3"><div class="p-4 rounded-5 text-center text-white shadow-sm" style="background: linear-gradient(135deg, #E57373, #EF5350);"><i class="fas fa-exclamation-triangle fa-4x position-absolute" style="opacity:0.2; right:-10px; bottom:-10px;"></i><h2 class="fw-bold mb-0"><?php echo number_format($stats['late']); ?></h2><small>ล่าช้า</small></div></div>
            </div>

            <!-- ตารางรายการล่าสุด -->
            <h5 class="mb-3 fw-bold text-secondary">**รายการเอกสารล่าสุด**</h5>
            <div class="table-responsive rounded-4 shadow-sm border">
                <table class="table table-hover mb-0 align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">เลขทะเบียน</th>
                            <th class="py-3 text-start">เรื่อง</th>
                            <th class="py-3">วันที่สร้าง</th>
                            <th class="py-3">สถานะ / การเข้าชม</th>
                            <th class="py-3">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_docs) > 0): ?>
                            <?php foreach ($recent_docs as $doc): ?>
                                <tr>
                                    <td>
                                        <a href="javascript:void(0)"
                                           onclick="openDetailModal('<?php echo $doc['document_code']; ?>')"
                                           class="doc-link shadow-sm">
                                            <i class="fas fa-qrcode me-1"></i> <?php echo htmlspecialchars($doc['document_code']); ?>
                                        </a>
                                    </td>
                                    <td class="text-start">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($doc['type_name'] ?? '-'); ?></small>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?>
                                    </td>
                                    <td>
                                        <!-- เรียกใช้ฟังก์ชันแสดงสีที่ปรับปรุงแล้ว -->
                                        <?php echo getStatusBadge($doc['current_status']); ?>
                                        
                                        <div class="mt-1 text-muted small">
                                            <i class="far fa-eye"></i> <?php echo number_format($doc['view_count'] ?? 0); ?> ครั้ง
                                        </div>
                                    </td>
                                    <td>
                                        <button onclick="showQRModal('<?php echo htmlspecialchars($doc['document_code']); ?>', '<?php echo htmlspecialchars($doc['title']); ?>')" class="btn btn-sm btn-light border rounded-pill shadow-sm text-dark"><i class="fas fa-qrcode text-success"></i> QR</button>
                                        <a href="print_cover.php?code=<?php echo $doc['document_code']; ?>" class="btn btn-sm btn-light border rounded-circle shadow-sm ms-1"><i class="fas fa-print"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="py-4 text-muted">ยังไม่มีข้อมูลเอกสาร</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal QR Code -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 bg-light rounded-top-4">
                <h5 class="modal-title fw-bold text-secondary"><i class="fas fa-qrcode me-2"></i>QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <h5 id="modalDocTitle" class="fw-bold mb-1 text-primary">...</h5>
                <small id="modalDocCode" class="text-muted d-block mb-3">...</small>
                <div id="qrcode" class="d-flex justify-content-center my-3"></div>
                <p class="small text-muted mt-3">ใช้แอปพลิเคชันสแกนเพื่ออัปเดตสถานะ</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <a id="btnPrintLink" href="#" class="btn btn-primary rounded-pill px-4"><i class="fas fa-print me-2"></i>พิมพ์ใบปะหน้า</a>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal รายละเอียดเอกสาร -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-alt me-2"></i>รายละเอียดเอกสาร</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div id="modalLoading" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>

                <div id="modalContent" style="display:none;">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 id="d_title" class="fw-bold text-primary mb-3">...</h4>
                                <span class="view-count-badge shadow-sm">
                                    <i class="far fa-eye text-primary"></i> ถูกสแกน: <strong id="d_views" class="text-dark">0</strong> ครั้ง
                                </span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6"><small class="text-muted d-block">เลขทะเบียน</small><strong id="d_code" class="fs-5">...</strong></div>
                                <div class="col-md-6"><small class="text-muted d-block">สถานะปัจจุบัน</small><span id="d_status" class="badge bg-secondary">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ประเภท</small><span id="d_type">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">วันที่สร้าง</small><span id="d_date">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ผู้ส่ง</small><span id="d_sender">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ผู้รับ</small><span id="d_receiver">...</span></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary ps-2 border-start border-4 border-primary mb-3">ประวัติการดำเนินงาน (Timeline)</h6>
                    <div id="d_timeline" class="timeline ms-2"></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light"><button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button></div>
        </div>
    </div>
</div>

<script>
    // แสดงเวลาโหลดหน้า
    window.addEventListener('load', function() {
        const totalLoadTime = (performance.now() / 1000).toFixed(3);
        document.getElementById('loadTime').textContent = totalLoadTime;
    });

    function showQRModal(docCode, docTitle) {
        document.getElementById('modalDocCode').innerText = "รหัส: " + docCode;
        document.getElementById('modalDocTitle').innerText = docTitle;
        document.getElementById('btnPrintLink').href = 'print_cover.php?code=' + docCode;
        const qrContainer = document.getElementById("qrcode");
        qrContainer.innerHTML = "";
        new QRCode(qrContainer, { text: docCode, width: 180, height: 180 });
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    }

    async function openDetailModal(code) {
        new bootstrap.Modal(document.getElementById('detailModal')).show();
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';

        try {
            const res = await fetch(`api/get_doc_info.php?code=${code}`);
            const data = await res.json();

            if(data.error) throw new Error(data.error);

            const doc = data.doc;
            document.getElementById('d_title').innerText = doc.title;
            document.getElementById('d_code').innerText = doc.document_code;
            document.getElementById('d_status').innerText = doc.current_status;
            document.getElementById('d_type').innerText = doc.type_name || '-';
            document.getElementById('d_date').innerText = doc.created_at;
            document.getElementById('d_sender').innerText = doc.sender_name;
            document.getElementById('d_receiver').innerText = doc.receiver_name;
            document.getElementById('d_views').innerText = doc.view_count || 0;
            
            // อัปเดตสีของ Badge ใน Modal ด้วย (ใช้ Class ง่ายๆ จาก JS)
            const statusEl = document.getElementById('d_status');
            // หมายเหตุ: ใน Modal เนื่องจากเป็น JS เราดึง Class สีจาก PHP ไม่ได้โดยตรง 
            // อาจจะใช้ switch case ง่ายๆ ใน JS หรือปล่อยเป็นสีเทาก็ได้ แต่ผมจะปล่อย Default ไว้เพื่อความเสถียร

            let html = '';
            if(data.logs && data.logs.length > 0) {
                data.logs.forEach((log, index) => {
                    const activeClass = index === 0 ? 'active' : '';
                    const actor = log.actor_name_snapshot || log.fullname || 'Unknown';
                    const actorPic = log.actor_pic_snapshot ? `<img src="${log.actor_pic_snapshot}" class="rounded-circle me-1" width="20">` : '<i class="fas fa-user-circle me-1"></i>';

                    html += `
                        <div class="timeline-item">
                            <div class="timeline-dot ${activeClass}"></div>
                            <div class="ps-4">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-dark">${log.status}</strong>
                                    <small class="text-muted">${log.action_time}</small>
                                </div>
                                <small class="text-secondary d-flex align-items-center mt-1">
                                    โดย: ${actorPic} ${actor}
                                </small>
                                ${log.location_note ? `<br><small class="text-danger"><i class="fas fa-map-marker-alt"></i> ${log.location_note}</small>` : ''}
                            </div>
                        </div>`;
                });
            } else {
                html = '<p class="text-muted ms-4">ยังไม่มีประวัติ</p>';
            }
            document.getElementById('d_timeline').innerHTML = html;

            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';

        } catch (err) {
            alert("ไม่สามารถโหลดข้อมูลได้: " + err.message);
        }
    }
</script>

</body>
</html>