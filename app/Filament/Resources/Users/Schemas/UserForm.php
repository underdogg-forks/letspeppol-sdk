<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(4)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Personal Information')
                            ->columnSpan(2)
                            ->columns(1)
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                                TextInput::make('email')
                                    ->label('Email address')
                                    ->email()
                                    ->required(),
                            ]),
                        Fieldset::make('Account')
                            ->columnSpan(2)
                            ->schema([
                                DateTimePicker::make('email_verified_at'),
                                TextInput::make('password')
                                    ->password()
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
