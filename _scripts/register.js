// register.js - JavaScript สำหรับหน้าลงทะเบียนเอกสาร

// ตรวจสอบว่ามีค่า Config จากหน้า PHP หรือไม่ ถ้าไม่มีให้ใช้ค่า Default
const API_URL = (typeof window.PAGE_API_URL !== 'undefined') 
                ? window.PAGE_API_URL 
                : 'api/manage_workflow.php';

// ดึง User ID จาก global
const userId = (typeof window.CURRENT_USER_ID !== 'undefined') 
               ? window.CURRENT_USER_ID 
               : '';

// ฟังก์ชันหลักที่ทำงานเมื่อโหลดหน้าเว็บเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    loadWorkflows();

    const wfSelect = document.getElementById('workflowSelect');
    if (wfSelect) {
        wfSelect.addEventListener('change', function() {
            const selectedId = this.value;
            const submitBtn = document.getElementById('btnSubmit');
            const statusPreview = document.getElementById('statusPreview');
            const initialStatusInput = document.getElementById('initialStatusInput');
            const workflowIdInput = document.getElementById('workflowIdInput');

            if (selectedId) {
                const selectedCat = allWorkflows.find(cat => cat.id === selectedId);

                if (selectedCat && selectedCat.statuses.length > 0) {
                    submitBtn.classList.remove('btn-disabled-custom');
                    submitBtn.style.backgroundColor = '#00E676';
                    submitBtn.style.color = 'black';

                    const firstStatus = selectedCat.statuses[0];
                    initialStatusInput.value = firstStatus.name;
                    workflowIdInput.value = selectedCat.id;

                    statusPreview.innerHTML = `<i class="fas fa-check-circle text-success"></i> สถานะเริ่มต้น: <span class="badge bg-${firstStatus.color}">${firstStatus.name}</span>`;
                } else {
                    alert('หมวดหมู่นี้ยังไม่มีการกำหนดสถานะ (Workflow ว่างเปล่า) กรุณาไปตั้งค่าก่อน');
                    this.value = "";
                    disableSubmit();
                }
            } else {
                disableSubmit();
            }
        });
    }
});

function disableSubmit() {
    const btn = document.getElementById('btnSubmit');
    const preview = document.getElementById('statusPreview');

    if (btn) {
        btn.classList.add('btn-disabled-custom');
        btn.removeAttribute('style');
    }

    if (preview) {
        preview.innerHTML = `<i class="fas fa-arrow-right"></i> สถานะเริ่มต้น: <span class="text-secondary">-</span>`;
    }
    
    const statusInput = document.getElementById('initialStatusInput');
    if (statusInput) statusInput.value = "";
}

function loadWorkflows() {
    const select = document.getElementById('workflowSelect');
    if (!select) return;

    select.innerHTML = '<option value="" disabled selected>กำลังโหลดข้อมูล...</option>';

    // [แก้ไข] ตรวจสอบตัวเชื่อม URL (ใช้ & ถ้ามี ? อยู่แล้ว)
    const separator = API_URL.includes('?') ? '&' : '?';

    fetch(`${API_URL}${separator}action=list&user_id=${userId}`)
        .then(res => res.json())
        .then(res => {
            select.innerHTML = '<option value="" selected disabled>-- กรุณาเลือกหมวดหมู่สถานะ --</option>';

            if (res.success && res.data.length > 0) {
                allWorkflows = res.data;
                res.data.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="" disabled>ไม่พบข้อมูล (ต้องสร้างหมวดหมู่ก่อน)</option>';
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="" disabled>Error loading workflows</option>';
        });
}