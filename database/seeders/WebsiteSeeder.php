<?php

namespace Database\Seeders;

use App\Models\Website;
use Illuminate\Database\Seeder;

class WebsiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entities = [
            // --- OPD ---
            [
                'name' => 'Dinas Komunikasi, Informatika dan Persandian',
                'slug' => 'diskominfo',
                'category' => 'opd',
                'parent_region' => 'Pemerintah Kabupaten Sinjai',
                'domain' => 'diskominfo.sinjaikab.go.id',
                'url' => 'https://diskominfo.sinjaikab.go.id',
                'api_key' => 'sinjai-secret-monitoring-key-2026',
                'is_active' => true,
            ],
            [
                'name' => 'Badan Perencanaan Pembangunan Daerah',
                'slug' => 'bappeda',
                'category' => 'opd',
                'parent_region' => 'Pemerintah Kabupaten Sinjai',
                'domain' => 'bappeda.sinjaikab.go.id',
                'url' => 'https://bappeda.sinjaikab.go.id',
                'api_key' => null,
                'is_active' => true,
            ],
            [
                'name' => 'RSUD Sinjai',
                'slug' => 'rsud',
                'category' => 'opd',
                'parent_region' => 'Pemerintah Kabupaten Sinjai',
                'domain' => 'rsud.sinjaikab.go.id',
                'url' => 'https://rsud.sinjaikab.go.id',
                'api_key' => null,
                'is_active' => true,
            ],

            // --- KECAMATAN ---
            [
                'name' => 'Kecamatan Sinjai Utara',
                'slug' => 'kec-sinjai-utara',
                'category' => 'kecamatan',
                'parent_region' => 'Kabupaten Sinjai',
                'domain' => 'sinjaiutara.sinjaikab.go.id',
                'url' => 'https://sinjaiutara.sinjaikab.go.id',
                'api_key' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Kecamatan Bulupoddo',
                'slug' => 'kec-bulupoddo',
                'category' => 'kecamatan',
                'parent_region' => 'Kabupaten Sinjai',
                'domain' => 'bulupoddo.sinjaikab.go.id',
                'url' => 'https://bulupoddo.sinjaikab.go.id',
                'api_key' => null,
                'is_active' => true,
            ],

            // --- KELURAHAN ---
            [
                'name' => 'Kelurahan Bongki',
                'slug' => 'kel-bongki',
                'category' => 'kelurahan',
                'parent_region' => 'Kecamatan Sinjai Utara',
                'domain' => 'bongki.sinjaikab.go.id',
                'url' => 'https://bongki.sinjaikab.go.id',
                'api_key' => 'sinjai-secret-monitoring-key-2026',
                'is_active' => true,
            ],
            [
                'name' => 'Kelurahan Biringere',
                'slug' => 'kel-biringere',
                'category' => 'kelurahan',
                'parent_region' => 'Kecamatan Sinjai Utara',
                'domain' => 'biringere.sinjaikab.go.id',
                'url' => 'https://biringere.sinjaikab.go.id',
                'api_key' => 'sinjai-secret-monitoring-key-2026',
                'is_active' => true,
            ],

            // --- DESA ---
            [
                'name' => 'Desa Tompobulu',
                'slug' => 'desa-tompobulu',
                'category' => 'desa',
                'parent_region' => 'Kecamatan Bulupoddo',
                'domain' => 'tompobulu.desa.id',
                'url' => 'https://tompobulu.desa.id',
                'api_key' => 'sinjai-secret-monitoring-key-2026',
                'is_active' => true,
            ],
            [
                'name' => 'Desa Lamattiriattang',
                'slug' => 'desa-lamattiriattang',
                'category' => 'desa',
                'parent_region' => 'Kecamatan Bulupoddo',
                'domain' => 'lamattiriattang.desa.id',
                'url' => 'https://lamattiriattang.desa.id',
                'api_key' => 'sinjai-secret-monitoring-key-2026',
                'is_active' => true,
            ],
            [
                'name' => 'Desa Lappacinrana',
                'slug' => 'desa-lappacinrana',
                'category' => 'desa',
                'parent_region' => 'Kecamatan Bulupoddo',
                'domain' => 'lappacinrana.desa.id',
                'url' => 'https://lappacinrana.desa.id',
                'api_key' => 'sinjai-secret-monitoring-key-2026',
                'is_active' => true,
            ],
            [
                'name' => 'Desa Massaile',
                'slug' => 'desa-massaile',
                'category' => 'desa',
                'parent_region' => 'Kecamatan Tellu Limpoe',
                'domain' => 'massaile.desa.id',
                'url' => 'https://massaile.desa.id',
                'api_key' => 'sinjai-secret-monitoring-key-2026',
                'is_active' => true,
            ],
            [
                'name' => 'Desa Panaikang',
                'slug' => 'desa-panaikang',
                'category' => 'desa',
                'parent_region' => 'Kecamatan Sinjai Timur',
                'domain' => 'panaikang.desa.id',
                'url' => 'https://panaikang.desa.id',
                'api_key' => 'sinjai-secret-monitoring-key-2026',
                'is_active' => true,
            ],
        ];

        foreach ($entities as $entity) {
            Website::updateOrCreate(['domain' => $entity['domain']], $entity);
        }
    }
}
