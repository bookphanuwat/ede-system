<?php
    $page_title   = "รายงาน";
    $header_class = "header-report";
    include 'includes/topbar.php';
    
    // กำหนดค่าเริ่มต้น
    $default_start = date('Y-m-01');
    $default_end   = date('Y-m-t');
    
    $is_admin = ( stripos( $_SESSION['role'] ?? '', 'admin' ) !== false );
    $user_dept = $_SESSION['department'] ?? '';
?>

<div class="page-content">
    <h5 class="mb-4 fw-bold text-secondary">**📊 สรุปการรับ-ส่งเอกสารตามหน่วยงาน** <?php echo $is_admin ? '(ทั้งหมด)' : "(เฉพาะแผนก $user_dept)"; ?></h5>

    <form id="searchForm" class="row justify-content-center mb-5" onsubmit="return false;">
        <div class="col-md-9 text-center">
            <div class="d-flex align-items-center justify-content-center gap-2 bg-light p-3 rounded-pill shadow-sm border">
                <span class="fw-bold text-secondary"><i class="far fa-calendar-alt"></i> ช่วงเวลา:</span>
                <input type="date" id="start_date" class="form-control rounded-pill border-0 custom-input py-2" style="max-width: 160px;" value="<?php echo $default_start; ?>">
                <span class="text-muted">ถึง</span>
                <input type="date" id="end_date" class="form-control rounded-pill border-0 custom-input py-2" style="max-width: 160px;" value="<?php echo $default_end; ?>">
                
                <button type="button" id="btnSearch" class="btn btn-danger rounded-circle shadow-sm" style="width: 40px; height: 40px;">
                    <i class="fas fa-search"></i>
                </button>
                <button type="button" id="btnReset" class="btn btn-secondary rounded-circle shadow-sm" style="width: 40px; height: 40px;">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </form>

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
            <tbody id="reportTableBody">
                <tr><td colspan="4" class="py-5 text-muted">กำลังโหลดข้อมูล...</td></tr>
            </tbody>
            <tfoot id="reportTableFoot" class="table-secondary fw-bold" style="display: none;">
                <tr>
                    <td class="text-end pe-3">รวมทั้งสิ้น</td>
                    <td class="text-success" id="sumSent">0</td>
                    <td class="text-primary" id="sumRecv">0</td>
                    <td id="sumTotal">0</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="text-end mt-4 mx-auto" style="max-width: 900px;">
        <button id="btnPrint" class="btn btn-outline-dark border-0 fw-bold rounded-pill px-4">
            <i class="fas fa-print me-2"></i>พิมพ์
        </button>
        <button id="btnExport" class="btn btn-success border-0 fw-bold rounded-pill px-4 ms-2" style="background-color: #1D6F42;">
            <i class="fas fa-file-excel me-2"></i>Export สรุป
        </button>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-list-ul me-2"></i>รายละเอียดเอกสาร</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <strong class="text-secondary" id="modalDeptName">-</strong> | 
                    <span id="modalTypeBadge" class="badge bg-secondary">Unknown</span>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped mb-0 align-middle" style="font-size: 0.9rem;" id="detailTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-4">รหัสเอกสาร</th>
                                <th>ชื่อเรื่อง</th>
                                <th>เกี่ยวข้องกับ</th>
                                <th>สถานะ</th>
                                <th>วันที่</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" id="btnExportDetail" class="btn btn-success rounded-pill px-4 me-auto">
                    <i class="fas fa-file-excel me-2"></i>Export รายการนี้
                </button>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo $nonce; ?>">
    // Config API URL
    const API_BASE = '../api/index.php';

    document.addEventListener('DOMContentLoaded', () => {
        // Event Listeners
        const btnSearch = document.getElementById('btnSearch');
        if(btnSearch) btnSearch.addEventListener('click', loadReport);

        const btnReset = document.getElementById('btnReset');
        if(btnReset) btnReset.addEventListener('click', resetForm);

        const btnPrint = document.getElementById('btnPrint');
        if(btnPrint) btnPrint.addEventListener('click', () => window.print());

        const btnExport = document.getElementById('btnExport');
        if(btnExport) btnExport.addEventListener('click', () => exportTableToExcel('reportTable', 'Report_Summary'));

        // Event Delegation สำหรับลิงก์ในตาราง
        const tableBody = document.getElementById('reportTableBody');
        if(tableBody) {
            tableBody.addEventListener('click', function(e) {
                const link = e.target.closest('.js-view-detail');
                if (link) {
                    e.preventDefault();
                    const dept = link.getAttribute('data-dept');
                    const type = link.getAttribute('data-type');
                    viewDetail(dept, type);
                }
            });
        }

        // โหลดข้อมูลเริ่มต้น
        loadReport();
    });

    function loadReport() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const tbody = document.getElementById('reportTableBody');
        const tfoot = document.getElementById('reportTableFoot');

        tbody.innerHTML = '<tr><td colspan="4" class="py-5"><i class="fas fa-spinner fa-spin fa-2x text-secondary"></i><br>กำลังประมวลผล...</td></tr>';
        tfoot.style.display = 'none';

        fetch(`${API_BASE}?dev=report&start_date=${startDate}&end_date=${endDate}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data.length > 0) {
                    renderTable(res.data, res.summary);
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="py-5 text-muted">ไม่พบข้อมูลในช่วงเวลาที่เลือก</td></tr>';
                    tfoot.style.display = 'none';
                }
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="4" class="py-5 text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            });
    }

    function renderTable(data, summary) {
        const tbody = document.getElementById('reportTableBody');
        const tfoot = document.getElementById('reportTableFoot');
        let html = '';

        data.forEach(row => {
            const sentLink = row.sent > 0 
                ? `<a href="#" class="js-view-detail text-decoration-none fw-bold text-success hover-zoom" data-dept="${row.department}" data-type="sent">${numberFormat(row.sent)}</a>` 
                : '<span class="text-muted opacity-50">0</span>';

            const recvLink = row.received > 0 
                ? `<a href="#" class="js-view-detail text-decoration-none fw-bold text-primary hover-zoom" data-dept="${row.department}" data-type="received">${numberFormat(row.received)}</a>` 
                : '<span class="text-muted opacity-50">0</span>';

            html += `
                <tr>
                    <td class='text-start ps-5 fw-bold text-secondary'>${row.department}</td>
                    <td style='font-size: 1.1rem;'>${sentLink}</td>
                    <td style='font-size: 1.1rem;'>${recvLink}</td>
                    <td class='text-secondary fw-bold'>${numberFormat(row.total)}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        document.getElementById('sumSent').innerText = numberFormat(summary.sent);
        document.getElementById('sumRecv').innerText = numberFormat(summary.received);
        document.getElementById('sumTotal').innerText = numberFormat(summary.total);
        tfoot.style.display = 'table-footer-group';
    }

    function viewDetail(department, type) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const modalBody = document.getElementById('modalTableBody');
        
        // Header Config
        document.getElementById('modalDeptName').innerText = "แผนก: " + department;
        const typeBadge = document.getElementById('modalTypeBadge');
        if(type === 'sent') {
            typeBadge.className = 'badge bg-success';
            typeBadge.innerText = 'รายการส่งออก (Sent)';
        } else {
            typeBadge.className = 'badge bg-primary';
            typeBadge.innerText = 'รายการรับเข้า (Received)';
        }

        // [แก้ไข 3] ตั้งค่าปุ่ม Export ใน Modal ให้ทำงานกับข้อมูลชุดนี้
        const btnExportDetail = document.getElementById('btnExportDetail');
        if(btnExportDetail) {
            // ตั้ง onclick ผ่าน JS เพื่อให้รองรับชื่อไฟล์แบบ Dynamic
            btnExportDetail.onclick = function() {
                const safeDept = department.replace(/\s+/g, '_'); // แทนช่องว่างด้วย _
                const filename = `Report_${safeDept}_${type}_${startDate}`;
                exportTableToExcel('detailTable', filename);
            };
        }

        // Open Modal
        const modalEl = document.getElementById('detailModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        
        modalBody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</td></tr>';

        // Fetch Data
        fetch(`${API_BASE}?dev=report_detail&department=${encodeURIComponent(department)}&type=${type}&start_date=${startDate}&end_date=${endDate}`)
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success' && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(item => {
                        const targetUser = item.target_name || '-';
                        html += `
                            <tr>
                                <td class="ps-4 fw-bold text-primary">${item.document_code}</td>
                                <td>${item.title}</td>
                                <td><small class="text-muted">${type === 'sent' ? 'ผู้รับ:' : 'ผู้ส่ง:'}</small> ${targetUser}</td>
                                <td><span class="badge bg-info text-dark">${item.current_status}</span></td>
                                <td><small>${item.created_at_fmt}</small></td>
                            </tr>
                        `;
                    });
                    modalBody.innerHTML = html;
                } else {
                    modalBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูลรายละเอียด</td></tr>';
                }
            })
            .catch(err => {
                console.error(err);
                modalBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">เกิดข้อผิดพลาดในการดึงข้อมูล</td></tr>';
            });
    }

    function resetForm() {
        document.getElementById('start_date').value = "<?php echo $default_start; ?>";
        document.getElementById('end_date').value = "<?php echo $default_end; ?>";
        loadReport();
    }

    function numberFormat(num) {
        return new Intl.NumberFormat('th-TH').format(num);
    }
    
    // ฟังก์ชัน Export Excel (ใช้ได้ทั้งตารางหลักและตารางใน Modal)
    function exportTableToExcel(tableID, filename = '') {
        const table = document.getElementById(tableID);
        if(!table) return;

        let html = "<table>";
        // Header
        html += "<tr>";
        // รองรับทั้ง th ใน thead และ tr แรก
        const headers = table.querySelectorAll('thead th');
        if(headers.length > 0) {
            for (let cell of headers) html += "<th>" + cell.textContent + "</th>";
        } else {
            if(table.rows[0]) {
                for (let cell of table.rows[0].cells) html += "<th>" + cell.textContent + "</th>";
            }
        }
        html += "</tr>";
        
        // Body (ตาราง Modal อาจมี tbody)
        const tbody = table.querySelector('tbody');
        const rows = tbody ? tbody.rows : table.rows;
        
        for (let i = 0; i < rows.length; i++) {
             // ข้าม row ที่เป็น header ถ้าไม่ได้แยก thead/tbody ชัดเจน
             if(!tbody && i === 0 && headers.length === 0) continue; 
             
             html += "<tr>";
             for (let cell of rows[i].cells) {
                 html += "<td>" + cell.textContent + "</td>";
             }
             html += "</tr>";
        }

        // Footer (เฉพาะตารางหลักที่มี tfoot)
        const tfoot = table.querySelector('tfoot');
        if (tfoot && tfoot.style.display !== 'none') {
            html += "<tr>";
            for (let cell of tfoot.rows[0].cells) html += "<td><b>" + cell.textContent + "</b></td>";
            html += "</tr>";
        }
        html += "</table>";

        const link = document.createElement("a");
        const blob = new Blob([html], { type: "application/vnd.ms-excel" });
        link.href = URL.createObjectURL(blob);
        link.download = filename + ".xls";
        link.click();
    }
</script>

<style>
    .hover-zoom:hover {
        transform: scale(1.2);
        display: inline-block;
        transition: transform 0.2s;
        text-decoration: underline !important;
    }
</style>