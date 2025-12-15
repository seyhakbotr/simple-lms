# Transaction and Fee System Migration Guide

This guide helps you migrate from the old transaction/fee system to the new integrated system where all fee calculations go through the centralized `FeeCalculator` service.

## What Changed?

### Before (Old System)
- Fee calculation logic was duplicated in `TransactionItem` model and `FeeCalculator` service
- Inconsistent usage of fee calculation methods
- Direct access to `FeeSettings` from multiple places
- Manual fine formatting in resources

### After (New System)
- Single source of truth: `FeeCalculator` service
- Models delegate all fee calculations to `FeeCalculator`
- Consistent fee formatting across the application
- Better separation of concerns

## Migration Steps

### Step 1: Update Existing Fines (Optional)

If you have existing transactions with stored fines that need recalculation:

```php
// Run this in tinker or create a migration
php artisan tinker

// Recalculate all fines for returned transactions
use App\Models\Transaction;
use App\Services\FeeCalculator;

$feeCalculator = app(FeeCalculator::class);

Transaction::whereNotNull('returned_date')
    ->with('items')
    ->chunk(100, function ($transactions) use ($feeCalculator) {
        foreach ($transactions as $transaction) {
            $feeCalculator->updateTransactionFines($transaction);
        }
    });
```

### Step 2: Verify Fee Settings

Make sure your `FeeSettings` are properly configured:

```php
php artisan tinker

use App\Settings\FeeSettings;

$settings = app(FeeSettings::class);

// Verify settings
echo "Overdue Fee Enabled: " . ($settings->overdue_fee_enabled ? 'Yes' : 'No') . "\n";
echo "Fee Per Day: $" . $settings->overdue_fee_per_day . "\n";
echo "Grace Period: " . $settings->grace_period_days . " days\n";
echo "Currency: " . $settings->currency_symbol . "\n";
```

### Step 3: Update Custom Code

If you have custom code that calculates fees, update it to use `FeeCalculator`:

#### Old Way ❌
```php
// Don't do this anymore
$feeSettings = app(FeeSettings::class);
$daysLate = $dueDate->diffInDays($returnDate);
$fine = $daysLate * $feeSettings->overdue_fee_per_day;
```

#### New Way ✅
```php
// Do this instead
$feeCalculator = app(FeeCalculator::class);
$fine = $feeCalculator->calculateOverdueFine($item);
```

### Step 4: Update Custom Resources

If you created custom Filament resources that display fines:

#### Old Way ❌
```php
Placeholder::make('fine')
    ->content(fn($record) => '$' . number_format($record->fine / 100, 2))
```

#### New Way ✅
```php
Placeholder::make('fine')
    ->content(fn($record) => $record->formatted_fine)
```

### Step 5: Update API/Controllers

If you have API endpoints or controllers that return fine information:

#### Old Way ❌
```php
return response()->json([
    'fine' => $transaction->total_fine / 100,
    'currency' => 'USD'
]);
```

#### New Way ✅
```php
$feeCalculator = app(FeeCalculator::class);

return response()->json([
    'fine' => $transaction->total_fine, // Still in cents
    'fine_formatted' => $feeCalculator->formatFine($transaction->total_fine),
    'currency_symbol' => $feeCalculator->getFeeSummary()['currency_symbol'],
    'currency_code' => $feeCalculator->getFeeSummary()['currency_code']
]);
```

## Database Changes

**No database migration needed!** The new system uses the same database schema:

- `transactions.returned_date` - Still used to trigger fine calculation
- `transaction_items.fine` - Still stores fines (in cents)
- `transaction_items.borrowed_for` - Still used for due date calculation

## Testing After Migration

### Test 1: Verify Fine Calculation

```php
php artisan tinker

use App\Models\Transaction;
use App\Services\FeeCalculator;

// Get a returned transaction with overdue items
$transaction = Transaction::whereNotNull('returned_date')
    ->with('items')
    ->first();

$feeCalculator = app(FeeCalculator::class);

// Check total fine
echo "Total Fine: " . $transaction->formatted_total_fine . "\n";

// Check breakdown
$breakdown = $feeCalculator->getTransactionFeeBreakdown($transaction);
print_r($breakdown);
```

### Test 2: Verify Active Transaction Fine Preview

```php
// Get an active (not returned) transaction
$transaction = Transaction::whereNull('returned_date')
    ->with('items')
    ->first();

// This should show current overdue if past due date
echo "Current Overdue: " . $transaction->formatted_total_fine . "\n";
```

### Test 3: Test Return Flow

