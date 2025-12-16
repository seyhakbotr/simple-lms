# Lifecycle Status Implementation Guide

## Overview

This library system implements a **two-level status system** following industry standards used by major library management systems like Koha, Evergreen, SirsiDynix, and Ex Libris.

## Two-Level Status Architecture

### Level 1: Transaction Lifecycle Status
**Location:** `transactions.lifecycle_status`  
**Purpose:** Track where the transaction is in its lifecycle  
**Enum:** `App\Enums\LifecycleStatus`

```
┌─────────┐
│ ACTIVE  │ → Books currently checked out with borrower
└────┬────┘
     │
     ▼ (when all items returned/lost)
┌───────────┐
│ COMPLETED │ → All items processed, transaction closed
└─────┬─────┘
     │
     ▼ (optional)
┌──────────┐
│ ARCHIVED │ → Historical record for old transactions
└──────────┘
```

**Values:**
- `active` - Books currently with borrower
- `completed` - All items returned or marked as lost
- `cancelled` - Transaction cancelled before completion
- `archived` - Historical record (for old transactions)

### Level 2: Item Status
**Location:** `transaction_items.item_status`  
**Purpose:** Track the condition/state of each individual book  
**Existing Field:** Already implemented

**Values:**
- `borrowed` - Book is currently with borrower
- `returned` - Book returned in good condition
- `delayed` - Book returned late
- `lost` - Book not returned, marked as lost
- `damaged` - Book returned with damage

### Level 3: Item Lifecycle Status (Granular)
**Location:** `transaction_items.lifecycle_status`  
**Purpose:** Track individual item lifecycle for partial returns  
**New Field:** Added for partial return support

**Values:**
- `active` - Item still with borrower
- `returned` - Item has been returned
- `lost` - Item marked as lost

## Real-World Scenarios

### Scenario 1: Normal Checkout
**Action:** Student checks out 2 books

```
Transaction:
  lifecycle_status: 'active'
  status: null
  returned_date: null

Items:
  Book A:
    item_status: 'borrowed'
    lifecycle_status: 'active'
    returned_date: null
    
  Book B:
    item_status: 'borrowed'
    lifecycle_status: 'active'
    returned_date: null
```

**Staff View:** "Active Transaction - Books with borrower"

---

### Scenario 2: Return On Time
**Action:** Student returns both books on time

```
Transaction:
  lifecycle_status: 'completed'
  status: 'returned'
  returned_date: 2025-12-20

Items:
  Book A:
    item_status: 'returned'
    lifecycle_status: 'returned'
    returned_date: 2025-12-20
    overdue_fine: 0
    
  Book B:
    item_status: 'returned'
    lifecycle_status: 'returned'
    returned_date: 2025-12-20
    overdue_fine: 0
```

**Staff View:** "Completed Transaction - Returned on time"  
**Invoice:** Not generated (no fees)

---

### Scenario 3: Partial Return (Some Lost, Some Returned)
**Action:** Student returns Book A but lost Book B

**First Return (Book A only):**
```
Transaction:
  lifecycle_status: 'active'  ← Still active!
  status: null
  returned_date: null  ← Not fully returned yet

Items:
  Book A:
    item_status: 'returned'
    lifecycle_status: 'returned'
    returned_date: 2025-12-20
    overdue_fine: $2.50
    
  Book B:
    item_status: 'borrowed'
    lifecycle_status: 'active'  ← Still out!
    returned_date: null
```

**Later: Mark Book B as lost:**
```
Transaction:
  lifecycle_status: 'completed'  ← Now completed
  status: 'lost'  ← Shows there's a lost item
  returned_date: 2025-12-20  ← Set when last item processed

Items:
  Book A:
    item_status: 'returned'
    lifecycle_status: 'returned'
    returned_date: 2025-12-20
    overdue_fine: $2.50
    lost_fine: 0
    
  Book B:
    item_status: 'lost'
    lifecycle_status: 'lost'
    returned_date: null
    lost_fine: $45.00
```

