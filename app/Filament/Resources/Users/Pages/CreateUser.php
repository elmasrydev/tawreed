<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Admin\SaveUserAccount;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveUserAccount::class)->handle($data, auth()->user());
    }
}
