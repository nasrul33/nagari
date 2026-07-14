/**
 * Subsistem offline-first (M5).
 *
 * Livewire server-rendered tidak jalan offline, jadi entri draft transaksi
 * ditangani lapisan terpisah ini: IndexedDB sebagai antrian + sinkronisasi
 * idempoten ke endpoint /sync/transaksi saat online. Aturan resolusi konflik
 * (locking berbasis state approval) ditegakkan di server; klien hanya mengirim
 * antrian dan menindaklanjuti hasil per item.
 */

const DB_NAME = 'keuangan-desa-offline';
const STORE = 'antrian_draft';

function bukaDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, 1);
        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE, { keyPath: 'uuid' });
            }
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function idbSemua() {
    const db = await bukaDb();
    return new Promise((resolve, reject) => {
        const req = db.transaction(STORE, 'readonly').objectStore(STORE).getAll();
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function idbSimpan(item) {
    const db = await bukaDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');
        tx.objectStore(STORE).put(item);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

async function idbHapus(uuid) {
    const db = await bukaDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');
        tx.objectStore(STORE).delete(uuid);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

/** Komponen Alpine untuk halaman entri draft offline. */
export function offlineDraft({ tahunAnggarans, akuns, syncUrl, csrf }) {
    return {
        tahunAnggarans,
        akuns,
        syncUrl,
        csrf,
        antrian: [],
        online: navigator.onLine,
        menyinkron: false,
        error: '',
        pesanSync: '',
        form: {
            tahun_anggaran_id: '',
            akun_id: '',
            tanggal: new Date().toISOString().slice(0, 10),
            uraian: '',
            jumlah: '',
        },

        async init() {
            this.antrian = await idbSemua();

            window.addEventListener('online', () => {
                this.online = true;
                this.sinkronkan();
            });
            window.addEventListener('offline', () => {
                this.online = false;
            });

            if (this.online && this.antrian.length > 0) {
                this.sinkronkan();
            }
        },

        formKosong() {
            return {
                tahun_anggaran_id: '',
                akun_id: '',
                tanggal: new Date().toISOString().slice(0, 10),
                uraian: '',
                jumlah: '',
            };
        },

        async tambah() {
            this.error = '';
            const f = this.form;

            if (!f.tahun_anggaran_id || !f.akun_id || !f.tanggal || !f.uraian || !(f.jumlah > 0)) {
                this.error = 'Lengkapi semua kolom dengan benar.';
                return;
            }

            const item = {
                uuid: crypto.randomUUID(),
                tahun_anggaran_id: f.tahun_anggaran_id,
                akun_id: f.akun_id,
                tanggal: f.tanggal,
                uraian: f.uraian,
                jumlah: f.jumlah,
                client_updated_at: new Date().toISOString(),
            };

            await idbSimpan(item);
            this.antrian.push(item);
            this.form = this.formKosong();

            if (this.online) {
                this.sinkronkan();
            }
        },

        async sinkronkan() {
            if (this.menyinkron || this.antrian.length === 0) {
                return;
            }
            this.menyinkron = true;
            this.pesanSync = '';

            try {
                const res = await fetch(this.syncUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ items: this.antrian }),
                });

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }

                const data = await res.json();
                const ringkas = { dibuat: 0, diperbarui: 0, konflik: 0, terkunci: 0, ditolak: 0 };

                for (const hasil of data.results) {
                    // 'ditolak' (tidak valid) tetap di antrian untuk diperiksa user;
                    // sisanya sudah final di server → keluarkan dari antrian.
                    if (hasil.hasil !== 'ditolak') {
                        await idbHapus(hasil.uuid);
                        this.antrian = this.antrian.filter((i) => i.uuid !== hasil.uuid);
                    }

                    if (hasil.hasil === 'dibuat') ringkas.dibuat++;
                    else if (hasil.hasil === 'diperbarui') ringkas.diperbarui++;
                    else if (hasil.hasil === 'konflik_ditolak') ringkas.konflik++;
                    else if (hasil.hasil === 'terkunci') ringkas.terkunci++;
                    else if (hasil.hasil === 'ditolak') ringkas.ditolak++;
                }

                const bagian = [];
                if (ringkas.dibuat) bagian.push(`${ringkas.dibuat} dibuat`);
                if (ringkas.diperbarui) bagian.push(`${ringkas.diperbarui} diperbarui`);
                if (ringkas.konflik) bagian.push(`${ringkas.konflik} konflik (versi server dipertahankan)`);
                if (ringkas.terkunci) bagian.push(`${ringkas.terkunci} terkunci`);
                if (ringkas.ditolak) bagian.push(`${ringkas.ditolak} ditolak — periksa data`);
                this.pesanSync = bagian.length ? `Sinkronisasi: ${bagian.join(', ')}.` : 'Tidak ada perubahan.';
            } catch (e) {
                this.pesanSync = 'Gagal menyinkronkan — akan dicoba lagi saat koneksi stabil.';
            } finally {
                this.menyinkron = false;
            }
        },
    };
}

/** Badge status koneksi global di navbar. */
function pasangIndikatorKoneksi() {
    const el = document.getElementById('indikator-koneksi');
    if (!el) return;

    const render = () => {
        const online = navigator.onLine;
        el.textContent = online ? 'Online' : 'Offline';
        el.className =
            'rounded-full px-2.5 py-0.5 text-xs font-medium ' +
            (online ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800');
    };

    window.addEventListener('online', render);
    window.addEventListener('offline', render);
    render();
}

function daftarkanServiceWorker() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {
                // Registrasi SW gagal (mis. konteks non-HTTPS) — app tetap jalan online.
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', pasangIndikatorKoneksi);
daftarkanServiceWorker();
