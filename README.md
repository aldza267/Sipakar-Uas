# Si-PAKAR — Source Code
## Sistem Terpadu Pengelolaan Agenda dan Aktivitas Perkuliahan
**UTS Web Programming Praktikum — Kelompok 4**

---

## Struktur Folder
```
sipakar/
├── index.php                  → Router (redirect by role)
├── login.php                  → Halaman login
├── logout.php                 → Handler logout
├── database.sql               → Schema + seed data
│
├── includes/
│   ├── config.php             → Koneksi PDO database
│   └── auth.php               → Fungsi login/logout/session
│
├── student/
│   ├── dashboard.php          → Dashboard mahasiswa + progress bar
│   ├── jadwal.php             → CRUD jadwal kuliah
│   └── tugas.php              → CRUD to-do tugas
│
├── admin/
│   ├── dashboard.php          → Dashboard admin (statistik + user list)
│   ├── users.php              → CRUD akun mahasiswa
│   └── prodi.php              → CRUD master program studi
│
└── api/
    ├── update_task_status.php → AJAX endpoint (Fetch API)
    └── get_progress.php       → AJAX endpoint progress bar
```

---

## Cara Install

### 1. Persiapan
- XAMPP / Laragon dengan PHP 8.x dan MySQL 8.x
- Web server root: `htdocs/` (XAMPP) atau `www/` (Laragon)

### 2. Clone / Copy folder
Taruh folder `sipakar/` ke dalam root web server:
```
C:/xampp/htdocs/sipakar/    (XAMPP)
C:/laragon/www/sipakar/     (Laragon)
```

### 3. Import Database
1. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Buat database baru bernama `sipakar` (atau biarkan script yang buat)
3. Import file `database.sql`
   - Klik tab **Import** → pilih `database.sql` → klik **Go**

### 4. Konfigurasi (jika perlu)
Edit file `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');    // sesuaikan
define('DB_PASS', '');         // sesuaikan
define('DB_NAME', 'sipakar');
```

### 5. Akses Aplikasi
Buka browser: `http://localhost/sipakar/`

---

## Akun Demo

| Role      | Email                              | Password       |
|-----------|-----------------------------------|----------------|
| Admin     | admin@sipakar.ac.id               | admin123       |
| Mahasiswa | aldza@student.sipakar.ac.id       | mahasiswa123   |
| Mahasiswa | najwa@student.sipakar.ac.id       | mahasiswa123   |

---

## Fitur Implementasi

| Fitur                        | Status | File                          |
|------------------------------|--------|-------------------------------|
| Autentikasi + RBAC           | ✅     | includes/auth.php             |
| Session isolasi per user     | ✅     | includes/auth.php             |
| Password hashing (bcrypt)    | ✅     | includes/auth.php             |
| Dashboard Mahasiswa          | ✅     | student/dashboard.php         |
| CRUD Jadwal Kuliah           | ✅     | student/jadwal.php            |
| CRUD To-Do Tugas             | ✅     | student/tugas.php             |
| Progress Bar real-time       | ✅     | student/dashboard.php         |
| AJAX Update Status (Fetch)   | ✅     | api/update_task_status.php    |
| Filter & Overdue Detection   | ✅     | student/tugas.php             |
| Dashboard Admin              | ✅     | admin/dashboard.php           |
| CRUD Akun Mahasiswa          | ✅     | admin/users.php               |
| Master Data Prodi            | ✅     | admin/prodi.php               |
| Responsive Bootstrap 5       | ✅     | Semua halaman                 |
| SQL Injection Prevention     | ✅     | PDO Parameterized Queries     |

---

## Teknologi
- **Backend**: PHP 8.x (Native PHP, no framework)
- **Database**: MySQL 8.x via PDO
- **Frontend**: HTML5, CSS3, Bootstrap 5.3, Font Awesome 6
- **AJAX**: JavaScript Fetch API (vanilla, no jQuery)
- **Security**: bcrypt password hash, PDO prepared statements, RBAC session

---

*Kelompok 4 — UTS Praktikum Pemrograman Web 2026*
*UIN Maulana Malik Ibrahim Malang*
