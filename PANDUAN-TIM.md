# Panduan Pengembangan Tim — Twinkle Little Star

*Panduan internal untuk tim Frontend, Backend, dan Database · Laravel 13 · Blade · Tailwind CSS 3 · MySQL · Docker (Sail)*

Bagian 1–3 wajib dibaca semua anggota. Bagian 4–6 dibaca sesuai peran. Bagian 7–10 adalah aturan kerja sama, pemecahan masalah, dan daftar perintah.

## 1. Gambaran Proyek

Twinkle Little Star adalah aplikasi komunitas bergaya Reddit: pengguna bisa membuat komunitas, memposting, berkomentar bersarang (nested), dan nantinya melakukan voting.

| Bagian | Teknologi |
|---|---|
| Backend | PHP 8.3+ dan Laravel 13 |
| Template | Blade (file `.blade.php`) |
| CSS | **Tailwind CSS 3** + plugin `@tailwindcss/forms` |
| JavaScript | Alpine.js 3, dibundel dengan Vite |
| Login/register | Laravel Breeze (sudah terpasang) |
| Database | MySQL 8.4 (container Docker) |
| Lingkungan dev | Laravel Sail (Docker) di dalam WSL2 Ubuntu |

Struktur folder yang penting:

```text
app/Http/Controllers/   logika halaman (Community, Post, Comment, Auth, Profile)
app/Models/             model Eloquent (User, Community, Post, Comment, Vote)
routes/web.php          daftar URL aplikasi
resources/views/        halaman Blade (HTML)
resources/css/app.css   entri Tailwind
resources/js/app.js     entri JavaScript (Alpine)
database/migrations/    definisi tabel
database/seeders/       data awal / data contoh
compose.yaml            konfigurasi Docker (Sail)
```

Alur sebuah request: **URL → route (`routes/web.php`) → controller → model/database → view Blade**. Setiap peran mengerjakan satu bagian dari alur ini.

## 2. Setup Awal (semua anggota)

**Prasyarat:** Windows dengan WSL2 + Ubuntu, Docker Desktop (WSL integration untuk Ubuntu aktif), Git, dan VS Code dengan ekstensi **WSL**.

**Penting:** buka terminal **Ubuntu** (bukan PowerShell) dan simpan proyek di dalam filesystem WSL (`~/projects`), bukan di `C:\`, supaya cepat.

```bash
mkdir -p ~/projects && cd ~/projects
git clone <url-repo> twinklelittlestar
cd twinklelittlestar
docker run --rm -u $(id -u) -v $PWD:/app composer install --ignore-platform-reqs
cp .env.example .env
```

Buka `.env` dan pastikan bagian database seperti ini (hapus tanda `#` di depannya):

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=twinklelittlestar
DB_USERNAME=sail
DB_PASSWORD=password
```

Lalu jalankan:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Buka `http://localhost`. Biarkan `npm run dev` berjalan di satu terminal; buka terminal Ubuntu kedua untuk perintah lain.

Agar tidak mengetik path panjang, buat alias sekali saja:

```bash
echo "alias sail='./vendor/bin/sail'" >> ~/.bashrc
source ~/.bashrc
```

Setelah itu cukup mengetik `sail up -d`, `sail artisan migrate`, dan seterusnya (hanya di dalam folder proyek).

**Aturan emas:** semua perintah `artisan`, `composer`, dan `npm` harus diawali `sail`. Tanpa `sail`, perintah berjalan di luar Docker dan akan gagal (misalnya error `getaddrinfo for mysql failed`, atau `vite is not recognized`).

**Jika port bentrok** (Laragon atau MySQL lain di Windows memakai port 80/3306), tambahkan di `.env`:

```text
APP_PORT=8080
FORWARD_DB_PORT=3307
```

lalu `sail down`, `sail up -d`, dan buka `http://localhost:8080`.

## 3. Alur Kerja Git (semua anggota)