**Staff View:** "Completed Transaction - Lost (1 returned, 1 lost)"  
**Invoice:** Generated for $47.50 ($2.50 overdue + $45.00 lost)

---

### Scenario 4: Late Return with Damage
**Action:** Student returns both books late, one is damaged

```
Transaction:
  lifecycle_status: 'completed'
  status: 'damaged'  ← Priority: damaged > delayed
  returned_date: 2025-12-23 (3 days late)

Items:
  Book A:
    item_status: 'damaged'
    lifecycle_status: 'returned'
    returned_date: 2025-12-23
    overdue_fine: $7.50 (3 days)
    damage_fine: $15.00
    damage_notes: "Water damage on pages 45-60"
    
  Book B:
    item_status: 'returned'
    lifecycle_status: 'returned'
    returned_date: 2025-12-23
    overdue_fine: $7.50 (3 days)
```

**Staff View:** "Completed Transaction - Damaged (overdue, 1 damaged)"  
**Invoice:** Generated for $30.00 ($15.00 overdue + $15.00 damage)

---

## Code Implementation

### Migration Files

1. **Transaction Lifecycle Status**
```php
// 2025_12_16_091749_add_lifecycle_status_to_transactions_table.php
Schema::table('transactions', function (Blueprint $table) {
    $table->string('lifecycle_status', 20)
        ->default('active')
        ->after('status')
        ->comment('Lifecycle state: active, completed, cancelled, archived');
    
    $table->index('lifecycle_status');
});
```

2. **Transaction Item Lifecycle Status**
```php
// 2025_12_16_092110_add_lifecycle_status_to_transaction_items_table.php
Schema::table('transaction_items', function (Blueprint $table) {
    $table->string('lifecycle_status', 20)
        ->default('active')
        ->after('item_status')
        ->comment('Lifecycle state: active, returned, lost, archived');
    
    $table->date('returned_date')
        ->nullable()
        ->after('lifecycle_status')
        ->comment('Date when this specific item was returned');
    
    $table->index('lifecycle_status');
});
```

### Enum: LifecycleStatus

```php
// app/Enums/LifecycleStatus.php
enum LifecycleStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Archived = 'archived';
    
    public function canRenew(): bool
    {
        return $this === self::Active;
    }
    
    public function canReturn(): bool
    {
        return $this === self::Active;
    }
    
    public function isCompleted(): bool
    {
        return $this === self::Completed || $this === self::Archived;
    }
}
```

### Model: Transaction

```php
// app/Models/Transaction.php

protected $fillable = [
    'user_id',
    'reference_no',
    'borrowed_date',
    'due_date',
    'returned_date',
    'renewed_count',
    'status',
    'lifecycle_status',  // NEW
];

protected $casts = [
    'status' => BorrowedStatus::class,
    'lifecycle_status' => LifecycleStatus::class,  // NEW
    'borrowed_date' => 'date',
    'due_date' => 'date',
    'returned_date' => 'date',
];

// Helper methods
public function isActive(): bool
{
    return $this->lifecycle_status === LifecycleStatus::Active;
}

public function isCompleted(): bool
{
    return $this->lifecycle_status?->isCompleted() ?? false;
}

// Query scopes
public function scopeActive($query)
{
    return $query->where('lifecycle_status', LifecycleStatus::Active);
}

public function scopeCompleted($query)
{
    return $query->where('lifecycle_status', LifecycleStatus::Completed);
}

public function scopeOverdue($query)
{
    return $query->where('lifecycle_status', LifecycleStatus::Active)
                 ->where('due_date', '<', now());
}

// Lifecycle management
public function updateLifecycleStatus(): void
{
    $counts = $this->getItemStatusCounts();
    
    // All items lost
    if ($counts['lost'] > 0 && $counts['active'] === 0 && $counts['returned'] === 0) {
        $this->lifecycle_status = LifecycleStatus::Completed;
    }
    // All items returned
    elseif ($counts['returned'] > 0 && $counts['active'] === 0 && $counts['lost'] === 0) {
        $this->lifecycle_status = LifecycleStatus::Completed;
    }
    // Mix of returned and lost (partial completion)
    elseif ($counts['active'] === 0 && ($counts['returned'] > 0 || $counts['lost'] > 0)) {
        $this->lifecycle_status = LifecycleStatus::Completed;
    }
    // Still has active items
    elseif ($counts['active'] > 0) {
        $this->lifecycle_status = LifecycleStatus::Active;
    }
    
    $this->save();
}
```

