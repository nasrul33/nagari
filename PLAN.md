# PLAN.md — Rencana Implementasi Bertahap

Gunakan bersama `writing-plans` dan `subagent-driven-development` skill. Setiap milestone
harus disetujui (brainstorming + plan tertulis) sebelum subagent mulai eksekusi.

## Prasyarat sebelum M0 dimulai

- [ ] Model harga & badan hukum penyedia diputuskan (lihat CLAUDE.md §keputusan terbuka).
- [ ] Repo Laravel + Livewire + Tailwind + Alpine sudah di-scaffold (`laravel new`, `livewire:install`).
- [ ] Git worktree awal dibuat (`using-git-worktrees` skill).

---

## M0 — Fondasi data & COA (single-tenant, tanpa offline, tanpa integrasi eksternal)

**Tujuan**: alur inti APBDes bisa dipakai satu desa, semua transaksi tercatat sesuai COA resmi.

Tugas:
- Migration + seeder COA 5 level (Akun/Kelompok/Jenis/Objek/Rincian Objek) sesuai Permendagri 20/2018.
- Model Eloquent: Desa, TahunAnggaran, RKPDes, APBDes, Transaksi, Akun.
- RBAC dasar: role Kades, Sekdes, Kaur Keuangan, BPD (spatie/laravel-permission).

Subagent terlibat: `backend-builder` (implementasi), `domain-compliance` (review COA & RBAC scope).

Kriteria selesai:
- Seeder COA menghasilkan struktur yang bisa divalidasi manual terhadap dokumen Permendagri.
- Test Pest: setiap role hanya bisa akses aksi sesuai wewenangnya.

---

## M1 — Alur approval SPP → SPM → Pencairan

**Tujuan**: state machine approval berjenjang berfungsi penuh dengan audit trail.

Tugas:
- State machine: Draft → SPP Diajukan (Kaur Keuangan) → Diverifikasi (Sekdes) →
  SPM Diterbitkan (Kades) → Dicairkan (Kaur Keuangan + rekomendasi Camat) → Selesai.
- UI Livewire untuk tiap state + Alpine.js untuk interaksi form.
- Audit log tiap transisi state (siapa, kapan, dari state apa ke apa).

Subagent terlibat: `backend-builder`, `livewire-ui-builder`, `domain-compliance`, `security-auditor` (audit trail).

Kriteria selesai:
- Semua transisi state punya test yang membuktikan role yang salah tidak bisa memicu transisi.
- Audit log bisa direkonstruksi jadi timeline lengkap per transaksi.

**Ini milestone paling penting untuk divalidasi manual oleh Anda / pengguna nyata sebelum lanjut ke M2.**

---

## M2 — Multi-tenancy

**Tujuan**: satu instalasi bisa melayani banyak desa dengan data terisolasi secara logis.

Tugas:
- Tambahkan `tenant_id` scoping ke semua model dari M0/M1 (global scope Eloquent).
- Onboarding flow: buat tenant baru, seed COA otomatis untuk tenant baru.
- Review ulang semua query M0/M1 supaya tidak ada kebocoran data antar-tenant.

Subagent terlibat: `backend-builder`, `security-auditor` (validasi isolasi data), `qa-agent` (test kebocoran cross-tenant).

Kriteria selesai:
- Test eksplisit: user tenant A tidak bisa akses data tenant B lewat endpoint manapun.

---

## M3 — Dashboard & analitik

**Tujuan**: nilai jual "dashboard & analitik visual" terwujud.

Tugas:
- Livewire component untuk ringkasan realisasi anggaran per periode.
- Grafik (Chart.js atau setara) untuk tren pendapatan/belanja.
- Export laporan sesuai format wajib (BKU, Buku Pembantu, LRA) — **butuh template resmi, lihat status di bawah**.

Subagent terlibat: `livewire-ui-builder`, `domain-compliance` (format laporan harus sesuai standar).

Status blocking: template resmi format laporan wajib belum ada di repo ini — ambil dari
dokumen Permendagri/BPKP sebelum finalisasi modul ini.

---

## M4 — Integrasi SIKD Teman Desa

**Tujuan**: sinkronisasi otomatis data APBDES/LRA ke portal Kemenkeu.

**JANGAN MULAI sebelum skema API resmi (payload, autentikasi) didapat dari Kemenkeu/DJPK.**

Tugas (setelah skema didapat):
- Job queue untuk POST API dengan retry/backoff.
- Fallback generator ZIP manual untuk kondisi internet tidak stabil.
- Mapping 4 kategori data: Data Umum Desa, APBDES, LRA, DTH/RTH.