| Aturan | Penjelasan |
|---|---|
| Jangan push langsung ke `main` | Selalu lewat branch dan Pull Request (PR) |
| Nama branch | `feat/fe-...` (frontend), `feat/be-...` (backend), `feat/db-...` (database), `fix/...` untuk perbaikan bug |
| Commit kecil dan jelas | Contoh: `feat(fe): kartu post`, `fix(be): validasi url post` |
| Satu PR = satu tujuan | Jangan campur banyak fitur dalam satu PR |
| Minimal 1 reviewer | Anggota lain membaca dan mencoba dulu sebelum merge |

Alur harian:

```bash
git checkout main && git pull
git checkout -b feat/fe-halaman-profil
# ...kerjakan, lalu:
git add .
git commit -m 'feat(fe): halaman profil'
git push -u origin feat/fe-halaman-profil
```

Setelah itu buka Pull Request di GitHub.

**Jangan pernah di-commit:** `.env` (berisi password dan APP_KEY), `vendor/`, `node_modules/`, `public/build/`, `public/hot`. File-file ini sudah ada di `.gitignore` bawaan Laravel; jangan dipaksa masuk.

**Setiap kali selesai `git pull`**, jalankan:

```bash
sail composer install
sail npm install
sail artisan migrate
```

Supaya paket baru dan migration baru dari teman ikut terpasang.

**Titik rawan konflik** (beri tahu tim di grup sebelum mengubahnya): `routes/web.php`, `resources/views/layouts/navigation.blade.php`, `package.json`, dan file migration.

## 4. Panduan Frontend

### 4.1 File yang menjadi tanggung jawab frontend

| Lokasi | Isi |
|---|---|
| `resources/views/**` | Halaman Blade (HTML) |
| `resources/views/components/` | Komponen yang bisa dipakai ulang |
| `resources/views/layouts/` | Kerangka halaman (`app` untuk user login, `guest` untuk login/register) |
| `resources/css/app.css` | Entri Tailwind (jarang diubah) |
| `resources/js/app.js` | Entri JavaScript / Alpine |
| `tailwind.config.js` | Konfigurasi tema Tailwind |

Jangan mengubah `app/Http`, `routes/`, atau `database/` tanpa berdiskusi dengan backend atau database.

### 4.2 Blade: HTML dengan data dari backend

| Sintaks | Fungsi |
|---|---|
| `{{ $post->title }}` | Menampilkan data (otomatis di-escape, aman dari XSS) |
| `@foreach ($posts as $post) ... @endforeach` | Perulangan |
| `@forelse ... @empty ... @endforelse` | Perulangan dengan tampilan jika data kosong |
| `@if (...) ... @endif` | Kondisi |
| `@auth ... @endauth` / `@guest ... @endguest` | Isi hanya untuk user login / belum login |
| `@csrf` | **Wajib** di setiap form POST (tanpa ini muncul error 419) |
| `@method('DELETE')` | Form yang memakai method DELETE/PUT/PATCH |
| `route('posts.show', $post)` | Membuat URL dari nama route |
| `<x-app-layout> ... </x-app-layout>` | Membungkus halaman dengan layout utama |
| `@include('comments._comment', [...])` | Menyisipkan potongan view |

**Keamanan:** jangan memakai `{!! !!}` untuk data dari pengguna. Gunakan `{{ }}` saja.

Contoh halaman baru:

```html
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Judul Halaman</h2>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- isi halaman -->
    </div>
</x-app-layout>
```

Nama variabel yang tersedia di sebuah view ditentukan oleh controller (lihat bagian 7). Catat di deskripsi PR: “halaman ini menerima `$post` (beserta `user` dan `community`)”.

### 4.3 Proyek ini memakai Tailwind, bukan Bootstrap

Tailwind sudah terpasang dan dipakai oleh seluruh halaman Breeze. **Jangan menambahkan Bootstrap.** Keduanya sama-sama mengatur reset CSS dan tombol/form, sehingga tampilan bentrok dan ukuran CSS membengkak. Pakai padanan Tailwind berikut:

| Bootstrap | Tailwind |
|---|---|
| `container` | `max-w-4xl mx-auto px-4` |
| `row` / `col-md-4` | `grid grid-cols-1 md:grid-cols-3 gap-4` |
| `d-flex justify-content-between` | `flex justify-between items-center` |
| `btn btn-primary` | `px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500` |
| `card` | `bg-white p-4 rounded-lg shadow-sm` |
| `text-muted` | `text-gray-500` |
| `form-control` | `block w-full border-gray-300 rounded-md shadow-sm` |
| `badge` | `px-2 py-0.5 text-xs rounded-full bg-gray-100` |
| `d-none d-md-block` | `hidden md:block` |
| `mt-3`, `p-3` | `mt-3`, `p-3` (nama sama, tapi 1 unit = 0,25rem) |

Cara pakai Tailwind: tulis class langsung di elemen HTML. Awalan `sm:`, `md:`, `lg:` mengatur tampilan per ukuran layar (mobile-first), dan `hover:` mengatur tampilan saat disorot. Dokumentasi: tailwindcss.com/docs (cari nama properti CSS yang dimaksud).

**Perhatian:** Tailwind hanya membuat class yang ia temukan **utuh** di file view. Jangan menyusun nama class secara dinamis seperti `text-{{ $warna }}-500`. Tulis class lengkap, misalnya `text-red-500`.

### 4.4 Dua mode CSS/JS: dev dan build

| Perintah | Kapan dipakai |
|---|---|
| `sail npm run dev` | Saat mengedit tampilan. Biarkan terus berjalan; perubahan langsung muncul. Hentikan dengan **Ctrl+C**. |
| `sail npm run build` | Sekali sebelum menguji/membuat PR, atau saat tidak ingin menjalankan `dev`. |

Jika halaman tampil **tanpa style** padahal `dev` tidak berjalan, hapus file sisa:

```bash
rm -f public/hot
sail npm run build
```

Lalu refresh keras dengan **Ctrl+Shift+R**. File `public/hot` yang tertinggal membuat Laravel mencari server Vite yang sudah mati.

### 4.5 Komponen yang bisa dipakai ulang

Buat file `resources/views/components/post-card.blade.php`:

```html
@props(['post'])
<a href="{{ route('posts.show', $post) }}" class="block bg-white p-4 rounded-lg shadow-sm hover:bg-gray-50">
    <div class="font-semibold">{{ $post->title }}</div>
    <p class="text-xs text-gray-400">oleh {{ $post->user->name }}</p>
</a>
```

Pakai di halaman mana pun:

```html
<x-post-card :post="$post" />
```

Komponen bawaan Breeze yang sudah ada: `x-primary-button`, `x-text-input`, `x-input-label`, `x-input-error`, `x-modal`, `x-dropdown`, `x-nav-link`. Pakai ulang sebelum membuat yang baru.

### 4.6 Interaksi dengan Alpine.js

Alpine sudah aktif di semua halaman. Contoh dropdown sederhana:

```html
<div x-data="{ open: false }">
    <button @click="open = !open">Menu</button>
    <div x-show="open">Isi menu</div>
</div>
```

Untuk interaksi sederhana (buka/tutup, tab, modal) cukup Alpine. Tidak perlu jQuery atau framework tambahan.

### 4.7 Menambah library frontend

Pasang lewat npm (di dalam Sail), bukan dengan menyalin file:

```bash
sail npm install nama-paket
```

Lalu `import` di `resources/js/app.js`. Jika library punya CSS, taruh `@import 'nama-paket/css';` di **baris paling atas** `resources/css/app.css`. Jalankan `sail npm run build`, pastikan tidak ada error, dan commit `package.json` serta `package-lock.json`. Beri tahu tim karena anggota lain perlu `sail npm install`. Untuk ikon, pakai SVG inline (misalnya Heroicons).

### 4.8 Checklist frontend sebelum PR

- `sail npm run build` selesai tanpa error
- Tampilan dicek di lebar HP (buka DevTools, tombol responsive) dan desktop
- Tampilan dicek saat login dan saat belum login
- Setiap form punya `@csrf`, dan pesan error validasi terlihat (`x-input-error`)
- Tidak ada `{!! !!}` pada data pengguna
- Tidak ada class Bootstrap yang tersisa

## 5. Panduan Backend

### 5.1 Peta file backend

