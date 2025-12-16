# Two-Level Status System for Library Transactions

## Overview

You've identified an important architectural pattern used in professional library management systems! Most library systems actually use **TWO separate status dimensions**:

1. **Transaction Lifecycle Status** - The state of the transaction itself
2. **Item Condition Status** - The state of individual books

---

## The Two Status Dimensions

### Dimension 1: Transaction Lifecycle Status (Transaction Table)

Tracks **where the transaction is in its lifecycle**:

```
┌──────────┐
│  ACTIVE  │ → Books are checked out, still with borrower
└────┬─────┘
     │
     ▼
┌──────────┐
│COMPLETED │ → Books returned, transaction closed
└────┬─────┘
     │
     ▼ (optional)
┌──────────┐
│ ARCHIVED │ → Old transaction, moved to archive
└──────────┘

Alternative states:
┌──────────┐
│CANCELLED │ → Transaction cancelled before return
└──────────┘
```

**Purpose:** Transaction management, workflow control

### Dimension 2: Item Condition Status (Transaction Items Table)

Tracks **the condition/state of each book**:

```
borrowed → returned (good)
borrowed → returned (late) = delayed
borrowed → lost
borrowed → damaged
```

**Purpose:** Fee calculation, inventory management

---

## Industry Standard: What Most Library Systems Do

### Major Library Systems Analysis

#### 1. **Koha (Open Source)**
```
Transaction (Checkout):
- Status: issued, returned
- Renewal count, dates

Issues (Items):
- Status: on_loan, returned, lost, damaged, withdrawn
```

#### 2. **Evergreen (Open Source)**
```
Circulation:
- Status: CHECKED_OUT, CHECKED_IN, RENEWED
- xact_finish (completion timestamp)

Copy Status:
- Available, Checked out, Lost, Damaged, Missing
```

#### 3. **SirsiDynix Symphony (Commercial)**
```
Charge Record:
- Active, Discharged (returned), Renewed

Item Status:
- CHECKEDOUT, LOST, DAMAGED, CLAIMS_RETURNED
```

#### 4. **Ex Libris Alma (Commercial)**
```
Loan:
- Active, Returned, Renewed

Item Status:
- In place, On loan, Lost, Missing
```

### Common Pattern Found:

✅ **Transaction Level:** Active/Completed (lifecycle)  
✅ **Item Level:** Borrowed/Returned/Lost/Damaged (condition)  
✅ **Both levels tracked separately**

---

## Recommended Implementation for Your System

### Current Implementation (Single Status)

**Problem:**
- `transactions.status` mixes lifecycle AND condition
- `borrowed` = active
- `returned`, `delayed`, `lost`, `damaged` = completed
- Ambiguous: Is a "Lost" transaction active or completed?

### Proposed Implementation (Two-Level Status)

#### Database Schema Changes

**1. Update `transactions` table:**

```sql
ALTER TABLE transactions 
  ADD COLUMN lifecycle_status VARCHAR(20) DEFAULT 'active' AFTER status,
  ADD INDEX idx_lifecycle_status (lifecycle_status);

-- Possible values:
-- 'active'     - Books currently borrowed
-- 'completed'  - Books returned, transaction closed
-- 'cancelled'  - Transaction cancelled
-- 'archived'   - Old completed transaction
```

**2. Update `transaction_items` table:**

```sql
-- Already has item_status field ✅
-- Values: 'borrowed', 'returned', 'lost', 'damaged'
-- This is correct!
```

#### Migration Strategy

```php
// Migration: add lifecycle_status field
Schema::table('transactions', function (Blueprint $table) {
    $table->string('lifecycle_status')->default('active')->after('status');
    $table->index('lifecycle_status');
});

// Populate lifecycle_status based on current status
DB::statement("
    UPDATE transactions 
    SET lifecycle_status = CASE
        WHEN status = 'borrowed' THEN 'active'
        WHEN status IN ('returned', 'delayed', 'lost', 'damaged') THEN 'completed'
        ELSE 'active'
    END
");

// Keep old status field for backward compatibility initially
// Later rename it to condition_status or remove it
```

---

## Status Flow Examples

### Example 1: Normal Checkout & Return

```
┌─────────────────────────────────────────────────────┐
│ Transaction: TXN-001                                │
│ Lifecycle: active → completed                       │
│                                                     │
│ Items:                                              │
│ - Book A: borrowed → returned                       │
│ - Book B: borrowed → returned                       │
└─────────────────────────────────────────────────────┘

Day 1: Checkout
  Transaction.lifecycle_status = 'active'
  Item A.item_status = 'borrowed'
  Item B.item_status = 'borrowed'

Day 10: Return (on time)
  Transaction.lifecycle_status = 'completed'
  Transaction.status = 'returned' (condition summary)
  Item A.item_status = 'returned'
  Item B.item_status = 'returned'
```

