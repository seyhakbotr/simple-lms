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

        <!-- Most Borrowed Books -->
        <x-filament::section>
            <x-slot name="heading">
                Most Borrowed Books
            </x-slot>
            <x-slot name="description">
                Books that have been borrowed the most times
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ISBN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Times Borrowed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Stock</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->getMostBorrowedBooks() as $book)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ Str::limit($book->title, 50) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->isbn }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $book->author->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ $book->transaction_items_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $book->stock > 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        {{ $book->stock }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <!-- Overdue Books -->
        <x-filament::section>
            <x-slot name="heading">
                Currently Overdue Books
            </x-slot>
            <x-slot name="description">
                Books that are currently overdue and not yet returned
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transaction #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrower</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Books</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Days Overdue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Fine</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->getOverdueBooks() as $transaction)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $transaction->reference_no }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $transaction->user->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    @foreach($transaction->items->take(2) as $item)
                                        {{ $item->book->title }}@if(!$loop->last), @endif
                                    @endforeach
                                    @if($transaction->items->count() > 2)
                                        <span class="text-gray-400">(+{{ $transaction->items->count() - 2 }} more)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400">{{ $transaction->due_date->format('M d, Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        {{ $transaction->getDaysOverdue() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 dark:text-yellow-400">{{ $transaction->formatted_total_fine }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No overdue books</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <!-- Lost & Damaged Books -->
        <x-filament::section>
            <x-slot name="heading">
                Lost & Damaged Books
            </x-slot>
            <x-slot name="description">
                Books reported as lost or damaged during transactions
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ISBN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrower</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reported</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->getLostDamagedBooks() as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $item->book->title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $item->book->isbn }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $item->transaction->user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->item_status === 'lost' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                        {{ ucfirst($item->item_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($item->damage_notes ?? 'N/A', 50) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $item->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No lost or damaged books</td>
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