| Lokasi | Isi |
|---|---|
| `routes/web.php` | URL dan nama route |
| `routes/auth.php` | Route login/register bawaan Breeze (jangan diubah) |
| `app/Http/Controllers/` | Logika setiap halaman |
| `app/Models/` | Model Eloquent dan relasinya |
| `config/` | Pengaturan Laravel (jarang diubah) |

Route yang sudah ada: `communities.index/create/store/show/join/leave`, `posts.create/store/show/destroy`, `comments.store/destroy`, dan route profil serta autentikasi dari Breeze. Komunitas dicari berdasarkan **slug** (`/c/web-dev`), post berdasarkan **id** (`/posts/1`).

### 5.2 Model dan relasi yang sudah ada

| Model | Relasi |
|---|---|
| `User` | `posts`, `comments`, `communities` |
| `Community` | `creator`, `members` (pivot `role`), `posts` |
| `Post` | `user`, `community`, `comments`, `votes` |
| `Comment` | `user`, `post`, `parent`, `replies`, `votes` |
| `Vote` | `votable` (polimorfik: bisa untuk post atau komentar) |

### 5.3 Contoh: menambah fitur “edit post”

Langkah standar untuk setiap fitur baru: **route → controller → validasi → otorisasi → view**.

Tambahkan di grup `auth` pada `routes/web.php`:

```php
Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
```

Tambahkan di `PostController`:

```php
public function edit(Post $post)
{
    abort_unless(auth()->id() === $post->user_id, 403);

    return view('posts.edit', compact('post'));
}

public function update(Request $request, Post $post)
{
    abort_unless($request->user()->id === $post->user_id, 403);

    $data = $request->validate([
        'title' => ['required', 'string', 'max:300'],
        'body'  => ['nullable', 'string', 'max:10000'],
    ]);

    $post->update($data);

    return redirect()->route('posts.show', $post);
}
```

Setelah itu frontend membuat view `posts/edit.blade.php` dengan form `@method('PUT')`.

### 5.4 Aturan penulisan backend

- **Validasi** selalu lewat `$request->validate([...])`. Jangan percaya input pengguna.
- **Otorisasi:** periksa siapa yang boleh melakukan aksi. Pola di proyek ini: `abort_unless($user->id === $model->user_id || $isMod, 403)`.
- **Mass assignment:** kolom yang boleh diisi massal harus ada di `$fillable` model, jika tidak muncul error `MassAssignmentException`.
- **Hindari query berulang (N+1):** muat relasi di awal, contoh `Post::with('user', 'community')->latest()->paginate(15)`.
- **Gunakan `paginate()`**, jangan `get()` untuk daftar yang bisa panjang.
- **Jangan menulis SQL mentah** dengan menempelkan input pengguna. Gunakan Eloquent atau query builder dengan binding.
- **Nama route** memakai pola `sumber.aksi` (`posts.show`) supaya mudah dipakai frontend lewat `route()`.
- Password otomatis di-hash oleh model `User`. Jangan menyimpan password polos.

### 5.5 Perintah artisan yang sering dipakai

```bash
sail artisan make:controller NamaController   # controller baru
sail artisan make:model Nama -m                # model + migration
sail artisan make:request NamaRequest          # form request (validasi terpisah)
sail artisan route:list                        # lihat semua route
sail artisan tinker                            # konsol PHP interaktif
sail artisan pail                              # lihat log secara live
sail artisan test                              # jalankan test
```

### 5.6 Debugging

- Halaman **kosong** biasanya berarti method controller kosong atau view kosong.
- Halaman **error 500**: lihat `sail artisan pail` atau `storage/logs/laravel.log`. Pastikan `APP_DEBUG=true` di `.env` lokal.
- Sementara waktu pakai `dd($variabel);` untuk melihat isi data, **hapus sebelum commit**.
- Setelah mengubah `.env` atau config: `sail artisan config:clear`. Setelah mengubah route atau view yang aneh: `sail artisan optimize:clear`.

### 5.7 Pekerjaan backend berikutnya

