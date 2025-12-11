# EDE System - โครงสร้างที่ปรับปรุงแล้ว

## สรุปการปรับปรุง

### ✅ สิ่งที่ดำเนินการเสร็จแล้ว

1. **สร้างโครงสร้างใหม่แบบ goodcatalog**
   - ✅ สร้างโฟลเดอร์ `pages/` สำหรับแยกหน้าต่างๆ
   - ✅ สร้างโฟลเดอร์ `_scripts/` สำหรับ JavaScript
   - ✅ ปรับปรุง `index.php` ให้ใช้ switch case routing

2. **JavaScript Files ใน _scripts/**
   - `global.js` - ฟังก์ชันที่ใช้ร่วมกันทั้งระบบ
   - `dashboard.js` - JavaScript สำหรับหน้า Dashboard
   - `register.js` - JavaScript สำหรับหน้าลงทะเบียน

3. **Pages Files ใน pages/**
   - `main-menu.php` - หน้าเมนูหลัก
   - `dashboard-page.php` - หน้า Dashboard
   - `register-page.php` - หน้าลงทะเบียนเอกสาร
   - `tracking-page.php` - หน้าติดตามเอกสาร
   - `report-page.php` - หน้ารายงาน (ยังไม่มีโค้ดเต็ม)
   - `settings-page.php` - หน้าตั้งค่า (ยังไม่มีโค้ดเต็ม)
   - `scan-history-page.php` - หน้าประวัติการสแกน (ยังไม่มีโค้ดเต็ม)
   - `workflow-settings-page.php` - หน้าจัดการสถานะ (ยังไม่มีโค้ดเต็ม)
   - `page-not-found.php` - หน้า 404

4. **Routing System**
   - URL เดิม: `dashboard.php`, `register.php`, `tracking.php`
   - URL ใหม่: `index.php?page=dashboard`, `index.php?page=register`, `index.php?page=tracking`
   - อัพเดท `includes/sidebar.php` ให้ใช้ลิงก์แบบใหม่

5. **ไฟล์สำรอง**
   - สำรอง `index.php` เดิมเป็น `index-old.php` แล้ว

### ⚠️ สิ่งที่ต้องทำต่อ

1. **ทดสอบระบบ**
   - ทดสอบการ routing ว่าทำงานถูกต้อง
   - ทดสอบ JavaScript ที่แยกออกมาแล้ว
   - ตรวจสอบ links ทั้งหมดว่าเชื่อมต่อถูกต้อง

2. **เพิ่มเนื้อหาหน้าที่ยังไม่เสร็จ**
   - `report-page.php` - ยังต้องเพิ่มโค้ดเต็ม
   - `settings-page.php` - ยังต้องเพิ่มโค้ดเต็ม
   - `scan-history-page.php` - ยังต้องเพิ่มโค้ดเต็ม
   - `workflow-settings-page.php` - ยังต้องเพิ่มโค้ดเต็ม

3. **จัดระเบียบ CSS**
   - รวม inline styles ไปไว้ใน `assets/css/style.css`
   - ลบ `<style>` tags ที่ซ้ำซ้อน

4. **ไฟล์เก่าที่ยังอยู่**
   - `dashboard.php`, `register.php`, `tracking.php`, etc.
   - สามารถลบได้หลังจากทดสอบว่าระบบใหม่ทำงานได้แล้ว

### 📁 โครงสร้างใหม่

```
ede-system/
├── index.php                    # Main routing file (ใช้ switch case)
├── index-old.php               # Backup ไฟล์เดิม
├── login.php                   # หน้า login (ยังคงเดิม)
├── logout.php                  # หน้า logout (ยังคงเดิม)
├── print/             # พิมพ์ใบปะหน้า (ยังคงเดิม)
├── _scripts/                   # JavaScript files
│   ├── global.js
│   ├── dashboard.js
│   └── register.js
├── pages/                      # Page components
│   ├── main-menu.php
│   ├── dashboard-page.php
│   ├── register-page.php
│   ├── tracking-page.php
│   ├── report-page.php
│   ├── settings-page.php
│   ├── scan-history-page.php
│   ├── workflow-settings-page.php
│   └── page-not-found.php
├── includes/                   # Common includes
│   ├── sidebar.php            # อัพเดทลิงก์แล้ว
│   └── topbar.php
├── api/                        # API endpoints (ไม่เปลี่ยนแปลง)
├── assets/                     # CSS & other assets
│   └── css/
│       └── style.css
├── config/                     # Configuration files
└── database/                   # Database schemas
```

### 🔗 URL Routing

| หน้า | URL เดิม | URL ใหม่ |
|------|----------|----------|
| เมนูหลัก | `index.php` | `index.php` หรือ `index.php?page=main` |
| Dashboard | `dashboard.php` | `index.php?page=dashboard` |
| ลงทะเบียน | `register.php` | `index.php?page=register` |
| ติดตาม | `tracking.php` | `index.php?page=tracking` |
| รายงาน | `report.php` | `index.php?page=report` |
| ตั้งค่า | `settings.php` | `index.php?page=settings` |
| ประวัติสแกน | `scan_history.php` | `index.php?page=scan-history` |
| จัดการสถานะ | `workflow_settings.php` | `index.php?page=workflow-settings` |

### 📝 หมายเหตุ

- โครงสร้างใหม่ทำให้ code เป็นระเบียบและบำรุงรักษาง่ายขึ้น
- JavaScript แยกออกจาก HTML ทำให้ debug ง่าย
- Routing แบบใหม่ทำให้ควบคุม flow ได้ดีขึ้น
- ไฟล์เดิมยังคงอยู่เพื่อ reference และสามารถลบได้หลังจากทดสอบเสร็จ