### Model: TransactionItem

```php
// app/Models/TransactionItem.php

protected $fillable = [
    'transaction_id',
    'book_id',
    'borrowed_for',
    'item_status',
    'lifecycle_status',  // NEW
    'returned_date',     // NEW (individual item return date)
    'overdue_fine',
    'lost_fine',
    'damage_fine',
    'damage_notes',
    'total_fine',
];

protected $casts = [
    'returned_date' => 'date',  // NEW
    // ... other casts
];

// Helper methods
public function isReturned(): bool
{
    return $this->lifecycle_status === 'returned';
}

public function isActive(): bool
{
    return $this->lifecycle_status === 'active';
}

public function markAsReturned(?Carbon $returnDate = null): void
{
    $this->update([
        'item_status' => 'returned',
        'lifecycle_status' => 'returned',
        'returned_date' => $returnDate ?? now(),
    ]);
    $this->updateFines();
}

public function markAsLost(): void
{
    $this->update([
        'item_status' => 'lost',
        'lifecycle_status' => 'lost',
        'lost_fine' => $this->calculateLostBookFine(),
    ]);
    $this->updateFines();
}
```

### Service: TransactionService

```php
// app/Services/TransactionService.php

public function createBorrowTransaction(array $data): Transaction
{
    return DB::transaction(function () use ($data, $user, $borrowDays) {
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'borrowed_date' => $borrowedDate,
            'due_date' => $dueDate,
            'status' => BorrowedStatus::Borrowed,
            'lifecycle_status' => LifecycleStatus::Active,  // NEW
            'renewed_count' => 0,
        ]);
        
        // ... create items
    });
}

public function returnTransaction(Transaction $transaction, array $data = []): Transaction
{
    // Validate lifecycle status
    if (!$transaction->isActive()) {
        throw ValidationException::withMessages([
            'transaction' => 'Cannot return a completed transaction.',
        ]);
    }
    
    return DB::transaction(function () use ($transaction, $returnDate, $data) {
        // Process each item
        foreach ($transaction->items as $item) {
            // Skip items already returned or lost
            if ($item->lifecycle_status !== 'active') {
                continue;
            }
            
            $lifecycleStatus = 'returned';
            
            // Handle lost items
            if (isset($data['lost_items']) && in_array($item->id, $data['lost_items'])) {
                $lifecycleStatus = 'lost';
            }
            
            // Update item
            $item->update([
                'item_status' => $itemStatus,
                'lifecycle_status' => $lifecycleStatus,
                'returned_date' => $lifecycleStatus === 'returned' ? $returnDate : null,
                // ... fees
            ]);
        }
        
        // Check if all items are processed
        $allItemsProcessed = $transaction->items->every(
            fn($item) => in_array($item->lifecycle_status, ['returned', 'lost'])
        );
        
        // Update transaction
        $transactionUpdate = ['status' => $status];
        
        if ($allItemsProcessed) {
            $transactionUpdate['returned_date'] = $returnDate;
            $transactionUpdate['lifecycle_status'] = LifecycleStatus::Completed;
        } else {
            // Partial return - transaction stays active
            $transactionUpdate['lifecycle_status'] = LifecycleStatus::Active;
        }
        
        $transaction->update($transactionUpdate);
        
        // Generate invoice only for completed transactions
        if ($transaction->isCompleted()) {
            $this->invoiceService->generateInvoiceForTransaction($transaction);
        }
    });
}

public function renewTransaction(Transaction $transaction): array
{
    // Check lifecycle first
    if (!$transaction->isActive()) {
        return [
            'success' => false,
            'message' => 'Cannot renew completed transaction',
        ];
    }
    
    // ... rest of renewal logic
}
```

