<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Update Transaction Fines Script ===\n\n";

// Get all returned transactions (where fines should be finalized)
$returnedTransactions = App\Models\Transaction::with(['items.book'])
    ->whereNotNull('returned_date')
    ->get();

echo "Found " . $returnedTransactions->count() . " returned transactions\n\n";

$updatedCount = 0;
$itemsUpdated = 0;

foreach ($returnedTransactions as $transaction) {
    echo "Processing Transaction ID: " . $transaction->id . "\n";
    echo "  Reference: " . ($transaction->reference_no ?? 'N/A') . "\n";
    echo "  Borrower: " . $transaction->user->name . "\n";
    echo "  Returned: " . $transaction->returned_date->format('Y-m-d') . "\n";
    echo "  Status: " . $transaction->status->value . "\n";

    $hadUpdates = false;

    foreach ($transaction->items as $item) {
        $beforeFine = $item->total_fine ?? 0;

        // Update fines for this item
        $item->updateFines();
        $item->refresh();

        $afterFine = $item->total_fine ?? 0;

        if ($beforeFine !== $afterFine) {
            echo "  ✓ Item #" . $item->id . " (" . $item->book->title . "): ";
            echo "$beforeFine → $afterFine cents\n";
            $hadUpdates = true;
            $itemsUpdated++;
        }
    }

    if ($hadUpdates) {
        $updatedCount++;
        echo "  Total Fine: " . $transaction->formatted_total_fine . "\n";
    } else {
        echo "  No changes needed\n";
    }

    echo "\n";
}

echo str_repeat("=", 70) . "\n";
echo "Summary:\n";
echo "  Transactions processed: " . $returnedTransactions->count() . "\n";
echo "  Transactions updated: " . $updatedCount . "\n";
echo "  Items updated: " . $itemsUpdated . "\n";
echo "\n=== Update Complete ===\n";
