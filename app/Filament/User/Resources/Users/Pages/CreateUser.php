<?php

namespace App\Filament\User\Resources\Users\Pages;

use App\Filament\User\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
