# API Kasir Cafe Multi-Lokasi

API RESTful komprehensif untuk mengelola operasional cafe multi-lokasi, termasuk manajemen inventori, transaksi POS, perhitungan HPP (Harga Pokok Penjualan), laporan keuangan, dan update order real-time.

## 🚀 Fitur Utama

### Modul Inti

-   **Autentikasi & Otorisasi** - Autentikasi berbasis token dengan kontrol akses berbasis peran
-   **Manajemen Multi-Cabang** - Kelola beberapa lokasi cafe
-   **Manajemen Karyawan** - Manajemen staff dengan penugasan peran
-   **Manajemen Inventori** - Supplier, bahan baku, purchase order
-   **Resep & Perhitungan HPP** - Perhitungan biaya otomatis dari bahan baku
-   **Manajemen Stok** - Pelacakan stok real-time dengan riwayat pergerakan
-   **Manajemen Menu** - Kategori, item, harga dengan analisis margin keuntungan
-   **Sistem POS** - Manajemen order untuk dine-in, takeaway, dan delivery
-   **Proses Pembayaran** - Multiple metode pembayaran (tunai, QRIS, kartu)
-   **Laporan Keuangan** - Laporan penjualan, profit, inventori, dan arus kas

### Fitur Premium

-   **Real-Time WebSocket** - Update order dapur menggunakan Laravel Reverb
-   **Upload Gambar** - Foto menu dengan validasi
-   **Export PDF** - Laporan penjualan dan profit profesional
-   **Notifikasi Email** - Peringatan stok menipis dengan pengecekan terjadwal

## 📋 Kebutuhan Sistem

-   PHP 8.2 atau lebih tinggi
-   Composer
-   MySQL/PostgreSQL
-   Node.js & NPM (untuk frontend assets)
-   Redis (opsional, untuk queue dan cache)

## 🔧 Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd caskoe_bela
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Konfigurasi Database

Edit file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cafe_db
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Konfigurasi Email (untuk notifikasi)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@cafepro.com
MAIL_FROM_NAME="Cafe Pro"
```

### 6. Jalankan Migrasi & Seeder

```bash
php artisan migrate:fresh --seed
```

Ini akan membuat:

-   Semua tabel database
-   5 role: `owner`, `admin_pusat`, `manajer_cabang`, `kasir`, `karyawan_dapur`
-   50+ permissions
-   User test: `owner@test.com` / `password`

### 7. Buat Storage Link

```bash
php artisan storage:link
```

### 8. Build Assets

```bash
npm run build
```

### 9. Jalankan Development Server

```bash
# Terminal 1: API Server
php artisan serve

# Terminal 2: WebSocket Server (opsional)
php artisan reverb:start

# Terminal 3: Queue Worker (untuk notifikasi)
php artisan queue:work
```

## 📚 Dokumentasi API

### Akses Dokumentasi Interaktif

Dokumentasi API interaktif tersedia menggunakan **Scramble** (OpenAPI/Swagger):

```
http://localhost:8000/docs/api
```

Dokumentasi ini menyediakan:

-   Daftar lengkap semua endpoint
-   Parameter request dan response schema
-   Fitur "Try it out" untuk testing langsung
-   Contoh request dan response
-   Autentikasi dengan Bearer token

### Autentikasi

#### Login

```http
POST /api/login
Content-Type: application/json

{
  "email": "owner@test.com",
  "password": "password"
}
```

Response:

```json
{
    "user": {
        "id": 1,
        "name": "Test Owner",
        "email": "owner@test.com"
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
}
```

#### Dapatkan User Saat Ini

```http
GET /api/me
Authorization: Bearer {token}
```

#### Logout

```http
POST /api/logout
Authorization: Bearer {token}
```

### Manajemen Cabang

#### Daftar Cabang

```http
GET /api/branches?search=pusat&is_active=1
Authorization: Bearer {token}
```

#### Buat Cabang Baru

```http
POST /api/branches
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Cabang Pusat",
  "code": "CP001",
  "address": "Jl. Sudirman No. 1",
  "phone": "021-1234567",
  "email": "pusat@cafepro.com",
  "opening_time": "08:00",
  "closing_time": "22:00"
}
```

### Manajemen Karyawan

#### Buat Karyawan Baru

```http
POST /api/employees
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@cafepro.com",
  "password": "password123",
  "phone": "08123456789",
  "branch_id": 1,
  "employee_code": "EMP001",
  "position": "Kasir",
  "role": "kasir",
  "salary": 5000000
}
```

### Manajemen Inventori

#### Tambah Bahan Baku

```http
POST /api/raw-materials
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Kopi Arabica",
  "code": "RM001",
  "unit": "gram",
  "unit_price": 150,
  "min_stock": 5000
}
```

#### Buat Purchase Order

```http
POST /api/purchase-orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "supplier_id": 1,
  "branch_id": 1,
  "order_date": "2025-01-15",
  "items": [
    {
      "raw_material_id": 1,
      "quantity": 10000,
      "unit_price": 150
    }
  ]
}
```

### Manajemen Resep & HPP

#### Buat Resep

```http
POST /api/recipes
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Kopi Susu",
  "description": "Kopi susu signature",
  "yield_quantity": 1,
  "yield_unit": "cup",
  "items": [
    {
      "raw_material_id": 1,
      "quantity": 15
    },
    {
      "raw_material_id": 2,
      "quantity": 100
    }
  ]
}
```

#### Hitung Biaya Resep

```http
POST /api/recipes/{id}/calculate-cost
Authorization: Bearer {token}
```

### Manajemen Menu

#### Buat Item Menu

```http
POST /api/menus
Authorization: Bearer {token}
Content-Type: application/json

