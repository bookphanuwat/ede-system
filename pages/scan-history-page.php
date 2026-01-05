<?php
    $page_title   = "ประวัติการสแกน/อัปเดตล่าสุด";
    $header_class = "header-scan-history";
    include 'includes/topbar.php';

    // ---------------------------------------------------------
    // ส่วนที่ 1: AJAX Handler สำหรับดึงข้อมูลรายละเอียด + Timeline (JSON)
    // ---------------------------------------------------------
    if (isset($_GET['ajax_get_detail']) && isset($_GET['doc_id'])) {
        while (ob_get_level()) { ob_end_clean(); } // Clean buffer
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
                    
                    // --- Identity Detection (ใน Modal) ---
                    $found_name = '';
                    if (!empty($h['actor_name_snapshot'])) {
                        $found_name = $h['actor_name_snapshot'];
                    } elseif (!empty($h['fullname'])) {
                        $found_name = $h['fullname'];
                    } elseif (!empty($h['username'])) {
                        $found_name = $h['username'];
                    }
                    
                    if (empty($found_name)) {
                         $found_name = "User ID: " . ($h['action_by'] ?? 'Unknown');
                    }

                    // Image Check (เอา profile_img ออก)
                    $img_src = '';
                    if (!empty($h['actor_pic_snapshot'])) {
                        $img_src = $h['actor_pic_snapshot'];
                    }
                    
                    if (!empty($img_src)) {
                        $user_icon = "
                        <div class='me-2 position-relative' style='width:35px; height:35px;'>
                            <img src='$img_src' class='rounded-circle border w-100 h-100' style='object-fit:cover;' 
                                 onerror=\"this.style.display='none'; this.nextElementSibling.style.display='flex';\">
                            <div class='rounded-circle bg-light align-items-center justify-content-center border w-100 h-100 position-absolute top-0 start-0' style='display:none;'>
                                <i class='fas fa-user text-secondary'></i>
                            </div>
                        </div>";
                    } else {
                        $user_icon = "<div class='rounded-circle bg-light d-flex align-items-center justify-content-center me-2 border' style='width:35px; height:35px;'><i class='fas fa-user text-secondary'></i></div>";
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
                                    <span class='me-2'><i class='fas fa-network-wired me-1'></i> IP: $h_ip</span>
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
    // ส่วนที่ 2: Main Page Logic (แก้ไข SQL: เอา u.profile_img ออก)
    // ---------------------------------------------------------
    function getStatusBadge($status) {
        switch ($status) {
            case 'Received': return '<span class="badge rounded-pill bg-success">สำเร็จ/ได้รับแล้ว</span>';
            case 'Registered': return '<span class="badge rounded-pill bg-info text-dark">ลงทะเบียนใหม่</span>';
            case 'Sent': return '<span class="badge rounded-pill bg-warning text-dark">กำลังนำส่ง</span>';
            case 'Late': return '<span class="badge rounded-pill bg-danger">ล่าช้า</span>';
            case 'เปิดอ่าน': case 'Viewed': return '<span class="badge rounded-pill bg-light text-dark border">เปิดอ่าน</span>';
            default: return '<span class="badge rounded-pill bg-secondary">' . htmlspecialchars($status) . '</span>';
        }
    }

    // แก้ไข SQL: เอา u.profile_img ออก เพราะไม่มีในตาราง users
    $sql = "SELECT l.*, d.title, d.document_code, d.current_status, 
                   u.fullname, u.username, 
                   l.actor_name_snapshot, l.actor_pic_snapshot
            FROM document_status_log l 
            JOIN documents d ON l.document_id = d.document_id 
            LEFT JOIN users u ON l.action_by = u.user_id 
            ORDER BY l.action_time DESC 
            LIMIT 50";
            
    $history = CON::selectArrayDB([], $sql) ?? [];

    $historyRows = '';
    if (count($history) > 0) {
        foreach ($history as $row) {
            $time = date('d/m/Y H:i', strtotime($row['action_time']));
            $code = htmlspecialchars($row['document_code'] ?? '', ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $status = $row['status'] ?? '-';
            $ip = htmlspecialchars($row['ip_address'] ?? '', ENT_QUOTES, 'UTF-8');
            $device = htmlspecialchars($row['device_info'] ?? '-', ENT_QUOTES, 'UTF-8');
            $docId = $row['document_id'];

            // --- ส่วนระบุตัวตนคนสแกน ---
            $actorName = 'Unknown';
            if (!empty($row['actor_name_snapshot'])) {
                $actorName = $row['actor_name_snapshot'];
            } elseif (!empty($row['fullname'])) {
                $actorName = $row['fullname'];
            } elseif (!empty($row['username'])) {
                $actorName = $row['username'];
            }

            // รูปภาพคนสแกน (ใช้เฉพาะจาก Snapshot หรือ Default)
            $actorImg = 'assets/images/avatar_default.png'; 
            if (!empty($row['actor_pic_snapshot'])) {
                $actorImg = $row['actor_pic_snapshot'];
            }

            // HTML แสดง User
            $userDisplay = "
            <div class='d-flex align-items-center'>
                <div class='me-2' style='width:30px; height:30px;'>
                     <img src='$actorImg' class='rounded-circle border w-100 h-100' style='object-fit:cover;' 
                          onerror=\"this.src='https://via.placeholder.com/30?text=U';\">
                </div>
                <div class='text-dark fw-bold text-truncate' style='font-size:0.85rem; max-width: 150px;'>$actorName</div>
            </div>";
            // ------------------------------------------

            $codeLink = "<a href='javascript:void(0)' class='doc-link shadow-sm btn-open-detail' data-id='$docId'><i class='fas fa-search me-1'></i>$code</a>";
            $statusBadge = getStatusBadge($status);

            $historyRows .= "<tr>
                <td class='ps-4 text-muted small'>$time</td>
                <td>
                    $codeLink
                    <div class='mt-1 text-dark small'>$title</div>
                </td>
                <td>$userDisplay</td>
                <td>$statusBadge</td>
                <td class='text-muted small'>
                    <div><i class='fas fa-desktop me-1'></i>$ip</div>
                    <div class='text-secondary' style='font-size: 0.75rem;'><i class='fas fa-info-circle me-1'></i>$device</div>
                </td>
            </tr>";
        }
    } else {
        $historyRows = '<tr><td colspan="5" class="text-center py-5 text-muted">ยังไม่พบประวัติการทำงานในระบบ</td></tr>';
    }
?>

<style>
    /* สไตล์ปุ่มเลขเอกสาร */
    .doc-link {
        color: #29B6F6; font-weight: bold; text-decoration: none;
        background: rgba(41, 182, 246, 0.1); padding: 5px 12px; border-radius: 20px; transition: 0.2s; display: inline-block;
    }
    .doc-link:hover { background: #29B6F6; color: white; }
    
    .view-count-badge { font-size: 0.85rem; color: #555; background: #eee; padding: 5px 10px; border-radius: 15px; display: inline-flex; align-items: center; gap: 5px; }
</style>

<div class="page-content">
    
    <h5 class="mb-4 fw-bold text-secondary">**🕒 ประวัติการสแกน/อัปเดตล่าสุด (ทั้งหมด)**</h5>

    <div class="table-responsive rounded-4 shadow-sm border">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="py-3 ps-4">เวลา</th>
                    <th class="py-3">เอกสาร (กดเพื่อดูรายละเอียด)</th>
                    <th class="py-3">ผู้ดำเนินการ</th>
                    <th class="py-3">สถานะตอนสแกน</th>
                    <th class="py-3">อุปกรณ์</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $historyRows; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-alt me-2"></i>รายละเอียดเอกสาร</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
                </div>

                <div id="modalContent" style="display:none;">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h4 id="d_title" class="fw-bold text-primary mb-0">...</h4>
                                <span class="view-count-badge shadow-sm">
                                    <i class="far fa-eye text-primary"></i> <strong id="d_views" class="text-dark ms-1">0</strong>
                                </span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6"><small class="text-muted d-block">เลขทะเบียน</small><strong id="d_code" class="fs-5 text-dark">...</strong></div>
                                <div class="col-md-6"><small class="text-muted d-block">สถานะปัจจุบัน</small><span id="d_status" class="badge bg-secondary">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ประเภท</small><span id="d_type" class="text-dark">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">วันที่สร้าง</small><span id="d_date" class="text-dark">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ผู้ส่ง</small><span id="d_sender" class="text-dark">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ผู้รับ</small><span id="d_receiver" class="text-dark">...</span></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary ps-2 border-start border-4 border-primary mb-3">ประวัติการดำเนินงาน (Timeline)</h6>
                    <div id="d_timeline" class="timeline ms-1">
                        </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo $nonce; ?>">
document.addEventListener('DOMContentLoaded', function() {
    // ใช้ Event Delegation แทน onclick เพื่อหลีกเลี่ยง CSP Error
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
    // 1. เปิด Modal
    var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
    myModal.show();
    
    // 2. แสดง Loading / ซ่อน Content
    document.getElementById('modalLoading').style.display = 'block';
    document.getElementById('modalContent').style.display = 'none';
    
    // 3. เรียก AJAX
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('ajax_get_detail', '1');
    currentUrl.searchParams.set('doc_id', docId);

    fetch(currentUrl)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // ใส่ข้อมูลส่วนหัว (Details)
            document.getElementById('d_title').innerText = data.doc.title;
            document.getElementById('d_code').innerText = data.doc.code;
            document.getElementById('d_views').innerText = data.doc.view_count;
            document.getElementById('d_type').innerText = data.doc.type;
            document.getElementById('d_date').innerText = data.doc.created_at;
            document.getElementById('d_sender').innerText = data.doc.sender;
            document.getElementById('d_receiver').innerText = data.doc.receiver;
            
            // ใส่ Badge Status
            document.getElementById('d_status').innerHTML = `<span class='badge bg-info text-dark'>${data.doc.status}</span>`;

            // ใส่ข้อมูล Timeline (HTML)
            document.getElementById('d_timeline').innerHTML = data.history_html;

            // สลับการแสดงผล
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
        } else {
            alert('ไม่พบข้อมูลเอกสาร');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('d_timeline').innerHTML = '<div class="text-danger text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        document.getElementById('modalLoading').style.display = 'none';
        document.getElementById('modalContent').style.display = 'block';
    });
}
</script>