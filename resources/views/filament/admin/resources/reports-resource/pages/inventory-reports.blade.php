<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Book Availability -->
        <x-filament::section>
            <x-slot name="heading">
                Book Availability Status
            </x-slot>
            <x-slot name="description">
                Complete inventory of all books with current stock levels
            </x-slot>

            {{ $this->table("book-availability") }}
        </x-filament::section>

        <!-- Low Stock Alert -->
        <x-filament::section>
            <x-slot name="heading">
                Low Stock Alert (≤5 copies)
            </x-slot>
            <x-slot name="description">
                Books that need restocking soon
            </x-slot>

            {{ $this->table("low-stock") }}
        </x-filament::section>

        <!-- Out of Stock -->
        <x-filament::section>
            <x-slot name="heading">
                Out of Stock Books
            </x-slot>
            <x-slot name="description">
                Books currently unavailable for borrowing
            </x-slot>

            {{ $this->table("out-of-stock") }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