### Example 2: Late Return

```
Day 1: Checkout
  Transaction.lifecycle_status = 'active'
  Item.item_status = 'borrowed'

Day 20: Return (10 days late)
  Transaction.lifecycle_status = 'completed'
  Transaction.status = 'delayed' (condition summary)
  Item.item_status = 'returned'
  Overdue fee calculated
```

### Example 3: Lost Book

```
Day 1: Checkout
  Transaction.lifecycle_status = 'active'
  Item A.item_status = 'borrowed'
  Item B.item_status = 'borrowed'

Day 60: Mark Book A as lost
  Transaction.lifecycle_status = 'completed'
  Transaction.status = 'lost' (worst condition)
  Item A.item_status = 'lost'
  Item B.item_status = 'returned'
  Lost fee calculated
```

### Example 4: Mixed Conditions

```
Day 1: Checkout (2 books)
  Transaction.lifecycle_status = 'active'
  Item A.item_status = 'borrowed'
  Item B.item_status = 'borrowed'

Day 15: Return
  Transaction.lifecycle_status = 'completed'
  Transaction.status = 'lost' (priority: lost > damaged)
  Item A.item_status = 'lost'
  Item B.item_status = 'damaged'
  Invoice generated
```

---

## Querying Patterns

### With Two-Level Status

**Find active transactions:**
```php
Transaction::where('lifecycle_status', 'active')->get();
```

**Find completed transactions with lost items:**
```php
Transaction::where('lifecycle_status', 'completed')
           ->where('status', 'lost')
           ->get();
```

**Find all active transactions that are overdue:**
```php
Transaction::where('lifecycle_status', 'active')
           ->where('due_date', '<', now())
           ->get();
```

**Find completed transactions with fees:**
```php
Transaction::where('lifecycle_status', 'completed')
           ->whereHas('invoice', function($q) {
               $q->whereIn('status', ['unpaid', 'partially_paid']);
           })
           ->get();
```

---

## Benefits of Two-Level System

### 1. Clearer Separation of Concerns

| Aspect | Single Status | Two-Level Status |
|--------|---------------|------------------|
| **Active Transactions** | `WHERE status = 'borrowed'` | `WHERE lifecycle_status = 'active'` |
| **Overdue Books** | `WHERE status = 'borrowed' AND due_date < NOW()` | `WHERE lifecycle_status = 'active' AND due_date < NOW()` |
| **Lost Items** | `WHERE status = 'lost'` | `WHERE status = 'lost'` (same) |
| **Transaction History** | All statuses mixed | `WHERE lifecycle_status = 'completed'` |

### 2. Better Business Logic

```php
// Renew: Only active transactions
if ($transaction->lifecycle_status !== 'active') {
    throw new Exception('Cannot renew completed transaction');
}

// Invoice: Only completed transactions
if ($transaction->lifecycle_status !== 'completed') {
    throw new Exception('Cannot invoice active transaction');
}

// Archive: Only old completed transactions
Transaction::where('lifecycle_status', 'completed')
    ->where('returned_date', '<', now()->subYears(2))
    ->update(['lifecycle_status' => 'archived']);
```

### 3. Improved Reporting

```php
// Dashboard stats
$stats = [
    'active_transactions' => Transaction::where('lifecycle_status', 'active')->count(),
    'overdue_active' => Transaction::where('lifecycle_status', 'active')
                                   ->where('due_date', '<', now())->count(),
    'completed_today' => Transaction::where('lifecycle_status', 'completed')
                                    ->whereDate('returned_date', today())->count(),
    'lost_items_total' => Transaction::where('status', 'lost')->count(),
];
```

### 4. State Machine Clarity

```
LIFECYCLE STATE MACHINE:
┌────────┐
│ Active │ ←──┐ (Renew)
└───┬────┘    │
    │         │
    ▼         │
┌───────────┐ │
│ Completed │──┘ (Cannot renew)
└─────┬─────┘
      │
      ▼
┌──────────┐
│ Archived │ (Read-only)
└──────────┘
```

---

## Proposed Enums

### LifecycleStatus Enum