## Query Examples

### Before (Single Status)
```php
// Active transactions
Transaction::where('status', 'borrowed')->get();

// Completed transactions
Transaction::whereIn('status', ['returned', 'delayed', 'lost', 'damaged'])->get();

// Overdue transactions
Transaction::where('status', 'borrowed')
    ->where('due_date', '<', now())
    ->get();
```

### After (Two-Level Status)
```php
// Active transactions
Transaction::active()->get();

// Completed transactions
Transaction::completed()->get();

// Overdue active transactions
Transaction::overdue()->get();

// Completed with lost items
Transaction::completed()
    ->where('status', 'lost')
    ->get();

// Active transactions with specific user
Transaction::active()
    ->where('user_id', $userId)
    ->get();

// Transactions ready to archive (completed > 2 years ago)
Transaction::completed()
    ->where('returned_date', '<', now()->subYears(2))
    ->update(['lifecycle_status' => LifecycleStatus::Archived]);
```

## Status Priority Rules

When a transaction has multiple item conditions, the `status` field shows the most severe:

```
Priority (highest to lowest):
1. Lost
2. Damaged  
3. Delayed
4. Returned
```

**Example:**
- 1 book returned on time
- 1 book returned damaged
- 1 book lost

**Result:** `status = 'lost'` (lost has highest priority)

## Validation Rules

### Cannot Return
```php
if (!$transaction->isActive()) {
    throw ValidationException::withMessages([
        'transaction' => 'Cannot return a completed transaction.'
    ]);
}
```

### Cannot Renew
```php
if (!$transaction->isActive()) {
    return ['success' => false, 'message' => 'Cannot renew completed transaction'];
}
```

### Invoice Generation
```php
// Only generate invoice for completed transactions
if ($transaction->isCompleted() && $transaction->total_fine > 0) {
    $this->invoiceService->generateInvoiceForTransaction($transaction);
}
```

## UI Display Recommendations

### Transaction List
```
┌──────────────────────────────────────────────┐
│ TXN-20251220-0001                            │
│ Lifecycle: [🔵 Active]                       │
│ John Doe • Due: Dec 25, 2025 (5 days left)   │
│ Items: 2 books borrowed                      │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ TXN-20251218-0042                            │
│ Lifecycle: [✅ Completed]                    │
│ Condition: [⚠️ Delayed]                      │
│ Jane Smith • Returned: Dec 18, 2025          │
│ Items: 2 returned (3 days late)              │
│ Fees: $7.50 overdue                          │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ TXN-20251215-0023                            │
│ Lifecycle: [✅ Completed]                    │
│ Condition: [🔴 Lost]                         │
│ Bob Wilson • Partial return                  │
│ Items: 1 returned, 1 lost                    │
│ Fees: $47.50 ($2.50 overdue + $45.00 lost)   │
└──────────────────────────────────────────────┘
```

### Filament Resource Columns
```php
Tables\Columns\BadgeColumn::make('lifecycle_status')
    ->label('Lifecycle')
    ->colors([
        'info' => 'active',
        'success' => 'completed',
        'gray' => 'cancelled',
        'secondary' => 'archived',
    ]),

Tables\Columns\BadgeColumn::make('status')
    ->label('Condition')
    ->visible(fn($record) => $record->isCompleted())
    ->colors([
        'success' => 'returned',
        'warning' => 'delayed',
        'danger' => 'lost',
        'primary' => 'damaged',
    ]),
```

