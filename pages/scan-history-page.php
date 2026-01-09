<?php
    $page_title   = "ประวัติการสแกน/อัปเดตล่าสุด";
    $header_class = "header-scan-history";
    include 'includes/topbar.php';

    // ---------------------------------------------------------
    // ส่วนที่ 1: AJAX Handler
    // ---------------------------------------------------------
    if (isset($_GET['ajax_get_detail']) && isset($_GET['doc_id'])) {
        while (ob_get_level()) { ob_end_clean(); } 
        header('Content-Type: application/json');

        $doc_id = $_GET['doc_id'];
        $response = ['success' => false];

        // 1. ดึงรายละเอียดเอกสาร
        $sql_doc = "SELECT d.*, dt.type_name 
                    FROM documents d 
                    LEFT JOIN document_type dt ON d.type_id = dt.type_id 
                    WHERE d.document_id = ?";
        $docData = CON::selectArrayDB([$doc_id], $sql_doc);

        if (!empty($docData)) {
            $d = $docData[0];
            $response['doc'] = [
                'code' => $d['document_code'],
                'title' => $d['title'],
                'type' => $d['type_name'] ?? '-',
                'status' => $d['current_status'],
                'created_at' => date('d/m/Y H:i', strtotime($d['created_at'])),
                'sender' => $d['sender_name'] ?? '-',
                'receiver' => $d['receiver_name'] ?? '-',
                'view_count' => number_format($d['view_count'] ?? 0)
            ];

            // 2. ดึงประวัติ Timeline
            $sql_hist = "SELECT u.*, l.* FROM document_status_log l 
                         LEFT JOIN users u ON l.action_by = u.user_id 
                         WHERE l.document_id = ? 
                         ORDER BY l.action_time DESC";
            $histData = CON::selectArrayDB([$doc_id], $sql_hist) ?? [];

            $html = '<ul class="list-group list-group-flush">';
            if (count($histData) > 0) {
                foreach ($histData as $h) {
                    $h_time = date('d/m/Y H:i', strtotime($h['action_time']));
                    $found_name = $h['actor_name_snapshot'] ?: ($h['fullname'] ?: ($h['username'] ?: "User ID: " . $h['action_by']));
                    $img_src = $h['actor_pic_snapshot'] ?: '';
                    
                    // [LOGIC ใหม่] ถ้ามีรูป (LINE) โชว์รูป / ถ้าไม่มี (User ระบบ) โชว์ไอคอนสร้างเอง
                    if (!empty($img_src)) {
                        $user_icon = "<img src='$img_src' class='rounded-circle border me-2 user-avatar' style='width:35px; height:35px; object-fit:cover;'>";
                    } else {
                        // สร้างรูป Avatar ขึ้นมาเอง (ไม่ต้องใช้ไฟล์ภาพ)
                        $user_icon = "<div class='rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-2 border border-primary border-opacity-25' style='width:35px; height:35px;'>
                                        <i class='fas fa-user-tie text-primary'></i>
                                      </div>";
                    }

                    $h_status = htmlspecialchars($h['status'] ?? '-', ENT_QUOTES, 'UTF-8');
                    $h_ip     = htmlspecialchars($h['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8');
                    $h_device = htmlspecialchars($h['device_info'] ?? '-', ENT_QUOTES, 'UTF-8');

                    $html .= "
                    <li class='list-group-item px-0 border-bottom-0'>
                        <div class='d-flex align-items-start'>
                            <div class='me-3 text-center' style='width: 60px;'>
                                <small class='text-muted d-block' style='font-size: 0.75rem;'>".date('H:i', strtotime($h['action_time']))."</small>
                                <small class='text-muted' style='font-size: 0.7rem;'>".date('d/m/y', strtotime($h['action_time']))."</small>
                            </div>
                            <div class='flex-grow-1'>
                                <div class='d-flex align-items-center mb-1'>
                                    $user_icon
                                    <div>
                                        <span class='fw-bold text-dark d-block' style='line-height:1.2;'>$found_name</span>
                                        <span class='badge bg-light text-secondary border rounded-pill small'>$h_status</span>
                                    </div>
                                </div>
                                <div class='small text-muted ps-5 ms-1'>
                                    <span class='me-2'><i class='fas fa-network-wired me-1'></i> $h_ip</span>
                                    <span><i class='fas fa-mobile-alt me-1'></i> $h_device</span>
                                </div>
                            </div>
                        </div>
                    </li>";
                }
            } else {
                $html .= "<li class='list-group-item text-center text-muted'>ยังไม่มีประวัติ</li>";
            }
            $html .= '</ul>';

            $response['history_html'] = $html;
            $response['success'] = true;
        }

        echo json_encode($response);
        exit;
    }

    // ---------------------------------------------------------
    // ส่วนที่ 2: Main Page Logic
    // ---------------------------------------------------------
    function getStatusBadge($status) {
        switch ($status) {
            case 'Received': return '<span class="badge rounded-pill bg-success px-3">สำเร็จ/ได้รับแล้ว</span>';
            case 'Registered': return '<span class="badge rounded-pill bg-info text-dark px-3">ลงทะเบียนใหม่</span>';
            case 'Sent': return '<span class="badge rounded-pill bg-warning text-dark px-3">กำลังนำส่ง</span>';
            case 'Late': return '<span class="badge rounded-pill bg-danger px-3">ล่าช้า</span>';
            case 'เปิดอ่าน': case 'Viewed': 
                return '<span class="badge rounded-pill bg-primary px-3 shadow-sm" style="background: linear-gradient(45deg, #42a5f5, #1e88e5);"><i class="far fa-eye me-1"></i>สแกนเปิดอ่าน</span>';
            default: return '<span class="badge rounded-pill bg-secondary px-3">' . htmlspecialchars($status) . '</span>';
        }
    }

    // SQL
    $sql = "SELECT l.*, d.title, d.document_code, d.current_status, 
                   u.fullname, u.username, 
                   l.actor_name_snapshot, l.actor_pic_snapshot
            FROM document_status_log l 
            JOIN documents d ON l.document_id = d.document_id 
            LEFT JOIN users u ON l.action_by = u.user_id 
            WHERE l.log_id IN (
                SELECT MAX(log_id) 
                FROM document_status_log 
                GROUP BY document_id
            )
            ORDER BY l.action_time DESC 
            LIMIT 50";
            
    $history = CON::selectArrayDB([], $sql) ?? [];

    $historyRows = '';
    if (count($history) > 0) {
        foreach ($history as $row) {
            $timeObj = strtotime($row['action_time']);
            $dateStr = date('d/m/y', $timeObj);
            $timeStr = date('H:i', $timeObj);
            
            $code = htmlspecialchars($row['document_code'] ?? '', ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $status = $row['status'] ?? '-';
            $ip = htmlspecialchars($row['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8');
            $device = htmlspecialchars($row['device_info'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
            $docId = $row['document_id'];

            // Highlight row
            $isViewed = in_array($status, ['เปิดอ่าน', 'Viewed']);
            $rowClass = $isViewed ? 'bg-soft-primary' : ''; 
            
            // Icon Document
            $iconType = $isViewed 
                ? '<div class="icon-circle bg-white text-primary shadow-sm"><i class="fas fa-qrcode"></i></div>' 
                : '<div class="icon-circle bg-light text-secondary"><i class="fas fa-file-alt"></i></div>';

            // User Info
            $actorName = $row['actor_name_snapshot'] ?: ($row['fullname'] ?: ($row['username'] ?: 'Unknown'));
            $actorImgUrl = $row['actor_pic_snapshot'];

            // [LOGIC สำคัญ] การแสดงผลรูปประจำตัว
            if (!empty($actorImgUrl)) {
                // กรณี: มีรูป (มาจาก LINE) -> แสดงรูป <img>
                // ใส่ onerror เผื่อรูป LINE หมดอายุ ให้กลับไปเป็นไอคอน
                $userAvatarHtml = "<img src='$actorImgUrl' class='rounded-circle border w-100 h-100 shadow-sm user-avatar' style='object-fit:cover;'>";
            } else {
                // กรณี: ไม่มีรูป (User ระบบ) -> แสดงไอคอน <i class="fa-user-tie"></i>
                $userAvatarHtml = "<div class='rounded-circle bg-white w-100 h-100 d-flex align-items-center justify-content-center border shadow-sm'>
                                      <i class='fas fa-user-tie text-secondary' style='font-size: 1.2rem;'></i>
                                   </div>";
            }

            $userDisplay = "
            <div class='d-flex align-items-center'>
                <div class='me-3' style='width:45px; height:45px; min-width:45px;'>
                     $userAvatarHtml
                </div>
                <div class='text-dark fw-bold text-truncate' style='max-width: 160px; font-size: 1rem;'>$actorName</div>
            </div>";

            // Code & Title
            $codeLink = "<a href='#' class='doc-link shadow-sm btn-open-detail mb-2' data-id='$docId'><i class='fas fa-search me-1'></i>$code</a>";
            $titleDisplay = "<div class='text-dark fw-bold text-wrap' style='font-size: 1.2rem; line-height:1.4;'>$title</div>";

            $statusBadge = getStatusBadge($status);

            $historyRows .= "<tr class='$rowClass'>
                <td class='ps-4 align-middle'>
                    <div class='d-flex align-items-center'>
                        <div class='text-center me-3'>
                            <span class='d-block fw-bold text-dark' style='font-size:1.1rem; line-height:1;'>$timeStr</span>
                            <small class='text-muted' style='font-size:0.8rem;'>$dateStr</small>
                        </div>
                        $iconType
                    </div>
                </td>
                <td class='align-middle py-3'>
                    <div class='d-flex flex-column align-items-start'>
                        $codeLink
                        $titleDisplay
                    </div>
                </td>
                <td class='align-middle'>$userDisplay</td>
                <td class='align-middle'>$statusBadge</td>
                <td class='text-muted small align-middle'>
                    <div class='d-flex align-items-center' title='$ip'>
                        <i class='fas fa-mobile-alt me-2'></i> 
                        <span class='text-truncate' style='max-width: 120px;'>$device</span>
                    </div>
                </td>
            </tr>";
        }
    } else {
        $historyRows = '<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-history fa-3x mb-3 opacity-25"></i><br>ยังไม่พบประวัติการทำงานในระบบ</td></tr>';
    }
?>

<style>
    .doc-link {
        color: #29B6F6; font-weight: bold; text-decoration: none;
        background: rgba(41, 182, 246, 0.1); padding: 5px 15px; border-radius: 20px; 
        transition: 0.2s; display: inline-block; font-size: 1rem;
    }
    .doc-link:hover { background: #29B6F6; color: white; transform: translateY(-1px); }

    .bg-soft-primary { background-color: #f0f7ff !important; }
    .bg-soft-primary:hover { background-color: #e1effe !important; }
    
    .icon-circle {
        width: 40px; height: 40px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    
    .table > :not(caption) > * > * { padding: 1.2rem 1rem; border-bottom-color: #f1f1f1; }
    .table tbody tr:hover { background-color: #fafafa; }
    
    .view-count-badge { font-size: 0.9rem; color: #555; background: #eee; padding: 5px 15px; border-radius: 15px; display: inline-flex; align-items: center; gap: 5px; }
</style>

<div class="page-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold text-dark mb-1"><i class="fas fa-history me-2 text-primary"></i>ประวัติการสแกน/อัปเดต (ล่าสุด)</h5>
            <small class="text-muted" style="font-size: 0.9rem;">แสดงสถานะล่าสุดของเอกสารแต่ละฉบับ | คลิกที่ <span class="text-primary fw-bold">รหัสเอกสารสีฟ้า</span> เพื่อดูรายละเอียด</small>
        </div>
        <button id="btnReload" class="btn btn-light border rounded-pill shadow-sm text-secondary px-4 py-2">
            <i class="fas fa-sync-alt me-1"></i> รีเฟรช
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 ps-4 text-secondary" style="width: 15%; font-size: 1rem;">เวลา / ประเภท</th>
                        <th class="py-3 text-secondary" style="width: 35%; font-size: 1rem;">เอกสาร</th>
                        <th class="py-3 text-secondary" style="width: 20%; font-size: 1rem;">ผู้ดำเนินการล่าสุด</th>
                        <th class="py-3 text-secondary" style="width: 15%; font-size: 1rem;">สถานะล่าสุด</th>
                        <th class="py-3 text-secondary" style="width: 15%; font-size: 1rem;">อุปกรณ์</th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $historyRows; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 bg-white rounded-top-4 pb-0">
                <h5 class="modal-title fw-bold text-dark ps-2 pt-2" style="font-size: 1.3rem;">รายละเอียดและประวัติทั้งหมด</h5>
                <button type="button" class="btn-close mt-2 me-2" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="modalContent" style="display:none;">
                    <div class="card border bg-light rounded-4 mb-4 mt-3">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span id="d_code" class="badge bg-dark mb-2" style="font-size: 1rem;">...</span>
                                    <h4 id="d_title" class="fw-bold text-primary mb-0" style="font-size: 1.4rem;">...</h4>
                                </div>
                                <div class="text-end">
                                    <span class="view-count-badge shadow-sm bg-white">
                                        <i class="far fa-eye text-primary"></i> <strong id="d_views" class="text-dark ms-1">0</strong>
                                    </span>
                                </div>
                            </div>
                            <div class="row g-2 text-muted" style="font-size: 0.95rem;">
                                <div class="col-6">ประเภท: <span id="d_type" class="text-dark fw-bold">...</span></div>
                                <div class="col-6">วันที่สร้าง: <span id="d_date" class="text-dark fw-bold">...</span></div>
                                <div class="col-6">ผู้ส่ง: <span id="d_sender" class="text-dark fw-bold">...</span></div>
                                <div class="col-6">ผู้รับ: <span id="d_receiver" class="text-dark fw-bold">...</span></div>
                            </div>
                        </div>
                    </div>
                    <h6 class="fw-bold text-secondary mb-3 ps-1" style="font-size: 1.1rem;">Timeline การทำงาน (ทั้งหมด)</h6>
                    <div id="d_timeline" class="timeline ms-1"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo $nonce; ?>">
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Handle Image Errors (กรณีรูป LINE เสีย ให้เปลี่ยนกลับเป็นไอคอน)
    document.addEventListener('error', function(e) {
        if (e.target && e.target.tagName === 'IMG' && e.target.classList.contains('user-avatar')) {
            // แทนที่จะโหลดรูป 404 ให้เปลี่ยน img เป็น div icon (โดยการซ่อน img แล้วแทรก div)
            // แต่วิธีง่ายกว่าคือใช้ placeholder
             e.target.src = 'https://via.placeholder.com/45/f0f0f0/888888?text=USER'; 
        }
    }, true);

    // 2. Handle Refresh Button
    var btnReload = document.getElementById('btnReload');
    if (btnReload) {
        btnReload.addEventListener('click', function() {
            window.location.reload();
        });
    }

    // 3. Handle Open Detail Modal
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('.btn-open-detail');
        if (target) {
            e.preventDefault();
            const docId = target.getAttribute('data-id');
            openDetailModal(docId);
        }
    });
});

function openDetailModal(docId) {
    var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
    myModal.show();
    
    document.getElementById('modalLoading').style.display = 'block';
    document.getElementById('modalContent').style.display = 'none';
    
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('ajax_get_detail', '1');
    currentUrl.searchParams.set('doc_id', docId);

    fetch(currentUrl)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('d_title').innerText = data.doc.title;
            document.getElementById('d_code').innerText = data.doc.code;
            document.getElementById('d_views').innerText = data.doc.view_count;
            document.getElementById('d_type').innerText = data.doc.type;
            document.getElementById('d_date').innerText = data.doc.created_at;
            document.getElementById('d_sender').innerText = data.doc.sender;
            document.getElementById('d_receiver').innerText = data.doc.receiver;
            document.getElementById('d_timeline').innerHTML = data.history_html;

            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
        } else {
            alert('ไม่พบข้อมูลเอกสาร');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('d_timeline').innerHTML = '<div class="text-danger text-center">เกิดข้อผิดพลาด</div>';
    });
}
</script>