1. **Voting:** `VoteController` dengan route upvote/downvote untuk post dan komentar (tabel `votes` sudah ada). Satu user hanya satu vote per item; klik ulang membatalkan, klik arah lain mengganti nilai.
2. **Sorting:** New (`latest()`), Top (urut `score`), Hot (rumus sederhana: `score / (jam sejak dibuat + 2) ^ 1,5`).
3. **Moderasi:** tombol hapus untuk moderator, batas agar pembuat komunitas tidak bisa keluar dari komunitasnya sendiri.

### 5.8 Checklist backend sebelum PR

- Semua input divalidasi dan semua aksi punya otorisasi
- `sail artisan route:list` menampilkan route baru dengan nama yang benar
- Fitur dicoba sebagai user biasa, moderator, dan tamu (belum login)
- `sail artisan test` tidak error
- Tidak ada `dd()` yang tertinggal

## 6. Panduan Database

### 6.1 Aturan emas

**Struktur tabel hanya boleh diubah lewat migration, bukan langsung di DBeaver.** Alasannya: migration ada di Git sehingga semua anggota dan server produksi mendapat struktur yang sama. Perubahan manual di DBeaver hanya ada di laptop sendiri dan akan membuat repo dan database tidak sinkron.

DBeaver dipakai untuk **melihat data**, menguji query, dan mengisi data contoh.

### 6.2 Menyambungkan DBeaver

Pastikan Sail berjalan (`sail ps`). Buat koneksi **MySQL** baru:

| Kolom | Isi |
|---|---|
| Host | `127.0.0.1` |
| Port | `3306` (atau `3307` jika memakai `FORWARD_DB_PORT=3307`) |
| Database | `twinklelittlestar` |
| User / Password | `sail` / `password` |

Jika muncul error *Public Key Retrieval is not allowed*, buka tab **Driver properties** dan set `allowPublicKeyRetrieval` ke `true`.

### 6.3 Skema saat ini

| Tabel | Kolom penting | Keterangan |
|---|---|---|
| `users` | name, email, password | Dari Breeze |
| `communities` | name (unik), slug (unik), description, creator_id | `creator_id` mengacu ke `users` |
| `community_user` | community_id, user_id, role | Keanggotaan; `role` = `member` atau `mod`; pasangan (community, user) unik |
| `posts` | user_id, community_id, title, body, url, score | Hapus komunitas, post ikut terhapus (cascade) |
| `comments` | user_id, post_id, parent_id, body, score | `parent_id` mengacu ke `comments` sendiri untuk balasan |
| `votes` | user_id, votable_id, votable_type, value | Polimorfik; `value` = +1 atau -1; unik per (user, item) |

Tabel lain (`sessions`, `cache`, `jobs`, `migrations`, `password_reset_tokens`) dibuat Laravel; jangan diubah. Kolom `votable_type` menyimpan nama kelas lengkap seperti `App\Models\Post`.

### 6.4 Membuat tabel baru

Contoh tabel “post tersimpan”. Buat model beserta migration-nya:

```bash
sail artisan make:model SavedPost -m
```

Edit file baru di `database/migrations/`, bagian `up()`:

```php
Schema::create('saved_posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['user_id', 'post_id']);
});
```

Jalankan `sail artisan migrate`, lalu segarkan DBeaver (F5). Beri tahu backend agar mengisi `$fillable` dan relasi di model.

Urutan file migration ditentukan oleh timestamp di nama file. Tabel yang diacu foreign key harus dibuat **lebih dulu** daripada tabel yang mengacunya.

### 6.5 Mengubah tabel yang sudah ada

**Jangan mengedit migration lama** yang sudah di-merge atau dipakai orang lain. Buat migration baru:

```bash
sail artisan make:migration add_is_pinned_to_posts_table --table=posts
```

```php
public function up(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->boolean('is_pinned')->default(false)->after('score');
    });
}

public function down(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn('is_pinned');
    });
}
```

Selalu isi `down()` agar perubahan bisa dibatalkan.

### 6.6 Tipe kolom dan constraint yang sering dipakai