{
  "menu_category_id": 1,
  "recipe_id": 1,
  "name": "Kopi Susu Signature",
  "code": "MENU001",
  "description": "Kopi susu dengan rasa nikmat",
  "price": 25000,
  "preparation_time": 5
}
```

### Manajemen Order (POS)

#### Buat Order

```http
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "branch_id": 1,
  "table_id": 5,
  "order_type": "dine_in",
  "items": [
    {
      "menu_id": 1,
      "quantity": 2,
      "notes": "Less sugar"
    }
  ]
}
```

#### Update Status Order

```http
POST /api/orders/{id}/update-status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "preparing"
}
```

Statuses: `pending`, `preparing`, `ready`, `served`, `completed`, `cancelled`

#### Proses Pembayaran

```http
POST /api/orders/{id}/payment
Authorization: Bearer {token}
Content-Type: application/json

{
  "payment_method": "cash",
  "amount": 50000,
  "cash_received": 100000
}
```

### Laporan

#### Laporan Penjualan

```http
GET /api/reports/sales?start_date=2025-01-01&end_date=2025-12-31&branch_id=1
Authorization: Bearer {token}
```

#### Laporan Profit

```http
GET /api/reports/profit?start_date=2025-01-01&end_date=2025-12-31
Authorization: Bearer {token}
```

#### Peringatan Stok Menipis

```http
GET /api/reports/low-stock?branch_id=1
Authorization: Bearer {token}
```

#### Export ke PDF

```http
GET /api/reports/sales/pdf?start_date=2025-01-01&end_date=2025-12-31
Authorization: Bearer {token}
```

### Upload Gambar

#### Upload Gambar

```http
POST /api/upload/image
Authorization: Bearer {token}
Content-Type: multipart/form-data

image: [file]
type: menu
```

Response:

```json
{
    "success": true,
    "filename": "menu_1732778400_xY9z2K4pLm.jpg",
    "path": "images/menu/menu_1732778400_xY9z2K4pLm.jpg",
    "url": "/storage/images/menu/menu_1732778400_xY9z2K4pLm.jpg"
}
```

## 🔐 Role & Permission

### Role yang Tersedia

1. **Owner** - Akses penuh ke semua fitur
2. **Admin Pusat** - Kelola semua cabang, karyawan, inventori, dan laporan
3. **Manajer Cabang** - Kelola operasional cabang tertentu
4. **Kasir** - Proses order dan pembayaran
5. **Karyawan Dapur** - Lihat order untuk persiapan

### Kategori Permission

-   **Branch**: lihat, buat, edit, hapus
-   **Employee**: lihat, buat, edit, hapus
-   **Inventory**: lihat, buat, edit, hapus
-   **Menu**: lihat, buat, edit, hapus
-   **Order**: lihat, buat, edit, hapus
-   **Reports**: penjualan, profit, inventori, keuangan
-   **Financial**: lihat, buat, edit

## 📡 WebSocket (Update Real-Time)

### Jalankan WebSocket Server

```bash
php artisan reverb:start
```

### Koneksi Client-Side (JavaScript)

```javascript
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
});

// Dengarkan channel dapur
Echo.private("kitchen.1")
    .listen(".order.created", (e) => {
        console.log("Order baru:", e.order);
        // Update tampilan dapur
    })
    .listen(".order.status.updated", (e) => {
        console.log("Order diupdate:", e.order);
        // Update status order di tampilan
    });
```

## 📧 Notifikasi Email

### Pengecekan Manual

```bash
php artisan stock:check-low
```

### Jadwalkan Pengecekan Harian

Tambahkan ke `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('stock:check-low')->dailyAt('09:00');
}
```

Kemudian tambahkan ke crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🧪 Testing

### Jalankan Tests

```bash
php artisan test
```

### Test API dengan cURL

1. **Login:**

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"owner@test.com","password":"password"}'
```

