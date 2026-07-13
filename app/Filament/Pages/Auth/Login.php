<?php

namespace App\Filament\Pages\Auth;

// use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;

class Login extends BaseLogin
{

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('nokp')
            ->label('No. KP')
            ->required()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'nokp' => $data['nokp'],
            'password' => $data['password'],
        ];
    }

    public function getView(): string
    {
        return 'filament.pages.auth.login';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function redirectTo(): string
    {
        return '/app/dashboard';
    }
}
