# EDE System - Copilot Instructions

## Project Overview

**EDE System** (Electronic Document Exchange System) is a PHP-based web application for document management and tracking at a hospital. The system features a modular architecture with page-based routing, RESTful APIs, and comprehensive security implementations.

### Key Features
- 📋 Document registration and tracking
- 📊 Dashboard with statistics
- 📱 LIFF Scan integration (LINE Bot support)
- 📈 Reporting and analytics
- 👥 User and role management
- 🔄 Workflow/status management
- 🖨️ Document printing with barcode/QR code

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 7.4+ (modern features) |
| **Database** | MySQL with PDO (utf8mb4) |
| **Frontend** | Bootstrap 5, Vanilla JavaScript |
| **Build Tools** | SCSS, UglifyJS (minification) |
| **Security** | CSRF tokens, password hashing, CSP headers |
| **Dependencies** | Composer (Endroid QR Code) |

---

## Project Structure

```
ede-system/
├── index.php ........................ Main entry point (switch case routing)
├── login.php ........................ Authentication page
├── logout.php ....................... Session cleanup
├── composer.json .................... PHP dependencies
│
├── config/
│   └── db.php ....................... Database connection (PDO)
│
├── includes/
│   ├── sidebar.php .................. Navigation sidebar (shared)
│   └── topbar.php ................... Header/top navigation
│
├── pages/ ........................... Page components (routable)
│   ├── main-menu.php ................ Main menu page
│   ├── dashboard-page.php ........... Dashboard with stats
│   ├── register-page.php ............ Document registration
│   ├── tracking-page.php ............ Document tracking
│   ├── liff-scan.php ................ LINE LIFF scan interface
│   ├── report-page.php .............. Reports/analytics
│   ├── scan-history-page.php ........ Scan history
│   ├── workflow-settings-page.php ... Status/workflow config
│   ├── settings-page.php ............ System settings
│   ├── user-page.php ................ User management
│   └── page-not-found.php ........... 404 page
│
├── api/ ............................. REST API endpoints
│   ├── index.php .................... API dispatcher
│   ├── auth.php ..................... Login/authentication
│   ├── save_user.php ................ User CRUD operations
│   ├── delete_user.php .............. User deletion
│   ├── public_register.php .......... Public document registration
│   └── data/
│       └── workflow_data.json ....... Workflow status definitions
│
├── js/ .............................. Minified JavaScript (production)
│   ├── global.min.js
│   ├── dashboard.min.js
│   ├── register.min.js
│   ├── report.min.js
│   ├── liffscan.min.js
│   ├── qrcode.min.js
│   ├── Sortable.min.js
│   └── sdk.js ....................... External SDK
│
├── _scripts/ ........................ Source JavaScript (development)
│   ├── global.js .................... Shared functions
│   ├── dashboard.js ................. Dashboard features
│   └── register.js .................. Registration features
│
├── css/ ............................. Compiled CSS
│   └── main.min.css ................. Minified stylesheet
│
├── _styles/ ......................... SCSS source files
│   └── main.scss .................... Main stylesheet
│
├── assets/ .......................... Third-party libraries
│   ├── bootstrap/ ................... Bootstrap framework
│   ├── @fortawesome/ ................ Font Awesome icons
│   ├── sweetalert2/ ................. Alert dialogs
│   └── select2/ ..................... Select dropdown
│
├── database/
│   └── ede_system.sql ............... Database schema
│
├── print/
│   └── index.php .................... Document printing (QR/barcode)
│
├── vendor/ .......................... Composer dependencies
└── RESTRUCTURE.md ................... Project restructure notes
```

---

## Routing System

### How Routing Works

The application uses **URL parameter-based routing** (not URL rewriting):

```
URL Pattern: index.php?dev=PAGE_NAME
```

| URL Parameter | Page File | Component |
|--------------|-----------|-----------|
| `?dev=` or empty | `main-menu.php` | Main menu |
| `?dev=dashboard` | `dashboard-page.php` | Dashboard |
| `?dev=register` | `register-page.php` | Register document |
| `?dev=tracking` | `tracking-page.php` | Track document |
| `?dev=scan-history` | `scan-history-page.php` | Scan history |
| `?dev=report` | `report-page.php` | Reports |
| `?dev=workflow-settings` | `workflow-settings-page.php` | Workflow config |
| `?dev=settings` | `settings-page.php` | System settings |
| `?dev=liffscan` | `liff-scan.php` | LINE LIFF integration |
| `?dev=users` | `user-page.php` | User management |

### Routing Implementation (index.php)

```php
// Line ~40-60
switch ( $GET_DEV ) {
    case '':
    case 'main':
        $jsReq = '';
        $pageFile = 'pages/main-menu.php';
        break;
    
    case 'dashboard':
        $jsReq = 'dashboard';
        $pageFile = 'pages/dashboard-page.php';
        break;
    
    // ... more cases ...
    
    default:
        $pageFile = 'pages/page-not-found.php';
        break;
}
```

### Creating a New Page

