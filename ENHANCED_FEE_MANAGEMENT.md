# Enhanced Fee Management System

## Overview

The fee management system has been significantly enhanced to support multiple fee types beyond just overdue fines. The system now handles:

- **Overdue Fees** - Daily charges for late returns
- **Lost Book Fees** - Replacement costs for lost books
- **Damage Fees** - Custom charges for damaged books
- **Real-time Fee Calculation** - Live preview of fees as you edit

## What's New

### 1. Multiple Fee Types

Each transaction item can now have multiple types of fees:

```php
TransactionItem:
- overdue_fine    // Late return fees
- lost_fine       // Lost book replacement
- damage_fine     // Damage assessment
- total_fine      // Sum of all fees
```

### 2. Enhanced Status System

New statuses have been added to `BorrowedStatus` enum:

| Status | Description | Icon | Color |
|--------|-------------|------|-------|
| `borrowed` | Currently borrowed, on time | Arrow Path | Blue |
| `returned` | Returned successfully | Check Badge | Green |
| `delayed` | Overdue but not returned | Clock | Yellow |
| `lost` | Marked as lost | Exclamation Triangle | Red |
| `damaged` | Returned damaged | Exclamation Circle | Red |

### 3. Real-Time Fine Preview

**NEW!** You can now see fine calculations in real-time as you set the return date:

```
Return Date: [2024-01-20]
💰 Estimated fine: $15.00
```

The form updates instantly when you change the return date, showing:
- Calculated overdue fees
- Days late
- Total amount owed

### 4. Fee Breakdown Display

Detailed breakdown of all fee types:

```
Fee Breakdown:
Overdue: $10.00
Lost Books: $25.00
Damage: $5.00
─────────────────
Total: $40.00
```

## Database Schema

### Migration Added

New fields in `transaction_items` table:

```sql
item_status       VARCHAR   -- borrowed, returned, lost, damaged
overdue_fine      INTEGER   -- Cents
lost_fine         INTEGER   -- Cents
damage_fine       INTEGER   -- Cents
damage_notes      TEXT      -- Optional damage description
total_fine        INTEGER   -- Sum of all fines (cents)
```

**Note:** The old `fine` field is kept for backward compatibility.

## Usage

### Calculating Overdue Fines

Overdue fines are calculated automatically based on:
- **Fee Settings** (`overdue_fee_per_day`)
- **Grace Period** (`grace_period_days`)
- **Maximum Days** (`overdue_fee_max_days`)
- **Maximum Amount** (`overdue_fee_max_amount`)
- **Small Amount Waiver** (`waive_small_amounts`)

```php
$item = TransactionItem::find(1);

// Calculate overdue fine
$overdueFine = $item->calculateOverdueFine(); // int (cents)

// Get formatted
echo $item->formatted_overdue_fine; // "$5.00"
```

### Calculating Lost Book Fines

Lost book fines use the fee settings:
- **Type**: Percentage of book price or Fixed amount
- **Rate**: `lost_book_fine_rate`
- **Min/Max**: `lost_book_minimum_fine`, `lost_book_maximum_fine`

```php
// Calculate lost book fine
$lostFine = $item->calculateLostBookFine(); // int (cents)

// Mark item as lost (auto-calculates fine)
$item->markAsLost();

echo $item->formatted_lost_fine; // "$25.00"
```

### Setting Damage Fees

Damage fees are set manually by staff:

```php
// Mark as damaged with custom fine
$item->markAsDamaged(
    damageFineAmount: 500, // $5.00 in cents
    notes: 'Water damage on cover'
);

echo $item->formatted_damage_fine; // "$5.00"
echo $item->damage_notes; // "Water damage on cover"
```

### Getting Fee Breakdown

```php
$item = TransactionItem::find(1);
$breakdown = $item->getFeeBreakdown();

// Returns:
[
    'overdue' => [
        'amount' => 1000,        // cents
        'formatted' => '$10.00'
    ],
    'lost' => [
        'amount' => 2500,
        'formatted' => '$25.00'
    ],
    'damage' => [
        'amount' => 500,
        'formatted' => '$5.00'
    ],
    'total' => [
        'amount' => 4000,
        'formatted' => '$40.00'
    ]
]
```

### Transaction-Level Fees

```php
$transaction = Transaction::find(1);

// Get total of all items' fines
echo $transaction->formatted_total_fine; // "$40.00"

// Get breakdown
$breakdown = $transaction->fee_breakdown;
/*
[
    'overdue' => ['amount' => 1000, 'formatted' => '$10.00'],
    'lost' => ['amount' => 2500, 'formatted' => '$25.00'],
    'damage' => ['amount' => 500, 'formatted' => '$5.00'],
    'total' => ['amount' => 4000, 'formatted' => '$40.00']
]
*/

// Check for specific issues
$transaction->hasLostItems();      // bool
$transaction->hasDamagedItems();   // bool
```

