<?php

namespace App\Filament\Staff\Resources\UserResource\Pages;

use App\Filament\Staff\Resources\UserResource;
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

    protected function getHeaderActions(): array
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

        // Find a default membership type
        $membershipType = MembershipType::where('is_active', true)
            ->where('membership_fee', 0)
            ->first();

        if (!$membershipType) {
            $membershipType = MembershipType::where('is_active', true)->first();
        }

        if ($membershipType) {
            // Assign membership to user
            $user->membership_type_id = $membershipType->id;
            $user->membership_started_at = Carbon::now();
            $user->membership_expires_at = Carbon::parse($user->membership_started_at)->addMonths($membershipType->membership_duration_months);
            $user->save();
            
            Notification::make()
                ->success()
                ->title('Membership assigned')
                ->body("User {$user->name} assigned default membership: {$membershipType->name}.")
                ->send();

            // Generate invoice if there's a fee
            if ($membershipType->membership_fee > 0) {
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
                }
            }
        } else {
            Notification::make()
                ->warning()
                ->title('No default membership assigned')
                ->body('No active membership types found in the system.')
                ->send();
        }
    }
}