1. Create file in `pages/xxx-page.php`
2. Add case in `index.php` switch statement
3. Add navigation link in `includes/sidebar.php`
4. Create/update JavaScript in `_scripts/` if needed

---

## Security Implementation

### 1. **Authentication & Session**
- Session-based authentication (checked in `index.php`)
- Session hijacking prevention: `session_regenerate_id(true)`
- HttpOnly cookies: `session.cookie_httponly = 1`
- Secure cookies: `session.cookie_secure = 1`

```php
// Check in index.php line ~25
if ( !isset( $_SESSION['user_id'] ) && $dev_mode !== 'liffscan' ) {
    header( "Location: login.php" );
    exit;
}
```

### 2. **CSRF Protection**
- Nonce-based tokens in forms
- Session token validation in API endpoints

```php
// Example in api/save_user.php
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error: Invalid CSRF Token");
}
```

### 3. **Content Security Policy (CSP)**
Configured in `index.php` line ~11:

```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://static.line-scdn.net; ...");
```

### 4. **Input Validation**
- Username only alphanumeric + underscore: `/^[a-zA-Z0-9_]+$/`
- Path traversal prevention: `preg_match('/(\.\.|[\/\\\\])/', $data)`
- XSS prevention: `strip_tags()`, `htmlspecialchars()`
- SQL Injection prevention: Prepared statements with PDO

### 5. **Password Policy**
- Minimum 12 characters
- Must contain letters and numbers
- Hashed with `password_hash()` (bcrypt)
- Verified with `password_verify()`

### 6. **Error Handling**
- Production errors logged to file, not displayed
- Test errors shown with `error_reporting(E_ALL)`

```php
// config/db.php
catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    echo "เกิดข้อผิดพลาดในการเชื่อมต่อระบบ";
    exit;
}
```

---

## Database & Models

### Database Configuration

**File:** `config/db.php`

```php
$host     = '127.0.0.1';      // Use IP not localhost
$dbname   = 'ede_system';      // Database name
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password, $options);
```

### Key Tables (from database/ede_system.sql)

| Table | Purpose |
|-------|---------|
| `users` | User accounts with credentials |
| `roles` | User role definitions |
| `documents` | Document registry |
| `document_status` | Document workflow tracking |
| `workflow_categories` | Status category definitions |
| `scan_logs` | LIFF scan history |

### Workflow Data Structure

**File:** `api/data/workflow_data.json`

```json
{
    "value": [
        {
            "id": "cat_default",
            "name": "สถานะพื้นฐาน (General)",
            "created_by": "system",
            "statuses": [
                {
                    "id": "st_def_1",
                    "name": "ลงทะเบียนเกิดเอกสารใหม่",
                    "color": "#6c757d"
                }
            ]
        }
    ]
}
```

---

## API Endpoints

### Authentication Endpoints

#### `POST /api/auth.php`
- **Purpose:** User login
- **Parameters:** `username`, `password`, `csrf_token`
- **Response:** Sets session, redirects to index.php
- **Security:** Password verified with bcrypt

#### `POST /api/public_register.php`
- **Purpose:** Public document registration (no auth required)
- **Parameters:** Document form data

### User Management

#### `POST /api/save_user.php`
- **Purpose:** Create or update user
- **Parameters:** `user_id`, `username`, `fullname`, `department`, `password`, `role_id`
- **Security:** Role-based access control

#### `POST /api/delete_user.php`
- **Purpose:** Delete user by ID
- **Parameters:** `user_id`
- **Security:** Admin only

### API Response Pattern

```php
// Standard response format (recommend)
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Operation successful',
    'data' => $resultData
]);
```

---

## Development Workflow

### Running the Application

1. **Database Setup**
   ```sql
   mysql> source database/ede_system.sql
   ```

2. **Access Application**
   ```
   http://localhost/ede-system/
   Login page → Enter credentials → index.php (routing starts)
   ```

3. **XAMPP Prerequisites**
   - MySQL running
   - PHP 7.4+ enabled
   - Composer installed (for dependencies)

### Installing Dependencies

```bash
cd c:\xampp\htdocs\ede-system
composer install
```

### Building Assets (Optional)

- **SCSS to CSS:** Use your SCSS compiler on `_styles/main.scss`
- **Minify JS:** Use UglifyJS or similar on files in `_scripts/`
- Output to `css/main.min.css` and `js/*.min.js`

### Common Development Tasks

#### Adding a New Page
1. Create `pages/new-feature-page.php`
2. Add case in `index.php` switch (line ~60)
3. Update `includes/sidebar.php` with link
4. Add CSS to `_styles/main.scss`
5. Add JS to `_scripts/new-feature.js` → minify to `js/`

#### Adding API Endpoint
1. Create `api/new_endpoint.php`
2. Check authentication if needed
3. Validate CSRF token
4. Use prepared statements
5. Return JSON response

#### Updating Database
1. Modify schema in `database/ede_system.sql`
2. Create migration or backup
3. Run SQL updates
4. Test affected pages

