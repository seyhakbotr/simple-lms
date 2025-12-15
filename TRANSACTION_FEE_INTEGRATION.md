# Transaction and Fee Management Integration

## Overview

This document explains how the Transaction system and Fee Management system work together in the Library Management System. The integration has been refactored to ensure a single source of truth for all fee calculations through the centralized `FeeCalculator` service.

## Architecture

### Components

1. **FeeCalculator Service** (`app/Services/FeeCalculator.php`)
   - Central service for all fee calculations
   - Single source of truth for fee logic
   - Handles formatting and display of fees

2. **Transaction Model** (`app/Models/Transaction.php`)
   - Represents a borrowing transaction
   - Delegates fee calculations to FeeCalculator
   - Contains transaction-level fine aggregation

3. **TransactionItem Model** (`app/Models/TransactionItem.php`)
   - Represents individual books in a transaction
   - Delegates fee calculations to FeeCalculator
   - Stores calculated fines when transaction is returned

4. **FeeSettings** (`app/Settings/FeeSettings.php`)
   - Configuration for all fee-related settings
   - Accessed through FeeCalculator service

## How It Works

### Fee Calculation Flow

```
User Returns Books
    ↓
Transaction.returned_date is set
    ↓
Transaction model's 'saved' event fires
    ↓
Transaction::updateFines() is called
    ↓
For each TransactionItem:
    TransactionItem::updateFine()
        ↓
    FeeCalculator::calculateOverdueFine()
        ↓
    Fine is calculated and stored in TransactionItem.fine
```

### Key Principles

1. **Single Source of Truth**: All fee calculations go through `FeeCalculator` service
2. **Lazy Calculation**: Fines are only stored when a transaction is returned
3. **Current Overdue**: For active transactions, fines are calculated on-the-fly
4. **Consistent Formatting**: All fee displays use `FeeCalculator::formatFine()`

## Usage Examples

### Calculating Fine for a Transaction Item

```php
// Get a transaction item
$item = TransactionItem::find($id);

// Calculate fine (delegates to FeeCalculator)
$fine = $item->calculateFine(); // Returns integer in cents

// Get formatted fine for display
$formattedFine = $item->formatted_fine; // Returns "$X.XX"
```

### Getting Total Fine for a Transaction

```php
$transaction = Transaction::find($id);

// Get total fine (stored if returned, calculated if active)
$totalFine = $transaction->total_fine; // Integer in cents

// Get formatted total fine
$formattedTotal = $transaction->formatted_total_fine; // Returns "$X.XX"
```

### Using FeeCalculator Directly

```php
use App\Services\FeeCalculator;

$feeCalculator = app(FeeCalculator::class);

// Calculate overdue fine for an item
$fine = $feeCalculator->calculateOverdueFine($item);

// Calculate current overdue (if not yet returned)
$currentFine = $feeCalculator->calculateCurrentOverdueFine($item);

// Calculate lost book fine
$lostBookFine = $feeCalculator->calculateLostBookFine($book);

// Format a fine for display
$formatted = $feeCalculator->formatFine($amountInCents);

// Get fee summary
$summary = $feeCalculator->getFeeSummary();
```

### Getting Fee Breakdown

```php
$feeCalculator = app(FeeCalculator::class);
$breakdown = $feeCalculator->getTransactionFeeBreakdown($transaction);

// Returns:
// [
//     'items' => [
//         [
//             'book_title' => 'Book Title',
//             'fine' => 500, // cents
//             'formatted_fine' => '$5.00'
//         ],
//         // ... more items
//     ],
//     'total' => 1000, // cents
//     'formatted_total' => '$10.00',
//     'currency_symbol' => '$'
// ]
```

## Model Methods

### Transaction Model

| Method | Description | Returns |
|--------|-------------|---------|
| `getTotalFineAttribute()` | Get total fine for all items | int (cents) |
| `getFormattedTotalFineAttribute()` | Get formatted total fine | string |
| `updateFines()` | Recalculate and update all item fines | void |
| `isOverdue()` | Check if transaction is overdue | bool |
| `getDaysOverdue()` | Get number of days overdue | int |

### TransactionItem Model

| Method | Description | Returns |
|--------|-------------|---------|
| `calculateFine()` | Calculate fine for this item | int (cents) |
| `getCurrentOverdueFine()` | Get current overdue fine | int (cents) |
| `updateFine()` | Recalculate and save fine | void |
| `getFormattedFineAttribute()` | Get formatted fine | string |
| `getDueDateAttribute()` | Get due date for this item | Carbon |

## FeeCalculator Methods

### Core Calculation Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `calculateOverdueFine()` | `TransactionItem $item, ?Carbon $returnDate` | int | Calculate overdue fine for an item |
| `calculateCurrentOverdueFine()` | `TransactionItem $item` | int | Calculate current overdue (as if returned today) |
| `calculateLostBookFine()` | `Book $book` | int | Calculate lost book replacement fine |
| `calculateTransactionTotalFine()` | `Transaction $transaction` | int | Calculate total fine for a transaction |

### Utility Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `formatFine()` | `int $amountInCents` | string | Format fine with currency symbol |
| `shouldWaiveFine()` | `float $amount` | bool | Check if fine should be waived |
| `getFeeSummary()` | - | array | Get fee configuration summary |
| `getTransactionFeeBreakdown()` | `Transaction $transaction` | array | Get detailed fee breakdown |

