# Sistem Keuangan Desa Premium — Working Memory Proyek

Baca file ini di awal setiap sesi Claude Code sebelum mengerjakan apa pun di proyek ini.

## Apa yang dibangun

SaaS multi-desa untuk pengelolaan keuangan desa/nagari di Indonesia. Empat nilai jual utama:
dashboard & analitik visual, offline-first & sinkronisasi, alur approval & audit trail ketat,
integrasi otomatis ke sistem pemerintah pusat (SIKD Teman Desa).

Stack: **TALL** — Tailwind CSS, Alpine.js, Laravel, Livewire.

## Fondasi domain (jangan riset ulang, ini sudah final dari riset awal)

- **Bagan akun (COA)** baku secara nasional per Permendagri 113/2014, dikunci strukturnya
  (5 level: Akun → Kelompok → Jenis → Objek → Rincian Objek) per Permendagri 20/2018.
  Kodefikasi TIDAK BOLEH diubah aplikasi — di-seed, bukan dibuat custom per tenant.
  Detail lengkap: lihat `.claude/skills/coa-desa/SKILL.md`.
- **Alur approval**: Kaur Keuangan ajukan SPP → Sekdes verifikasi → SPM diterbitkan &
  ditandatangani Kades → pencairan oleh Kaur Keuangan dengan rekomendasi Camat.
  Detail state machine: `.claude/skills/spp-spm-workflow/SKILL.md`.
- **Integrasi resmi**: ke SIKD Teman Desa (portal Kemenkeu), BUKAN ke Siskeudes langsung
  (Siskeudes closed-source, desktop-based). Dua jalur: upload ZIP manual atau POST API.
  Skema API resmi BELUM didapat — lihat status di `.claude/skills/sikd-teman-desa-integration/SKILL.md`
  dan jangan mulai implementasi integrasi sebelum skema didapat dari Kemenkeu/DJPK.
- **Kepatuhan data**: UU PDP No. 27/2022 mewajibkan enkripsi dan kontrol akses ketat atas
  data pribadi warga (NIK, dll).
- **Benchmark harga kompetitor**: Simpeldesa (Telkom) — Rp25 juta setup + Rp2 juta/tahun
  maintenance + Rp15 juta/6 bulan pendampingan.

## Keputusan arsitektur (sudah diputuskan, ikuti konsisten)

| Area | Keputusan |
|---|---|
| Multi-tenancy | Single-DB dengan `tenant_id` scoping (evaluasi ulang hanya jika ada kontrak yang mensyaratkan isolasi fisik) |
| RBAC & audit trail | spatie/laravel-permission + paket audit log (mis. owen-it/laravel-auditing) |
| Offline-first | Livewire TIDAK jalan offline (server-rendered). Perlu lapisan PWA terpisah: service worker + IndexedDB untuk antrian draft transaksi, sync via endpoint API saat online. Ini bukan fitur Livewire biasa — treat sebagai subsistem sendiri. |
| Integrasi SIKD Teman Desa | Job/queue terpisah (Laravel Queue + retry policy), bukan sinkron di request-response, karena internet desa sering tidak stabil |
| Enkripsi | Laravel encrypted casts untuk data pribadi warga |

## Konvensi kode

- Gunakan Actions/Service pattern, hindari fat controller.
- Setiap Livewire component untuk alur approval harus punya test Pest yang menutup semua state transition.
- Migration COA harus berbasis seeder yang mengikuti struktur 5 level, tidak boleh hardcode angka di tempat lain.
- Semua perubahan yang menyentuh COA, alur approval, atau format laporan WAJIB direview oleh subagent `domain-compliance` sebelum merge.

## Keputusan yang masih terbuka (jangan asumsikan, tanya user/PM dulu)

1. Aturan resolusi konflik sinkronisasi offline (last-write-wins vs manual merge vs locking).
2. Model harga & badan hukum penyedia.
3. Skema API SIKD Teman Desa (menunggu dokumen resmi Kemenkeu).
4. Status pendaftaran sebagai Pengendali Data per UU PDP (perlu konsultasi legal).
5. Siapa yang berwenang memicu state "Selesai" di alur SPP/SPM — skill spp-spm-workflow tidak
   menetapkannya. Implementasi saat ini mengasumsikan Kaur Keuangan (fungsi penatausahaan);
   konfirmasi ke user/PM, terutama terkait struktur PKPKD/PPKD 2026 (temuan T4 review
   domain-compliance atas commit c275f62).

## Referensi

- `PLAN.md` — rencana implementasi bertahap per milestone.
- `.claude/agents/` — definisi subagent development.
- `.claude/skills/` — referensi domain (COA, workflow SPP/SPM, integrasi SIKD).

## Alur kerja standar

Setiap modul baru: `brainstorming` (klarifikasi) → `writing-plans` (tulis plan) →
`using-git-worktrees` (isolasi) → `subagent-driven-development` (dispatch ke subagent relevan) →
`test-driven-development` (test dulu) → review oleh `domain-compliance`/`security-auditor` →
`requesting-code-review`/`receiving-code-review` → `verification-before-completion` →
`finishing-a-development-branch`.