## Real-Time Fine Preview in Filament

### How It Works

When editing a transaction:

1. **Select Return Date** - Pick when the books were returned
2. **Instant Calculation** - Fine calculates immediately
3. **See Preview** - View estimated charges before saving
4. **Helper Text** - Shows "💰 Estimated fine: $X.XX" or "✓ No fine - returned on time"
5. **Fee Breakdown** - Detailed breakdown of all fee types

### Example Flow

```
Edit Transaction #123
─────────────────────

Status: [Delayed ▼]
Return Date: [2024-01-20] 
             💰 Estimated fine: $15.00

Fee Breakdown:
─────────────────────
Overdue: $15.00
─────────────────────
Total: $15.00
```

Change the date and watch it update live:

```
Return Date: [2024-01-18]
             ✓ No fine - returned on time

Fee Breakdown:
─────────────────────
✓ No fines
```

## Fee Settings Configuration

All fee calculations respect the settings in **Admin > Settings > Fee Management**:

### Overdue Fee Settings
- `overdue_fee_enabled` - Enable/disable overdue fees
- `overdue_fee_per_day` - Daily charge (e.g., $0.50)
- `grace_period_days` - Days before fees start (e.g., 3)
- `overdue_fee_max_days` - Maximum days to charge (optional)
- `overdue_fee_max_amount` - Maximum total overdue fee (optional)

### Lost Book Fee Settings
- `lost_book_fine_type` - "percentage" or "fixed"
- `lost_book_fine_rate` - Percentage (e.g., 100%) or fixed amount
- `lost_book_minimum_fine` - Minimum charge for lost books
- `lost_book_maximum_fine` - Maximum charge for lost books

### Other Settings
- `waive_small_amounts` - Auto-waive small fines
- `small_amount_threshold` - Threshold for waiving (e.g., $1.00)
- `currency_symbol` - Display symbol (e.g., "$")
- `currency_code` - Currency code (e.g., "USD")

## TransactionItem Methods

### Fee Calculation
```php
$item->calculateOverdueFine();      // int - Calculate overdue
$item->calculateLostBookFine();     // int - Calculate lost book fee
$item->calculateTotalFine();        // int - Sum all fees
$item->getCurrentOverdueFine();     // int - Preview current overdue
```

### Fee Display
```php
$item->formatted_fine;              // string - Total fine formatted
$item->formatted_overdue_fine;      // string - Overdue fine formatted
$item->formatted_lost_fine;         // string - Lost fine formatted
$item->formatted_damage_fine;       // string - Damage fine formatted
```

### Status Checks
```php
$item->isLost();                    // bool
$item->isDamaged();                 // bool
$item->hasFines();                  // bool
```

### Actions
```php
$item->markAsLost();                           // Set lost status & calculate
$item->markAsDamaged($amount, $notes);         // Set damaged & custom fine
$item->updateFines();                          // Recalculate all fines
```

### Fee Information
```php
$item->getFeeBreakdown();           // array - Detailed breakdown
```

## Transaction Methods

### Fee Information
```php
$transaction->total_fine;                      // int - Total in cents
$transaction->formatted_total_fine;            // string - Formatted
$transaction->fee_breakdown;                   // array - All fee types
```

### Status Checks
```php
$transaction->hasLostItems();                  // bool
$transaction->hasDamagedItems();               // bool
$transaction->isOverdue();                     // bool
```

## Workflow Examples

### Scenario 1: Simple Overdue Return

```php
// User returns book 3 days late
$transaction = Transaction::find(1);
$service = app(TransactionService::class);

// Return the transaction
$returned = $service->returnTransaction($transaction);

// Fines calculated automatically
echo $returned->formatted_total_fine; // "$1.50" (3 days × $0.50)
```

### Scenario 2: Lost Book

```php
$item = TransactionItem::find(5);

// Mark as lost
$item->markAsLost();

// Fee calculated based on book price
// If book price is $25 and lost_book_fine_rate is 100%:
echo $item->formatted_lost_fine; // "$25.00"
echo $item->item_status; // "lost"

// Transaction status updates automatically
$transaction = $item->transaction;
echo $transaction->status; // "lost"
```

### Scenario 3: Damaged Book with Overdue

```php
$item = TransactionItem::find(7);

// Book returned 5 days late AND damaged
$service->returnTransaction($item->transaction);

// Add damage fee
$item->markAsDamaged(
    damageFineAmount: 1000, // $10.00
    notes: 'Torn pages, water damage'
);

$breakdown = $item->getFeeBreakdown();
// Overdue: $2.50 (5 days late)
// Damage: $10.00
// Total: $12.50
```