### User-Level Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `calculateUserTotalFines()` | `User $user` | int | Calculate total outstanding fines for a user |

## Fee Settings Integration

The `FeeCalculator` automatically applies all settings from `FeeSettings`:

### Overdue Fee Settings
- `overdue_fee_enabled` - Enable/disable overdue fees
- `overdue_fee_per_day` - Daily overdue charge
- `overdue_fee_max_days` - Maximum days to charge
- `overdue_fee_max_amount` - Maximum total overdue fee
- `grace_period_days` - Grace period before fees apply

### Lost Book Fee Settings
- `lost_book_fine_type` - 'percentage' or 'fixed'
- `lost_book_fine_rate` - Rate or fixed amount
- `lost_book_minimum_fine` - Minimum fine for lost books
- `lost_book_maximum_fine` - Maximum fine for lost books

### Other Settings
- `waive_small_amounts` - Auto-waive small fines
- `small_amount_threshold` - Threshold for waiving
- `currency_symbol` - Display symbol (e.g., "$")
- `currency_code` - Currency code (e.g., "USD")

## Data Storage

### Fine Storage Strategy

- **Active Transactions**: Fines are NOT stored, calculated on-the-fly
- **Returned Transactions**: Fines are calculated and stored in `transaction_items.fine` field
- **Storage Format**: Integer in cents (e.g., $10.50 = 1050)

### Why Cents?

Storing monetary values as integers (in cents) avoids floating-point precision issues:

```php
// Bad: Floating point precision issues
$fine = 10.50; // Might be 10.499999999

// Good: Integer storage
$fine = 1050; // Always exact
$display = $fine / 100; // Convert to dollars for display
```

## Display in Filament Resources

### Form Fields

```php
use App\Services\FeeCalculator;

// Display fine for individual item
Placeholder::make('fine')
    ->label('Fine')
    ->content(fn($record) => $record ? $record->formatted_fine : 'N/A'),

// Display total fine for transaction
Placeholder::make('fine')
    ->label(function (): string {
        $feeCalculator = app(FeeCalculator::class);
        $summary = $feeCalculator->getFeeSummary();
        
        if (!$summary['overdue_enabled']) {
            return 'Overdue Fees (Disabled)';
        }
        
        return $summary['currency_symbol'] . 
               number_format($summary['overdue_per_day'], 2) . 
               ' Per Day';
    })
    ->content(fn($record) => $record ? $record->formatted_total_fine : 'N/A')
```

### Table Columns

```php
// Display total fine in table
TextColumn::make('total_fine')
    ->label('Total Fine')
    ->getStateUsing(fn($record) => $record->formatted_total_fine)
    ->placeholder('$0.00'),
```

## Best Practices

### DO ✅

1. **Always use FeeCalculator** for any fee-related calculations
2. **Use model attributes** for display (`formatted_fine`, `formatted_total_fine`)
3. **Store fines in cents** as integers
4. **Calculate on return** - update fines when `returned_date` is set
5. **Use lazy calculation** for active transactions

### DON'T ❌

1. **Don't duplicate fee logic** in models or controllers
2. **Don't access FeeSettings directly** for calculations (use FeeCalculator)
3. **Don't use floats** for monetary storage
4. **Don't calculate fines** for active transactions and store them
5. **Don't format manually** - use FeeCalculator's format methods

## Example: Adding a New Fee Type

If you need to add a new type of fee (e.g., reservation fee):

1. **Add settings** to `FeeSettings.php`:
```php
public float $reservation_fee;
public bool $reservation_fee_enabled;
```

2. **Add calculation** to `FeeCalculator.php`:
```php
public function calculateReservationFee(Reservation $reservation): int
{
    if (!$this->feeSettings->reservation_fee_enabled) {
        return 0;
    }
    
    return (int) round($this->feeSettings->reservation_fee * 100);
}
```

3. **Use in models** through FeeCalculator:
```php
public function getReservationFineAttribute(): int
{
    $feeCalculator = app(FeeCalculator::class);
    return $feeCalculator->calculateReservationFee($this);
}
```

## Testing

When testing fee calculations:

```php
use App\Services\FeeCalculator;

// Mock FeeSettings
$this->app->singleton(FeeSettings::class, function () {
    return new FeeSettings([
        'overdue_fee_enabled' => true,
        'overdue_fee_per_day' => 0.50,
        'grace_period_days' => 3,
        'currency_symbol' => '$',
    ]);
});

// Test calculation
$feeCalculator = app(FeeCalculator::class);
$fine = $feeCalculator->calculateOverdueFine($item);

$this->assertEquals(500, $fine); // $5.00
```

## Troubleshooting

### Fines Not Updating

- Check if `returned_date` is being set properly
- Verify `overdue_fee_enabled` is true in FeeSettings
- Ensure the transaction's saved event is firing

### Incorrect Fine Amounts

- Check grace period settings
- Verify max days/amount caps
- Review small amount waiver threshold

### Display Issues

- Use `formatted_fine` and `formatted_total_fine` attributes
- Don't manually format - let FeeCalculator handle it
- Check currency symbol in FeeSettings

## Related Documentation

- [Fee Management Guide](FEE_MANAGEMENT.md)
- [Transaction System](MEMBERSHIP_CIRCULATION.md)
- [Quick Reference](QUICK_REFERENCE.md)