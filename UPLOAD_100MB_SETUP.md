# Setup Upload File Gambar Kerja 100MB

## Perubahan yang Dilakukan

### 1. **Validasi Laravel** (`app/Services/ProyekService.php`)
   - Updated: `file_spk` max size dari 10MB ke 100MB (102400 KB)
   - Updated: `file_gambar_kerja` max size dari 10MB ke 100MB (102400 KB)
   - Added support untuk format: `pdf, jpg, jpeg, png`

### 2. **Konfigurasi Server**

Ada 2 cara untuk mengkonfigurasi server agar mendukung upload 100MB:

#### **Opsi A: Menggunakan `.htaccess` (Apache)**

Jika server menggunakan Apache, copy configurasi dari `.htaccess.upload` ke `.htaccess` di root folder:

```
upload_max_filesize 100M
post_max_size 100M
max_execution_time 300
max_input_time 300
memory_limit 256M
```

#### **Opsi B: Mengubah `php.ini` (Semua Server)**

Edit file `php.ini` dan update setting berikut:

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

Lokasi `php.ini`:
- **Linux/Mac**: Biasanya di `/etc/php/[version]/apache2/php.ini` atau `/etc/php.ini`
- **Windows**: Biasanya di `C:\php\php.ini`

Jalankan command berikut untuk menemukan lokasi:
```bash
php -i | grep "php.ini"
```

#### **Opsi C: Konfigurasi di `nginx.conf` (Nginx)**

Jika menggunakan Nginx, update `nginx.conf`:

```nginx
http {
    client_max_body_size 100M;
}
```

### 3. **Restart Server**

Setelah mengubah konfigurasi, restart server:

```bash
# Apache
sudo systemctl restart apache2

# Nginx
sudo systemctl restart nginx

# PHP-FPM (jika digunakan)
sudo systemctl restart php-fpm
```

## Testing

Untuk memverifikasi setting sudah benar:

```bash
php -i | grep -E "(upload_max_filesize|post_max_size)"
```

Output seharusnya menunjukkan:
```
upload_max_filesize => 100M => 100M
post_max_size => 100M => 100M
```

## Troubleshooting

### Upload tetap dibatasi 10MB
- Restart browser dan server
- Cek file konfigurasi yang benar telah di-edit
- Verify dengan `php -i`

### "Maximum file size exceeded" error di form
- Cek konfigurasi PHP `upload_max_filesize` dan `post_max_size`
- Cek konfigurasi server (Nginx: `client_max_body_size`)

### Upload timeout
- Naikan `max_execution_time` dan `max_input_time` di php.ini
- Gunakan:
  ```ini
  max_execution_time = 600
  max_input_time = 600
  ```

## Fitur Upload Gambar Kerja

### File yang didukung
- PDF (.pdf)
- Gambar (.jpg, .jpeg, .png)

### Maksimum ukuran
- **100MB** per file

### Lokasi penyimpanan
- `storage/app/public/proyek/gambar_kerja/`

### Form di aplikasi
- Form edit Proyek memiliki field `file_gambar_kerja`
- File lama akan otomatis dihapus saat upload file baru

## Catatan
- Perubahan sudah diterapkan di:
  - `app/Services/ProyekService.php` (validasi Laravel)
  - Keseluruhan form Proyek (create & update)
- Setting 100MB sudah optimal untuk sebagian besar use case
- Jika butuh lebih besar, sesuaikan dengan resources server (RAM, disk)
