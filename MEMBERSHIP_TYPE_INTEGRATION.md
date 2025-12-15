# Membership Type Integration with Transactions

## Overview

The Library Management System fully integrates membership types with the transaction system, automatically enforcing borrowing limits, loan periods, and renewal rules based on each user's membership tier.

## How It Works

### Membership Types Define Rules

Each membership type specifies:
- **Max Books Allowed** - Maximum number of books a user can borrow simultaneously
- **Max Borrow Days** - Maximum loan period for borrowed books
- **Renewal Limit** - Maximum number of times a transaction can be renewed
- **Fine Rate** - (Legacy field, now managed by Fee Management system)

### Transactions Respect These Rules

When creating or managing transactions, the system:
1. **Validates** borrowing limits before allowing new checkouts
2. **Enforces** loan duration maximums
3. **Calculates** due dates based on membership settings
4. **Controls** renewal capabilities per membership type

## 📊 Default Membership Tiers

| Tier | Max Books | Max Days | Renewals | Fee/Day |
|------|-----------|----------|----------|---------|
| **Basic** | 3 | 14 | 1 | $10.00 |
| **Premium** | 10 | 30 | 3 | $5.00 |
| **Student** | 5 | 21 | 2 | $5.00 |
| **Faculty** | 15 | 60 | 5 | $0.00 |
| **Lifetime** | 20 | 90 | 10 | $0.00 |

## User Interface Integration

### Creating a Transaction

When you select a borrower, the system automatically:

1. **Displays Current Status**
   - Shows how many books they currently have borrowed
   - Shows their membership type limits
   - Calculates remaining borrowing capacity

2. **Example Display:**
   ```
   ✓ Can borrow 2 more book(s) (Currently: 3/5)
   ```
   
   Or if at limit:
   ```
   ⚠️ User has reached borrowing limit (5/5)
   ```

3. **Enforces Limits**
   - Repeater field is limited to available slots
   - Cannot add more books than membership allows
   - Form shows helpful error messages

### Borrow Duration Field

The "Borrowed For" field automatically:

1. **Sets Default** - Uses membership type's max_borrow_days
2. **Shows Helper Text** - Displays maximum allowed for membership tier
3. **Validates Input** - Prevents exceeding membership limits

**Example:**
```
Borrowed For: [14] Days
Max: 30 days for Premium membership
```

## Server-Side Validation

Even if client-side validation is bypassed, server-side checks ensure:

### 1. User Has Membership Type
```php
if (!$user->membershipType) {
    throw ValidationException::with([
        'user_id' => 'User does not have a membership type assigned.'
    ]);
}
```

### 2. Borrowing Limit Check
```php
$currentBorrowedCount = $user->getCurrentBorrowedBooksCount();
$maxAllowed = $user->membershipType->max_books_allowed;
$booksToAdd = count($data['transactions']);

if ($currentBorrowedCount + $booksToAdd > $maxAllowed) {
    // Reject transaction with notification
}
```

### 3. Loan Duration Validation
```php
$maxBorrowDays = $user->membershipType->max_borrow_days;

foreach ($items as $item) {
    if ($item['borrowed_for'] > $maxBorrowDays) {
        // Reject transaction with notification
    }
}
```

## Model Methods

### User Model

#### Check Borrowing Capacity
```php
$user->canBorrowMoreBooks(); // Returns bool
```

#### Get Current Count
```php
$user->getCurrentBorrowedBooksCount(); // Returns int
```

#### Get Active Transactions
```php
$user->activeTransactions(); // Returns QueryBuilder
```

### Transaction Model

#### Check Renewal Eligibility
```php
$transaction->canRenew(); // Returns bool
```

This checks:
- Transaction not yet returned
- Transaction not overdue
- Renewals less than membership limit

#### Perform Renewal
```php
$transaction->renew(); // Returns bool
```

Automatically:
- Extends due date by membership's max_borrow_days
- Increments renewed_count
- Returns false if renewal not allowed

#### Calculate Due Date
```php
$transaction->calculateDueDate(); // Returns Carbon
```

Uses the longest `borrowed_for` value from items, or membership default.

### MembershipType Model