```php
<?php

namespace App\Enums;

enum LifecycleStatus: string
{
    case Active = 'active';         // Books currently borrowed
    case Completed = 'completed';   // Books returned, transaction closed
    case Cancelled = 'cancelled';   // Transaction cancelled (rare)
    case Archived = 'archived';     // Old transaction, historical record
    
    public function getLabel(): string
    {
        return match($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Archived => 'Archived',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::Active => 'info',
            self::Completed => 'success',
            self::Cancelled => 'gray',
            self::Archived => 'secondary',
        };
    }
    
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

### Keep BorrowedStatus (Rename to ConditionStatus)

```php
<?php

namespace App\Enums;

// Rename BorrowedStatus → ConditionStatus
enum ConditionStatus: string
{
    case Returned = 'returned';     // Clean return
    case Delayed = 'delayed';       // Late return
    case Lost = 'lost';             // Book(s) lost
    case Damaged = 'damaged';       // Book(s) damaged
    
    // Priority for determining overall condition
    public function getPriority(): int
    {
        return match($this) {
            self::Lost => 1,      // Highest severity
            self::Damaged => 2,
            self::Delayed => 3,
            self::Returned => 4,  // Lowest severity (best)
        };
    }
}
```

---

## Updated Transaction Model

```php
class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'reference_no',
        'borrowed_date',
        'due_date',
        'returned_date',
        'renewed_count',
        'lifecycle_status',  // NEW: active, completed, cancelled, archived
        'status',            // KEEP: returned, delayed, lost, damaged (condition)
    ];

    protected $casts = [
        'lifecycle_status' => LifecycleStatus::class,  // NEW
        'status' => ConditionStatus::class,            // RENAMED from BorrowedStatus
        'borrowed_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
    ];
    
    /**
     * Check if transaction is active (books still borrowed)
     */
    public function isActive(): bool
    {
        return $this->lifecycle_status === LifecycleStatus::Active;
    }
    
    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->lifecycle_status->isCompleted();
    }
    
    /**
     * Check if transaction can be renewed
     */
    public function canRenew(): bool
    {
        return $this->isActive() 
            && !$this->isOverdue() 
            && $this->renewed_count < $this->getMaxRenewals();
    }
    
    /**
     * Scope: Active transactions
     */
    public function scopeActive($query)
    {
        return $query->where('lifecycle_status', LifecycleStatus::Active);
    }
    
    /**
     * Scope: Completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('lifecycle_status', LifecycleStatus::Completed);
    }
    
    /**
     * Scope: Overdue active transactions
     */
    public function scopeOverdue($query)
    {
        return $query->active()
                     ->where('due_date', '<', now());
    }
}
```

---

## Updated TransactionService

```php
class TransactionService
{
    /**
     * Create a new borrow transaction
     */
    public function createBorrowTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'borrowed_date' => $data['borrowed_date'],
                'borrow_days' => $data['borrow_days'],
                'lifecycle_status' => LifecycleStatus::Active,  // NEW: Set to active
                // status is null until returned
            ]);
            
            // ... create items
            
            return $transaction;
        });
    }
    
    /**
     * Return a transaction
     */
    public function returnTransaction(Transaction $transaction, array $data = []): Transaction
    {
        // Validate transaction is active
        if (!$transaction->isActive()) {
            throw new ValidationException('Cannot return a completed transaction');
        }
        
        return DB::transaction(function () use ($transaction, $data) {
            $returnDate = Carbon::parse($data['returned_date'] ?? now());
            
            // ... process items, calculate fees
            
            // Determine condition status based on items
            $conditionStatus = $this->determineConditionStatus($transaction, $returnDate);
            
            // Update transaction
            $transaction->update([
                'returned_date' => $returnDate,
                'lifecycle_status' => LifecycleStatus::Completed,  // NEW: Mark completed
                'status' => $conditionStatus,                      // Set condition
            ]);
            
            // Generate invoice if fees
            $this->invoiceService->generateInvoiceForTransaction($transaction);
            
            return $transaction->fresh();
        });
    }
    
    /**
     * Determine condition status (renamed from determineReturnStatus)
     */
    protected function determineConditionStatus(
        Transaction $transaction,
        Carbon $returnDate
    ): ConditionStatus {
        // Priority: Lost > Damaged > Delayed > Returned
        
        if ($transaction->items->contains(fn($item) => $item->item_status === 'lost')) {
            return ConditionStatus::Lost;
        }
        
        if ($transaction->items->contains(fn($item) => $item->item_status === 'damaged')) {
            return ConditionStatus::Damaged;
        }
        
        if ($returnDate->gt($transaction->due_date)) {
            return ConditionStatus::Delayed;
        }
        
        return ConditionStatus::Returned;
    }
    
    /**
     * Renew a transaction
     */
    public function renewTransaction(Transaction $transaction): array
    {
        // Check lifecycle status
        if (!$transaction->isActive()) {
            return [
                'success' => false,
                'message' => 'Cannot renew completed transaction',
            ];
        }
        
        // ... rest of renewal logic
    }
}
```

---

## UI Changes

### Transaction List

**Before:**
```
Status: Borrowed | Returned | Delayed | Lost | Damaged
```

**After:**
```
Lifecycle: [Active] [Completed] [Archived]
Condition: [Returned] [Delayed] [Lost] [Damaged]
```

**Example Display:**
```
┌────────────────────────────────────────────┐
│ TXN-001                                    │
│ Lifecycle: [🔵 Active]                     │
│ Due: Dec 20, 2025 (2 days left)            │
│ Books: 2                                   │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│ TXN-002                                    │
│ Lifecycle: [✅ Completed]                  │
│ Condition: [⚠️ Delayed] (3 days late)      │
│ Returned: Dec 18, 2025                     │
│ Fees: $7.50                                │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│ TXN-003                                    │
│ Lifecycle: [✅ Completed]                  │
│ Condition: [🔴 Lost] (1 lost, 1 damaged)   │
│ Returned: Dec 15, 2025                     │
│ Fees: $53.00                               │
└────────────────────────────────────────────┘
```

### Filters

```
Lifecycle Status:
☑️ Active
☐ Completed
☐ Archived

