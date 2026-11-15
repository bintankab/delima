<?php

namespace Database\Seeders;

use App\Enums\JenisInisiator;
use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InnovationSeeder extends Seeder
{
    public function run(): void
    {
        $author = UserModel::query()->where('email', 'delima@bintankab.go.id')->first()
            ?? UserModel::query()->first();

        if ($author === null) {
            $this->command?->warn('Inovasi tidak diisi karena belum ada pengguna.');

            return;
        }

        foreach ($this->innovations() as $innovation) {
            $category = InnovationCategoryModel::query()
                ->where('slug', Str::slug($innovation['category']))
                ->first();

            InnovationModel::query()->firstOrCreate(
                ['slug' => Str::slug($innovation['title'])],
                [
                    'user_id' => $author->id,
                    'inisiator' => $innovation['inisiator'],
                    'category_id' => $category?->id,
                    'title' => $innovation['title'],
                    'description' => $innovation['description'],
                    'latar_belakang' => $innovation['latar_belakang'],
                    'manfaat' => $innovation['manfaat'],
                    'jenis_inovasi' => $innovation['jenis_inovasi'],
                    'tahapan' => $innovation['tahapan'],
                    'tahun' => $innovation['tahun'],
                    'status' => 'published',
                    'origin' => 'submission',
                    'is_featured' => $innovation['is_featured'],
                    'implementation_at' => $innovation['implementation_at'],
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     title: string,
     *     category: string,
     *     inisiator: JenisInisiator,
     *     jenis_inovasi: JenisInovasi,
     *     tahapan: TahapanInovasi,
     *     tahun: int,
     *     is_featured: bool,
     *     implementation_at: string,
     *     description: string,
     *     latar_belakang: string,
     *     manfaat: string,
     * }>
     */
    private function innovations(): array
    {
        return [
            [
                'title' => 'Posyandu Digital Bintan Sehat',
                'category' => 'Pelayanan Publik',
                'inisiator' => JenisInisiator::OPD,
                'jenis_inovasi' => JenisInovasi::Digital,
                'tahapan' => TahapanInovasi::Implementasi,
                'tahun' => 2024,
                'is_featured' => true,
                'implementation_at' => '2024-03-01',
                'description' => 'Posyandu Digital Bintan Sehat lahir dari keresahan kader posyandu yang setiap bulan harus menyalin data berat badan, tinggi badan, dan status imunisasi balita dari buku KIA ke buku rekap desa, lalu menyalinnya lagi ke laporan puskesmas. Proses berulang ini memakan waktu, rawan salah tulis, dan membuat data terlambat sampai ke pengambil keputusan di tingkat kecamatan maupun dinas kesehatan. Ketika seorang balita mengalami gangguan pertumbuhan, petugas sering baru menyadarinya sebulan setelah angka itu dicatat, bukan pada hari penimbangan itu sendiri.

Inovasi ini mengganti pencatatan manual dengan formulir digital sederhana yang bisa diisi kader langsung dari telepon genggam saat hari pelayanan posyandu berlangsung. Begitu data berat, tinggi, dan status imunisasi dimasukkan, sistem otomatis menghitung status gizi balita dan memberi tanda peringatan bila ditemukan indikasi stunting atau gizi kurang. Tanda ini langsung terlihat oleh bidan desa dan puskesmas tanpa menunggu proses rekap bulanan, sehingga rujukan dan intervensi dapat dilakukan lebih cepat.

Selama tahap uji coba di tiga kecamatan, waktu rekap laporan bulanan berkurang dari rata-rata tiga hari kerja menjadi kurang dari satu jam, karena data sudah tersusun rapi sejak awal dan tidak perlu disalin ulang. Kader posyandu yang sebelumnya kesulitan memakai aplikasi juga mendapat pendampingan tatap muka dan kartu panduan bergambar supaya proses pengisian tetap mudah meski jaringan internet di desa tidak selalu stabil. Data yang terkumpul kini dipakai dinas kesehatan untuk memetakan wilayah dengan risiko stunting tertinggi, sehingga program intervensi gizi dapat diarahkan tepat sasaran dan tidak lagi dibagi rata ke semua desa tanpa melihat kebutuhan sebenarnya. Inovasi ini diharapkan dapat direplikasi ke seluruh posyandu di Kabupaten Bintan.',
                'latar_belakang' => 'Pencatatan manual data balita di posyandu memakan waktu lama dan rawan salah tulis, sehingga indikasi stunting baru diketahui sebulan setelah penimbangan.',
                'manfaat' => 'Rekap laporan bulanan lebih cepat, indikasi gizi kurang terdeteksi sejak hari penimbangan, dan dinas kesehatan dapat mengarahkan intervensi ke wilayah yang paling membutuhkan.',
            ],
            [
                'title' => 'Sistem Informasi Jaga Pesisir',
                'category' => 'Urusan Penyelenggaraan Daerah',
                'inisiator' => JenisInisiator::ASN,
                'jenis_inovasi' => JenisInovasi::Teknologi,
                'tahapan' => TahapanInovasi::UjiCoba,
                'tahun' => 2025,
                'is_featured' => true,
                'implementation_at' => '2025-02-10',
                'description' => 'Nelayan di pesisir Bintan selama ini menentukan hari melaut hanya berdasarkan kebiasaan dan tanda-tanda alam yang diwariskan turun-temurun, seperti arah angin pagi atau warna air laut. Cara ini cukup diandalkan pada masa lalu, tetapi pola cuaca yang semakin tidak menentu membuat beberapa kelompok nelayan tetap berangkat pada hari yang ternyata berombak tinggi, sehingga perahu kecil mereka berisiko terbalik atau rusak di tengah laut. Setiap insiden seperti ini bukan hanya mengancam keselamatan jiwa, tetapi juga menghentikan pemasukan harian keluarga nelayan dalam waktu yang lama akibat perahu dan alat tangkap yang perlu diperbaiki.

Sistem Informasi Jaga Pesisir dibangun untuk mengisi kekosongan informasi tersebut dengan mengolah data prakiraan cuaca dan ketinggian gelombang dari sumber resmi, lalu menyajikannya dalam bentuk sederhana berupa status aman, waspada, atau bahaya untuk wilayah tangkap masing-masing kelompok nelayan. Informasi ini dikirim setiap pagi melalui pesan singkat dan papan pengumuman di tempat pelelangan ikan, sehingga nelayan yang tidak memiliki telepon pintar tetap dapat mengaksesnya sebelum memutuskan berangkat atau menunda keberangkatan.

Selama masa uji coba di dua desa pesisir, jumlah keberangkatan pada hari berstatus bahaya turun signifikan dibandingkan periode sebelum sistem ini berjalan, dan belum tercatat insiden kecelakaan pada kelompok yang secara rutin memantau informasi tersebut. Kelompok nelayan yang menjadi mitra uji coba juga dilibatkan dalam menilai akurasi status harian, karena pengalaman mereka di laut membantu menyesuaikan ambang batas ketinggian gelombang yang dipakai sistem. Pemerintah daerah berencana memperluas jangkauan informasi ini ke seluruh desa pesisir Bintan sambil tetap menjaga kearifan lokal nelayan dalam membaca tanda alam sebagai pelengkap data resmi yang disajikan setiap hari.',
                'latar_belakang' => 'Nelayan menentukan hari melaut hanya dari kebiasaan dan tanda alam, sehingga masih ada keberangkatan pada hari bergelombang tinggi yang berisiko membahayakan perahu dan jiwa.',
                'manfaat' => 'Keberangkatan pada hari berstatus bahaya menurun signifikan dan nelayan memperoleh informasi cuaca harian yang mudah diakses tanpa memerlukan telepon pintar.',
            ],
            [
                'title' => 'Lapor Izin Cepat',
                'category' => 'Tata Kelola Pemerintahan',
                'inisiator' => JenisInisiator::OPD,
                'jenis_inovasi' => JenisInovasi::Digital,
                'tahapan' => TahapanInovasi::Implementasi,
                'tahun' => 2023,
                'is_featured' => false,
                'implementation_at' => '2023-07-15',
                'description' => 'Pemohon izin usaha mikro di Bintan sebelumnya harus datang berulang kali ke kantor pelayanan hanya untuk menanyakan sejauh mana berkas mereka diproses, karena tidak ada cara lain untuk mengetahui statusnya selain bertanya langsung ke petugas loket. Banyak pelaku usaha kecil yang berjualan setiap hari merasa keberatan harus menutup tokonya sementara demi mengantre di kantor, sementara informasi yang mereka dapatkan kadang berbeda-beda tergantung petugas yang melayani, sehingga menimbulkan kebingungan mengenai dokumen apa saja yang sebenarnya masih kurang.

Lapor Izin Cepat dikembangkan sebagai layanan pemantauan status berkas yang dapat diakses pemohon dari mana saja menggunakan nomor pendaftaran yang diberikan saat berkas diterima. Setiap tahap, mulai dari verifikasi kelengkapan, pemeriksaan lapangan bila diperlukan, hingga penerbitan izin, diperbarui langsung oleh petugas ke dalam sistem sehingga pemohon dapat melihat posisi berkasnya secara real time tanpa harus menelepon atau datang ke kantor. Apabila ada dokumen yang kurang, catatan kekurangan tersebut langsung muncul dengan bahasa yang jelas, lengkap dengan contoh format dokumen yang benar.

Petugas loket juga memakai daftar periksa yang sama persis dengan yang ditampilkan ke pemohon, sehingga tidak ada lagi perbedaan jawaban antara satu petugas dengan petugas lainnya. Sejak diterapkan, rata-rata waktu penyelesaian izin usaha mikro berkurang hampir separuh dari waktu sebelumnya, dan jumlah kunjungan fisik ke kantor pelayanan turun drastis karena sebagian besar pertanyaan pemohon sudah terjawab lewat sistem. Pelaku usaha mikro yang menjadi mitra uji coba menyatakan mereka kini bisa tetap berjualan sambil memantau proses izinnya, dan tidak lagi kehilangan pendapatan harian hanya untuk mengantre di kantor pemerintah.',
                'latar_belakang' => 'Pemohon izin usaha mikro harus berulang kali datang ke kantor hanya untuk menanyakan status berkas, dan jawaban petugas sering berbeda-beda sehingga membingungkan.',
                'manfaat' => 'Waktu penyelesaian izin usaha mikro berkurang hampir separuh dan kunjungan fisik ke kantor pelayanan turun drastis karena status berkas dapat dipantau sendiri.',
            ],
            [
                'title' => 'Bank Sampah Desa Berdaya',
                'category' => 'Masyarakat / Umum',
                'inisiator' => JenisInisiator::Masyarakat,
                'jenis_inovasi' => JenisInovasi::NonDigital,
                'tahapan' => TahapanInovasi::Implementasi,
                'tahun' => 2022,
                'is_featured' => false,
                'implementation_at' => '2022-05-20',
                'description' => 'Sebelum program ini berjalan, sampah rumah tangga di sebagian desa Bintan dibuang begitu saja tanpa dipilah, sehingga truk pengangkut harus membawa seluruh sampah basah dan kering ke tempat pembuangan akhir yang kapasitasnya semakin menyusut setiap tahun. Warga juga tidak merasakan manfaat langsung dari memilah sampah, karena tidak ada mekanisme yang menghargai usaha mereka memisahkan plastik, kertas, dan logam dari sampah dapur sehari-hari, sehingga kebiasaan memilah sulit tumbuh meski sudah berulang kali disosialisasikan oleh pemerintah desa.

Bank Sampah Desa Berdaya mengubah pendekatan ini dengan menjadikan sampah terpilah sebagai sesuatu yang bernilai tukar. Warga membawa sampah yang sudah dipisah ke pos penimbangan setiap pekan, lalu petugas desa menimbang dan mencatat jenis serta beratnya ke dalam buku tabungan sederhana milik setiap warga. Nilai tabungan ini dihitung berdasarkan harga sampah yang berlaku di pengumpul besar, dan warga dapat mengambil tabungannya dalam bentuk uang tunai maupun menukarnya dengan kebutuhan pokok yang disediakan di pos yang sama.

Setelah berjalan selama satu tahun, volume sampah yang dikirim ke tempat pembuangan akhir dari desa ini berkurang cukup besar, sementara jumlah warga yang rutin menabung sampah terus bertambah setiap bulan. Anak-anak muda desa dilibatkan sebagai petugas pencatat sekaligus pengelola buku tabungan digital sederhana yang menggantikan pencatatan manual, sehingga rekap bulanan tidak lagi memerlukan waktu lama untuk disusun. Pemerintah desa kini mendokumentasikan seluruh tata cara pengelolaan bank sampah ini, mulai dari skema harga hingga pelatihan petugas, agar desa-desa tetangga yang memiliki karakteristik warga serupa dapat meniru pendekatan ini tanpa harus merancang ulang dari awal, cukup menyesuaikan dengan kondisi masing-masing wilayah.',
                'latar_belakang' => 'Sampah rumah tangga dibuang tanpa dipilah karena warga tidak merasakan manfaat langsung dari usaha memisahkan sampah, sehingga kebiasaan memilah sulit tumbuh meski sudah disosialisasikan.',
                'manfaat' => 'Volume sampah yang dikirim ke tempat pembuangan akhir berkurang signifikan dan warga memperoleh nilai tukar dari sampah yang mereka pilah sendiri.',
            ],
        ];
    }
}
