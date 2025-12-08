<?php 
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสถานะเอกสาร - EDE System</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-wrapper p-4 w-100">
        <h3 class="mb-4"><i class="fas fa-tags text-primary"></i> กำหนดสถานะการติดตาม (Custom Status)</h3>
        
        <div class="row">
            <!-- ฟอร์มเพิ่มสถานะ -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">เพิ่มสถานะใหม่</div>
                    <div class="card-body">
                        <form id="addStatusForm">
                            <div class="mb-3">
                                <label>ชื่อสถานะ</label>
                                <input type="text" name="status_name" class="form-control" placeholder="เช่น รอเซ็นอนุมัติ, ส่งกรมบัญชีกลาง" required>
                            </div>
                            <div class="mb-3">
                                <label>สีป้ายกำกับ</label>
                                <select name="color_class" class="form-select">
                                    <option value="secondary" class="text-secondary">สีเทา (ทั่วไป)</option>
                                    <option value="primary" class="text-primary">สีน้ำเงิน (ส่งต่อ)</option>
                                    <option value="info" class="text-info">สีฟ้า (แจ้งทราบ)</option>
                                    <option value="warning" class="text-warning">สีเหลือง (รอ/ระวัง)</option>
                                    <option value="success" class="text-success">สีเขียว (สำเร็จ)</option>
                                    <option value="danger" class="text-danger">สีแดง (ยกเลิก/ด่วน)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> เพิ่มสถานะ</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ตารางแสดงสถานะ -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">รายการสถานะที่มีอยู่</div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ชื่อสถานะ</th>
                                    <th>สีตัวอย่าง</th>
                                    <th>ประเภท</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="statusTableBody">
                                <!-- JS จะโหลดข้อมูลมาใส่ที่นี่ -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // โหลดข้อมูลเมื่อเปิดหน้า
    document.addEventListener('DOMContentLoaded', loadStatuses);

    function loadStatuses() {
        fetch('api/manage_status.php?action=list')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('statusTableBody');
                tbody.innerHTML = '';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(item => {
                        const isSystem = (item.created_by == null);
                        const typeBadge = isSystem 
                            ? '<span class="badge bg-secondary">ระบบ (Default)</span>' 
                            : '<span class="badge bg-info text-dark">กำหนดเอง</span>';
                        
                        const deleteBtn = isSystem 
                            ? '<button class="btn btn-sm btn-light" disabled><i class="fas fa-lock"></i></button>' 
                            : `<button onclick="deleteStatus(${item.status_id})" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>`;

                        const row = `
                            <tr>
                                <td>${item.status_name}</td>
                                <td><span class="badge bg-${item.color_class}">${item.status_name}</span></td>
                                <td>${typeBadge}</td>
                                <td>${deleteBtn}</td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3">ไม่พบข้อมูล</td></tr>';
                }
            });
    }

    // เพิ่มสถานะ
    document.getElementById('addStatusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('api/manage_status.php?action=add', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.reset();
                loadStatuses(); // รีโหลดตาราง
            } else {
                alert(data.message);
            }
        });
    });

    // ลบสถานะ
    function deleteStatus(id) {
        if(confirm('ต้องการลบสถานะนี้ใช่หรือไม่?')) {
            fetch(`api/manage_status.php?action=delete&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) loadStatuses();
                else alert(data.message);
            });
        }
    }
</script>
</body>
</html>