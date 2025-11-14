<?php

namespace Database\Seeders;

use App\Models\Indicator;
use Illuminate\Database\Seeder;

class IndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $indicators = [
            [
                'name' => 'Pertumbuhan Ekonomi',
                'slug' => 'pertumbuhan-ekonomi',
                'description' => 'Data dan analisis pertumbuhan ekonomi regional',
                'icon' => 'fas fa-chart-line',
                'is_active' => true,
            ],
            [
                'name' => 'Inflasi',
                'slug' => 'inflasi',
                'description' => 'Tingkat inflasi dan indeks harga konsumen',
                'icon' => 'fas fa-percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Nilai Tukar Petani (NTP)',
                'slug' => 'ntp',
                'description' => 'Nilai tukar petani dan indeks harga yang dibayar petani',
                'icon' => 'fas fa-seedling',
                'is_active' => true,
            ],
            [
                'name' => 'Ekspor dan Impor',
                'slug' => 'ekspor-impor',
                'description' => 'Data perdagangan luar negeri ekspor dan impor',
                'icon' => 'fas fa-ship',
                'is_active' => true,
            ],
            [
                'name' => 'Transportasi',
                'slug' => 'transportasi',
                'description' => 'Statistik transportasi dan mobilitas',
                'icon' => 'fas fa-truck',
                'is_active' => true,
            ],
            [
                'name' => 'Pariwisata',
                'slug' => 'pariwisata',
                'description' => 'Data kepariwisataan dan industri kreatif',
                'icon' => 'fas fa-camera',
                'is_active' => true,
            ],
            [
                'name' => 'Pembangunan Manusia',
                'slug' => 'pembangunan-manusia',
                'description' => 'Indeks pembangunan manusia dan kualitas hidup',
                'icon' => 'fas fa-users',
                'is_active' => true,
            ],
            [
                'name' => 'Pertanian',
                'slug' => 'pertanian',
                'description' => 'Statistik pertanian, perkebunan, dan kehutanan',
                'icon' => 'fas fa-leaf',
                'is_active' => true,
            ],
            [
                'name' => 'Ketenagakerjaan',
                'slug' => 'ketenagakerjaan',
                'description' => 'Data ketenagakerjaan dan angkatan kerja',
                'icon' => 'fas fa-briefcase',
                'is_active' => true,
            ],
            [
                'name' => 'Kemiskinan',
                'slug' => 'kemiskinan',
                'description' => 'Tingkat kemiskinan dan kesenjangan sosial',
                'icon' => 'fas fa-hand-holding-heart',
                'is_active' => true,
            ],
            [
                'name' => 'Industri',
                'slug' => 'industri',
                'description' => 'Statistik industri pengolahan dan manufaktur',
                'icon' => 'fas fa-industry',
                'is_active' => true,
            ],
            [
                'name' => 'Kependudukan',
                'slug' => 'kependudukan',
                'description' => 'Data demografi dan kependudukan',
                'icon' => 'fas fa-user-friends',
                'is_active' => true,
            ],
        ];

        foreach ($indicators as $indicator) {
            Indicator::create($indicator);
        }
    }
}
