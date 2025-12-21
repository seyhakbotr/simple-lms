<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Most Borrowed Books -->
        <x-filament::section>
            <x-slot name="heading">
                Most Borrowed Books
            </x-slot>
            <x-slot name="description">
                Books that have been borrowed the most times
            </x-slot>

            {{ $this->table("most-borrowed") }}
        </x-filament::section>

        <!-- Overdue Books -->
        <x-filament::section>
            <x-slot name="heading">
                Currently Overdue Books
            </x-slot>
            <x-slot name="description">
                Books that are currently overdue and not yet returned
            </x-slot>

            {{ $this->table("overdue-books") }}
        </x-filament::section>

        <!-- Lost & Damaged Books -->
        <x-filament::section>
            <x-slot name="heading">
                Lost & Damaged Books
            </x-slot>
            <x-slot name="description">
                Books reported as lost or damaged during transactions
            </x-slot>

            {{ $this->table("lost-damaged") }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