---

## Key Patterns & Conventions

### 1. **Variable Naming**
- Session variables: `$_SESSION['user_id']`, `$_SESSION['role']`
- GET parameters: `$_GET['dev']` → sanitized to `$GET_DEV`
- DB model: `$pdo` (PDO connection)

### 2. **File Naming**
- Pages: `words-separated-by-dash-page.php`
- API: `snake_case.php`
- JS: `filename.min.js` (minified)
- Partials: `includes/component.php`

### 3. **Function Naming**
- Common functions in `_scripts/global.js`:
  - `showAlert(type, message)` - SweetAlert2 wrapper
  - `showLoader()` / `hideLoader()` - Loading indicator
  - `fetchApi(method, endpoint, data)` - AJAX request

### 4. **HTML Structure**
- Bootstrap 5 grid system
- Container layout: `.container` or `.container-fluid`
- Sidebar present except in `?dev=liffscan` mode

### 5. **CSS Classes**
- Navigation buttons: `.nav-btn`, `.btn-dashboard`, `.btn-register`, etc.
- Color variables: `--color-dashboard`, `--color-register`, `--color-tracking`
- Icons: FontAwesome 6 classes (e.g., `fa-chart-pie`)

### 6. **Data Types**
- User roles: 'Administrator', 'User'
- Timestamps: MySQL DATETIME format
- IDs: Auto-increment integers
- Status colors: Hex codes (e.g., `#6c757d`)

---

## Important Security Notes

✅ **DO:**
- Use `$pdo->prepare()` and `execute()` for SQL
- Validate all user inputs
- Use `password_hash()` and `password_verify()`
- Check `$_SESSION['user_id']` on protected pages
- Sanitize output with `htmlspecialchars()`
- Use CSRF tokens in forms

❌ **DON'T:**
- Never use `mysql_*` functions (deprecated)
- Don't put user input directly in SQL
- Don't store plain text passwords
- Don't show database errors to users
- Don't use `eval()` or `include($_GET[...])` pattern
- Don't disable CSRF validation

---

## Troubleshooting Guide

### Issue: "เกิดข้อผิดพลาดในการเชื่อมต่อระบบ"
**Cause:** Database connection failed
- Check MySQL is running
- Verify credentials in `config/db.php`
- Check database name `ede_system` exists

### Issue: Page redirects to login.php
**Cause:** Session check failed
- Verify user session is set after login
- Check `api/auth.php` logic
- Clear browser cookies if corrupted

### Issue: CSRF token error
**Cause:** Form token mismatch
- Ensure form includes hidden field: `<input type="hidden" name="csrf_token" value="...">`
- Session must persist across request
- Check `session_start()` called in file

### Issue: Special characters display incorrectly
**Cause:** Character encoding issue
- All files must be UTF-8 encoded
- Database charset: `utf8mb4`
- HTML: `<meta charset="utf-8">`

---

## Performance Tips

1. **Database Queries:** Use proper indexes on frequently queried columns
2. **Assets:** All CSS/JS are minified, avoid inline scripts
3. **Session:** Keep session data minimal
4. **Images:** Compress before uploading
5. **Caching:** Consider browser caching for static assets

---

## Code Style Guidelines

### PHP
```php
// Use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validate input
$input = sanitizeInput($_POST['field'] ?? '');
```

### HTML
```html
<!-- Use Bootstrap 5 classes -->
<div class="container py-4">
    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <!-- Content -->
        </div>
    </div>
</div>
```

### JavaScript
```javascript
// Use modern syntax
const data = await fetchApi('POST', '/api/endpoint', { /* params */ });

// Error handling
.catch(error => {
    console.error('Error:', error);
    showAlert('error', 'Operation failed');
});
```

---

## Quick Reference: Adding Features

| Task | Location | Steps |
|------|----------|-------|
| Add page | `pages/` | 1. Create file 2. Add switch case in index.php 3. Update sidebar |
| Add API | `api/` | 1. Create file 2. Check auth 3. Validate CSRF 4. Return JSON |
| Add style | `_styles/main.scss` | 1. Add SCSS 2. Compile to `css/main.min.css` |
| Add script | `_scripts/` | 1. Create JS 2. Add functions 3. Minify to `js/` |
| Add DB table | `database/ede_system.sql` | 1. Create schema 2. Test with sample data |
| Update user model | `api/save_user.php` | 1. Validate input 2. Check permissions 3. Execute query |

---

## External Resources

- **Bootstrap 5:** https://getbootstrap.com/docs/5.0/
- **Font Awesome 6:** https://fontawesome.com/docs/web/
- **SweetAlert2:** https://sweetalert2.github.io/
- **Select2:** https://select2.org/
- **PHP PDO:** https://www.php.net/manual/en/class.pdo.php
- **MySQL Docs:** https://dev.mysql.com/doc/

---

## Last Updated

- **Date:** March 2026
- **Version:** 1.0.0
- **Restructured:** Module-based architecture with page routing
- **Status:** Core pages complete, additional pages in progress
