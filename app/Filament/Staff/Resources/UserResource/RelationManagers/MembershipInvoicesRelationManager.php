<?php

namespace App\Filament\Staff\Resources\UserResource\RelationManagers;

use App\Filament\Staff\Resources\InvoiceResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembershipInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'membershipInvoices';

    protected static ?string $title = 'Membership History';

    protected static ?string $pluralModelLabel = 'Membership History';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('membershipType.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->money('usd')->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Invoice')
                    ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([]);
    }
}
