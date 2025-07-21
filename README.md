# Snipnote - Platform Catatan Kolaboratif

Snipnote adalah sebuah aplikasi pencatat berbasis web yang dibangun menggunakan **Laravel**. Aplikasi ini dirancang untuk tidak hanya menjadi tempat mencatat pribadi, tetapi juga sebagai platform untuk berkolaborasi dan berbagi ide dengan pengguna lain secara aman dan terkontrol.

##  flowchart Alur Aplikasi

Berikut adalah alur kerja umum dari aplikasi Snipnote:

1.  **Akses Pengguna:** Pengguna dapat melakukan **Login** ke akun yang sudah ada atau melakukan **Registrasi** untuk membuat akun baru.
2.  **Dashboard:** Setelah berhasil login, pengguna akan diarahkan ke halaman *dashboard*. Halaman ini menampilkan semua catatan yang relevan dengan pengguna.
3.  **Manajemen Catatan:** Pengguna dapat **membuat catatan baru**, **membaca/mengedit** catatan yang ada, dan **menghapus** catatan.
4.  **Sistem Berbagi (Sharing):** Saat membuat atau mengedit catatan, pengguna dapat memilih untuk:
    * Menjadikannya **pribadi** (hanya bisa diakses oleh pemilik).
    * **Berbagi ke pengguna tertentu** dengan memilih dari daftar pengguna.
    * Menjadikannya **publik** agar bisa diakses oleh siapa saja.
5.  **Kontrol Akses:** Hak akses untuk setiap catatan diatur secara ketat menggunakan **Policy & Gate** dari Laravel. Ini memastikan hanya pengguna yang berhak yang dapat melihat atau mengedit catatan tertentu.
6.  **Kolaborasi dengan Komentar:** Pengguna yang memiliki akses ke sebuah catatan (baik dibagikan secara spesifik maupun publik) dapat menambahkan **komentar**. Pemilik catatan memiliki hak penuh untuk menghapus komentar apa pun di catatannya.
7.  **Notifikasi:** Pengguna akan menerima notifikasi ketika ada pengguna lain yang membagikan catatan kepadanya.

## 🚀 Fitur Unggulan

* **Manajemen Catatan (CRUD):** Fungsionalitas penuh untuk membuat, membaca, memperbarui, dan menghapus catatan.
* **Tabulasi Catatan:** Antarmuka yang terorganisir dengan baik menggunakan tab:
    * **All:** Menampilkan semua catatan.
    * **My Note:** Hanya menampilkan catatan yang dibuat oleh pengguna.
    * **Shared to Me:** Catatan yang dibagikan oleh pengguna lain kepada Anda.
    * **Public:** Catatan yang dapat diakses oleh semua pengguna.
* **Pencarian Lanjutan:** Fitur pencarian untuk menemukan catatan dengan cepat.
* **Sistem Berbagi Fleksibel:** Bagikan catatan ke satu atau banyak pengguna sekaligus, atau jadikan publik.
* **Manajemen Akses:** Pemilik catatan dapat dengan mudah memberikan dan mencabut akses dari pengguna lain.
* **Notifikasi Real-time:** Dapatkan pemberitahuan instan ketika sebuah catatan dibagikan kepada Anda.
* **Sistem Komentar:** Berdiskusi dan berkolaborasi langsung di halaman catatan. Pemilik catatan memiliki hak moderasi penuh.

## 🛠️ Teknologi yang Digunakan

* [**Laravel 12**](https://laravel.com/): Framework utama untuk membangun sisi server dan antarmuka pengguna aplikasi.
* [**Filament 3**](https://filamentphp.com/): Digunakan sebagai Admin Panel yang canggih, dengan plugin tambahan:
    * **`filament/notifications`**: Untuk sistem notifikasi.
    * **`bezhansalleh/filament-language-switch`**: Untuk fitur ganti bahasa pada panel admin.
    * **`joaopaulolndev/filament-edit-profile`**: Memberikan fungsionalitas edit profil untuk pengguna.

## ⚙️ Instalasi & Konfigurasi

Pastikan Anda sudah memiliki environment pengembangan lokal yang sesuai (PHP, Composer, Node.js, npm).

1.  **Clone Repositori**
    ```bash
    git clone [https://github.com/alfandy12/snipnote.git](https://github.com/alfandy12/snipnote.git)
    cd snipnote
    ```

2.  **Instalasi Dependensi**
    Instal semua dependensi PHP menggunakan Composer.
    ```bash
    composer install
    ```

3.  **Konfigurasi Environment**
    Salin file `.env.example` menjadi `.env` dan sesuaikan konfigurasinya, terutama untuk koneksi database.
    ```bash
    cp .env.example .env
    ```
    Setelah itu, generate kunci aplikasi.
    ```bash
    php artisan key:generate
    ```

4.  **Migrasi dan Seeding Database**
    Jalankan migrasi untuk membuat semua tabel yang dibutuhkan di database Anda. Tambahkan opsi `--seed` untuk menjalankan *database seeder* yang akan mengisi database dengan data dummy (contoh).
    ```bash
    php artisan migrate --seed
    ```

## ▶️ Menjalankan Proyek

Untuk menjalankan aplikasi, Anda hanya perlu menjalankan server pengembangan Laravel.

1.  **Jalankan Server Laravel**
    ```bash
    php artisan serve
    ```

2.  **Buka Aplikasi**
    Setelah server berjalan, buka aplikasi di peramban sesuai alamat yang diberikan (biasanya `http://127.0.0.1:8000`).

## 🤝 Kontribusi

Kontribusi, isu, dan permintaan fitur sangat kami hargai! Jangan ragu untuk membuka *issue* atau *pull request*.

## 📄 Lisensi

Proyek ini dilisensikan di bawah [Lisensi MIT](https://choosealicense.com/licenses/mit/).
