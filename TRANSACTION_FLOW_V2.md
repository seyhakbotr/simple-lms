# Transaction Flow V2 - Complete Rewrite

## Overview

The transaction flow has been completely rewritten to provide a cleaner, more intuitive experience for library staff. The new flow separates operations into dedicated pages and uses a simplified service layer.

## Key Changes

### 1. **Separated Operations**
- **Create Transaction** - Dedicated page for borrowing books
- **Return Transaction** - Dedicated page for processing returns with fee calculation
- **View Transaction** - Read-only view of transaction details
- **Renew Transaction** - Available as an action on view page

### 2. **Simplified Service Layer**
The `TransactionService` has been rewritten with focused methods:
- `createBorrowTransaction()` - Create new borrow transactions
- `returnTransaction()` - Process returns with fee calculation
- `renewTransaction()` - Renew active transactions
- `previewReturnFees()` - Preview fees before processing return
- `getUserBorrowingSummary()` - Get user's borrowing status

### 3. **Better Fee Management**
- Real-time fee preview when returning books
- Separate tracking for overdue, lost, and damage fees
- Automatic status determination based on return conditions

---

## Transaction Operations

### Creating a Transaction (Borrowing Books)

**Location:** Transactions → Create

**Process:**
1. Select borrower (shows membership info and borrowing capacity)
2. Set borrow duration (defaults to membership type's max days)
3. Select one or more books to borrow
4. System validates:
   - User has active membership
   - User hasn't exceeded borrowing limit
   - Books are available
   - Borrow duration is within allowed limits

**Data Structure:**
```php
[
    'user_id' => int,
    'borrowed_date' => Carbon|string,
    'borrow_days' => int,
    'books' => [book_id, book_id, ...] // Array of book IDs
]
```

**Backend Flow:**
```php
$transactionService = app(TransactionService::class);
$transaction = $transactionService->createBorrowTransaction($data);
```

**What Happens:**
- Validates membership status
- Checks borrowing capacity
- Validates book availability
- Creates transaction with status "Borrowed"
- Creates transaction items for each book
- Decreases book stock
- Calculates due date based on borrow duration

---

### Returning a Transaction

**Location:** Transactions → [Transaction] → Return Books

**Process:**
1. Shows transaction summary (borrower, books, due date)
2. Set return date
3. For each book:
   - Mark as lost (if applicable)
   - Mark as damaged (if applicable)
   - Set damage fine amount and notes
4. Real-time fee preview shows:
   - Overdue fees (if late)
   - Lost book fees (replacement cost)
   - Damage fees (manual entry)
   - Total fees
5. Confirm and process return

**Data Structure:**
```php
[
    'returned_date' => Carbon|string,
    'lost_items' => [item_id, item_id, ...],
    'damaged_items' => [
        item_id => [
            'fine' => int (in cents),
            'notes' => string
        ]
    ]
]
```

**Backend Flow:**
```php
$transactionService = app(TransactionService::class);
$transaction = $transactionService->returnTransaction($record, $returnData);
```

**What Happens:**
- Calculates overdue fees for each item
- Applies lost book fees if marked as lost
- Applies damage fees if marked as damaged
- Updates book stock (except for lost items)
- Determines final status:
  - "Returned" - On time, no issues
  - "Delayed" - Returned late
  - "Lost" - One or more books lost
  - "Damaged" - One or more books damaged
- Stores all fee information in database

---

### Viewing a Transaction

**Location:** Transactions → [Transaction] → View Details

**Features:**
- Complete transaction information
- Borrower details with membership type
- List of borrowed books with due dates
- Current overdue status
- Fee information (if any)
- Actions available:
  - Return Books (if not returned)
  - Renew Transaction (if eligible)
  - Delete (if not returned)

---

### Renewing a Transaction

**Location:** Transactions → [Transaction] → View → Renew

**Validation:**
- Transaction must not be returned
- Transaction must not be overdue
- User's membership must be active
- Renewal count must be below membership type limit

**What Happens:**
- Extends due date by membership type's max borrow days
- Increments renewal count
- Maintains same transaction and items

**Backend Flow:**
```php
$transactionService = app(TransactionService::class);
$result = $transactionService->renewTransaction($transaction);

// Returns:
[
    'success' => bool,
    'message' => string,
    'transaction' => Transaction|null,
    'new_due_date' => Carbon,
    'renewed_count' => int,
    'days_added' => int
]
```

---

## Fee Calculation

### Fee Types

1. **Overdue Fees**
   - Calculated based on days late
   - Respects grace period settings
   - Capped by maximum amount and maximum days
   - Can be waived for small amounts

2. **Lost Book Fees**
   - Calculated from book price (percentage or fixed)
   - Minimum and maximum fee limits apply
   - Book is not returned to stock

3. **Damage Fees**
   - Manually entered by staff
   - Includes damage notes
   - Book is returned to stock

### Fee Preview

When processing a return, staff sees a real-time preview:

```
Fee Preview
────────────────────────────────
Book: "The Great Gatsby"
  Overdue Fine: $2.00
  Total: $2.00

Book: "1984"
  Overdue Fine: $3.50
  Lost Book Fine: $15.00
  Total: $18.50
────────────────────────────────
Total Overdue Fees: $5.50
Grand Total: $20.50
```

### Fee Storage

Fees are stored in the `transaction_items` table:
- `overdue_fine` - Calculated late return fee
- `lost_fine` - Replacement cost for lost books
- `damage_fine` - Manually entered damage fee
- `total_fine` - Sum of all fees
- `fine` - Legacy field (equals total_fine)

---

## Database Schema

### Transactions Table
```sql
- id
- user_id (FK to users)
- reference_no (unique, auto-generated)
- borrowed_date
- due_date
- returned_date (nullable)
- status (enum: borrowed, returned, delayed, lost, damaged)
- renewed_count (default: 0)
- timestamps
```

### Transaction Items Table
```sql
- id
- transaction_id (FK to transactions)
- book_id (FK to books)
- borrowed_for (days)
- item_status (nullable: lost, damaged)
- overdue_fine (cents, default: 0)
- lost_fine (cents, default: 0)
- damage_fine (cents, default: 0)
- damage_notes (nullable)
- total_fine (cents, default: 0)
- fine (cents, legacy)
- timestamps
```

---

## Membership Type Integration

### Max Books Allowed
- Enforced when creating transactions
- Real-time validation shows current vs maximum
- Cannot exceed limit

### Max Borrow Days
- Default duration when borrowing
- Maximum allowed duration
- Used for renewal period

### Renewal Limit
- Controls how many times a transaction can be renewed
- Checked before allowing renewal

### Membership Expiry
- Users with expired memberships cannot borrow
- Shows expiry date in validation messages

---

## Transaction Status Flow

```
         ┌─────────────┐
         │  BORROWED   │ ← Created
         └──────┬──────┘
                │
    ┌───────────┼───────────┐
    │           │           │
    ▼           ▼           ▼
┌────────┐  ┌────────┐  ┌────────┐
│  LOST  │  │DAMAGED │  │RETURNED│
└────────┘  └────────┘  └───┬────┘
                            │
                         On Time?
                            │
                    ┌───────┴────────┐
                    │                │
                   Yes               No
                    │                │
                    ▼                ▼
              ┌──────────┐    ┌─────────┐
              │ RETURNED │    │ DELAYED │
              └──────────┘    └─────────┘
```

---

## User Borrowing Summary

Get a complete summary of a user's borrowing status:

```php
$transactionService = app(TransactionService::class);
$summary = $transactionService->getUserBorrowingSummary($user);

// Returns:
[
    'user_id' => int,
    'user_name' => string,
    'membership_type' => string,
    'membership_active' => bool,
    'membership_expires' => string,
    'total_books_borrowed' => int,
    'max_books_allowed' => int,
    'available_slots' => int,
    'can_borrow_more' => bool,
    'active_transactions_count' => int,
    'has_overdue' => bool,
    'overdue_count' => int
]
```

---

## Validation Rules

### Borrow Transaction
- User must have active membership
- User cannot exceed max books allowed
- Borrow days must be 1-{max_borrow_days}
- All books must be available (stock > 0)
- Books must be unique in the same transaction

### Return Transaction
- Transaction must not already be returned
- Return date cannot be in the future
- Damage fine must be non-negative
- Lost and damaged are mutually exclusive per item

### Renew Transaction
- Transaction must not be returned
- Transaction must not be overdue
- User's membership must be active
- Renewal count must be < renewal_limit

---

## API Examples

### Create a Borrow Transaction
```php
use App\Services\TransactionService;

$transactionService = app(TransactionService::class);

$transaction = $transactionService->createBorrowTransaction([
    'user_id' => 1,
    'borrowed_date' => now(),
    'borrow_days' => 14,
    'books' => [1, 2, 3] // Book IDs
]);
```

### Process a Return
```php
use App\Services\TransactionService;

$transactionService = app(TransactionService::class);

$transaction = $transactionService->returnTransaction($transaction, [
    'returned_date' => now(),
    'lost_items' => [1], // Transaction item ID
    'damaged_items' => [
        2 => [ // Transaction item ID
            'fine' => 500, // $5.00 in cents
            'notes' => 'Water damage on cover'
        ]
    ]
]);
```

### Renew a Transaction
```php
use App\Services\TransactionService;

$transactionService = app(TransactionService::class);

$result = $transactionService->renewTransaction($transaction);

if ($result['success']) {
    // Renewal successful
    echo "New due date: " . $result['new_due_date']->format('M d, Y');
} else {
    // Renewal failed
    echo "Error: " . $result['message'];
    // $result['reasons'] contains array of reasons
}
```

### Preview Return Fees
```php
use App\Services\TransactionService;

$transactionService = app(TransactionService::class);

$preview = $transactionService->previewReturnFees($transaction);

// Shows what fees would be if returned today
echo "Total fees: " . $preview['formatted_total_all'];
echo "Days overdue: " . $preview['days_overdue'];

foreach ($preview['items'] as $item) {
    echo "{$item['book_title']}: {$item['formatted_total']}";
}
```

---

## Testing Checklist

### Create Transaction
- [ ] Can select borrower
- [ ] Shows membership info correctly
- [ ] Enforces borrowing limit
- [ ] Prevents borrowing with expired membership
- [ ] Can select multiple books
- [ ] Validates book availability
- [ ] Validates borrow duration
- [ ] Creates transaction successfully
- [ ] Decreases book stock
- [ ] Generates unique reference number

### Return Transaction
- [ ] Shows transaction summary
- [ ] Can set return date
- [ ] Can mark books as lost
- [ ] Can mark books as damaged
- [ ] Damage fine field appears when marked damaged
- [ ] Fee preview updates in real-time
- [ ] Calculates overdue fees correctly
- [ ] Applies lost book fees
- [ ] Applies damage fees
- [ ] Determines correct final status
- [ ] Returns books to stock (except lost)
- [ ] Cannot return already-returned transaction

### View Transaction
- [ ] Shows all transaction details
- [ ] Shows borrower information
- [ ] Shows borrowed books
- [ ] Shows fee information
- [ ] Return button visible for active transactions
- [ ] Renew button visible for eligible transactions
- [ ] Delete button disabled for returned transactions

### Renew Transaction
- [ ] Extends due date correctly
- [ ] Increments renewal count
- [ ] Prevents renewal if overdue
- [ ] Prevents renewal if limit reached
- [ ] Prevents renewal if membership expired
- [ ] Shows success message with new due date

---

## Migration from Old Flow

If you have existing transactions:

1. All existing data remains compatible
2. Old `fine` field is maintained for backward compatibility
3. New fee fields (`overdue_fine`, `lost_fine`, `damage_fine`) are populated on return
4. Existing returned transactions will show fees from `fine` field
5. New returns will use the detailed fee breakdown

---

## Configuration

Fee settings are managed in Settings → Fee Management:

- Overdue fee per day
- Grace period (days)
- Maximum overdue amount
- Maximum overdue days
- Lost book fine calculation (percentage or fixed)
- Minimum/maximum lost book fees
- Small amount waiver threshold
- Currency symbol and code

---

## Tips for Staff

1. **Creating Transactions:**
   - Select borrower first to see their borrowing capacity
   - Default borrow duration is based on membership type
   - Can borrow multiple books at once

2. **Returning Transactions:**
   - Check the fee preview before processing
   - Mark books as lost only if not returned
   - Add damage notes for record keeping
   - Return date defaults to today

3. **Renewing Transactions:**
   - Can only renew if not overdue
   - Check renewal count vs limit
   - Extends by membership type's max days

4. **Viewing History:**
   - Use filters to find overdue transactions
   - Status badge shows current state
   - Total fees displayed in list view

---

## Technical Details

### Service Layer Architecture
```
TransactionService
├── Create Operations
│   ├── createBorrowTransaction()
│   ├── validateMembership()
│   ├── validateBorrowingCapacity()
│   ├── validateBorrowDuration()
│   └── validateBookAvailability()
├── Return Operations
│   ├── returnTransaction()
│   ├── determineReturnStatus()
│   ├── previewReturnFees()
│   └── getActualFees()
└── Renewal Operations
    ├── renewTransaction()
    └── validateRenewal()
```

### Fee Calculator Integration
```
FeeCalculator
├── calculateOverdueFine()
├── calculateLostBookFine()
├── calculateCurrentOverdueFine()
└── formatFine()
```

---

## Troubleshooting

**Cannot create transaction:**
- Check user has active membership
- Verify user hasn't exceeded borrowing limit
- Ensure books are available
- Check borrow duration is within limits

**Cannot return transaction:**
- Verify transaction is not already returned
- Check return date is valid
- Ensure damage fine is non-negative

**Cannot renew transaction:**
- Check transaction is not overdue
- Verify renewal limit not reached
- Ensure membership is active

**Fees not calculating correctly:**
- Review fee settings
- Check grace period configuration
- Verify book prices for lost book fees
- Check date calculations

---

## Future Enhancements

Potential improvements:
- Partial returns (return some books, not all)
- Fee payment tracking
- Email notifications for due dates
- Automatic overdue status updates
- Bulk return processing
- Advanced reporting
- Fee waiver/adjustment system

---

## Support

For issues or questions:
1. Check this documentation
2. Review TRANSACTION_SERVICE_GUIDE.md
3. Check FEE_MANAGEMENT.md for fee configuration
4. Review MEMBERSHIP_TYPE_INTEGRATION.md for membership features

---

**Last Updated:** January 2025
**Version:** 2.0
**Status:** Production Ready