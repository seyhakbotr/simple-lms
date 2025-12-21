<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Revenue Breakdown -->
        <x-filament::section>
            <x-slot name="heading">
                Monthly Revenue Breakdown
            </x-slot>
            <x-slot name="description">
                Financial summary showing different types of fees collected
            </x-slot>

            {{ $this->table("revenue-breakdown") }}
        </x-filament::section>

        <!-- Invoice Summary -->
        <x-filament::section>
            <x-slot name="heading">
                Invoice Summary
            </x-slot>
            <x-slot name="description">
                All invoices with payment status and amounts
            </x-slot>

            {{ $this->table("invoice-summary") }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
