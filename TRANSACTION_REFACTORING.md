# Transaction System Refactoring

## Overview

The transaction system has been refactored to use a proper one-to-many relationship structure. Instead of creating multiple transaction records when a user borrows multiple books, the system now creates **one transaction** with **multiple transaction items**.

## What Changed

### Database Structure

#### Before (Old Structure)
- **transactions** table contained book-specific fields
  - `id`
  - `user_id`
  - `book_id` ❌ (moved to transaction_items)
  - `borrowed_date`
  - `borrowed_for` ❌ (moved to transaction_items)
  - `returned_date`
  - `status`
  - `fine` ❌ (moved to transaction_items)

**Problem:** Borrowing 3 books created 3 separate transaction records with duplicate user/date/status data.

#### After (New Structure)
- **transactions** table (transaction-level data)
  - `id`
  - `user_id`
  - `borrowed_date`
  - `returned_date`
  - `status`

- **transaction_items** table (book-specific data)
  - `id`
  - `transaction_id` (foreign key)
  - `book_id`
  - `borrowed_for`
  - `fine`

**Benefit:** Borrowing 3 books creates 1 transaction record with 3 transaction_item records.

## Models

### Transaction Model
```php
// Relationships
$transaction->user          // BelongsTo User
$transaction->items         // HasMany TransactionItem
$transaction->books         // HasManyThrough Book

// Computed Attributes
$transaction->total_fine    // Sum of all item fines
```

### TransactionItem Model
```php
// Relationships
$transactionItem->transaction  // BelongsTo Transaction
$transactionItem->book        // BelongsTo Book

// Computed Attributes
$transactionItem->due_date    // Borrowed date + borrowed_for days

// Methods
$transactionItem->calculateFine()  // Returns fine amount for this item
```

## Filament Resource Changes

### Create Transaction Form
- User selects borrower, borrowed date, and status once
- **Repeater field** allows adding multiple books, each with its own `borrowed_for` duration
- Creates one `Transaction` with multiple `TransactionItem` records

### Edit Transaction Form
- Shows transaction details (user, dates, status)
- **Items repeater** displays all books in the transaction
- Each item shows: book title (read-only), borrowed_for, and calculated fine
- Total fine is calculated and displayed in the sidebar

### Transaction Table
- Shows one row per transaction (not per book)
- Displays:
  - Borrower name
  - Number of books (badge)
  - Book titles (expandable list)
  - Borrowed/returned dates
  - Status
  - Total fine (sum of all items)

## Migration Path

### Fresh Installation
Run migrations normally:
```bash
php artisan migrate
```

### Existing Database with Data
The migration `2024_01_13_055035_migrate_transactions_to_items.php` will:

1. Check if old structure exists
2. Create temporary backup of existing transactions
3. Remove old columns (`book_id`, `borrowed_for`, `fine`)
4. Group old transactions by user, date, and status
5. Create new consolidated transactions
6. Create transaction_items for each book
7. Preserve all fine calculations and timestamps

To migrate:
```bash
php artisan migrate
```

To rollback (if needed):
```bash
php artisan migrate:rollback
```

## Notifications

The `TransactionObserver` has been updated to:
- Show correct pluralization (e.g., "borrowed 3 books" vs "borrowed a book")
- Display total fine across all books when delayed
- Work seamlessly with the new structure

## Benefits

1. **Data Integrity**: No redundant data for user, dates, and status
2. **Better Queries**: Easier to find all books borrowed in a single transaction
3. **Cleaner Logic**: Transaction-level operations (like marking as returned) affect all books at once
4. **Accurate Fines**: Each book can have different borrow durations and individual fines
5. **Better UX**: Users see one transaction instead of multiple rows for the same borrow event

## Example Usage

### Creating a Transaction
```php
$transaction = Transaction::create([
    'user_id' => 1,
    'borrowed_date' => now(),
    'status' => BorrowedStatus::Borrowed,
]);

// Add books to transaction
$transaction->items()->create([
    'book_id' => 5,
    'borrowed_for' => 7,
]);

$transaction->items()->create([
    'book_id' => 8,
    'borrowed_for' => 14,
]);
```

### Returning Books and Calculating Fines
```php
$transaction->update([
    'returned_date' => now(),
    'status' => BorrowedStatus::Returned, // or Delayed
]);

// Fines are automatically calculated for each item
$totalFine = $transaction->total_fine;  // Sum of all item fines
```

### Querying Transactions
```php
// Get all transactions with their books
$transactions = Transaction::with('items.book')->get();

// Find transactions for a specific user
$userTransactions = Transaction::where('user_id', $userId)
    ->with('items.book')
    ->get();

// Get overdue transactions
$overdueTransactions = Transaction::where('status', BorrowedStatus::Delayed)
    ->with('items.book')
    ->get();
```

## Files Modified

- `database/migrations/2024_01_13_055033_create_transactions_table.php` - Updated schema
- `database/migrations/2024_01_13_055034_create_transaction_items_table.php` - New table
- `database/migrations/2024_01_13_055035_migrate_transactions_to_items.php` - Data migration
- `app/Models/Transaction.php` - Updated relationships and logic
- `app/Models/TransactionItem.php` - New model
- `app/Filament/Admin/Resources/TransactionResource.php` - Updated form and table
- `app/Filament/Admin/Resources/TransactionResource/Pages/CreateTransaction.php` - New creation logic
- `app/Observers/TransactionObserver.php` - Updated for new structure

## Testing Recommendations

After migration, verify:
1. ✅ Create new transactions with multiple books
2. ✅ Edit existing transactions
3. ✅ Return books and check fine calculations
4. ✅ View transaction list with correct book counts
5. ✅ Check notifications are sent correctly
6. ✅ Verify all books show in transaction details