Condition Status:
☐ Returned
☑️ Delayed
☑️ Lost
☐ Damaged
```

---

## Migration Guide

### Step 1: Create Migration

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add lifecycle_status field
            $table->string('lifecycle_status', 20)
                  ->default('active')
                  ->after('status');
            
            $table->index('lifecycle_status');
        });
        
        // Populate lifecycle_status based on existing data
        DB::statement("
            UPDATE transactions 
            SET lifecycle_status = CASE
                WHEN status = 'borrowed' THEN 'active'
                WHEN status IN ('returned', 'delayed', 'lost', 'damaged') THEN 'completed'
                ELSE 'active'
            END
        ");
        
        // Update status for active transactions (they shouldn't have a condition yet)
        DB::statement("
            UPDATE transactions 
            SET status = NULL
            WHERE lifecycle_status = 'active'
        ");
    }
    
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('lifecycle_status');
            $table->dropIndex(['lifecycle_status']);
        });
    }
};
```

### Step 2: Create LifecycleStatus Enum

Create `app/Enums/LifecycleStatus.php` (shown above)

### Step 3: Update Transaction Model

Add `lifecycle_status` to fillable and casts (shown above)

### Step 4: Update TransactionService

Update all methods to set `lifecycle_status` appropriately

### Step 5: Update Filament Resources

Update filters, tables, forms to show both statuses

---

## Comparison Table

| Aspect | Current (Single Status) | Proposed (Two-Level) |
|--------|------------------------|----------------------|
| **Active Transactions** | `status = 'borrowed'` | `lifecycle_status = 'active'` |
| **Overdue Books** | Complex query | `lifecycle_status = 'active' AND overdue` |
| **Transaction History** | Mix of all statuses | `lifecycle_status = 'completed'` |
| **Can Renew?** | `status = 'borrowed' AND not overdue` | `lifecycle_status = 'active' AND not overdue` |
| **Lost Items Report** | `status = 'lost'` | `status = 'lost'` (same) |
| **Archiving** | Not supported | `lifecycle_status = 'archived'` |
| **Cancelled Checkouts** | Delete record | `lifecycle_status = 'cancelled'` |
| **Query Clarity** | ⚠️ Moderate | ✅ Excellent |

---

## Conclusion

### Should You Implement This?

**YES, if:**
- ✅ You want to match industry standards
- ✅ You need clearer separation of concerns
- ✅ You plan to add archiving functionality
- ✅ You want more flexible querying
- ✅ You want to support transaction cancellation

**NO (or later), if:**
- ❌ Current system works fine for your needs
- ❌ You want to minimize database changes
- ❌ Simple is more important than perfect

### Recommendation: **Implement It** 🎯

**Why:**
1. Aligns with library industry standards
2. Clearer business logic
3. Better reporting capabilities
4. More maintainable code
5. Supports future features (archiving, cancellation)

**Migration Risk:** Low
- Single field addition
- Backward compatible during transition
- Can be done incrementally

---

**Status:** Recommended Enhancement  
**Priority:** Medium  
**Complexity:** Low  
**Impact:** High (Better architecture)  
**Industry Standard:** Yes ✅