# Membership & Circulation System

## Overview
The library system now includes a comprehensive membership management system and circulation features (renewals, due dates, borrowing limits).

---

## Features Added

### 1. **Membership Types**
Pre-configured membership tiers with different privileges:

| Type | Max Books | Loan Period | Renewals | Fine Rate | Fee |
|------|-----------|-------------|----------|-----------|-----|
| **Basic** | 3 books | 14 days | 1 | $10/day | $20/year |
| **Premium** | 10 books | 30 days | 3 | $5/day | $100/year |
| **Student** | 5 books | 21 days | 2 | $5/day | $10/year |
| **Faculty** | 15 books | 60 days | 5 | $0/day | Free |
| **Lifetime** | 20 books | 90 days | 10 | $0/day | $500 once |

### 2. **Circulation Management**
- **Due Dates**: Automatically calculated based on membership type
- **Renewals**: Members can extend loan periods
- **Overdue Detection**: System tracks and calculates late returns
- **Borrowing Limits**: Enforced based on membership type

---

## Database Structure

### `membership_types` Table
```
- id
- name (Basic, Premium, Student, etc.)
- description
- max_books_allowed (how many books at once)
- max_borrow_days (default loan period)
- renewal_limit (max times to renew)
- fine_rate (per day)
- membership_duration_months
- membership_fee
- is_active
```

### Updated `users` Table
```
+ membership_type_id (foreign key)
+ membership_started_at (date)
+ membership_expires_at (date)
```

### Updated `transactions` Table
```
+ due_date (calculated from borrowed_date + max_borrow_days)
+ renewed_count (number of times renewed)
```

---

## User (Member) Features

### Check Membership Status
```php
$user = User::find($id);

// Check if membership is active
$user->hasActiveMembership(); // true/false

// Check if expired
$user->membershipExpired(); // true/false

// Get membership details
$user->membershipType->name; // "Premium"
$user->membershipType->max_books_allowed; // 10
$user->membership_expires_at; // 2025-12-31
```

### Check Borrowing Capacity
```php
// Get current borrowed books count
$count = $user->getCurrentBorrowedBooksCount(); // e.g., 3

// Check if can borrow more
$canBorrow = $user->canBorrowMoreBooks(); // true/false

// Get active transactions
$activeTransactions = $user->activeTransactions()->get();
```

### Renew Membership
```php
// Renew for another period
$user->renewMembership();
// Extends membership_expires_at by membership_duration_months
```

---

## Transaction (Circulation) Features

### Check Transaction Status
```php
$transaction = Transaction::find($id);

// Check if overdue
$transaction->isOverdue(); // true/false

// Get days overdue
$transaction->getDaysOverdue(); // e.g., 5

// Check due date
$transaction->due_date; // 2025-12-31
```

### Renewal System
```php
// Check if can renew
$transaction->canRenew(); // true/false
// Returns false if:
// - Already returned
// - Overdue
// - Reached renewal limit

// Renew transaction
$transaction->renew(); // true/false
// Extends due_date by max_borrow_days
// Increments renewed_count

// Example
if ($transaction->canRenew()) {
    $transaction->renew();
    echo "New due date: " . $transaction->fresh()->due_date;
    echo "Renewals: " . $transaction->renewed_count;
}
```

---

## Usage Examples

### Create Transaction with Auto Due Date
```php
$transaction = Transaction::create([
    'user_id' => $userId,
    'borrowed_date' => now(),
    'status' => 'borrowed',
]);
// due_date automatically calculated based on user's membership type

$transaction->items()->create([
    'book_id' => 1,
    'borrowed_for' => 7,
]);
```

### Check Member's Limits Before Borrowing
```php
$user = User::with('membershipType')->find($userId);

if (!$user->hasActiveMembership()) {
    return "Membership expired!";
}

if (!$user->canBorrowMoreBooks()) {
    $max = $user->membershipType->max_books_allowed;
    return "You've reached your limit of {$max} books";
}

// Proceed with borrowing...
```

### Handle Overdue Books
```php
$overdueTransactions = Transaction::where('status', 'borrowed')
    ->whereNotNull('due_date')
    ->where('due_date', '<', now())
    ->with('user', 'items.book')
    ->get();

foreach ($overdueTransactions as $transaction) {
    $daysLate = $transaction->getDaysOverdue();
    $fineRate = $transaction->user->membershipType->fine_rate;
    $estimatedFine = $daysLate * $fineRate;
    
    // Send notification, update status, etc.
}
```

### Renew Transaction (Circulation)
```php
$transaction = Transaction::find($id);

if ($transaction->canRenew()) {
    $oldDueDate = $transaction->due_date;
    $transaction->renew();
    
    $newDueDate = $transaction->fresh()->due_date;
    $renewalsLeft = $transaction->user->membershipType->renewal_limit - $transaction->renewed_count;
    
    echo "Renewed! Old: {$oldDueDate}, New: {$newDueDate}";
    echo "You have {$renewalsLeft} renewals left.";
} else {
    if ($transaction->isOverdue()) {
        echo "Cannot renew overdue books. Please return them.";
    } elseif ($transaction->renewed_count >= $transaction->user->membershipType->renewal_limit) {
        echo "Maximum renewals reached.";
    }
}
```

---

