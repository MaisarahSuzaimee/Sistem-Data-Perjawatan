<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\Width;

class EditProfile extends BaseEditProfile
{

    public function getMaxContentWidth(): Width
    {
        return Width::FiveExtraLarge;
    }

    protected  function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Simpan')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Pengesahan')
            ->modalDescription('Adakah anda pasti mahu simpan perubahan ini?')
            ->action(fn() => $this->save());
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
        ->label('Batal');
    }
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        $this->getNameFormComponent()->readOnly()->columnSpanFull()->label('Nama'),
                        TextInput::make('ptj_id')
                            ->label('PTJ')
                            ->maxLength(255)
                            ->formatStateUsing(function ($record) {
                                return $record->ptj?->nama_ptj;
                            })
                            ->columnSpanFull()
                            ->dehydrated(false)
                            ->readonly(),
                        TextInput::make('nokp')
                            ->label('No Kad Pengenalan')
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->readonly(),

                        $this->getEmailFormComponent()->label('Email'),
                        TextInput::make('phone_number')
                            ->label('No Telefon')
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->readonly(),
                        TextInput::make('role')
                            ->label('Peranan')
                            ->maxLength(255)
                            ->formatStateUsing(function($state){
                                if($state === 1){
                                    return 'Superadmin';
                                } elseif ($state === 2) {
                                    return 'Admin';
                                } elseif ($state === 3) {
                                    return 'PTJ';
                                } else {
                                    return 'Pelawat';
                                }
                            })
                            ->dehydrated(false)
                            ->readonly(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent()
                    ])

            ]);
    }
}