```php
// Return a transaction and verify fine calculation
$transaction = Transaction::whereNull('returned_date')->first();
$transaction->update(['returned_date' => now()]);
$transaction->refresh();

// Fines should be automatically calculated
foreach ($transaction->items as $item) {
    echo "Item: " . $item->book->title . "\n";
    echo "Fine: " . $item->formatted_fine . "\n";
}

echo "Total: " . $transaction->formatted_total_fine . "\n";
```

## Common Issues and Solutions

### Issue 1: Fines showing as $0.00 for overdue items

**Possible Causes:**
- Overdue fees are disabled in settings
- Grace period covers the overdue period
- Small amount waiver is active

**Solution:**
```php
use App\Settings\FeeSettings;

$settings = app(FeeSettings::class);

// Check if fees are enabled
if (!$settings->overdue_fee_enabled) {
    echo "Overdue fees are DISABLED\n";
    echo "Enable in: Admin > Settings > Fee Management\n";
}

// Check grace period
echo "Grace Period: " . $settings->grace_period_days . " days\n";

// Check waiver settings
if ($settings->waive_small_amounts) {
    echo "Small amounts under $" . $settings->small_amount_threshold . " are waived\n";
}
```

### Issue 2: Fines not updating when returning books

**Solution:**
Make sure the `returned_date` field is being set properly:

```php
// In your controller/action
$transaction->update([
    'returned_date' => now(),
    'status' => BorrowedStatus::Returned
]);

// Fines are automatically calculated in the model's 'saved' event
```

### Issue 3: Different fine amounts in different places

**Solution:**
This should not happen with the new system. If it does, clear cache:

```php
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Rolling Back (If Needed)

If you need to rollback to the old system for any reason:

1. The old code is still in git history
2. No database changes were made, so no rollback needed
3. Simply revert the following files:
   - `app/Models/TransactionItem.php`
   - `app/Models/Transaction.php`
   - `app/Services/FeeCalculator.php`
   - `app/Filament/Admin/Resources/TransactionResource.php`
   - `app/Filament/Staff/Resources/TransactionResource.php`

```bash
git checkout HEAD~1 app/Models/TransactionItem.php
git checkout HEAD~1 app/Models/Transaction.php
# etc.
```

## Advantages of New System

1. **Single Source of Truth**: All fee logic in one place
2. **Easier to Maintain**: Change fee logic in one place, affects entire app
3. **Consistent Formatting**: Currency symbols and formatting handled centrally
4. **Better Testing**: Service can be mocked/tested independently
5. **Cleaner Code**: Models focus on data, service handles business logic
6. **Extensible**: Easy to add new fee types

## New Features Available

With the new integrated system, you can now:

### Get Fee Breakdown

```php
$feeCalculator = app(FeeCalculator::class);
$breakdown = $feeCalculator->getTransactionFeeBreakdown($transaction);

// Show per-item breakdown
foreach ($breakdown['items'] as $item) {
    echo $item['book_title'] . ': ' . $item['formatted_fine'] . "\n";
}
echo "Total: " . $breakdown['formatted_total'] . "\n";
```

### Calculate User's Total Outstanding Fines

```php
$totalFines = $feeCalculator->calculateUserTotalFines($user);
echo "User owes: " . $feeCalculator->formatFine($totalFines) . "\n";
```

### Check if Fine Should Be Waived

```php
if ($feeCalculator->shouldWaiveFine($amount)) {
    echo "Fine is below threshold and will be waived\n";
}
```

## Support

If you encounter any issues during migration:

1. Check the [Transaction Fee Integration Guide](TRANSACTION_FEE_INTEGRATION.md)
2. Review the [Fee Management Guide](FEE_MANAGEMENT.md)
3. Run diagnostics: `php artisan about`
4. Check logs: `storage/logs/laravel.log`

## Verification Checklist

After migration, verify:

- [ ] Overdue fines calculate correctly for returned transactions
- [ ] Active transactions show current overdue preview
- [ ] Fine formatting is consistent across all pages
- [ ] Currency symbol displays correctly
- [ ] Grace period applies correctly
- [ ] Maximum fine caps work as expected
- [ ] Small amount waivers work as expected
- [ ] User total fines calculate correctly
- [ ] Transaction fee breakdown displays properly
- [ ] No PHP errors in logs

## Conclusion

The new integrated system provides a cleaner, more maintainable approach to fee management. All fee calculations now go through a single service, making the codebase easier to understand, test, and extend.

For detailed usage information, see [TRANSACTION_FEE_INTEGRATION.md](TRANSACTION_FEE_INTEGRATION.md).