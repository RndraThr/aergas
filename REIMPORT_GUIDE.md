# Panduan Import Ulang Jalur Data

## Masalah yang Diperbaiki

**Bug:** Row numbering dalam import tidak akurat karena Laravel Excel collection index tidak match dengan actual Excel row number ketika ada empty rows atau rows yang di-skip.

**Dampak:** Foto dan data ter-import ke record yang salah.

**Solusi:**
- Membuat mapping `line_number + tanggal` → actual Excel row number
- Menggunakan mapping ini untuk mengambil hyperlink yang benar

## File yang Diupdate

1. `app/Imports/JalurLoweringImport.php` - Fixed row mapping logic
2. `app/Imports/JalurJointImport.php` - Fixed row mapping logic
3. `app/Console/Commands/DeleteJalurImportData.php` - NEW: Command untuk delete data import
4. `app/Console/Commands/DiagnoseJalurPhotoMapping.php` - UPDATED: Diagnostic tool
5. `app/Console/Commands/CheckExcelRow.php` - NEW: Tool untuk cek row spesifik

## Langkah-langkah Import Ulang di Production

### Step 1: Upload File yang Sudah Diperbaiki ke VPS

```bash
# Upload dari local ke VPS
scp app/Imports/JalurLoweringImport.php deploy@your-vps:/var/www/aergas/app/Imports/
scp app/Imports/JalurJointImport.php deploy@your-vps:/var/www/aergas/app/Imports/
scp app/Console/Commands/DeleteJalurImportData.php deploy@your-vps:/var/www/aergas/app/Console/Commands/
scp app/Console/Commands/DiagnoseJalurPhotoMapping.php deploy@your-vps:/var/www/aergas/app/Console/Commands/
scp app/Console/Commands/CheckExcelRow.php deploy@your-vps:/var/www/aergas/app/Console/Commands/
```

### Step 2: Preview Data yang Akan Dihapus (Dry Run)

```bash
# SSH ke VPS
ssh deploy@your-vps
cd /var/www/aergas

# Preview lowering data yang akan dihapus (import tanggal 24 Des 2025)
php artisan jalur:delete-import --date="2025-12-24" --module=lowering --dry-run

# Preview joint data
php artisan jalur:delete-import --date="2025-12-24" --module=joint --dry-run

# Preview semua data (lowering + joint)
php artisan jalur:delete-import --date="2025-12-24" --dry-run
```

Output akan menampilkan:
- Jumlah records yang akan dihapus
- Jumlah photos yang akan dihapus
- **Jumlah approved photos yang akan hilang (WARNING jika ada)**

### Step 3: Backup Database (PENTING!)

```bash
# Backup database sebelum delete
mysqldump -u db_user -p db_name > backup_before_reimport_$(date +%Y%m%d_%H%M%S).sql

# Atau jika pakai production script
php artisan db:backup
```

### Step 4: Delete Data Import yang Lama

```bash
# Delete dengan confirmation
php artisan jalur:delete-import --date="2025-12-24"

# Atau langsung delete tanpa confirmation (hati-hati!)
php artisan jalur:delete-import --date="2025-12-24" --force
```

### Step 5: Import Ulang dengan Bug Fix

```bash
# Import lowering
php artisan jalur:import-lowering bulk_jalur_lowering.xlsx

# Import joint
php artisan jalur:import-joint bulk_jalur_joint.xlsx
```

**Perhatikan log:**
- Log akan menampilkan "Using mapped row number" yang menunjukkan fix sudah bekerja
- Actual Excel row number akan digunakan untuk mengambil hyperlink

### Step 6: Verifikasi Hasil Import

```bash
# Cek specific line yang sebelumnya bermasalah
php artisan jalur:check-excel-row bulk_jalur_lowering.xlsx "63-PRW-LN030" "2025-08-09"

# Diagnostic photo mapping (harus tidak ada mismatch)
php artisan jalur:diagnose-photo-mapping bulk_jalur_lowering.xlsx

# Check via tinker
php artisan tinker
```

Di tinker:
```php
$lowering = \App\Models\JalurLoweringData::with(['lineNumber', 'photoApprovals'])->find(397);
echo "Line Number: " . $lowering->lineNumber->line_number . "\n";
echo "Tanggal: " . $lowering->tanggal_jalur->format('Y-m-d') . "\n";
foreach ($lowering->photoApprovals as $photo) {
    echo "Photo URL: " . $photo->photo_url . "\n";
}
exit
```

Expected: Photo URL harus contain `1mJNRCxqqVombMuY_RONp_4BuYqlus4OG`

### Step 7: Sync Approval Status

```bash
# Sync status untuk semua data yang baru di-import
php artisan jalur:sync-approval-status --force
```

## Troubleshooting

### Jika masih ada mismatch setelah import ulang

```bash
# Diagnostic dengan detail
php artisan jalur:diagnose-photo-mapping bulk_jalur_lowering.xlsx --dry-run

# Fix mismatch yang ditemukan
php artisan jalur:diagnose-photo-mapping bulk_jalur_lowering.xlsx --fix

# Copy foto yang sudah dikoreksi
php artisan jalur:fix-photo-copy --force
```

### Jika ingin delete data dari range tanggal

```bash
# Delete data dari tanggal tertentu sampai tanggal tertentu
php artisan jalur:delete-import --from-date="2025-12-20" --to-date="2025-12-24" --dry-run
```

### Jika ingin rollback

```bash
# Restore dari backup
mysql -u db_user -p db_name < backup_before_reimport_YYYYMMDD_HHMMSS.sql
```

## Testing di Local Sebelum Production

**SANGAT DISARANKAN** untuk test dulu di local:

```bash
# Di local environment
php artisan jalur:delete-import --date="2025-12-24" --dry-run
php artisan jalur:delete-import --date="2025-12-24" --force
php artisan jalur:import-lowering bulk_jalur_lowering.xlsx
php artisan jalur:diagnose-photo-mapping bulk_jalur_lowering.xlsx
```

## Catatan Penting

1. **SELALU backup database sebelum delete**
2. **Gunakan --dry-run dulu** untuk preview
3. **Verifikasi hasil import** dengan diagnostic command
4. **Check beberapa record secara manual** untuk memastikan foto benar
5. Bug ini mempengaruhi **SEMUA import yang punya empty rows di Excel**
6. Setelah fix ini, **import berikutnya akan otomatis benar**

## Log Files untuk Monitoring

```bash
# Monitor import log
tail -f storage/logs/laravel.log

# Check production log
tail -f storage/logs/production.log
```

## Support

Jika ada masalah:
1. Check log file di `storage/logs/`
2. Jalankan diagnostic command
3. Backup selalu ada untuk rollback