2. **Create Branch:**

```bash
curl -X POST http://localhost:8000/api/branches \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Cabang Jakarta","code":"CJ001","address":"Jakarta"}'
```

3. **Dapatkan Laporan Penjualan:**

```bash
curl -X GET "http://localhost:8000/api/reports/sales?start_date=2025-01-01&end_date=2025-12-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📊 Skema Database

### Tabel Utama

-   `users` - Akun pengguna
-   `branches` - Lokasi cafe
-   `employees` - Data karyawan
-   `suppliers` - Informasi supplier
-   `raw_materials` - Item inventori
-   `purchase_orders` - Header purchase order
-   `purchase_order_items` - Item PO
-   `recipes` - Resep menu
-   `recipe_items` - Bahan resep
-   `stocks` - Level stok saat ini
-   `stock_movements` - Riwayat transaksi stok
-   `menu_categories` - Pengelompokan menu
-   `menus` - Item menu
-   `tables` - Meja makan
-   `orders` - Header order
-   `order_items` - Item order
-   `payments` - Catatan pembayaran
-   `cashier_shifts` - Manajemen shift
-   `operational_costs` - Biaya operasional
-   `cash_books` - Catatan arus kas
-   `taxes` - Konfigurasi pajak
-   `promotions` - Kampanye promosi

## 🚀 Deployment ke Production

### A. Deployment ke VPS/Cloud Server (DigitalOcean, AWS, Google Cloud)

#### 1. Persiapan Server

**Install Nginx:**

```bash
sudo apt update
sudo apt install nginx
```

**Install PHP 8.2:**

```bash
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-redis
```

**Install MySQL:**

```bash
sudo apt install mysql-server
sudo mysql_secure_installation
```

**Install Composer:**

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**Install Node.js & NPM:**

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs
```

**Install Redis (untuk queue & cache):**

```bash
sudo apt install redis-server
sudo systemctl enable redis-server
```

**Install Supervisor (untuk queue worker):**

```bash
sudo apt install supervisor
```

#### 2. Upload Aplikasi

```bash
# Clone repository
cd /var/www
sudo git clone <repository-url> cafe-api
cd cafe-api

# Set permission
sudo chown -R www-data:www-data /var/www/cafe-api
sudo chmod -R 755 /var/www/cafe-api/storage
sudo chmod -R 755 /var/www/cafe-api/bootstrap/cache
```

#### 3. Install Dependencies

```bash
cd /var/www/cafe-api
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

#### 4. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="Cafe Pro API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.cafepro.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cafe_production
DB_USERNAME=cafe_user
DB_PASSWORD=strong_password_here

# Queue menggunakan Redis
QUEUE_CONNECTION=redis

# Cache menggunakan Redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@cafepro.com
MAIL_FROM_NAME="Cafe Pro"

# Reverb WebSocket
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="api.cafepro.com"
REVERB_PORT=8080
REVERB_SCHEME=https
```

#### 5. Setup Database

```bash
# Buat database
sudo mysql -u root -p
```

```sql
CREATE DATABASE cafe_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cafe_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON cafe_production.* TO 'cafe_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Jalankan migrasi
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

#### 6. Optimasi Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

#### 7. Konfigurasi Nginx

Buat file `/etc/nginx/sites-available/cafe-api`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.cafepro.com;
    root /var/www/cafe-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # WebSocket Reverb Proxy
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://127.0.0.1:8080;
    }
}
```

Aktifkan site:

```bash
sudo ln -s /etc/nginx/sites-available/cafe-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 8. Install SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.cafepro.com
```

#### 9. Setup Queue Worker dengan Supervisor

Buat file `/etc/supervisor/conf.d/cafe-worker.conf`:

```ini
[program:cafe-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cafe-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/cafe-api/storage/logs/worker.log
stopwaitsecs=3600
```

Buat file `/etc/supervisor/conf.d/cafe-reverb.conf`:

```ini
[program:cafe-reverb]
process_name=%(program_name)s
command=php /var/www/cafe-api/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/cafe-api/storage/logs/reverb.log
```

Jalankan supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cafe-worker:*
sudo supervisorctl start cafe-reverb
sudo supervisorctl status
```

#### 10. Setup Cron untuk Scheduler

Edit crontab:

```bash
sudo crontab -e -u www-data
```

Tambahkan:

```
* * * * * cd /var/www/cafe-api && php artisan schedule:run >> /dev/null 2>&1
```

#### 11. Setup Firewall

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8080/tcp
sudo ufw enable
```

---

### B. Deployment ke Shared Hosting (cPanel/Plesk)

#### 1. Persiapan File

Di komputer lokal:

```bash
# Install dependencies production
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Hapus file tidak perlu
rm -rf node_modules
rm -rf tests
rm .env
```

#### 2. Upload ke Hosting

