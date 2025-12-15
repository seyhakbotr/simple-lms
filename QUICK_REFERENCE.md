# Quick Reference Guide - Transaction System

## Overview
The transaction system now supports borrowing multiple books in a single transaction.

---

## Creating a Transaction

### Via Admin/Staff Panel

1. Navigate to **Transactions** → **Create**
2. Fill in the form:
   - **Borrower**: Select the user
   - **Borrowed Date**: Select date (defaults to today)
   - **Books to Borrow**: Click "Add item" for each book
     - Select book from dropdown
     - Set "borrowed for" duration (days)
   - **Status**: Will default to "Borrowed"
3. Click **Create**

**Result**: One transaction with multiple books created!

---

## Editing a Transaction

1. Navigate to **Transactions** → Click **Edit** on a transaction
2. You'll see:
   - **Transaction Details**: User, dates, status
   - **Books in Transaction**: List of all books (read-only)
     - Each shows: book title, borrowed duration, fine
   - **Return Date**: Only visible when status is "Returned" or "Delayed"
3. To mark as returned:
   - Change **Status** to "Returned" or "Delayed"
   - Set **Returned Date**
   - Fines automatically calculated and displayed
4. Click **Save**

---

## Understanding the Table

| Column | Description |
|--------|-------------|
| **Borrower** | Name of the person who borrowed books |
| **Books** | Badge showing count (e.g., "2 books") |
| **Book Titles** | List of books (click to expand if >2) |
| **Borrowed Date** | When books were borrowed |
| **Returned Date** | When books were returned (empty if not returned) |
| **Status** | Borrowed / Returned / Delayed |
| **Total Fine** | Sum of all fines if delayed |

---

## Fine Calculation

### How it Works
- Each book has its own due date: `borrowed_date + borrowed_for days`
- If returned late: `fine = days_late × $10`
- Total fine = sum of all book fines

### Example
```
Transaction borrowed on: Jan 1, 2025
- Book A: borrowed for 7 days → due Jan 8
- Book B: borrowed for 14 days → due Jan 15

Returned on: Jan 20, 2025

Fines:
- Book A: 12 days late × $10 = $120
- Book B: 5 days late × $10 = $50
- TOTAL: $170
```

---

## Code Examples

### Get Transaction with Books
```php
$transaction = Transaction::with('items.book')->find($id);

// Access books
foreach ($transaction->items as $item) {
    echo $item->book->title;
    echo $item->borrowed_for . ' days';
    echo '$' . $item->fine;
}

// Get total fine
echo '$' . $transaction->total_fine;
```

### Create Transaction Programmatically
```php
$transaction = Transaction::create([
    'user_id' => 1,
    'borrowed_date' => now(),
    'status' => BorrowedStatus::Borrowed,
]);

// Add books
$transaction->items()->create([
    'book_id' => 5,
    'borrowed_for' => 7,
]);

$transaction->items()->create([
    'book_id' => 8,
    'borrowed_for' => 14,
]);
```

### Mark as Returned
```php
$transaction->update([
    'returned_date' => now(),
    'status' => BorrowedStatus::Returned, // or Delayed
]);

// Fines automatically calculated
$totalFine = $transaction->fresh()->total_fine;
```

### Query Examples
```php
// Get all active transactions
Transaction::where('status', 'borrowed')->with('items.book')->get();

// Get delayed transactions with fines
Transaction::where('status', 'delayed')
    ->with('items.book')
    ->get()
    ->filter(fn($t) => $t->total_fine > 0);

// Get user's transaction history
Transaction::where('user_id', $userId)
    ->with('items.book')
    ->latest()
    ->get();

// Get most borrowed books
DB::table('transaction_items')
    ->select('book_id', DB::raw('count(*) as borrow_count'))
    ->groupBy('book_id')
    ->orderByDesc('borrow_count')
    ->limit(10)
    ->get();
```

---

## Database Structure

### transactions
- `id`
- `user_id` → who borrowed
- `borrowed_date` → when borrowed
- `returned_date` → when returned (nullable)
- `status` → borrowed/returned/delayed

### transaction_items
- `id`
- `transaction_id` → parent transaction
- `book_id` → which book
- `borrowed_for` → how many days
- `fine` → late fee (calculated)

---

## Relationships

```
Transaction
├─ belongsTo User
├─ hasMany TransactionItem (items)
└─ hasManyThrough Book (books)

TransactionItem
├─ belongsTo Transaction
└─ belongsTo Book
```

---

## Common Tasks

### Check if Transaction is Overdue
```php
$transaction = Transaction::find($id);

foreach ($transaction->items as $item) {
    if ($item->due_date->isPast() && !$transaction->returned_date) {
        echo "{$item->book->title} is overdue!";
    }
}
```

### Calculate Potential Fine
```php
$transaction = Transaction::find($id);

foreach ($transaction->items as $item) {
    if (!$transaction->returned_date) {
        // Calculate if returned today
        $daysLate = now()->diffInDays($item->due_date, false);
        if ($daysLate > 0) {
            $potentialFine = $daysLate * 10;
            echo "If returned today: \${$potentialFine}";
        }
    }
}
```

### Get Borrowing Statistics
```php
// Most active borrower
$topBorrower = User::withCount('transactions')
    ->orderByDesc('transactions_count')
    ->first();

// Average books per transaction
$avg = TransactionItem::count() / Transaction::count();

// Total fines collected
$totalFines = TransactionItem::sum('fine');
```

---

## Troubleshooting

### Form not showing repeater?
- Make sure you're on the **Create** page (not Edit)
- Clear cache: `php artisan optimize:clear`

### Fines not calculating?
- Ensure `returned_date` is set
- Check status is "Returned" or "Delayed"
- Verify due_date vs returned_date

### Can't add books in edit mode?
- This is by design for data integrity
- Books are locked once transaction is created
- Create a new transaction for additional books

### Total fine showing $0?
- Check if books were returned after due date
- Formula: `(return_date - due_date) × $10`
- If returned early, fine is $0 (correct behavior)

---

## Tips & Best Practices

1. **Always use the repeater** when creating transactions with multiple books
2. **Don't manually edit transaction_items** table - use the forms
3. **Check due dates** before marking as returned to ensure correct fines
4. **Use filters** in the table to find specific transactions quickly
5. **Export data** regularly for reporting and analytics

---

## Keyboard Shortcuts (Filament)

- `Ctrl/Cmd + K` → Global search
- `Ctrl/Cmd + S` → Save form
- `Ctrl/Cmd + /` → Open command palette
- `Esc` → Close modals

---

## Support

**Issues?** Check:
1. `storage/logs/laravel.log` for errors
2. `TRANSACTION_REFACTORING.md` for technical details
3. `SETUP_INSTRUCTIONS.md` for setup help

**Migration:** See `SETUP_INSTRUCTIONS.md`

**Complete Guide:** See `REFACTORING_COMPLETE.md`

---

*Last Updated: December 15, 2025*