---
name: coa-desa
description: Referensi bagan akun standar (COA) desa. Use ketika membuat migration/seeder COA, memvalidasi kode akun di form transaksi, atau mereview PR yang menyentuh struktur keuangan desa.
---

# Bagan Akun Standar Desa

Sumber hukum: Permendagri 113/2014 (klasifikasi pendapatan/belanja/pembiayaan) dan
Permendagri 20/2018 (mengunci struktur kodefikasi — TIDAK BOLEH diubah aplikasi pihak ketiga).

## Struktur 5 level

Akun → Kelompok → Jenis → Objek → Rincian Objek

## 5 kategori akun utama

1. Aset
2. Kewajiban
3. Kekayaan bersih
4. Pendapatan
5. Belanja

## Status data di skill ini

STATUS: KERANGKA SAJA — daftar kode rekening lengkap (angka per Kelompok/Jenis/Objek/Rincian
Objek) BELUM dilampirkan di file ini. Sebelum implementasi seeder final, ambil daftar kode
rekening resmi dari dokumen Permendagri 113/2014 lampiran, atau dari Siskeudes/SIMDA yang
sudah berjalan di daerah, lalu tempel di sini sebagai tabel referensi.

## Aturan untuk subagent

- Jangan generate kode akun sendiri secara sembarangan — kalau daftar resmi belum ada di
  sini, tandai task sebagai BLOCKED dan minta data ke user/PM.
- Perubahan pada struktur 5 level (bukan isi datanya) HARUS ditolak — struktur ini baku
  secara nasional per regulasi, bukan keputusan desain proyek ini.
