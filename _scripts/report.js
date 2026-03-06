/**
 * Report Page Script
 * Handle date filtering, API fetching, table rendering, and department filtering
 * Updated: Highlight active date filter button
 */

const API_BASE = '../api/index.php';
let RAW_REPORT_DATA = []; // ✅ ตัวแปร Global เก็บข้อมูลดิบทั้งหมด

document.addEventListener('DOMContentLoaded', () => {
    // 1. ป้องกัน Form Submit
    const searchForm = document.getElementById('searchForm');
    if(searchForm) {
        searchForm.addEventListener('submit', (e) => e.preventDefault());
    }

    // 2. ผูกปุ่มตัวกรองวันที่
    const filterBtns = document.querySelectorAll('.js-date-filter');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filterType = this.getAttribute('data-filter');
            setDateFilter(filterType);
            // ✅ อัปเดตสีปุ่มเมื่อกด
            updateActiveButton(filterType);
        });
    });

    // 3. ปุ่มค้นหา และ Reset
    const btnSearch = document.getElementById('btnSearch');
    if(btnSearch) btnSearch.addEventListener('click', loadReport);

    const btnReset = document.getElementById('btnReset');
    if(btnReset) btnReset.addEventListener('click', () => {
        // ✅ รีเซ็ตเป็น "ทั้งหมด"
        setDateFilter('all'); 
        updateActiveButton('all'); // อัปเดตสีปุ่มกลับเป็น All
        
        // Reset Dropdown แผนกด้วย
        const deptSelect = document.getElementById('deptFilter');
        if(deptSelect) deptSelect.value = 'all';
    });

    // 4. ปุ่มพิมพ์ และ Export
    const btnPrint = document.getElementById('btnPrint');
    if(btnPrint) btnPrint.addEventListener('click', () => window.print());

    const btnExport = document.getElementById('btnExport');
    if(btnExport) {
        btnExport.addEventListener('click', () => {
            // ตั้งชื่อไฟล์ตามแผนกที่เลือก
            const deptSelect = document.getElementById('deptFilter');
            const deptName = (deptSelect && deptSelect.value !== 'all') ? `_${deptSelect.value}` : '_All';
            exportTableToExcel('reportTable', `Report_Document${deptName}`);
        });
    }

    // 5. Event Listener สำหรับ Dropdown เลือกแผนก
    const deptSelect = document.getElementById('deptFilter');
    if (deptSelect) {
        deptSelect.addEventListener('change', filterAndRender);
    }

    // ✅✅✅ โหลดข้อมูลครั้งแรก ให้เป็น 'all' ทันที พร้อมไฮไลท์ปุ่ม
    setDateFilter('all');
    updateActiveButton('all');
});

// ✅ ฟังก์ชันเปลี่ยนสีปุ่มที่เลือก (Highlight Active Button)
function updateActiveButton(activeType) {
    const btns = document.querySelectorAll('.js-date-filter');
    
    btns.forEach(btn => {
        const btnType = btn.getAttribute('data-filter');
        
        if (btnType === activeType) {
            // ถ้าเป็นปุ่มที่เลือก: เปลี่ยนเป็นสีทึบ (ลบ outline)
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-secondary');
            btn.classList.add('fw-bold'); // เพิ่มตัวหนาให้ชัดขึ้น
        } else {
            // ถ้าไม่ใช่: กลับเป็นแบบโปร่ง (ใส่ outline)
            btn.classList.remove('btn-secondary');
            btn.classList.remove('fw-bold');
            btn.classList.add('btn-outline-secondary');
        }
    });
}

function setDateFilter(type) {
    const today = new Date();
    let start = new Date();
    let end = new Date();

    today.setHours(0,0,0,0); 

    if (type === 'today') {
        start = new Date(today);
        end = new Date(today);
    } else if (type === 'month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (type === 'year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    } else if (type === 'all') {
        start = new Date(2020, 0, 1);
        end = new Date(today);
    }

    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');

    if(startInput) startInput.value = formatDateToISO(start);
    if(endInput) endInput.value = formatDateToISO(end);
    
    loadReport();
}

