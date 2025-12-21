<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Date Filters -->
        <x-filament::section class="no-print">
            <x-slot name="heading">
                Filter by Date
            </x-slot>
            <x-slot name="description">
                Select start and end dates to filter the reports
            </x-slot>

            {{ $this->form }}
        </x-filament::section>
        <!-- Book Availability -->
        <x-filament::section>
            <x-slot name="heading">
                Book Availability Status
            </x-slot>
            <x-slot name="description">
                Complete inventory of all books with current stock levels
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ISBN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Genre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Available</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Added</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->getBookAvailability() as $book)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ Str::limit($book->title, 50) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->isbn }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->author->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->genre->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $stockColor = $book->stock == 0 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($book->stock <= 5 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200');
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stockColor }}">
                                        {{ $book->stock }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $book->available ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        {{ $book->available ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No books found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <!-- Low Stock Alert -->
        <x-filament::section>
            <x-slot name="heading">
                Low Stock Alert (≤5 copies)
            </x-slot>
            <x-slot name="description">
                Books that need restocking soon
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ISBN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Genre</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->getLowStock() as $book)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ Str::limit($book->title, 50) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->isbn }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->author->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        {{ $book->stock }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->genre->name ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No low stock books</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <!-- Out of Stock -->
        <x-filament::section>
            <x-slot name="heading">
                Out of Stock Books
            </x-slot>
            <x-slot name="description">
                Books currently unavailable for borrowing
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ISBN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Genre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Publisher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->getOutOfStock() as $book)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ Str::limit($book->title, 50) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->isbn }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->author->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->genre->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->publisher->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->updated_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No out of stock books</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    <!-- Print Styles -->
    <style media="print">
        /* Hide only the filter section */
        .no-print {
            display: none !important;
        }

        /* Basic body styling */
        body {
            font-family: Arial, sans-serif !important;
            font-size: 12px !important;
            line-height: 1.4 !important;
            margin: 0 !important;
            padding: 10px !important;
        }

        /* Simple table styling */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 20px !important;
            font-size: 11px !important;
        }

        th, td {
            border: 1px solid #000 !important;
            padding: 6px !important;
            text-align: left !important;
        }

        th {
            background-color: #f0f0f0 !important;
            font-weight: bold !important;
        }

        /* Page margins */
        @page {
            margin: 0.5in;
        }
    </style>

    <!-- Print Script -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('print-report', () => {
                window.print();
            });
        });
    </script>
</x-filament-panels::page>
