<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Services\InvoiceService;
use App\Models\MembershipType;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getActions(): array
    {
        return [
            Action::make('reset')
                ->outlined()
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->fillForm()),
        ];
    }

    protected function afterCreate(): void
    {
        $invoiceService = resolve(InvoiceService::class);
        $user = $this->record;
        $data = $this->form->getState();

        if (isset($data['membership_type_id']) && $data['membership_type_id']) {
            $membershipType = MembershipType::find($data['membership_type_id']);

            if ($membershipType) {
                // Generate invoice for membership
                $invoice = $invoiceService->generateInvoiceForMembership(
                    $user,
                    $membershipType
                );

                if ($invoice) {
                    Notification::make()
                        ->success()
                        ->title('Membership invoice generated')
                        ->body("Invoice {$invoice->invoice_number} generated for membership fee.")
                        ->send();

                    // Only assign membership dates if an invoice was successfully generated (and thus "paid" conceptually)
                    $user->membership_type_id = $membershipType->id;
                    $user->membership_started_at = $data['membership_started_at'] ?? Carbon::now();
                    $user->membership_expires_at = Carbon::parse($user->membership_started_at)->addMonths($membershipType->membership_duration_months);
                    $user->save();

                    Notification::make()
                        ->success()
                        ->title('Membership assigned')
                        ->body("User {$user->name} assigned {$membershipType->name} membership.")
                        ->send();
                } else {
                    Notification::make()
                        ->warning()
                        ->title('Membership not assigned')
                        ->body('No invoice generated for membership fee (possibly 0 amount). Membership dates not set.')
                        ->send();
                }
            } else {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('Selected membership type not found.')
                    ->send();
            }
        }
    }
}