### Scenario 4: Grace Period Example

```php
// Fee Settings:
// overdue_fee_per_day = $0.50
// grace_period_days = 3

// User returns 2 days late
$item = TransactionItem::find(8);
$overdueFine = $item->calculateOverdueFine();

echo $overdueFine; // 0 (within grace period)

// User returns 5 days late
$overdueFine = $item->calculateOverdueFine();
// Only charged for 2 days (5 - 3 grace period)
echo $overdueFine; // 100 ($1.00)
```

## Migration Guide

### For Existing Transactions

Run the migration to add new fields:

```bash
php artisan migrate
```

The migration will:
1. Add new fee fields to `transaction_items`
2. Copy existing `fine` values to `total_fine` and `overdue_fine`
3. Keep old `fine` field for backward compatibility

### Backward Compatibility

The system maintains backward compatibility:

```php
// Old way (still works)
$item->fine;                    // Returns total_fine value
$item->calculateFine();         // Returns overdue fine

// New way (recommended)
$item->total_fine;              // Total of all fees
$item->overdue_fine;            // Just overdue
$item->lost_fine;               // Just lost
$item->damage_fine;             // Just damage
```

## Benefits

### 1. Comprehensive Fee Tracking
- Track multiple fee types separately
- Clear breakdown of charges
- Audit trail with damage notes

### 2. Better User Experience
- **Real-time preview** - See fees before saving
- **Instant feedback** - Updates as you type
- **Clear display** - Breakdown shows all charges
- **No surprises** - Know the cost immediately

### 3. Flexible Fee Management
- Different fee types for different situations
- Custom damage assessments
- Configurable lost book rates
- Grace periods and caps

### 4. Accurate Reporting
- Separate totals for each fee type
- Easy to generate reports by fee category
- Track lost vs damaged vs overdue revenue

## Configuration Examples

### Conservative Library (Low Fees)
```php
overdue_fee_per_day = 0.25        // $0.25/day
grace_period_days = 5              // 5 day grace period
lost_book_fine_type = 'fixed'
lost_book_fine_rate = 10.00       // $10 flat fee
waive_small_amounts = true
small_amount_threshold = 1.00     // Waive under $1
```

### Standard Library
```php
overdue_fee_per_day = 0.50        // $0.50/day
grace_period_days = 3              // 3 day grace period
lost_book_fine_type = 'percentage'
lost_book_fine_rate = 100         // 100% of book price
lost_book_minimum_fine = 5.00     // At least $5
lost_book_maximum_fine = 100.00   // Max $100
```

### Strict Library (High Fees)
```php
overdue_fee_per_day = 1.00        // $1.00/day
grace_period_days = 0              // No grace period
overdue_fee_max_days = 30         // Cap at 30 days
overdue_fee_max_amount = 50.00    // Max $50 overdue
lost_book_fine_type = 'percentage'
lost_book_fine_rate = 150         // 150% of book price
```

## Troubleshooting

### Fines Not Calculating
1. Check if `overdue_fee_enabled` is true in Fee Settings
2. Verify return date is after due date
3. Check if grace period covers the delay
4. Ensure FeeCalculator service is being used

### Lost Book Fine is $0
1. Verify book has a price set
2. Check `lost_book_fine_type` and `lost_book_fine_rate`
3. If using percentage, ensure book price exists
4. Check min/max fine settings

### Real-Time Preview Not Updating
1. Ensure form field has `->live()` attribute
2. Check that `returned_date` is set
3. Verify record is loaded in form
4. Clear browser cache

### Damage Fee Not Saving
1. Use `markAsDamaged()` method or manually set fields
2. Call `updateFines()` after setting damage_fine
3. Ensure `damage_fine` is in cents, not dollars

## Related Documentation

- [Transaction Service Guide](TRANSACTION_SERVICE_GUIDE.md)
- [Transaction & Fee Integration](TRANSACTION_FEE_INTEGRATION.md)
- [Fee Management Guide](FEE_MANAGEMENT.md)
- [Membership Type Integration](MEMBERSHIP_TYPE_INTEGRATION.md)

## Summary

✅ **Multiple fee types** - Overdue, Lost, Damage
✅ **Real-time calculation** - See fees instantly
✅ **Detailed breakdown** - Know exactly what's charged
✅ **Flexible configuration** - Customize for your library
✅ **Backward compatible** - Existing data works seamlessly
✅ **Better UX** - No more save-and-refresh to see fees

The enhanced fee management system provides complete visibility into all charges, making it easier for staff to manage fees and for users to understand their charges.