#### Check Book Allowance
```php
$membershipType->allowsMoreBooks($currentCount); // Returns bool
```

#### Get Default Duration
```php
$membershipType->getDefaultBorrowDays(); // Returns int
```

## Automatic Calculations

### Due Date Calculation

When a transaction is created:

```php
// Automatic calculation in Transaction::boot()
if (!$transaction->due_date) {
    $maxBorrowDays = $transaction->user->membershipType?->max_borrow_days ?? 14;
    $transaction->due_date = Carbon::parse($transaction->borrowed_date)
        ->addDays($maxBorrowDays);
}
```

### Renewal Due Date Extension

When a transaction is renewed:

```php
$renewalDays = $this->user->membershipType?->max_borrow_days ?? 14;
$this->update([
    'due_date' => $this->due_date->addDays($renewalDays),
    'renewed_count' => $this->renewed_count + 1,
]);
```

## Usage Examples

### Example 1: Creating a Transaction for a Student

**Scenario:** Student member tries to borrow 3 books

```php
// Student membership: max_books_allowed = 5
$student = User::find(1);
$student->membershipType->name; // "Student"
$student->membershipType->max_books_allowed; // 5
$student->getCurrentBorrowedBooksCount(); // 3

// Can borrow? 
$student->canBorrowMoreBooks(); // true (3 < 5)

// Create transaction for 2 more books (total will be 5)
Transaction::create([
    'user_id' => $student->id,
    'borrowed_date' => now(),
    // due_date calculated automatically using max_borrow_days = 21
]);
```

### Example 2: Premium Member Renewal

```php
$transaction = Transaction::find(1);
$transaction->user->membershipType->name; // "Premium"
$transaction->user->membershipType->renewal_limit; // 3
$transaction->renewed_count; // 1

// Can renew?
$transaction->canRenew(); // true (1 < 3)

// Perform renewal
$transaction->renew();
// Due date extended by 30 days (Premium max_borrow_days)
// renewed_count now = 2
```

### Example 3: Faculty Member Borrowing

```php
$faculty = User::find(3);
$faculty->membershipType->name; // "Faculty"
$faculty->membershipType->max_books_allowed; // 15
$faculty->membershipType->max_borrow_days; // 60

// Creating transaction
// - Can borrow up to 15 books
// - Each book can be borrowed for up to 60 days
// - Can renew up to 5 times
// - No overdue fees (fine_rate = 0.00)
```

## Validation Error Messages

The system provides clear, actionable error messages:

### Borrowing Limit Exceeded
```
❌ Borrowing Limit Exceeded

User has 3 book(s) borrowed. Their membership type (Student) 
allows maximum 5 book(s). Cannot borrow 3 more book(s).
```

### Invalid Borrow Duration
```
❌ Invalid Borrow Duration

Borrow duration for book #2 exceeds the maximum of 21 days 
allowed for Student membership.
```

### No Membership Type
```
❌ No Membership Type

The selected user does not have a membership type assigned.
```

## Migration from Old System

If your system previously didn't enforce these limits:

### Step 1: Verify All Users Have Membership Types

```php
use App\Models\User;
use App\Models\MembershipType;

// Find users without membership type
$usersWithoutMembership = User::whereRole('borrower')
    ->whereNull('membership_type_id')
    ->get();

// Assign default membership
$basicType = MembershipType::where('name', 'Basic')->first();

foreach ($usersWithoutMembership as $user) {
    $user->update([
        'membership_type_id' => $basicType->id,
        'membership_started_at' => now(),
        'membership_expires_at' => now()->addYear(),
    ]);
}
```

### Step 2: Review Existing Active Transactions

```php
// Find users who currently exceed their new limits
$violations = User::whereRole('borrower')
    ->with('membershipType')
    ->get()
    ->filter(function ($user) {
        return $user->getCurrentBorrowedBooksCount() > 
               $user->membershipType->max_books_allowed;
    });

// Report violations
foreach ($violations as $user) {
    echo $user->name . " has " . 
         $user->getCurrentBorrowedBooksCount() . 
         " books but limit is " . 
         $user->membershipType->max_books_allowed . "\n";
}

// Option: Grandfather existing transactions
// New transactions will respect limits
```