## Membership Management

### Create New Membership Type
```php
MembershipType::create([
    'name' => 'Corporate',
    'description' => 'For corporate partners',
    'max_books_allowed' => 25,
    'max_borrow_days' => 45,
    'renewal_limit' => 4,
    'fine_rate' => 0.00,
    'membership_duration_months' => 12,
    'membership_fee' => 200.00,
    'is_active' => true,
]);
```

### Assign Membership to User
```php
$user = User::find($userId);
$membershipType = MembershipType::where('name', 'Premium')->first();

$user->update([
    'membership_type_id' => $membershipType->id,
    'membership_started_at' => now(),
    'membership_expires_at' => now()->addMonths($membershipType->membership_duration_months),
]);
```

### Query Members by Type
```php
// Get all premium members
$premiumMembers = User::whereHas('membershipType', function($query) {
    $query->where('name', 'Premium');
})->get();

// Get expired memberships
$expiredMembers = User::where('membership_expires_at', '<', now())
    ->whereNotNull('membership_expires_at')
    ->get();
```

---

## Business Rules

### Borrowing Rules
1. Member must have **active membership** (not expired)
2. Cannot exceed **max_books_allowed** for their membership type
3. Due date = borrowed_date + max_borrow_days (from membership type)
4. Each transaction can be renewed up to **renewal_limit** times

### Renewal Rules
1. Cannot renew if **overdue**
2. Cannot renew if already **returned**
3. Cannot exceed **renewal_limit** for membership type
4. Each renewal extends due_date by **max_borrow_days**

### Fine Calculation
1. Fine rate varies by **membership type**
2. Faculty and Lifetime members have **$0 fine rate**
3. Formula: `days_overdue × fine_rate`
4. Calculated when book is returned late

---

## Reports & Queries

### Membership Statistics
```php
// Active memberships by type
$stats = User::select('membership_type_id', DB::raw('count(*) as count'))
    ->whereNotNull('membership_type_id')
    ->where('membership_expires_at', '>', now())
    ->groupBy('membership_type_id')
    ->with('membershipType')
    ->get();

// Expiring soon (next 30 days)
$expiringSoon = User::whereBetween('membership_expires_at', [now(), now()->addDays(30)])
    ->with('membershipType')
    ->get();
```

### Circulation Statistics
```php
// Most renewed transactions
$mostRenewed = Transaction::where('renewed_count', '>', 0)
    ->orderByDesc('renewed_count')
    ->with('user', 'items.book')
    ->limit(10)
    ->get();

// Current circulation count
$currentlyBorrowed = Transaction::where('status', 'borrowed')
    ->with('items')
    ->get()
    ->sum(fn($t) => $t->items->count());

// Overdue items
$overdueCount = Transaction::where('status', 'borrowed')
    ->where('due_date', '<', now())
    ->count();
```

---

## Testing

### Test Data
Run the seeder to get 5 default membership types:
```bash
php artisan db:seed --class=MembershipTypeSeeder
```

### Test Scenarios

**1. Create Member with Membership**
```bash
php artisan tinker
```
```php
$user = User::factory()->create(['role_id' => 3]); // Borrower
$membershipType = MembershipType::where('name', 'Student')->first();
$user->update([
    'membership_type_id' => $membershipType->id,
    'membership_started_at' => now(),
    'membership_expires_at' => now()->addMonths(12),
]);
```

**2. Test Borrowing Limits**
```php
$user->canBorrowMoreBooks(); // Check limit
$user->getCurrentBorrowedBooksCount(); // Current count
```

**3. Test Renewal**
```php
$transaction = Transaction::where('status', 'borrowed')->first();
$transaction->canRenew(); // true/false
$transaction->renew(); // Extend due date
```

---

## Migration Guide

### Fresh Installation
```bash
php artisan migrate
php artisan db:seed --class=MembershipTypeSeeder
```

### Existing System
The migrations automatically:
1. Create `membership_types` table
2. Add membership fields to `users`
3. Add circulation fields to `transactions`
4. Populate due dates for existing transactions

All existing data is preserved!

---

## Best Practices

1. **Always check membership status** before allowing borrowing
2. **Enforce borrowing limits** at the UI level
3. **Send renewal reminders** before due dates
4. **Notify about membership expiry** 30 days in advance
5. **Prevent overdues from renewing** to encourage returns
6. **Use membership type fine rates** for consistency
7. **Log all renewals** for audit trail

---

## Future Enhancements

Possible additions:
- [ ] Hold/Reserve system for popular books
- [ ] Waiting list management
- [ ] Automatic membership renewal reminders
- [ ] Membership upgrade/downgrade workflows
- [ ] Suspension for excessive overdue items
- [ ] Fine payment tracking
- [ ] Digital membership cards
- [ ] Self-service renewal portal

---

## Summary

✅ **Membership Types**: 5 pre-configured tiers with different privileges
✅ **User Memberships**: Track start/expiry dates, enforce limits
✅ **Circulation**: Due dates, renewals, overdue tracking
✅ **Borrowing Limits**: Based on membership type
✅ **Renewal System**: Members can extend loans (with limits)
✅ **Backward Compatible**: Existing data preserved

**Status: READY TO USE** 🚀

---

*Last Updated: December 15, 2025*