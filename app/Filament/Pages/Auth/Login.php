<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return 'Masuk ke PANTAU WEB';
    }

    public function getSubheading(): ?string
    {
        return 'Pemerintah Kabupaten Sinjai';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'username' => $data['login'],
            'password' => $data['password'],
        ];
    }
}
