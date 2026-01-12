/**
 * Report Page Script
 * Handle date filtering, API fetching, and table rendering
 */

const API_BASE = '../api/index.php';

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
        });
    });

    // 3. ปุ่มค้นหา และ Reset
    const btnSearch = document.getElementById('btnSearch');
    if(btnSearch) btnSearch.addEventListener('click', loadReport);

    const btnReset = document.getElementById('btnReset');
    if(btnReset) btnReset.addEventListener('click', () => {
        setDateFilter('month');
    });

    // 4. ปุ่มพิมพ์ และ Export
    const btnPrint = document.getElementById('btnPrint');
    if(btnPrint) btnPrint.addEventListener('click', () => window.print());

    const btnExport = document.getElementById('btnExport');
    if(btnExport) btnExport.addEventListener('click', () => exportTableToExcel('reportTable', 'Report_Document_Grouped'));

    // โหลดข้อมูลครั้งแรก
    loadReport();
});

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
    // แก้ไขปี พ.ศ.
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

    if(tbody) tbody.innerHTML = '<tr><td colspan="6" class="py-5 text-center"><i class="fas fa-spinner fa-spin fa-2x text-secondary"></i><br>กำลังประมวลผล...</td></tr>';
    if(countSpan) countSpan.innerText = '0';

    fetch(`${API_BASE}?dev=report&start_date=${startDate}&end_date=${endDate}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data.length > 0) {
                renderTable(res.data);
                if(countSpan) countSpan.innerText = numberFormat(res.total_count);
            } else {
                if(tbody) tbody.innerHTML = '<tr><td colspan="6" class="py-5 text-center text-muted">ไม่พบข้อมูลในช่วงเวลาที่เลือก</td></tr>';
                if(countSpan) countSpan.innerText = '0';
            }
        })
        .catch(err => {
            console.error(err);
            if(tbody) tbody.innerHTML = '<tr><td colspan="6" class="py-5 text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
        });
}

function renderTable(data) {
    const tbody = document.getElementById('reportTableBody');
    if(!tbody) return;

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
            const isHeaderRow = rows[i].cells.length === 1;
            const bgStyle = isHeaderRow ? "style='background-color: #cce5ff; font-weight: bold;'" : "";
            
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