## Best Practices

### DO ✅

1. **Assign membership types to all borrowers** before they can borrow
2. **Use appropriate membership tiers** based on user categories
3. **Review limits regularly** - adjust as needed
4. **Monitor renewals** - track users hitting renewal limits
5. **Update membership types** in settings when policies change

### DON'T ❌

1. **Don't bypass validation** - it exists for good reason
2. **Don't create transactions without membership checks**
3. **Don't manually calculate due dates** - let the system handle it
4. **Don't ignore membership expiration** - check expiry dates
5. **Don't hardcode limits** - use membership type settings

## Troubleshooting

### User Can't Borrow Books

**Check:**
1. Does user have a membership type assigned?
2. Has user reached their borrowing limit?
3. Is user's membership expired?
4. Is user's account active?

```php
$user = User::with('membershipType')->find($id);

echo "Membership: " . $user->membershipType?->name ?? 'None' . "\n";
echo "Currently Borrowed: " . $user->getCurrentBorrowedBooksCount() . "\n";
echo "Max Allowed: " . $user->membershipType?->max_books_allowed ?? 'N/A' . "\n";
echo "Can Borrow More: " . ($user->canBorrowMoreBooks() ? 'Yes' : 'No') . "\n";
echo "Membership Expires: " . $user->membership_expires_at . "\n";
```

### Can't Renew Transaction

**Check:**
1. Is transaction already returned?
2. Is transaction overdue?
3. Has renewal limit been reached?

```php
$transaction = Transaction::find($id);

echo "Status: " . $transaction->status . "\n";
echo "Returned: " . ($transaction->returned_date ?? 'Not yet') . "\n";
echo "Overdue: " . ($transaction->isOverdue() ? 'Yes' : 'No') . "\n";
echo "Renewals: " . $transaction->renewed_count . "/" . 
     $transaction->user->membershipType->renewal_limit . "\n";
echo "Can Renew: " . ($transaction->canRenew() ? 'Yes' : 'No') . "\n";
```

## Custom Membership Types

To create a custom membership type:

### Via Admin Panel
1. Navigate to **Settings > Membership Types**
2. Click **New Membership Type**
3. Fill in:
   - Name (e.g., "VIP", "Corporate")
   - Description
   - Max Books Allowed (1-100)
   - Max Borrow Days (1-365)
   - Renewal Limit (0-20)
   - Membership Duration (months)
   - Membership Fee
4. Click **Create**

### Via Code
```php
use App\Models\MembershipType;

MembershipType::create([
    'name' => 'VIP',
    'description' => 'VIP membership with extended privileges',
    'max_books_allowed' => 25,
    'max_borrow_days' => 120,
    'renewal_limit' => 10,
    'fine_rate' => 0.00, // Use Fee Management instead
    'membership_duration_months' => 12,
    'membership_fee' => 200.00,
    'is_active' => true,
]);
```

## Integration with Fee Management

**Note:** The `fine_rate` field in MembershipType is legacy. The system now uses the centralized Fee Management system for all fee calculations.

Overdue fees are calculated by:
1. **FeeCalculator Service** - Not membership type fine_rate
2. **FeeSettings** - Configurable in Admin > Settings > Fee Management
3. **Grace periods, caps, and waivers** - Applied consistently

See [TRANSACTION_FEE_INTEGRATION.md](TRANSACTION_FEE_INTEGRATION.md) for details.

## Related Documentation

- [Transaction & Fee Integration](TRANSACTION_FEE_INTEGRATION.md)
- [Fee Management Guide](FEE_MANAGEMENT.md)
- [Membership & Circulation](MEMBERSHIP_CIRCULATION.md)
- [Quick Reference](QUICK_REFERENCE.md)

## Summary

✅ **Membership types fully integrated with transactions**
✅ **Automatic validation and enforcement**
✅ **Clear error messages for users**
✅ **Flexible configuration per tier**
✅ **Server-side validation ensures integrity**
✅ **Smart defaults based on membership**

The system ensures that all borrowing activities respect the rules defined by each user's membership type, providing a fair and consistent experience while preventing policy violations.