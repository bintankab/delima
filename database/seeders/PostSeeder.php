<?php

namespace Database\Seeders;

use App\Models\PostModel;
use App\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $author = UserModel::query()->where('email', 'delima@bintankab.go.id')->first()
            ?? UserModel::query()->first();

        if ($author === null) {
            $this->command?->warn('Post tidak diisi karena belum ada pengguna.');

            return;
        }

        foreach ($this->articles() as $article) {
            $slug = Str::slug($article['title']);
            $post = PostModel::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $author->id,
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'content' => $article['content'],
                    'status' => 'published',
                    'published_at' => $article['published_at'],
                ],
            );

            if ($post->getFirstMedia('cover') !== null || ! function_exists('imagecreatetruecolor')) {
                continue;
            }

            $path = storage_path('app/post-seed-'.$slug.'.jpg');
            $this->writeCover($path, $article['color']);
            $post->addMedia($path)->toMediaCollection('cover');
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function writeCover(string $path, array $color): void
    {
        $image = imagecreatetruecolor(1200, 700);
        $background = imagecolorallocate($image, $color[0], $color[1], $color[2]);
        $accent = imagecolorallocate(
            $image,
            min($color[0] + 35, 255),
            min($color[1] + 35, 255),
            min($color[2] + 35, 255),
        );
        imagefilledrectangle($image, 0, 0, 1200, 700, $background);
        imagefilledellipse($image, 920, 160, 480, 480, $accent);
        imagejpeg($image, $path, 85);
        imagedestroy($image);
    }

    /**
     * @return list<array{title: string, excerpt: string, content: string, published_at: string, color: array{0: int, 1: int, 2: int}}>
     */
    private function articles(): array
    {
        return [
            [
                'title' => 'Delima Menjadi Simpul Jaring Inovasi Daerah Bintan',
                'excerpt' => 'Kabupaten Bintan menempatkan Delima sebagai ruang dokumentasi, diseminasi, dan replikasi inovasi daerah.',
                'content' => '<p>Pemerintah Kabupaten Bintan menempatkan Delima sebagai simpul jaring inovasi daerah. Platform ini mengumpulkan karya inovasi, riset, dan dokumen publik agar mudah ditemukan oleh perangkat daerah maupun masyarakat.</p><p>Melalui Delima, inovasi yang sudah berjalan dapat dipelajari kembali, lalu dicoba di tempat lain tanpa harus memulai dari nol. Pendampingan antar pemangku kepentingan juga disiapkan lewat kanal interaksi yang menyatu dengan data inovasi.</p>',
                'published_at' => '2026-10-28 09:00:00',
                'color' => [30, 58, 138],
            ],
            [
                'title' => 'Posyandu Digital Mempercepat Catatan Kesehatan Balita',
                'excerpt' => 'Kader posyandu di beberapa kecamatan mulai mencatat pertumbuhan balita lewat aplikasi sederhana.',
                'content' => '<p>Kader posyandu di Bintan mulai beralih dari buku catatan manual ke pencatatan digital. Berat badan, tinggi badan, dan status imunisasi balita dapat direkap lebih cepat saat hari pelayanan.</p><p>Data yang rapi membantu puskesmas melihat wilayah yang masih tertinggal, lalu mengatur kunjungan susulan. Uji coba ini menjadi contoh inovasi pelayanan yang bisa direplikasi ke kecamatan lain.</p>',
                'published_at' => '2026-10-21 08:30:00',
                'color' => [15, 118, 110],
            ],
            [
                'title' => 'Nelayan Bintan Mencoba Jadwal Melaut Berdasarkan Cuaca',
                'excerpt' => 'Kelompok nelayan memakai prakiraan cuaca harian untuk mengurangi keberangkatan saat laut berbahaya.',
                'content' => '<p>Sebuah kelompok nelayan di pesisir Bintan mencoba menyusun jadwal melaut dari prakiraan cuaca yang dibagikan setiap pagi. Informasi itu menandai hari yang aman dan hari yang sebaiknya ditunda.</p><p>Kebiasaan baru ini menekan keberangkatan saat gelombang tinggi. Perangkat daerah mendampingi agar cara yang sama bisa dipakai kelompok nelayan di desa tetangga.</p>',
                'published_at' => '2026-10-14 07:00:00',
                'color' => [3, 105, 161],
            ],
            [
                'title' => 'Perizinan Usaha Mikro Kini Dilayani Lebih Singkat',
                'excerpt' => 'Pemohon izin usaha mikro dapat memantau status berkas tanpa harus bolak-balik ke loket.',
                'content' => '<p>Layanan perizinan usaha mikro di Bintan mempersingkat antrean dengan status berkas yang bisa dipantau. Pemohon mengetahui dokumen yang kurang sebelum hari pengambilan.</p><p>Petugas loket juga memakai daftar periksa yang sama, sehingga jawaban antar petugas tidak berbeda-beda. Inovasi ini menjadi bagian dari perbaikan pelayanan publik daerah.</p>',
                'published_at' => '2026-10-07 10:00:00',
                'color' => [109, 40, 217],
            ],
            [
                'title' => 'Sekolah Pesisir Membuka Kelas Praktik Inovasi',
                'excerpt' => 'Siswa mencoba merancang solusi sederhana untuk sampah pantai dan air bersih di lingkungan sekolah.',
                'content' => '<p>Beberapa sekolah di pesisir Bintan membuka kelas praktik yang meminta siswa mengamati masalah di sekitar sekolah, lalu merancang solusi kecil yang bisa diuji.</p><p>Hasil kelas itu dipamerkan kepada guru dan orang tua. Gagasan yang layak diteruskan ke pendamping inovasi daerah agar tidak berhenti sebagai tugas sekolah.</p>',
                'published_at' => '2026-09-29 09:00:00',
                'color' => [180, 83, 9],
            ],
            [
                'title' => 'Bank Sampah Desa Mengurangi Kiriman ke Tempat Pembuangan',
                'excerpt' => 'Warga menukar sampah terpilah dengan tabungan, sementara petugas desa mencatat setoran setiap pekan.',
                'content' => '<p>Bank sampah di salah satu desa Bintan mencatat setoran warga setiap pekan. Sampah yang sudah terpilah tidak lagi ikut truk menuju tempat pembuangan akhir.</p><p>Tabungan dari setoran dipakai warga untuk kebutuhan harian. Pemerintah desa mendokumentasikan cara kerja ini supaya desa lain dapat menirunya dengan perangkat yang sama.</p>',
                'published_at' => '2026-09-18 08:00:00',
                'color' => [22, 101, 52],
            ],
            [
                'title' => 'Klinik Inovasi Mendampingi Perangkat Daerah Menyusun Uji Coba',
                'excerpt' => 'OPD yang punya gagasan baru mendapat jadwal pendampingan untuk merapikan masalah, manfaat, dan ukuran keberhasilan.',
                'content' => '<p>Klinik inovasi daerah membuka pendampingan bagi perangkat daerah yang ingin menguji gagasan baru. Setiap sesi membantu merumuskan masalah, pihak yang terdampak, dan tanda bahwa uji coba berhasil.</p><p>Pendampingan ini mencegah inovasi berhenti di laporan kegiatan. Gagasan yang lolos uji coba kemudian dicatat di Delima agar dapat dipelajari organisasi lain.</p>',
                'published_at' => '2026-09-08 13:00:00',
                'color' => [157, 23, 77],
            ],
            [
                'title' => 'Festival Inovasi Daerah Menampilkan Karya OPD dan Warga',
                'excerpt' => 'Pameran sehari mempertemukan inovasi pelayanan, riset kampus, dan gagasan masyarakat dalam satu tempat.',
                'content' => '<p>Festival inovasi daerah mempertemukan perangkat daerah, kampus, dan warga yang sudah menjalankan gagasan di lingkungannya. Pengunjung dapat melihat cara kerja tiap inovasi, bukan hanya membaca brosur.</p><p>Sebagian karya yang dipamerkan kemudian masuk ke etalase Delima. Tujuannya agar festival tidak selesai saat stan ditutup, melainkan menjadi bahan replikasi di tahun berikutnya.</p>',
                'published_at' => '2026-08-26 09:30:00',
                'color' => [71, 85, 105],
            ],
        ];
    }
}
