# Sistem Keuangan Desa Premium — TALL Stack

SaaS multi-desa untuk pengelolaan keuangan desa/nagari. Empat nilai jual utama: dashboard &
analitik visual, offline-first & sinkronisasi, alur approval & audit trail ketat, dan
integrasi otomatis ke SIKD Teman Desa (Kemenkeu).

Stack: **Tailwind CSS · Alpine.js · Laravel 13 · Livewire 4** — dikembangkan via Claude Code
dengan subagent & skill domain di `.claude/`.

## Status implementasi

| Milestone | Status |
|---|---|
| Prasyarat — scaffold Laravel + git + paket inti | ✅ Selesai |
| M0 — Fondasi data & COA (single-tenant) | ✅ Struktur & guard selesai; **kode rekening level 2–5 menunggu lampiran resmi Permendagri** |
| M1 — State machine SPP → SPM → Pencairan | ✅ Selesai — backend + audit trail + UI Livewire (login, daftar/buat/detail transaksi, panel aksi per peran) |
| M2 — Multi-tenancy | ✅ Selesai — global scope `desa_id` (trait `MilikDesa`), guard lintas desa di Action, onboarding `php artisan desa:baru` |
| M3 — Dashboard & analitik | ✅ Dashboard realisasi + grafik tren (Chart.js); **export BKU/Buku Pembantu/LRA menunggu template resmi** |
| M4 — Integrasi SIKD Teman Desa | ⛔ Menunggu skema API resmi Kemenkeu/DJPK |
| M5 — Offline-first | ✅ Entri draft offline (IndexedDB + service worker) + sync idempoten; konflik = locking state approval |
| M6 — Hardening UU PDP | ⬜ |

Detail milestone: [PLAN.md](PLAN.md). Konteks proyek & keputusan arsitektur: [CLAUDE.md](CLAUDE.md).

## Setup pengembangan

Prasyarat: PHP ≥ 8.3 (ekstensi sqlite3), Composer, Node.js ≥ 20.

```bash
composer install
cp .env.example .env          # sudah default sqlite
php artisan key:generate
php artisan migrate --seed    # seed peran RBAC + kerangka COA
php artisan db:seed --class=DemoSeeder   # opsional: desa demo + 4 user per peran
npm install && npm run build
composer run dev              # macOS/Linux: server + queue + logs(pail) + vite
composer run dev:win         # Windows: server + queue + vite (tanpa pail — pcntl tak ada di Windows)
```

> **Windows**: pakai `composer run dev:win`. Skrip `dev` bawaan Laravel menyertakan
> `php artisan pail` yang butuh ekstensi `pcntl` (tidak tersedia di Windows); dengan
> `--kill-others` kegagalan pail ikut mematikan server, queue, dan vite. `dev:win`
> membuang pail dan memakai `--kill-others-on-fail`.
>
> Aplikasi jalan di **http://localhost:8000** (dari `php artisan serve`).
> `localhost:5173` hanya server aset Vite, bukan halaman aplikasi — jangan dibuka langsung.

Login demo (setelah DemoSeeder): `kades@demo.test`, `sekdes@demo.test`, `kaur@demo.test`,
`bpd@demo.test` — password semuanya `password`.

**Onboarding desa (tenant) baru:**

```bash
php artisan desa:baru --kode=13.01.02.2003 --nama="Nagari Baru" \
  --kecamatan="..." --kabupaten="..." --provinsi="..."
```

Membuat desa + tahun anggaran aktif + 4 akun perangkat (password acak ditampilkan sekali).
Tenant = desa; semua data ter-scope `desa_id` otomatis (trait `MilikDesa`). COA global,
tidak digandakan per tenant.

## Test

```bash
./vendor/bin/pest
```

Suite menutup: invariant struktur COA 5 level (Permendagri 20/2018), penguncian kodefikasi
resmi, idempotensi seeder, RBAC 4 peran, dan **seluruh transisi state machine SPP→SPM→pencairan**
— di lapisan Action maupun lewat komponen Livewire — termasuk penolakan peran salah, state
yang dilompati, isolasi antar desa, dan pencatatan percobaan gagal di `transaksi_logs`.

## Peta kode domain

```
app/Enums/            LevelAkun (5 level COA), StatusTransaksi (state machine), PeranDesa
app/Models/           Desa, TahunAnggaran, Akun (COA + guard), Apbdes, Transaksi (auditable), TransaksiLog
app/Models/Concerns/  MilikDesa — global scope multi-tenant (tenant = desa)
app/Actions/Workflow/ TransisiWorkflow (kerangka) + AjukanSpp, VerifikasiSpp, TerbitkanSpm,
                      CairkanDana, SelesaikanTransaksi
app/Livewire/         Auth\Login; Transaksi\{DaftarTransaksi, BuatTransaksi, DetailTransaksi}
app/Console/Commands/ BuatDesaBaru (onboarding tenant: desa:baru)
database/seeders/     PeranSeeder, CoaSeeder (kerangka level 1), DemoSeeder (dev only)
.claude/              subagent (domain-compliance, backend-builder, dst.) + skill domain
```

## Hal yang WAJIB dilengkapi sebelum go-live

Berisi data yang belum ada dari sumber resmi — sengaja ditandai supaya subagent tidak menebak:

1. `.claude/skills/coa-desa/SKILL.md` — daftar kode rekening lengkap dari lampiran
   Permendagri 113/2014, lalu perluas `CoaSeeder::kerangkaResmi()`.
2. `.claude/skills/sikd-teman-desa-integration/SKILL.md` — skema API resmi dari Kemenkeu/DJPK.
3. `CLAUDE.md` bagian "keputusan yang masih terbuka" — resolusi konflik offline, model harga,
   badan hukum, status pendaftaran UU PDP.

## Alur kerja pengembangan

Setiap modul baru mengikuti alur di `CLAUDE.md`: brainstorming → writing-plans →
using-git-worktrees → subagent-driven-development → test-driven-development →
review `domain-compliance`/`security-auditor` → code review → verification-before-completion →
finishing-a-development-branch.

Perubahan apa pun yang menyentuh COA, alur approval, atau format laporan **wajib** direview
subagent `domain-compliance` sebelum merge.
