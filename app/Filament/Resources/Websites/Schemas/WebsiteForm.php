<?php

namespace App\Filament\Resources\Websites\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WebsiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Instansi / Portal')
                    ->required()
                    ->placeholder('Contoh: Dinas Komunikasi, Informatika dan Persandian'),

                Select::make('category')
                    ->label('Kategori Instansi / Wilayah')
                    ->options([
                        'opd' => 'OPD (Dinas / Badan)',
                        'kecamatan' => 'Kecamatan',
                        'kelurahan' => 'Kelurahan',
                        'desa' => 'Desa',
                    ])
                    ->default('opd')
                    ->required(),

                TextInput::make('parent_region')
                    ->label('Wilayah / Naungan')
                    ->placeholder('Contoh: Pemerintah Kabupaten Sinjai / Kecamatan Bulupoddo'),

                TextInput::make('domain')
                    ->label('Nama Domain')
                    ->required()
                    ->placeholder('Contoh: diskominfo.sinjaikab.go.id / tompobulu.desa.id'),

                TextInput::make('url')
                    ->label('URL Lengkap')
                    ->required()
                    ->url()
                    ->placeholder('https://diskominfo.sinjaikab.go.id'),

                TextInput::make('api_key')
                    ->label('Monitoring API Key (Token Telemetri)')
                    ->password()
                    ->revealable()
                    ->helperText('Token rahasia telemetri (opsional, untuk website berbasis CMS Desa atau yang mendukung endpoint telemetri).'),

                Toggle::make('is_active')
                    ->label('Status Pemantauan Aktif')
                    ->default(true),
            ]);
    }
}
