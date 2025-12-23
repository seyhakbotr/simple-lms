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
                if ($membershipType->membership_fee > 0) {
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
                    } else {
                        Notification::make()
                            ->warning()
                            ->title('Invoice not generated')
                            ->body('Failed to generate invoice for membership fee.')
                            ->send();
                    }
                }

                Notification::make()
                    ->success()
                    ->title('Membership assigned')
                    ->body("User {$user->name} assigned {$membershipType->name} membership.")
                    ->send();
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