-   Buat database MySQL melalui cPanel
-   Upload semua file ke folder `public_html/api` atau `domains/api.cafepro.com/public_html`
-   Catat: Database name, username, password

#### 3. Konfigurasi Environment

Buat file `.env` di root folder:

```env
APP_NAME="Cafe Pro API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.cafepro.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanel_dbname
DB_USERNAME=cpanel_dbuser
DB_PASSWORD=cpanel_dbpass

QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=file
```

Generate key:

```bash
php artisan key:generate
```

#### 4. Setup Public Directory

Edit `.htaccess` di root atau gunakan cPanel untuk redirect domain ke folder `public`

Buat file `.htaccess` di root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### 5. Jalankan Migrasi

Melalui SSH atau Terminal di cPanel:

```bash
cd public_html/api
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 6. Setup Cron Job (cPanel)

Tambahkan di Cron Jobs:

```
* * * * * cd /home/username/public_html/api && php artisan schedule:run >> /dev/null 2>&1
```

#### 7. Set Permission

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

**Catatan Penting untuk Shared Hosting:**

-   WebSocket (Reverb) tidak didukung di shared hosting
-   Queue worker berjalan via cron (kurang optimal)
-   Pertimbangkan upgrade ke VPS jika perlu fitur real-time

---

### C. Deployment ke Platform Cloud (Laravel Forge/Vapor)

#### Laravel Forge (Recommended)

1. **Hubungkan Server:**

    - Login ke Laravel Forge
    - Tambahkan server (DigitalOcean/AWS/Linode)
    - Forge akan setup Nginx, PHP, MySQL otomatis

2. **Buat Site:**

    - Klik "New Site"
    - Domain: `api.cafepro.com`
    - Project Type: Laravel
    - Repository: hubungkan dengan GitHub/GitLab

3. **Deploy:**

    - Forge akan otomatis setup deployment script
    - Enable "Quick Deploy" untuk auto-deploy saat push

4. **Setup Queue & Scheduler:**

    - Tab "Queue" → Aktifkan worker
    - Tab "Scheduler" → Otomatis aktif

5. **SSL:**
    - Tab "SSL" → Klik "LetsEncrypt" (gratis)

---

## 🔧 Maintenance & Monitoring

### Monitoring Log

```bash
# Application log
tail -f /var/www/cafe-api/storage/logs/laravel.log

# Nginx access log
sudo tail -f /var/log/nginx/access.log

# Nginx error log
sudo tail -f /var/log/nginx/error.log

# Queue worker log
sudo tail -f /var/www/cafe-api/storage/logs/worker.log
```

### Update Aplikasi

```bash
cd /var/www/cafe-api

# Pull update dari repository
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Jalankan migrasi baru
php artisan migrate --force

# Clear dan rebuild cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo supervisorctl restart cafe-worker:*
sudo supervisorctl restart cafe-reverb
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

### Backup Database

```bash
# Manual backup
mysqldump -u cafe_user -p cafe_production > backup_$(date +%Y%m%d_%H%M%S).sql

# Otomatis dengan cron (setiap hari jam 2 pagi)
0 2 * * * mysqldump -u cafe_user -p'password' cafe_production > /backups/cafe_$(date +\%Y\%m\%d).sql
```

## 🛠️ Troubleshooting

### Bersihkan Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Reset Database

```bash
php artisan migrate:fresh --seed
```

### Cek Log

```bash
tail -f storage/logs/laravel.log
```

### Permission Denied Error

```bash
sudo chown -R www-data:www-data /var/www/cafe-api
sudo chmod -R 755 /var/www/cafe-api/storage
sudo chmod -R 755 /var/www/cafe-api/bootstrap/cache
```

### Queue Worker Tidak Berjalan

```bash
# Cek status
sudo supervisorctl status cafe-worker:*

# Restart
sudo supervisorctl restart cafe-worker:*

# Lihat log error
tail -f /var/www/cafe-api/storage/logs/worker.log
```

### WebSocket Tidak Konek

```bash
# Cek status Reverb
sudo supervisorctl status cafe-reverb

# Restart Reverb
sudo supervisorctl restart cafe-reverb

# Test port
telnet localhost 8080
```

### Database Connection Error

```bash
# Test koneksi database
php artisan tinker
>>> DB::connection()->getPdo();

# Pastikan credentials di .env benar
cat .env | grep DB_
```

## 📝 Lisensi

Proyek ini adalah perangkat lunak proprietary. Semua hak dilindungi.

## 👥 Dukungan

Untuk dukungan teknis, silakan hubungi tim pengembang.

## 🙏 Kontributor

Terima kasih kepada semua kontributor yang telah membantu pengembangan aplikasi ini.

---

**Dibangun dengan Laravel 12** | **Didukung oleh PHP 8.4**
