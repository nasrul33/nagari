---
name: sikd-teman-desa-integration
description: Status dan referensi integrasi ke portal SIKD Teman Desa (Kemenkeu). Use sebelum mengerjakan task apa pun di modul integration-engineer — cek status skema API dulu.
---

# Integrasi SIKD Teman Desa

## Fakta yang sudah dikonfirmasi (dari riset publik)

- Integrasi resmi keuangan desa ke pemerintah pusat lewat portal **SIKD Teman Desa**
  (Kemenkeu/DJPK), BUKAN langsung ke Siskeudes (Siskeudes adalah aplikasi desktop
  closed-source milik BPKP/Kemendagri untuk internal desa).
- Siskeudes versi 2.0.9 punya fitur transfer data ke SIKD Teman Desa lewat dua jalur:
  1. **Upload ZIP manual** — export data jadi file terkompresi, upload manual ke website
     SIKD Teman Desa. Direkomendasikan untuk koneksi internet tidak stabil.
  2. **POST API langsung** — data dikirim langsung dari aplikasi ke server Kemenkeu tanpa
     download file dulu.
- 4 kategori data yang diproses: Data Umum Desa (profil & administrasi), APBDES (laporan
  anggaran), LRA (Laporan Realisasi Anggaran), DTH/RTH (data pajak desa).

## STATUS SKEMA API: BELUM DIDAPAT

Skema payload, mekanisme autentikasi, rate limit, dan dokumentasi resmi POST API BELUM ada
di repo ini. Ini BUKAN sesuatu yang boleh ditebak oleh subagent mana pun.

### Hasil riset publik 2026-07-14 (Claude, disetujui user/PM)

- Dokumen publik (ciptadesa.com, portal DJPK) hanya menjelaskan ALUR pemakaian:
  autentikasi POST API memakai "Service Password" yang disalin dari profil akun
  SIKD Teman Desa; verifikasi kirim via menu APBDes → Monitoring (status "Dikirim").
- TIDAK ada spesifikasi endpoint, format payload, maupun struktur isi ZIP yang
  dipublikasikan — ZIP di-generate internal oleh Siskeudes. Jalur ZIP fallback pun
  belum bisa diverifikasi dari dokumen publik.
- Keputusan user/PM 2026-07-14: fondasi schema-agnostic M4 BOLEH dibangun
  (pelacakan PengirimanSikd, job KirimKeSikd + retry/backoff, boundary
  PenyusunPayloadSikd yang melempar SkemaSikdBelumTersediaException, config
  sikd.enabled=false). Payload/pengiriman nyata TETAP BLOCKED.

## Aksi yang diperlukan (dari user/PM, bukan dari subagent)

1. Ajukan permintaan akses/dokumentasi API resmi ke Kemenkeu/DJPK atau BPKP.
2. Setelah dokumen didapat, update file ini dengan skema payload lengkap, lalu ubah status
   di atas jadi "TERSEDIA" dengan tanggal dan sumber dokumen.
3. Baru setelah itu `integration-engineer` boleh mulai implementasi M4 di PLAN.md.

## Aturan untuk subagent

- Kalau task menyentuh integrasi SIKD Teman Desa dan status di atas masih "BELUM DIDAPAT",
  STOP dan laporkan BLOCKED — jangan implementasi berdasarkan asumsi format API.
- Jalur ZIP manual (fallback) bisa mulai dikerjakan lebih dulu karena formatnya lebih bisa
  diverifikasi dari dokumentasi publik Siskeudes, tapi tetap konfirmasi ke `domain-compliance`.