Subagent terlibat: `integration-engineer`, `qa-agent` (test retry & fallback), `security-auditor` (keamanan kredensial API).

---

## M5 — Offline-first

**Tujuan**: Kaur Keuangan bisa input draft transaksi tanpa internet, sync otomatis saat online.

**Butuh keputusan resolusi konflik dulu (lihat CLAUDE.md) sebelum subagent mulai desain.**

Tugas:
- Service worker + IndexedDB untuk antrian draft transaksi.
- Endpoint API sync + logic resolusi konflik sesuai keputusan yang sudah diambil.
- UI indikator status online/offline & antrian belum tersinkron.

Subagent terlibat: `offline-sync-engineer`, `qa-agent` (test skenario konflik), `security-auditor`.

Ini milestone paling kompleks — kerjakan paling akhir, setelah alur online (M0-M4) benar-benar stabil.

---

## M6 — Hardening kepatuhan & audit UU PDP

**Tujuan**: siap onboarding desa nyata dengan data warga sungguhan.

Tugas:
- Enkripsi field data pribadi (NIK, dll.) dengan Laravel encrypted casts.
- Audit trail lengkap untuk semua akses data pribadi, bukan cuma transaksi keuangan.
- Checklist kepatuhan UU PDP (idealnya direview konsultan legal, bukan cuma subagent).

Subagent terlibat: `security-auditor`.

---

## Tindak lanjut audit M2 (temuan non-blocking, kerjakan segera)

Dari review security-auditor + domain-compliance atas commit M2 (temuan blocking T-1/T-2/T-5
sudah ditutup sebelum merge):

- [x] **T-4**: email onboarding `desa:baru` berbasis slug nama — tabrakan untuk nama desa
      yang sama lintas kabupaten; pakai kode_desa di domain email + pesan error anggun.
- [x] **T-6**: rate limiting login (RateLimiter per email+IP) + kebijakan kekuatan password.
- [x] **T-8**: kolom `must_change_password` + middleware pemaksa ganti password login pertama.
- [x] **T-3**: validasi konsistensi `Apbdes.desa_id` vs desa tahun anggaran induk (lempar, bukan diam).
- [x] **T-7**: migration NOT NULL untuk `apbdes.desa_id` setelah backfill terverifikasi.
- [x] **DC-3**: log penolakan transisi ditulis di luar DB::transaction — dokumentasikan larangan
      membungkus `handle()` dalam transaksi luar, atau buat log tahan rollback.
- [x] **DC-1**: guard pembuatan Akun pakai `runningInConsole()` (true juga di queue worker) —
      pertimbangkan flag eksplisit ala `denganTransisiDiizinkan()`.
- [ ] **T-9** (wajib sebelum M4/M5): pola "tenant context" eksplisit untuk queue job —
      job wajib menerima `desa_id`, scope mati di konteks console.
- [ ] **T-10** (wajib saat UI audit dibuat): halaman riwayat audit owen-it harus di-scope
      per tenant via join ke model auditable.
- [ ] **M3-T3** (wajib saat modul laporan resmi dimulai): agregasi dashboard memakai float —
      cukup untuk visualisasi, tapi laporan resmi (BKU/Buku Pembantu/LRA) WAJIB aritmetika
      desimal/integer sen, bukan float.

Verifikasi penutupan (security-auditor, commit 4effa05): SEMUA temuan T/DC di atas TERTUTUP.
Temuan hardening lanjutan (B-series):

- [x] **B-1**: ganti password kini memutus sesi lain (`logoutOtherDevices` + `AuthenticateSession`)
      — menutup jendela akses operator onboarding pasca serah terima.
- [x] **B-2**: limiter login kedua per-IP lintas email (20/menit) membendung password spraying.
- [x] **B-3**: invariant "flag must_change_password hanya di-set pra-login" didokumentasikan
      di middleware; fitur masa depan yang men-set flag mid-session wajib invalidasi sesi.
- [x] **B-4**: email onboarding = identifier login, BUKAN mailbox — jangan bangun reset-via-email
      di atasnya (didokumentasikan di BuatDesaBaru).
- [x] **B-5**: flag statis (Akun/Transaksi) tidak aman untuk Octane+Swoole coroutine —
      didokumentasikan; migrasi ke Context bila Octane diadopsi.

## Urutan eksekusi ringkas

M0 → M1 → M2 → M3 → (M4 menunggu skema API) → M5 (paling akhir) → M6 sebelum go-live nyata.
