<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Membership Types Overview -->
        <x-filament::section>
            <x-slot name="heading">
                Membership Types Overview
            </x-slot>
            <x-slot name="description">
                Summary of all membership types and their member counts
            </x-slot>

            {{ $this->table("membership-types") }}
        </x-filament::section>

        <!-- Active Members -->
        <x-filament::section>
            <x-slot name="heading">
                Active Members
            </x-slot>
            <x-slot name="description">
                All currently active library members and their borrowing statistics
            </x-slot>

            {{ $this->table("active-members") }}
        </x-filament::section>

        <!-- Top Borrowers -->
        <x-filament::section>
            <x-slot name="heading">
                Top Borrowers
            </x-slot>
            <x-slot name="description">
                Members who have borrowed the most books
            </x-slot>

            {{ $this->table("top-borrowers") }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