| Kode migration | Arti |
|---|---|
| `$table->id()` | Primary key auto-increment |
| `$table->string('name')` | Teks pendek (maks. 255) |
| `$table->text('body')` | Teks panjang |
| `$table->integer('score')` / `boolean` / `timestamp` | Angka / benar-salah / waktu |
| `->nullable()` | Boleh kosong |
| `->default(0)` | Nilai awal |
| `->unique()` | Tidak boleh ada nilai kembar |
| `->index()` | Mempercepat pencarian pada kolom itu |
| `$table->foreignId('x_id')->constrained()` | Foreign key ke tabel `xs` |
| `->cascadeOnDelete()` | Data anak ikut terhapus saat data induk dihapus |

### 6.7 Perintah migration

| Perintah | Fungsi |
|---|---|
| `sail artisan migrate` | Menjalankan migration yang belum jalan |
| `sail artisan migrate:status` | Melihat mana yang sudah/belum jalan |
| `sail artisan migrate:rollback` | Membatalkan batch terakhir |
| `sail artisan migrate:fresh` | **Menghapus semua tabel** lalu membuat ulang (hanya untuk lokal; semua data hilang) |
| `sail artisan migrate:fresh --seed` | Sama seperti di atas lalu mengisi data contoh |

### 6.8 Menambah data (rows)

1. **Lewat Seeder (disarankan):** data contoh ikut tersimpan di Git, jadi semua anggota punya data yang sama. Edit `database/seeders/DatabaseSeeder.php` (tambahkan `use App\Models\...` untuk model yang dipakai):

```php
public function run(): void
{
    $user = User::factory()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
    ]);

    $community = Community::create([
        'name' => 'Web Dev',
        'slug' => 'web-dev',
        'description' => 'Diskusi seputar pemrograman web',
        'creator_id' => $user->id,
    ]);

    $community->members()->attach($user->id, ['role' => 'mod']);
}
```

Jalankan dengan `sail artisan db:seed`. Password user hasil factory mengikuti `UserFactory` (cek file itu; biasanya `password`).

2. **Lewat Tinker:** cepat untuk mencoba, misalnya `sail artisan tinker` lalu `App\Models\Community::count();`.
3. **Lewat DBeaver:** klik kanan tabel, tambah baris. Hanya untuk eksperimen di laptop sendiri. Jangan mengandalkan cara ini untuk data yang harus dibagikan ke tim.

### 6.9 SQL dasar untuk memeriksa data

Buka *SQL Editor* di DBeaver (Ctrl+]).

```sql
-- jumlah post per komunitas
SELECT c.name, COUNT(p.id) AS total_post
FROM communities c
LEFT JOIN posts p ON p.community_id = c.id
GROUP BY c.id, c.name
ORDER BY total_post DESC;

-- komentar beserta penulisnya untuk satu post
SELECT cm.id, cm.parent_id, u.name, cm.body
FROM comments cm
JOIN users u ON u.id = cm.user_id
WHERE cm.post_id = 1
ORDER BY cm.created_at;

-- mengubah peran anggota
UPDATE community_user SET role = 'mod'
WHERE community_id = 1 AND user_id = 2;
```

**Peringatan:** `UPDATE` dan `DELETE` tanpa `WHERE` mengubah atau menghapus **seluruh** baris tabel. Selalu tulis `WHERE`, dan jalankan `SELECT` dengan `WHERE` yang sama dulu untuk melihat baris mana yang terkena.

### 6.10 Backup dan performa

Backup cepat database lokal:

```bash
sail exec mysql mysqldump -usail -ppassword twinklelittlestar > backup.sql
```

Untuk kolom yang sering dipakai mencari atau mengurutkan (misalnya `score`, `created_at` pada post), pertimbangkan `->index()` lewat migration baru. Foreign key sudah otomatis diberi index oleh Laravel.

## 7. Kerja Sama Antar Peran

**Kepemilikan file** (supaya tidak saling menimpa):

| Peran | Folder |
|---|---|
| Frontend | `resources/views/`, `resources/css/`, `resources/js/`, `tailwind.config.js` |
| Backend | `app/`, `routes/`, `config/` |
| Database | `database/migrations/`, `database/seeders/`, `database/factories/` |
| Bersama (koordinasi dulu) | `routes/web.php`, `layouts/navigation.blade.php`, `package.json`, `compose.yaml`, `.env.example` |

