<?php

namespace App\Filament\Admin\Resources\MembershipTypeResource\Pages;

use App\Filament\Admin\Resources\MembershipTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMembershipType extends CreateRecord
{
    protected static string $resource = MembershipTypeResource::class;
}
