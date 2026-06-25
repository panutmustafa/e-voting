Buatkan aplikasi web E-Voting Pemilihan Pengurus Komite Sekolah SD Negeri Jomblang 2 menggunakan:

PHP 8 Native (tanpa framework)
MySQL Database
Tailwind CSS terbaru
Alpine.js untuk interaktivitas
Chart.js untuk grafik hasil voting
DomPDF untuk cetak PDF
Responsive Mobile First
Desain Premium Modern
Dark Mode & Light Mode
Animasi Interaktif dan Smooth Transition
TUJUAN APLIKASI

Aplikasi digunakan untuk pemilihan pengurus Komite Sekolah secara digital, transparan, aman, dan hanya memperbolehkan setiap pemilih melakukan voting satu kali.

Pemilih terdiri dari:

Orang Tua/Wali Murid
Guru
Tenaga Kependidikan
ROLE USER
1. Administrator

Login menggunakan username dan password.

Hak akses:

Dashboard

Menampilkan:

Total Pemilih
Total Sudah Memilih
Total Belum Memilih
Total Kandidat
Grafik Perolehan Suara Realtime
Persentase Partisipasi
Kelola Pemilih

CRUD Data Pemilih:

NIK
Nama
Nama Siswa
Kelas
Nomor HP
Email

Fitur:

Import Excel
Export Excel
Generate Kartu Pemilih
Cetak Massal Kartu Pemilih
Kelola Kandidat

CRUD Kandidat:

Foto Kandidat
Nama Lengkap
Jabatan yang Dipilih
Visi
Misi
Program Kerja
Motto

Upload:

Foto Profil
Video Perkenalan
Dokumen CV
Monitoring Voting

Realtime Monitoring:

Jumlah Suara Masuk
Waktu Voting
Pemilih Sudah Memilih
Persentase Partisipasi
Hasil Voting

Realtime Result:

Ranking Kandidat
Grafik Batang
Grafik Donut
Persentase Suara

Tombol:

Cetak PDF
Export Excel
Publish Hasil
2. PEMILIH

Tidak perlu login menggunakan password.

Pemilih cukup menggunakan:

Kartu Pemilih

Berisi:

Nama Pemilih
Nomor Pemilih
QR Code
Token Rahasia

Contoh:

VOTER-2026-00001

Token:

A8XK-9PQT-7MNB

Saat membuka aplikasi:

Pemilih memasukkan:

Nomor Pemilih
Token

atau

Scan QR Code

Sistem melakukan validasi:

Jika belum memilih:

Masuk ke halaman voting.

Jika sudah memilih:

Tampilkan:

"Anda sudah menggunakan hak suara."

HALAMAN BERANDA

Desain sangat premium seperti website pemilu modern.

Hero Section:

"Pemilihan Pengurus Komite Sekolah SD Negeri Jomblang 2"

Subjudul:

"Bersama Membangun Pendidikan yang Berkualitas"

Animasi:

Parallax
Fade In
Floating Elements
Particle Background
HALAMAN PROFIL KANDIDAT

Tampilan Card Kandidat Premium

Setiap kandidat memiliki:

Foto Besar
Nama
Jabatan
Visi
Misi
Program Kerja

Efek:

Glassmorphism
Hover Zoom
Tilt Effect
Animated Border
Reveal Animation

Ketika diklik:

Muncul Modal Fullscreen berisi:

Profil Lengkap Kandidat
Biodata
Riwayat Pendidikan
Riwayat Organisasi
Pengalaman
Video Perkenalan

UI sangat elegan dan modern.

HALAMAN VOTING

Tampilan seperti aplikasi pemilu digital profesional.

Menampilkan:

Foto Kandidat
Nama Kandidat
Visi Singkat

Ketika memilih:

Muncul popup konfirmasi:

"Apakah Anda yakin memilih kandidat ini?"

Pilihan:

Ya
Tidak

Jika Ya:

Simpan suara ke database.

Lalu:

Update status pemilih menjadi:

Sudah Memilih

FITUR KEAMANAN

Wajib menerapkan:

One Person One Vote

Field database:

has_voted ENUM('0','1')

Setelah voting:

has_voted='1'

Tidak dapat memilih kembali.

Token Rahasia

Token dibuat otomatis:

Contoh:

bin2hex(random_bytes(8));
QR Code

Setiap kartu pemilih memiliki QR Code unik.

Anti Double Vote

Validasi:

if(has_voted==1){
   blok akses voting;
}
Session Protection

Gunakan:

session_regenerate_id(true);
CSRF Protection

Semua form wajib menggunakan token CSRF.

Password Hash

Admin menggunakan:

password_hash()
password_verify()
KARTU PEMILIH

Desain premium ukuran ID Card.

Menampilkan:

Logo Sekolah
Foto Sekolah
Nama Pemilih
Nomor Pemilih
QR Code
Token

Tombol:

Cetak PDF
Cetak Massal
DASHBOARD ADMIN

Sidebar modern:

Dashboard
Data Pemilih
Kandidat
Monitoring
Hasil Voting
Pengaturan
Logout

Efek:

Glass Sidebar
Smooth Animation
Mobile Responsive
PENGATURAN

Admin dapat mengatur:

Jadwal Voting

Tanggal Mulai

Tanggal Selesai

Status Voting
Dibuka
Ditutup
Tema
Light
Dark
Logo Sekolah

Upload Logo

Sambutan Kepala Sekolah

Editor WYSIWYG

LAPORAN

Generate otomatis:

PDF

Berisi:

Rekapitulasi
Grafik
Pemenang
Excel

Berisi:

Data Pemilih
Data Kandidat
Hasil Voting
DATABASE MYSQL

Tabel:

users
voters
candidates
votes
settings
logs

Relasi database harus normalisasi hingga 3NF.

DESAIN UI/UX

Gunakan konsep:

Apple Style
Glassmorphism
Modern Government Voting System
Material Design 3
Tailwind CSS Premium

Warna utama:

Biru Sekolah
Putih
Emas Elegan

Tambahkan:

Dark Mode
Skeleton Loading
Toast Notification
SweetAlert2
Animate On Scroll
Lottie Animation
OUTPUT YANG DIHARAPKAN

AI harus menghasilkan:

Struktur folder lengkap
Database MySQL lengkap
File SQL Installer
Koneksi Database PDO
Sistem Login Admin
Sistem Kartu Pemilih
Sistem QR Code
Halaman Kandidat Interaktif
Sistem Voting Aman
Dashboard Admin Premium
Grafik Realtime
Export PDF & Excel
Responsive Mobile
Dokumentasi Instalasi
Source Code Lengkap Siap Upload ke Hosting Shared Hosting/CPanel

Buat kode secara profesional, aman, modular, mudah dikembangkan, dan menggunakan standar produksi (production-ready).