**Urutan kerja satu fitur**, contoh voting:

1. **Database:** memastikan tabel dan kolom yang dibutuhkan ada (migration baru jika perlu), plus seeder data contoh.
2. **Backend:** route dan controller, lalu menyepakati nama route dan variabel yang dikirim ke view.
3. **Frontend:** tombol dan tampilan memakai nama route dan variabel tersebut.

Backend dan frontend bisa bekerja paralel jika sudah sepakat soal “kontrak” ini. Tulis di deskripsi PR: nama route (`votes.store`), method (POST), data yang dikirim form (`value` = 1 atau -1), dan variabel di view.

**Komunikasi:** umumkan di grup tim jika mengubah skema database, menambah library, atau mengubah file bersama. Setiap anggota harus menjalankan `sail artisan migrate` dan `sail npm install` setelah menarik perubahan tersebut.

## 8. Pemecahan Masalah

| Gejala | Penyebab dan solusi |
|---|---|
| Halaman tanpa style, request ke `localhost:5173` gagal | File `public/hot` tertinggal. `rm -f public/hot`, lalu `sail npm run build` |
| Class Tailwind baru tidak berpengaruh | Jalankan `sail npm run build` (atau pastikan `dev` berjalan). Cek nama class tidak disusun dinamis |
| `getaddrinfo for mysql failed` | Perintah dijalankan tanpa `sail`. Gunakan `sail artisan ...` |
| `vite is not recognized` | `npm` dijalankan tanpa `sail`, atau belum `sail npm install` |
| `Base table or view not found` | Migration belum dijalankan: `sail artisan migrate` |
| Error `ports are not available` (3306/80) | Port dipakai program lain (Laragon). Atur `FORWARD_DB_PORT` dan `APP_PORT` di `.env`, atau matikan Laragon |
| Error 419 Page Expired | Form tidak punya `@csrf` |
| `MassAssignmentException` | Kolom belum masuk `$fillable` di model |
| `Class ... not found` | Kurang baris `use` di bagian atas file PHP |
| Halaman kosong/blank | Method controller atau file view kosong; cek `sail artisan pail` |
| Perubahan `.env` tidak berlaku | `sail artisan config:clear` |
| Error aneh setelah `git pull` | `sail composer install`, `sail npm install`, `sail artisan migrate`, lalu `sail artisan optimize:clear` |
| `Access denied` untuk MySQL | Cek `.env`; jika MySQL pernah berjalan dengan password lain: `sail down -v` lalu `sail up -d` (data lokal hilang) |
| Docker tidak ditemukan di Ubuntu | Docker Desktop belum jalan, atau WSL integration untuk Ubuntu belum aktif |

## 9. Daftar Perintah Cepat

| Tugas | Perintah |
|---|---|
| Menyalakan / mematikan | `sail up -d` / `sail down` |
| Melihat status container | `sail ps` |
| Mode edit tampilan | `sail npm run dev` |
| Membuat file CSS/JS final | `sail npm run build` |
| Migration | `sail artisan migrate` |
| Lihat semua route | `sail artisan route:list` |
| Lihat log | `sail artisan pail` |
| Membersihkan cache | `sail artisan optimize:clear` |
| Konsol interaktif | `sail artisan tinker` |
| Menjalankan test | `sail artisan test` |
| Pasang paket PHP / JS | `sail composer require x` / `sail npm install x` |

## 10. Aturan Tim dan Checklist PR

- Branch dari `main` yang sudah di-pull terbaru, nama branch sesuai peran.
- Satu PR untuk satu tujuan, dengan deskripsi singkat: apa yang berubah, cara mengujinya, dan (jika ada) nama route dan variabel view.
- Jalankan `sail artisan migrate` dan `sail npm run build` sebelum membuka PR; pastikan tidak ada error.
- Uji sendiri di browser: daftar, login, buat komunitas, buat post, komentar, lalu logout.
- Jangan commit `.env`, password, atau data pribadi.
- Minimal satu anggota lain mereview dan mencoba sebelum merge.
- Kendala lebih dari 30 menit? Tanyakan ke tim dengan menyertakan pesan error lengkap dan perintah yang dijalankan.