function formatDateToISO(date) {
    if (!date) return '';
    const d = new Date(date);
    let year = d.getFullYear();
    // แก้ไขปี พ.ศ. (ถ้ามี)
    if (year > 2400) year = year - 543;
    let month = String(d.getMonth() + 1).padStart(2, '0');
    let day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function loadReport() {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    
    if(!startInput || !endInput) return;

    const startDate = startInput.value;
    const endDate = endInput.value;
    const tbody = document.getElementById('reportTableBody');
    const countSpan = document.getElementById('totalCount');

    // Reset Table
    if(tbody) tbody.innerHTML = '<tr><td colspan="6" class="py-5 text-center"><i class="fas fa-spinner fa-spin fa-2x text-secondary"></i><br>กำลังประมวลผล...</td></tr>';
    if(countSpan) countSpan.innerText = '0';

    fetch(`${API_BASE}?dev=report&start_date=${startDate}&end_date=${endDate}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data.length > 0) {
                // เก็บข้อมูลลงตัวแปร Global
                RAW_REPORT_DATA = res.data; 

                // สร้างตัวเลือกใน Dropdown แผนก
                populateDeptOptions(RAW_REPORT_DATA);

                // แสดงผล
                filterAndRender();
            } else {
                RAW_REPORT_DATA = [];
                if(tbody) tbody.innerHTML = '<tr><td colspan="6" class="py-5 text-center text-muted">ไม่พบข้อมูลในช่วงเวลาที่เลือก</td></tr>';
                if(countSpan) countSpan.innerText = '0';
            }
        })
        .catch(err => {
            console.error(err);
            if(tbody) tbody.innerHTML = '<tr><td colspan="6" class="py-5 text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
        });
}

// ฟังก์ชันสร้างตัวเลือกแผนก (Dropdown)
function populateDeptOptions(data) {
    const select = document.getElementById('deptFilter');
    if (!select) return;

    const currentValue = select.value;

    const departments = [...new Set(data.map(item => item.sender_dept || 'ไม่ระบุแผนก'))].sort();

    let html = '<option value="all">-- ทุกแผนก --</option>';
    departments.forEach(dept => {
        html += `<option value="${dept}">${dept}</option>`;
    });

    select.innerHTML = html;
    
    if (departments.includes(currentValue) || currentValue === 'all') {
        select.value = currentValue;
    }
}

// ฟังก์ชันกรองข้อมูลและวาดตาราง
function filterAndRender() {
    const select = document.getElementById('deptFilter');
    const filterValue = select ? select.value : 'all';
    const countSpan = document.getElementById('totalCount');

    let filteredData = RAW_REPORT_DATA;

    if (filterValue !== 'all') {
        filteredData = RAW_REPORT_DATA.filter(item => {
            const deptName = item.sender_dept || 'ไม่ระบุแผนก';
            return deptName === filterValue;
        });
    }

    if(countSpan) countSpan.innerText = numberFormat(filteredData.length);

    renderTable(filteredData);
}

function renderTable(data) {
    const tbody = document.getElementById('reportTableBody');
    if(!tbody) return;

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="py-5 text-center text-muted">ไม่พบข้อมูลที่ตรงกับเงื่อนไข</td></tr>';
        return;
    }

    let html = '';
    let currentDept = null;
    let runningNo = 1;

    data.forEach((row) => {
        const rowDept = row.sender_dept || 'ไม่ระบุแผนก (Unknown)';
        
        if (rowDept !== currentDept) {
            currentDept = rowDept;
            html += `
                <tr class="table-light border-bottom border-2">
                    <td colspan="6" class="py-3 ps-4 text-primary fw-bold" style="font-size: 1rem; background-color: #f8f9fa;">
                        <i class="fas fa-building me-2"></i> แผนก: ${currentDept}
                    </td>
                </tr>
            `;
        }

        let statusBadge = `<span class="badge bg-secondary">${row.current_status}</span>`;
        if(row.current_status === 'Received') statusBadge = `<span class="badge bg-warning text-dark">Received</span>`;
        else if(row.current_status === 'Sent') statusBadge = `<span class="badge bg-info text-dark">Sent</span>`;
        else if(row.current_status === 'Done' || row.current_status === 'ได้รับแล้ว') statusBadge = `<span class="badge bg-success">Success</span>`;

        const scanner = row.scanner_name ? `<span class="text-success"><i class="fas fa-qrcode me-1"></i>${row.scanner_name}</span>` : '<span class="text-muted">-</span>';

        html += `
            <tr>
                <td class="text-center text-muted">${runningNo++}</td>
                <td><small>${row.created_at_fmt}</small></td>
                <td><span class="text-dark fw-bold">${row.document_code}</span></td>
                <td>${row.title}</td>
                <td><small>${scanner}</small></td>
                <td class="text-center">${statusBadge}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function numberFormat(num) {
    return new Intl.NumberFormat('th-TH').format(num);
}

function exportTableToExcel(tableID, filename = '') {
    const table = document.getElementById(tableID);
    if(!table) return;

    let html = "<table border='1'>";
    const headers = table.querySelectorAll('thead th');
    if(headers.length > 0) {
        html += "<tr style='background-color: #eee;'>";
        for (let cell of headers) html += "<th>" + cell.innerText + "</th>";
        html += "</tr>";
    }
    
    const tbody = table.querySelector('tbody');
    const rows = tbody ? tbody.rows : table.rows;
    
    for (let i = 0; i < rows.length; i++) {
            const isGroupHeader = rows[i].cells.length === 1;
            const bgStyle = isGroupHeader ? "style='background-color: #cce5ff; font-weight: bold;'" : "";
            
            html += `<tr ${bgStyle}>`;
            for (let cell of rows[i].cells) {
                const colspan = cell.getAttribute('colspan') ? ` colspan="${cell.getAttribute('colspan')}"` : "";
                html += `<td${colspan}>` + cell.innerText + "</td>";
            }
            html += "</tr>";
    }
    html += "</table>";

    const link = document.createElement("a");
    const blob = new Blob([html], { type: "application/vnd.ms-excel" });
    link.href = URL.createObjectURL(blob);
    link.download = filename + ".xls";
    link.click();
}