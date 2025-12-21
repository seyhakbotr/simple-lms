<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Recent Activity -->
        <x-filament::section>
            <x-slot name="heading">
                Recent Activity (Last 30 Days)
            </x-slot>
            <x-slot name="description">
                All borrowing transactions from the past month
            </x-slot>

            {{ $this->table("recent-activity") }}
        </x-filament::section>

        <!-- All Transactions -->
        <x-filament::section>
            <x-slot name="heading">
                All Transactions
            </x-slot>
            <x-slot name="description">
                Complete transaction history with filtering options
            </x-slot>

            {{ $this->table("all-transactions") }}
        </x-filament::section>

        <!-- Overdue Transactions -->
        <x-filament::section>
            <x-slot name="heading">
                Overdue Transactions
            </x-slot>
            <x-slot name="description">
                Transactions that are currently overdue
            </x-slot>

            {{ $this->table("overdue-transactions") }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
