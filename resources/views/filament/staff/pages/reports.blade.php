<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Book Circulation Reports -->
            <div class="col-span-1">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-blue-500 rounded-lg">
                            <x-heroicon-o-book-open class="w-6 h-6 text-white" />
                        </div>
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Book Circulation</h3>
                    </div>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-4">
                        Most borrowed books, overdue statistics, and circulation trends
                    </p>
                    <x-filament::button
                        tag="a"
                        href="{{ route(filament.staff.resources.reports.book-reports) }}"
                        class="w-full"
                        color="primary"
                        size="sm"
                    >
                        View Report
                    </x-filament::button>
                </div>
            </div>

            <!-- Financial Reports -->
            <div class="col-span-1">
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900 p-6 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-green-500 rounded-lg">
                            <x-heroicon-o-banknotes class="w-6 h-6 text-white" />
                        </div>
                        <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">Financial Reports</h3>
                    </div>
                    <p class="text-sm text-green-700 dark:text-green-300 mb-4">
                        Revenue analysis, payment collections, and fee breakdowns
                    </p>
                    <x-filament::button
                        tag="a"
                        href="{{ route(filament.staff.resources.reports.financial-reports) }}"
                        class="w-full"
                        color="success"
                        size="sm"
                    >
                        View Report
                    </x-filament::button>
                </div>
            </div>

            <!-- Member Statistics -->
            <div class="col-span-1">
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900 p-6 rounded-lg border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-purple-500 rounded-lg">
                            <x-heroicon-o-users class="w-6 h-6 text-white" />
                        </div>
                        <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100">Member Statistics</h3>
                    </div>
                    <p class="text-sm text-purple-700 dark:text-purple-300 mb-4">
                        Membership trends, active users, and borrower analytics
                    </p>
                    <x-filament::button
                        tag="a"
                        href="{{ route(filament.staff.resources.reports.member-reports) }}"
                        class="w-full"
                        color="primary"
                        size="sm"
                    >
                        View Report
                    </x-filament::button>
                </div>
            </div>

            <!-- Inventory Status -->
            <div class="col-span-1">
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900 p-6 rounded-lg border border-orange-200 dark:border-orange-800">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-orange-500 rounded-lg">
                            <x-heroicon-o-archive-box class="w-6 h-6 text-white" />
                        </div>
                        <h3 class="text-lg font-semibold text-orange-900 dark:text-orange-100">Inventory Status</h3>
                    </div>
                    <p class="text-sm text-orange-700 dark:text-orange-300 mb-4">
                        Book availability, lost/damaged items, and stock levels
                    </p>
                    <x-filament::button
                        tag="a"
                        href="{{ route(filament.staff.resources.reports.inventory-reports) }}"
                        class="w-full"
                        color="warning"
                        size="sm"
                    >
                        View Report
                    </x-filament::button>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="col-span-1">
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-950 dark:to-indigo-900 p-6 rounded-lg border border-indigo-200 dark:border-indigo-800">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-indigo-500 rounded-lg">
                            <x-heroicon-o-document-text class="w-6 h-6 text-white" />
                        </div>
                        <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100">Transaction Details</h3>
                    </div>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300 mb-4">
                        Detailed transaction logs with filtering and export options
                    </p>
                    <x-filament::button
                        tag="a"
                        href="{{ route(filament.staff.resources.reports.transaction-reports) }}"
                        class="w-full"
                        color="info"
                        size="sm"
                    >
                        View Report
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