## Benefits

### 1. Clearer Business Logic
```php
// Clear intent
if ($transaction->isActive() && !$transaction->isOverdue()) {
    // Can renew
}

// vs unclear
if ($transaction->status === 'borrowed' && ...) {
    // What if status is null?
}
```

### 2. Better Queries
```php
// Find all active overdue transactions
Transaction::overdue()->get();

// vs
Transaction::where('status', 'borrowed')
    ->where('due_date', '<', now())
    ->whereNull('returned_date')
    ->get();
```

### 3. Partial Returns
```php
// Student returns 1 of 3 books
// Transaction stays 'active' until all items processed
// Individual items track their own lifecycle_status
```

### 4. Invoice Logic
```php
// Only generate invoice when transaction is complete
if ($transaction->isCompleted()) {
    $this->invoiceService->generateInvoiceForTransaction($transaction);
}
```

### 5. Archiving
```php
// Archive old completed transactions
Transaction::completed()
    ->where('returned_date', '<', now()->subYears(2))
    ->update(['lifecycle_status' => LifecycleStatus::Archived]);
```

## Testing Checklist

- [ ] Create transaction → `lifecycle_status = 'active'`
- [ ] Return all items on time → `lifecycle_status = 'completed'`, `status = 'returned'`
- [ ] Return all items late → `lifecycle_status = 'completed'`, `status = 'delayed'`
- [ ] Mark all items as lost → `lifecycle_status = 'completed'`, `status = 'lost'`
- [ ] Partial return (1 of 2) → `lifecycle_status = 'active'`
- [ ] Complete partial return → `lifecycle_status = 'completed'`
- [ ] Mixed return (1 returned, 1 lost) → `lifecycle_status = 'completed'`, `status = 'lost'`
- [ ] Cannot renew completed transaction → validation error
- [ ] Cannot return completed transaction → validation error
- [ ] Invoice generated only for completed transactions
- [ ] Query scopes work: `active()`, `completed()`, `overdue()`

## Industry Comparison

| System | Lifecycle Status | Condition Status |
|--------|------------------|------------------|
| **Koha** | issued, returned | on_loan, returned, lost, damaged |
| **Evergreen** | CHECKED_OUT, CHECKED_IN | Available, Lost, Damaged |
| **SirsiDynix** | Active, Discharged | CHECKEDOUT, LOST, DAMAGED |
| **Ex Libris** | Active, Returned | In place, On loan, Lost |
| **This System** | active, completed, cancelled, archived | returned, delayed, lost, damaged |

✅ **Matches industry standards!**

## Summary

The two-level status system provides:

1. **Transaction Lifecycle** (`lifecycle_status`) - Where is the transaction in its lifecycle?
   - Active, Completed, Cancelled, Archived

2. **Item Condition** (`item_status`) - What happened to each book?
   - Borrowed, Returned, Delayed, Lost, Damaged

3. **Item Lifecycle** (`lifecycle_status` on items) - Granular tracking for partial returns
   - Active, Returned, Lost

This architecture supports:
- ✅ Partial returns (some books returned, some still out)
- ✅ Clear business logic (isActive, isCompleted)
- ✅ Better queries (active(), completed(), overdue())
- ✅ Proper invoice generation (only for completed transactions)
- ✅ Industry standard approach
- ✅ Future archiving capability

**Files Modified:**
- `database/migrations/2025_12_16_091749_add_lifecycle_status_to_transactions_table.php`
- `database/migrations/2025_12_16_092110_add_lifecycle_status_to_transaction_items_table.php`
- `app/Models/Transaction.php`
- `app/Models/TransactionItem.php`
- `app/Services/TransactionService.php`
- `app/Enums/LifecycleStatus.php` (already existed)

**Migrations Applied:** ✅
```bash
ddev php artisan migrate
```
