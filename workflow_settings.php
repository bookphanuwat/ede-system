<?php 
session_start();
// if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่า Workflow - EDE System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link href="assets/css/style.css" rel="stylesheet"> 

    <style>
        body { background-color: #f8f9fa; }
        .header-workflow { background: linear-gradient(45deg, #fcc111ff, #fcc111ff); }
        .workflow-card { border-left: 5px solid #0d6efd; border-radius: 8px; transition: transform 0.2s; }
        .status-item { cursor: grab; position: relative; margin-right: 35px; margin-bottom: 10px; transition: transform 0.2s; }
        .status-item:active { cursor: grabbing; }
        .sortable-ghost { opacity: 0.4; background-color: #e9ecef; border: 1px dashed #999; border-radius: 20px; }
        .status-item:not(:last-child)::after { content: '\f054'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: -25px; top: 50%; transform: translateY(-50%); color: #ccc; font-size: 0.8rem; pointer-events: none; }
        .status-badge { font-size: 0.9rem; padding: 8px 18px; border-radius: 50px; display: inline-block; min-width: 120px; text-align: center; font-weight: 500; box-shadow: 0 2px 5px rgba(0,0,0,0.05); user-select: none; }
        .status-actions { position: absolute; top: -10px; right: -5px; background: white; border-radius: 10px; padding: 2px 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); opacity: 0; transition: opacity 0.2s; transform: scale(0.8); z-index: 10; }
        .status-item:hover .status-actions { opacity: 1; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-wrapper w-100 d-flex flex-column">
        <?php 
            $page_title = "ตั้งค่า Workflow";
            $header_class = "header-workflow"; 
            include 'includes/topbar.php'; 
        ?>

        <div class="p-4 flex-grow-1">
            <div class="alert alert-light border shadow-sm mb-4">
                <i class="fas fa-hand-pointer text-primary"></i> 
                <strong>Tip:</strong> คุณสามารถลากย้ายตำแหน่งสถานะได้ หมวดหมู่ "พื้นฐาน" จะไม่สามารถลบได้
            </div>

            <div class="row mb-3">
                <div class="col-12 text-end">
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus-circle"></i> เพิ่มหมวดหมู่
                    </button>
                </div>
            </div>

            <div id="workflowContainer">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Modals (เหมือนเดิม) -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">เพิ่มหมวดหมู่ใหม่</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4"><label class="form-label">ชื่อหมวดหมู่</label><input type="text" id="newCatName" class="form-control"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button><button type="button" class="btn btn-primary" onclick="addCategory()">บันทึก</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="addStatusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white"><h5 class="modal-title">เพิ่มสถานะ</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <input type="hidden" id="targetCatId">
                <div class="mb-3"><label class="form-label">ชื่อสถานะ</label><input type="text" id="newStatusName" class="form-control"></div>
                <div class="mb-3"><label class="form-label">สีป้าย</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="radio" class="btn-check" name="statusColor" id="c1" value="secondary" checked><label class="btn btn-outline-secondary rounded-pill px-3" for="c1">เทา</label>
                        <input type="radio" class="btn-check" name="statusColor" id="c2" value="info"><label class="btn btn-outline-info rounded-pill px-3" for="c2">ฟ้า</label>
                        <input type="radio" class="btn-check" name="statusColor" id="c3" value="warning"><label class="btn btn-outline-warning rounded-pill px-3" for="c3">เหลือง</label>
                        <input type="radio" class="btn-check" name="statusColor" id="c4" value="primary"><label class="btn btn-outline-primary rounded-pill px-3" for="c4">น้ำเงิน</label>
                        <input type="radio" class="btn-check" name="statusColor" id="c5" value="success"><label class="btn btn-outline-success rounded-pill px-3" for="c5">เขียว</label>
                        <input type="radio" class="btn-check" name="statusColor" id="c6" value="danger"><label class="btn btn-outline-danger rounded-pill px-3" for="c6">แดง</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button><button type="button" class="btn btn-info text-white" onclick="addStatus()">เพิ่ม</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark"><h5 class="modal-title">แก้ไขหมวดหมู่</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4"><input type="hidden" id="editCatId"><label class="form-label">ชื่อหมวดหมู่</label><input type="text" id="editCatName" class="form-control"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button><button type="button" class="btn btn-warning" onclick="updateCategory()">บันทึก</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStatusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark"><h5 class="modal-title">แก้ไขสถานะ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <input type="hidden" id="editStatusId"><input type="hidden" id="editStatusCatId">
                <div class="mb-3"><label class="form-label">ชื่อสถานะ</label><input type="text" id="editStatusName" class="form-control"></div>
                <div class="mb-3"><label class="form-label">สีป้าย</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="radio" class="btn-check" name="editStatusColor" id="ec1" value="secondary"><label class="btn btn-outline-secondary rounded-pill px-3" for="ec1">เทา</label>
                        <input type="radio" class="btn-check" name="editStatusColor" id="ec2" value="info"><label class="btn btn-outline-info rounded-pill px-3" for="ec2">ฟ้า</label>
                        <input type="radio" class="btn-check" name="editStatusColor" id="ec3" value="warning"><label class="btn btn-outline-warning rounded-pill px-3" for="ec3">เหลือง</label>
                        <input type="radio" class="btn-check" name="editStatusColor" id="ec4" value="primary"><label class="btn btn-outline-primary rounded-pill px-3" for="ec4">น้ำเงิน</label>
                        <input type="radio" class="btn-check" name="editStatusColor" id="ec5" value="success"><label class="btn btn-outline-success rounded-pill px-3" for="ec5">เขียว</label>
                        <input type="radio" class="btn-check" name="editStatusColor" id="ec6" value="danger"><label class="btn btn-outline-danger rounded-pill px-3" for="ec6">แดง</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button><button type="button" class="btn btn-warning" onclick="updateStatus()">บันทึก</button></div>
        </div>
    </div>
</div>

<script>
    const API_URL = 'api/manage_workflow.php'; 
    let workflowData = [];
    document.addEventListener('DOMContentLoaded', loadWorkflows);

    function loadWorkflows() {
        fetch(`${API_URL}?action=list`)
            .then(res => res.json())
            .then(res => { if(res.success) { workflowData = res.data; renderWorkflows(); } })
            .catch(err => console.error(err));
    }

    function renderWorkflows() {
        const container = document.getElementById('workflowContainer');
        if (workflowData.length === 0) {
            container.innerHTML = '<div class="text-center py-5 bg-white rounded shadow-sm border mt-3"><h5 class="text-muted">ไม่พบข้อมูล</h5></div>';
            return;
        }

        let html = '';
        workflowData.forEach(cat => {
            // เช็คว่าเป็น Category พื้นฐานหรือไม่ (ถ้าใช่ ห้ามลบ)
            const isSystemCat = (cat.id === 'cat_default');
            
            // ปุ่มแก้ไข Category (ถ้าเป็นพื้นฐาน อาจจะซ่อนปุ่มแก้ไขด้วยก็ได้ถ้าต้องการ แต่ที่นี้ซ่อนแค่ปุ่มลบ)
            // ปุ่มลบ Category
            const deleteCatBtn = isSystemCat 
                ? '<span class="badge bg-secondary ms-2"><i class="fas fa-lock"></i> Default</span>' 
                : `<button class="btn btn-outline-danger btn-sm" onclick="deleteCategory('${cat.id}')"><i class="fas fa-trash-alt"></i></button>`;

            let statusHtml = '';
            if (cat.statuses && cat.statuses.length > 0) {
                statusHtml = cat.statuses.map(st => {
                    // เช็คว่าเป็น Status พื้นฐานหรือไม่ (ID ขึ้นต้นด้วย st_def_)
                    const isSystemStatus = st.id.startsWith('st_def_');
                    
                    // ปุ่มลบ Status (ซ่อนถ้าเป็น System Status)
                    const deleteStatusBtn = isSystemStatus
                        ? '' // ไม่แสดงปุ่มลบ
                        : `<i class="fas fa-times text-danger cursor-pointer" onclick="deleteStatus('${cat.id}', '${st.id}')"></i>`;

                    return `
                    <div class="status-item" data-id="${st.id}">
                        <span class="badge bg-${st.color} status-badge">${st.name}</span>
                        <div class="status-actions">
                            <i class="fas fa-pen text-warning cursor-pointer me-1" 
                               onclick="openEditStatusModal('${cat.id}', '${st.id}', '${st.name}', '${st.color}')"></i>
                            ${deleteStatusBtn}
                        </div>
                    </div>`;
                }).join('');
            } else {
                statusHtml = '<div class="text-muted small py-2 nosort">ยังไม่มีสถานะ</div>';
            }

            html += `
                <div class="card workflow-card mb-4 shadow-sm bg-white">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width:40px; height:40px;">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div>
                                <h5 class="m-0 fw-bold text-dark d-inline-block">${cat.name}</h5>
                                ${!isSystemCat ? `<button class="btn btn-link text-muted p-0 ms-2" onclick="openEditCategoryModal('${cat.id}', '${cat.name}')"><i class="fas fa-pen" style="font-size:0.8rem"></i></button>` : ''}
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary btn-sm me-1" onclick="openAddStatusModal('${cat.id}', '${cat.name}')">
                                <i class="fas fa-plus"></i> เพิ่มสถานะ
                            </button>
                            ${deleteCatBtn}
                        </div>
                    </div>
                    <div class="card-body bg-light">
                        <div class="d-flex align-items-center flex-wrap p-3 bg-white rounded border sortable-list" data-cat-id="${cat.id}">
                            ${statusHtml}
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
        initSortable();
    }

    function initSortable() {
        document.querySelectorAll('.sortable-list').forEach(el => {
            new Sortable(el, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                filter: '.nosort',
                onEnd: function (evt) {
                    const container = evt.to;
                    const catId = container.getAttribute('data-cat-id');
                    const sortedIds = Array.from(container.querySelectorAll('.status-item')).map(item => item.getAttribute('data-id'));
                    updateOrder(catId, sortedIds);
                }
            });
        });
    }

    function updateOrder(catId, sortedIds) {
        const fd = new FormData(); fd.append('category_id', catId); fd.append('sorted_ids', JSON.stringify(sortedIds));
        fetch(`${API_URL}?action=reorder_status`, { method: 'POST', body: fd }).then(res=>res.json());
    }

    function callApi(action, formData, callback) {
        fetch(`${API_URL}?action=${action}`, { method: 'POST', body: formData }).then(res=>res.json()).then(res=>{
            if(res.success) { callback(); loadWorkflows(); }
            else { alert(res.message || 'Error'); }
        });
    }

    function addCategory() {
        const name = document.getElementById('newCatName').value;
        if(!name) return;
        const fd = new FormData(); fd.append('category_name', name);
        callApi('add_category', fd, () => {
            bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
            document.getElementById('newCatName').value='';
        });
    }
    
    function addStatus() {
        const catId = document.getElementById('targetCatId').value;
        const name = document.getElementById('newStatusName').value;
        const color = document.querySelector('input[name="statusColor"]:checked').value;
        if(!name) return;
        const fd = new FormData(); fd.append('category_id', catId); fd.append('status_name', name); fd.append('color_class', color);
        callApi('add_status', fd, () => {
            bootstrap.Modal.getInstance(document.getElementById('addStatusModal')).hide();
            document.getElementById('newStatusName').value='';
        });
    }

    function deleteCategory(id) { if(confirm('ลบหมวดหมู่นี้?')) { const fd = new FormData(); fd.append('id', id); callApi('delete_category', fd, ()=>{}); } }
    function deleteStatus(catId, stId) { if(confirm('ลบสถานะนี้?')) { const fd = new FormData(); fd.append('category_id', catId); fd.append('status_id', stId); callApi('delete_status', fd, ()=>{}); } }

    function openEditCategoryModal(id, name) { document.getElementById('editCatId').value = id; document.getElementById('editCatName').value = name; new bootstrap.Modal(document.getElementById('editCategoryModal')).show(); }
    function updateCategory() { const fd = new FormData(); fd.append('id', document.getElementById('editCatId').value); fd.append('category_name', document.getElementById('editCatName').value); callApi('edit_category', fd, () => bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide()); }

    function openEditStatusModal(catId, stId, name, color) { document.getElementById('editStatusCatId').value = catId; document.getElementById('editStatusId').value = stId; document.getElementById('editStatusName').value = name; const r = document.querySelector(`input[name="editStatusColor"][value="${color}"]`); if(r) r.checked = true; new bootstrap.Modal(document.getElementById('editStatusModal')).show(); }
    function updateStatus() { const fd = new FormData(); fd.append('category_id', document.getElementById('editStatusCatId').value); fd.append('status_id', document.getElementById('editStatusId').value); fd.append('status_name', document.getElementById('editStatusName').value); fd.append('color_class', document.querySelector('input[name="editStatusColor"]:checked').value); callApi('edit_status', fd, () => bootstrap.Modal.getInstance(document.getElementById('editStatusModal')).hide()); }
    
    function openAddStatusModal(catId, catName) { document.getElementById('targetCatId').value = catId; document.getElementById('modalCatName').innerText = catName; document.getElementById('c1').checked = true; new bootstrap.Modal(document.getElementById('addStatusModal')).show(); }
</script>

</body>
</html>