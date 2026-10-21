-- Jalankan sekali bila database sudah dibuat dari versi sebelumnya (tanpa kolom deskripsi barang):
--   psql -U postgres -d sipinjam_oki -f database/migrasi_deskripsi.sql
ALTER TABLE barang ADD COLUMN IF NOT EXISTS deskripsi VARCHAR(500) NOT NULL DEFAULT '';
