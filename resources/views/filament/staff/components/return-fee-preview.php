<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Fee Preview</h3>

    @php
        $transactionService = app(\App\Services\TransactionService::class);
        $feePreview = $transactionService->previewReturnFees($transaction);
    @endphp

    <div class="space-y-3">
        @foreach($feePreview['items'] as $item)
            <div class="border-b border-gray-200 dark:border-gray-700 pb-3 last:border-b-0">
                <div class="flex justify-between items-start mb-2">
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        {{ $item['book_title'] }}
                    </div>
                    @if($item['item_status'])
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            @if($item['item_status'] === 'lost')
                                bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @elseif($item['item_status'] === 'damaged')
                                bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                            @endif
                        ">
                            {{ ucfirst($item['item_status']) }}
                        </span>
                    @endif
                </div>

                <div class="space-y-1 text-sm">
                    @if($item['overdue_fine'] > 0)
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Overdue Fine:</span>
                            <span class="font-medium">{{ $item['formatted_overdue'] }}</span>
                        </div>
                    @endif

                    @if($item['lost_fine'] > 0)
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Lost Book Fine:</span>
                            <span class="font-medium text-red-600 dark:text-red-400">{{ $item['formatted_lost'] }}</span>
                        </div>
                    @endif

                    @if($item['damage_fine'] > 0)
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Damage Fine:</span>
                            <span class="font-medium text-orange-600 dark:text-orange-400">{{ $item['formatted_damage'] }}</span>
                        </div>
                    @endif

                    @if($item['total_fine'] > 0)
                        <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-100 pt-1 border-t border-gray-200 dark:border-gray-700">
                            <span>Item Total:</span>
                            <span>{{ $item['formatted_total'] }}</span>
                        </div>
                    @else
                        <div class="text-gray-500 dark:text-gray-400 italic">
                            No fees for this item
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Total Summary -->
    <div class="mt-4 pt-4 border-t-2 border-gray-300 dark:border-gray-600">
        <div class="space-y-2">
            @if($feePreview['total_overdue'] > 0)
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                    <span>Total Overdue Fees:</span>
                    <span class="font-medium">{{ $feePreview['formatted_total_overdue'] }}</span>
                </div>
            @endif

            <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-gray-100">
                <span>Grand Total:</span>
                <span class="text-{{ $feePreview['total_all_fees'] > 0 ? 'danger' : 'success' }}-600">
                    {{ $feePreview['formatted_total_all'] }}
                </span>
            </div>
        </div>

        @if($feePreview['is_preview'])
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 italic">
                * This is a preview. Actual fees will be calculated when you process the return.
            </div>
        @endif

        @if($transaction->isOverdue())
            <div class="mt-3 flex items-center gap-2 text-sm text-orange-600 dark:text-orange-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>This transaction is {{ $feePreview['days_overdue'] }} day(s) overdue</span>
            </div>
        @endif
    </div>
</div>
