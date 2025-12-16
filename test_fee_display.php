<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$transaction = App\Models\Transaction::with('items')->find(15);

if ($transaction) {
    echo "Transaction: {$transaction->reference_no}\n";
    echo "Status: {$transaction->status->value}\n";
    echo "Total Fine: {$transaction->formatted_total_fine}\n";
    echo "Fee Breakdown:\n";
    $breakdown = $transaction->fee_breakdown;
    echo "  - Overdue: {$breakdown['overdue']['formatted']}\n";
    echo "  - Lost: {$breakdown['lost']['formatted']}\n";
    echo "  - Damage: {$breakdown['damage']['formatted']}\n";
    echo "  - Total: {$breakdown['total']['formatted']}\n";
    
    echo "\nItems:\n";
    foreach ($transaction->items as $item) {
        echo "  - {$item->book->title}: Status={$item->item_status}, Lost Fine={$item->formatted_lost_fine}\n";
    }
}
