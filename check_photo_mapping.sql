-- Query untuk mengecek photo mapping di production
-- Jalankan query ini dan kirimkan hasilnya

-- 1. Cek lowering records untuk line numbers yang bermasalah
SELECT
    jld.id as lowering_id,
    jln.line_number,
    jld.tanggal_jalur,
    jld.status_laporan,
    COUNT(pa.id) as total_photos,
    GROUP_CONCAT(pa.photo_field_name) as photo_fields,
    MIN(pa.created_at) as first_photo_created,
    MAX(pa.created_at) as last_photo_created
FROM jalur_lowering_data jld
INNER JOIN jalur_line_numbers jln ON jln.id = jld.line_number_id
LEFT JOIN photo_approvals pa ON pa.module_record_id = jld.id AND pa.module_name = 'jalur_lowering'
WHERE jln.line_number IN ('63-PRW-LN030', '63-KRG-LN006', '63-PRW-LN044')
GROUP BY jld.id, jln.line_number, jld.tanggal_jalur, jld.status_laporan
ORDER BY jln.line_number, jld.tanggal_jalur;

-- 2. Cek detail photo_url untuk melihat pola
SELECT
    pa.id as photo_id,
    pa.module_record_id as lowering_id,
    jln.line_number,
    jld.tanggal_jalur,
    pa.photo_field_name,
    SUBSTRING(pa.photo_url, 1, 100) as photo_url_preview,
    pa.drive_file_id,
    pa.storage_path,
    pa.created_at as photo_created_at
FROM photo_approvals pa
INNER JOIN jalur_lowering_data jld ON jld.id = pa.module_record_id
INNER JOIN jalur_line_numbers jln ON jln.id = jld.line_number_id
WHERE pa.module_name = 'jalur_lowering'
AND jln.line_number IN ('63-PRW-LN030', '63-KRG-LN006', '63-PRW-LN044')
ORDER BY jln.line_number, jld.tanggal_jalur, pa.photo_field_name;

-- 3. Cek apakah ada duplicate photo_url
SELECT
    pa.photo_url,
    COUNT(*) as usage_count,
    GROUP_CONCAT(CONCAT(jln.line_number, ' (', DATE(jld.tanggal_jalur), ')')) as used_in_records
FROM photo_approvals pa
INNER JOIN jalur_lowering_data jld ON jld.id = pa.module_record_id
INNER JOIN jalur_line_numbers jln ON jln.id = jld.line_number_id
WHERE pa.module_name = 'jalur_lowering'
AND pa.photo_url IS NOT NULL
GROUP BY pa.photo_url
HAVING COUNT(*) > 1;

-- 4. Cek photo records tanpa drive_file_id (belum ter-copy)
SELECT
    pa.id,
    jln.line_number,
    jld.tanggal_jalur,
    pa.photo_field_name,
    CASE
        WHEN pa.photo_url IS NOT NULL AND (pa.drive_file_id IS NULL OR pa.drive_file_id = '') THEN 'URL exist but not copied'
        WHEN pa.photo_url IS NULL THEN 'No URL'
        ELSE 'Copied successfully'
    END as copy_status
FROM photo_approvals pa
INNER JOIN jalur_lowering_data jld ON jld.id = pa.module_record_id
INNER JOIN jalur_line_numbers jln ON jln.id = jld.line_number_id
WHERE pa.module_name = 'jalur_lowering'
AND jln.line_number IN ('63-PRW-LN030', '63-KRG-LN006', '63-PRW-LN044')
ORDER BY jln.line_number, jld.tanggal_jalur;
