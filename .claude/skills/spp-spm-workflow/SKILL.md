---
name: spp-spm-workflow
description: Referensi state machine alur approval keuangan desa (SPP/SPM/pencairan) dan wewenang tiap peran. Use ketika membangun atau mereview logika approval, RBAC, atau UI status transaksi.
---

# Alur Approval SPP → SPM → Pencairan

## State machine

1. **Draft** — Kaur Keuangan menyiapkan Rencana Kebutuhan Desa (RKD) dan bukti pengeluaran.
2. **SPP Diajukan** — Kaur Keuangan mengajukan Surat Permintaan Pembayaran (SPP) ke Kepala
   Desa melalui Sekretaris Desa, dilampiri RKD dan bukti pengeluaran sebelumnya.
3. **Diverifikasi** — Sekretaris Desa memverifikasi kelengkapan berkas SPP (sesuai rencana
   kerja & ketersediaan anggaran).
4. **SPM Diterbitkan** — Sekretaris Desa menerbitkan Surat Perintah Membayar (SPM),
   ditandatangani oleh Kepala Desa. Kepala Desa juga yang menyetujui Dokumen Pelaksanaan
   Anggaran (DPA).
5. **Dicairkan** — Kaur Keuangan mencairkan dana setelah menerima SPM dan surat rekomendasi
   Camat, ke pemegang kas desa di bank yang ditunjuk.
6. **Selesai** — transaksi tercatat lengkap dengan seluruh dokumen pendukung.

Catatan 2026: ada struktur PKPKD/PPKD baru yang menambah lapisan governance — cek
pembaruan regulasi ini sebelum finalisasi kalau ada perubahan wewenang.

## Wewenang per peran

| Peran | Wewenang |
|---|---|
| Kepala Desa | Kebijakan pelaksanaan APBDes, setujui DPA, tanda tangan SPM |
| Sekretaris Desa | Verifikator internal SPP, penerbit SPM |
| Kaur Keuangan (bendahara) | Ajukan SPP, terima/simpan/setorkan/tatausahakan uang, cairkan dana setelah SPM+rekomendasi Camat |
| BPD | Pengawasan, akses laporan (read-only di sistem) |

## Aturan untuk subagent

- State TIDAK BOLEH dilompati (misal SPM terbit tanpa verifikasi Sekdes) — ini harus jadi
  invariant yang dijaga di level service/action, bukan cuma validasi UI.
- Role yang salah mencoba memicu transisi harus ditolak dan tercatat di audit log
  (lihat subagent `security-auditor`).
