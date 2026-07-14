# Paket Perencanaan — Sistem Keuangan Desa Premium (TALL Stack, Claude Code)

Ini adalah paket dokumen perencanaan + konfigurasi Claude Code siap pakai untuk memulai
pengembangan sistem keuangan desa/nagari SaaS multi-tenant.

## Cara pakai

1. Salin seluruh isi folder ini (termasuk folder tersembunyi `.claude/`) ke root project
   Laravel Anda, atau jadikan folder ini root project baru lalu `laravel new` di dalamnya.
2. Buka Claude Code di folder tersebut. Claude Code otomatis membaca `CLAUDE.md` dan mengenali
   subagent di `.claude/agents/`.
3. Baca `PLAN.md` untuk urutan milestone (M0-M6).
4. Sebelum mulai M0, selesaikan dulu prasyarat yang tercantum di `PLAN.md` (keputusan harga,
   badan hukum, scaffold repo).
5. Untuk tiap milestone, jalankan alur kerja standar yang ada di CLAUDE.md:
   brainstorming → writing-plans → using-git-worktrees → subagent-driven-development →
   test-driven-development → review (domain-compliance/security-auditor) →
   requesting-code-review/receiving-code-review → verification-before-completion →
   finishing-a-development-branch.

## Isi paket

```
CLAUDE.md                                  working memory proyek — baca duluan tiap sesi
PLAN.md                                    rencana implementasi M0-M6
.claude/agents/
  domain-compliance.md                     reviewer kepatuhan COA/alur approval (read-only)
  backend-builder.md                       Eloquent model, migration, seeder, service layer
  livewire-ui-builder.md                   komponen Livewire + Alpine.js + Tailwind
  integration-engineer.md                  klien API SIKD Teman Desa (blocked sampai skema ada)
  offline-sync-engineer.md                 PWA/IndexedDB/resolusi konflik (blocked sampai aturan konflik diputuskan)
  security-auditor.md                      RBAC, audit trail, enkripsi, checklist UU PDP
  qa-agent.md                              test Pest/PHPUnit, state machine, isolasi tenant
.claude/skills/
  coa-desa/SKILL.md                        referensi bagan akun standar (kerangka, perlu diisi data resmi)
  spp-spm-workflow/SKILL.md                referensi state machine approval + wewenang peran
  sikd-teman-desa-integration/SKILL.md     status integrasi SIKD Teman Desa (belum ada skema API)
```

## Hal yang WAJIB Anda lengkapi sebelum go-live nyata

Tiga file di atas sengaja ditandai eksplisit statusnya karena berisi data yang saya (Claude)
belum punya dari sumber resmi — bukan kelalaian, tapi supaya subagent tidak menebak:

1. `.claude/skills/coa-desa/SKILL.md` — daftar kode rekening lengkap dari lampiran
   Permendagri 113/2014 atau dari Siskeudes/SIMDA yang sudah berjalan.
2. `.claude/skills/sikd-teman-desa-integration/SKILL.md` — skema API resmi dari Kemenkeu/DJPK.
3. `CLAUDE.md` bagian "keputusan yang masih terbuka" — resolusi konflik offline, model harga,
   badan hukum, status pendaftaran UU PDP.

Begitu tiga hal ini terisi, seluruh paket ini siap dieksekusi subagent demi subagent mengikuti
urutan di `PLAN.md`.
