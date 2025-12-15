<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-banknotes class="w-5 h-5" />
                Current Fee Structure
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::link
                :href="route('filament.admin.pages.manage-fees')"
                tag="a"
                icon="heroicon-m-cog-6-tooth"
                size="sm"
            >
                Configure Fees
            </x-filament::link>
        </x-slot>

        @php
            $feeData = $this->getFeeData();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Overdue Fees --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Overdue Fees
                        </h3>
                        @if($feeData['overdue_enabled'])
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Active
                            </p>
                        @else
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                Disabled
                            </p>
                        @endif
                    </div>
                    <x-heroicon-o-clock class="w-8 h-8 text-primary-500" />
                </div>

                @if($feeData['overdue_enabled'])
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Per Day:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                {{ $feeData['overdue_fee'] }}
                            </span>
                        </div>

                        @if($feeData['grace_period'] > 0)
                            <div class="flex justify-between items-baseline">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Grace Period:</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $feeData['grace_period'] }} {{ Str::plural('day', $feeData['grace_period']) }}
                                </span>
                            </div>
                        @endif

                        @if($feeData['max_days'])
                            <div class="flex justify-between items-baseline">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Max Days:</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $feeData['max_days'] }} days
                                </span>
                            </div>
                        @endif

                        @if($feeData['max_amount'])
                            <div class="flex justify-between items-baseline">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Max Amount:</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $feeData['max_amount'] }}
                                </span>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                        Overdue fees are currently disabled
                    </p>
                @endif
            </div>

            {{-- Lost Book Fines --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Lost Book Fines
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ ucfirst($feeData['lost_book_type']) }}
                        </p>
                    </div>
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-danger-500" />
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-gray-600 dark:text-gray-400">
                            @if($feeData['lost_book_type'] === 'percentage')
                                Book Price:
                            @else
                                Fixed Fine:
                            @endif
                        </span>
                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            {{ $feeData['lost_book_rate'] }}
                        </span>
                    </div>

                    @if($feeData['lost_book_type'] === 'percentage')
                        @if($feeData['lost_book_min'])
                            <div class="flex justify-between items-baseline">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Minimum:</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $feeData['lost_book_min'] }}
                                </span>
                            </div>
                        @endif

                        @if($feeData['lost_book_max'])
                            <div class="flex justify-between items-baseline">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Maximum:</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $feeData['lost_book_max'] }}
                                </span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Payment Settings --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Payment Options
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Member settings
                        </p>
                    </div>
                    <x-heroicon-o-credit-card class="w-8 h-8 text-success-500" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Partial Payments:</span>
                        <div class="flex items-center gap-1.5">
                            @if($feeData['partial_payments'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-success-500" />
                                <span class="text-xs text-success-600 dark:text-success-400 font-medium">Allowed</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-danger-500" />
                                <span class="text-xs text-danger-600 dark:text-danger-400 font-medium">Not Allowed</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Auto-waive Small:</span>
                        <div class="flex items-center gap-1.5">
                            @if($feeData['auto_waive'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-success-500" />
                                <span class="text-xs text-success-600 dark:text-success-400 font-medium">Yes</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-400" />
                                <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">No</span>
                            @endif
                        </div>
                    </div>

                    @if($feeData['auto_waive'])
                        <div class="flex justify-between items-baseline pt-1 border-t border-gray-100 dark:border-gray-800">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Waive Below:</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $feeData['waive_threshold'] }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex items-start gap-2">
                <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 mt-0.5 flex-shrink-0" />
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    <strong class="font-semibold text-gray-900 dark:text-gray-100">Quick Tip:</strong>
                    These settings apply to all new transactions. Click "Configure Fees" to adjust fee structure,
                    grace periods, or payment options.
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
