<?php

namespace App\Filament\Admin\Resources\MembershipTypeResource\Pages;

use App\Filament\Admin\Resources\MembershipTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMembershipType extends EditRecord
{
    protected static string $resource = MembershipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
