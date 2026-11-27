# finWise

Aplikasi pencatatan keuangan pribadi berbasis Laravel dan Livewire. finWise membantu mencatat pemasukan, pengeluaran, transfer antarakun, mengatur anggaran, dan memantau target simpanan.

## Fitur

- **Beranda:** ringkasan saldo, pemasukan/pengeluaran bulan ini, anggaran, serta transaksi terbaru.
- **Akun:** kelola akun tunai, bank, dan e-wallet. Saldo dihitung dari saldo awal dan seluruh transaksi.
- **Transaksi:** catat pemasukan, pengeluaran, dan transfer; cari serta filter riwayat.
- **Analisis:** grafik harian, mingguan, bulanan, dan tahunan; pilih satu atau beberapa kategori.
- **Anggaran:** tetapkan batas pengeluaran bulanan dan alokasikan sebagian ke kategori. Total alokasi kategori tidak boleh melebihi batas bulanan. Pengeluaran online maupun transaksi offline yang disinkronkan mengikuti batas ini.
- **Kategori:** lihat transaksi per kategori dan filter berdasarkan jenis serta tanggal.
- **Target simpanan:** buat target dan catat kontribusi.
- **Chat:** terhubung dengan kontak dan buat grup untuk bertukar pesan, stiker emoji, serta foto. Foto dapat dipilih lewat tombol lampiran atau ditempel dari clipboard; stiker gambar dari keypad ponsel dapat ditempel jika browser menyediakan gambar tersebut sebagai data clipboard. Chat pribadi juga mendukung panggilan suara WebRTC peer-to-peer tanpa relay media; koneksi tertentu dapat gagal jika jaringan memerlukan TURN.
- **Mode offline:** transaksi dapat disimpan di perangkat saat offline dan disinkronkan kembali saat online.
- **Tampilan:** pilih tema terang/gelap, warna aksen dan teks, pola latar Fuzzy Duck atau Rare Snake, font, serta ukuran font. Warna netral pola mengikuti tema; warna lainnya mengikuti aksen.
- **Bahasa:** seluruh teks bawaan aplikasi, termasuk formulir, validasi, notifikasi, dan mode offline, tersedia dalam Bahasa Indonesia, Inggris, dan Jepang. Ubah bahasa dari Profil → Tampilan aplikasi; pilihan disimpan pada sesi. Nama kategori, akun, dan target yang dibuat pengguna tetap mengikuti teks yang dimasukkan.
- **Nominal:** angka ditampilkan dengan pemisah ribuan; nilai yang disimpan tetap numerik.

## Memulai

Ikuti langkah instalasi di bawah ini untuk menjalankan finWise secara lokal. Pastikan PHP, Composer, Node.js, npm, dan ekstensi PDO SQLite tersedia.

## Kebutuhan

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm
- SQLite dengan ekstensi PDO SQLite untuk PHP

Proyek ini menggunakan Laravel 13, Livewire 4, Tailwind CSS 4, dan Vite Plus.

## Instalasi lokal

1. Pasang dependensi PHP:

       composer install

2. Buat file konfigurasi lokal.

   macOS/Linux:

       cp .env.example .env
       touch database/database.sqlite

   Windows PowerShell:

       Copy-Item .env.example .env
       New-Item -ItemType File -Path database/database.sqlite

   Jika file .env atau database/database.sqlite sudah ada, jangan buat ulang.

3. Buat application key:

       php artisan key:generate

4. Pasang dependensi frontend dan siapkan database:

       npm install
       php artisan migrate

5. Jalankan aplikasi. Gunakan dua terminal.

   Terminal pertama:

       php artisan serve

   Terminal kedua:

       npm run dev

6. Buka alamat yang ditampilkan oleh php artisan serve, biasanya http://127.0.0.1:8000, lalu daftar melalui halaman Register.

### Data contoh

Untuk mengisi database pengembangan dengan data contoh:

    php artisan db:seed

Seeder membuat akun pengembangan test@example.com dengan kata sandi password, beserta kategori default. Jangan gunakan kredensial tersebut di lingkungan produksi.

### Build aset

Untuk membuat aset frontend siap produksi:

    npm run build

### Halaman error dan maintenance

Halaman error finWise tersedia untuk HTTP 4xx/5xx umum. Di shared hosting, maintenance dapat diaktifkan hanya dengan mengubah `.env`; middleware membaca file itu langsung pada setiap request, termasuk saat konfigurasi Laravel dicache. Tidak perlu menjalankan perintah Artisan:

    APP_MAINTENANCE=true

Set `APP_MAINTENANCE=false` untuk membuka aplikasi kembali. Pesan dan interval refresh bisa diatur melalui `APP_MAINTENANCE_MESSAGE` dan `APP_MAINTENANCE_RETRY` (detik). Kosongkan pesan untuk memakai teks standar yang diterjemahkan.

Untuk penggunaan Artisan, halaman maintenance khusus juga tersedia:

    php artisan down --render=errors::maintenance --retry=60

Aktifkan kembali aplikasi setelah pekerjaan selesai:

    php artisan up

### Notifikasi push browser

finWise mengirim Web Push langsung dari Laravel dengan Web Push API browser dan package PHP lokal, tanpa SDK CDN atau server worker yang berjalan terus-menerus. HTTPS wajib di hosting. Siapkan VAPID satu kali:

    composer install --no-dev --optimize-autoloader

    php artisan webpush:generate-keys

Simpan tiga nilai yang dicetak ke `.env` sebagai `PUSH_VAPID_PUBLIC_KEY`, `PUSH_VAPID_PRIVATE_KEY`, dan `PUSH_VAPID_SUBJECT` (gunakan alamat email admin yang valid sebagai subject). Jangan bagikan private key. Kunci dibaca langsung dari `.env`, termasuk saat konfigurasi Laravel dicache. Jalankan migrasi:

    php artisan migrate --force

Setelah itu, pengguna login lalu memilih **Izinkan notif HP** dan menyetujui prompt browser. Service worker push memakai `public/service-worker.js` yang sama dengan PWA. Browser iPhone perlu memasang PWA ke Home Screen dan memakai iOS/iPadOS 16.4 atau lebih baru. Hosting perlu PHP 8.3 dengan ekstensi cURL, OpenSSL, dan mbstring.

## Pengembangan dan tes

Jalankan seluruh tes:

    php artisan test

Tes mencakup perhitungan saldo, transaksi, filter kategori, anggaran bulanan, sinkronisasi transaksi offline, target simpanan, profil, dan ringkasan beranda. Jalankan `npm run build` untuk memeriksa build aset frontend.

## Catatan anggaran

Batas bulanan adalah plafon seluruh pengeluaran pada bulan tersebut. Alokasi kategori bersifat opsional, tetapi total alokasi kategori harus tetap berada di dalam plafon. Transaksi yang membuat total pengeluaran melewati plafon ditolak, termasuk saat transaksi offline disinkronkan.

Tanggal transaksi disimpan sebagai tanggal kalender tanpa jam. Karena itu, analisis harian menghitung seluruh transaksi pada tanggal yang dipilih.

## Preferensi aplikasi

Pengaturan tema, warna, font, dan ukuran teks tersimpan di browser. Pilihan bahasa tersimpan di sesi, sehingga digunakan saat berpindah halaman selama sesi tersebut masih aktif.
