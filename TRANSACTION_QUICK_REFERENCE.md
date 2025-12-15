# Transaction System Quick Reference

## 🚀 Quick Start

### Create a Transaction
```php
use App\Services\TransactionService;

$service = app(TransactionService::class);

$transaction = $service->createTransaction([
    'user_id' => $userId,
    'borrowed_date' => now(),
    'items' => [
        ['book_id' => 10, 'borrowed_for' => 14],
        ['book_id' => 15, 'borrowed_for' => 21],
    ]
]);
```

### Return Books
```php
$returned = $service->returnTransaction($transaction);
echo "Total fine: " . $returned->formatted_total_fine;
```

### Renew Transaction
```php
$result = $service->renewTransaction($transaction);
if ($result['success']) {
    echo "New due date: " . $result['new_due_date'];
}
```

## 📊 Common Operations

### Check Borrowing Capacity
```php
$validation = $service->validateBorrowingCapacity($user, 3);

if ($validation['can_borrow']) {
    // User can borrow
} else {
    echo $validation['message'];
}
```

### Get Current Overdue Fine
```php
$fineInfo = $service->getCurrentOverdueFine($transaction);

echo "Fine: " . $fineInfo['formatted'];
echo "Days overdue: " . $fineInfo['days_overdue'];
```

### Get Transaction Summary
```php
$summary = $service->getTransactionSummary($transaction);

// Returns complete info: user, dates, status, items, fines
```

### Get User's Active Transactions
```php
$summary = $service->getUserActiveTransactionsSummary($user);

echo "Books borrowed: {$summary['total_books_borrowed']}/{$summary['max_books_allowed']}";
echo "Can borrow more: " . ($summary['can_borrow_more'] ? 'Yes' : 'No');
```

## 🎯 Model Helpers

### Transaction Model
```php
// Check status
$transaction->isOverdue();              // bool
$transaction->getDaysOverdue();         // int
$transaction->canRenew();               // bool

// Get fines
$transaction->total_fine;               // int (cents)
$transaction->formatted_total_fine;     // string "$X.XX"

// Perform actions
$transaction->renew();                  // bool
$transaction->updateFines();            // void
```

### TransactionItem Model
```php
// Calculate fines
$item->calculateFine();                 // int (cents)
$item->getCurrentOverdueFine();         // int (cents)

// Display
$item->formatted_fine;                  // string "$X.XX"

// Due date
$item->due_date;                        // Carbon
```

### User Model
```php
// Borrowing capacity
$user->canBorrowMoreBooks();            // bool
$user->getCurrentBorrowedBooksCount();  // int

// Transactions
$user->activeTransactions();            // QueryBuilder
```

## 🔍 Status Values

| Status | Meaning | Can Renew? | Has Fine? |
|--------|---------|------------|-----------|
| `borrowed` | Currently out, on time | ✅ Yes | ❌ No |
| `delayed` | Currently out, overdue | ❌ No | ✅ Yes (preview) |
| `returned` | Returned on time | ❌ No | ❌ No |

## 💰 Fine Display

### For Active Transactions
```php
// Delayed (overdue but not returned)
if (!$transaction->returned_date && $transaction->isOverdue()) {
    echo "Current Overdue: " . $transaction->formatted_total_fine;
    echo " (" . $transaction->getDaysOverdue() . " days late)";
}

// Borrowed (on time)
if (!$transaction->returned_date && !$transaction->isOverdue()) {
    echo "No fine yet";
}
```

### For Returned Transactions
```php
if ($transaction->returned_date) {
    if ($transaction->total_fine > 0) {
        echo "Total: " . $transaction->formatted_total_fine;
    } else {
        echo "No fine";
    }
}
```

## 🎓 Membership Types

| Type | Max Books | Max Days | Renewals |
|------|-----------|----------|----------|
| Basic | 3 | 14 | 1 |
| Premium | 10 | 30 | 3 |
| Student | 5 | 21 | 2 |
| Faculty | 15 | 60 | 5 |
| Lifetime | 20 | 90 | 10 |

## ⚠️ Validation Errors

### Borrowing Limit Exceeded
```
User has 3 book(s) borrowed. Their membership type (Student) 
allows maximum 5 book(s). Cannot borrow 2 more book(s).
```

### Invalid Borrow Duration
```
Borrow duration (45 days) exceeds the maximum of 30 days 
allowed for Premium membership.
```

### Cannot Renew
- Transaction already returned
- Transaction is overdue
- Maximum renewals reached

## 🛠️ Filament Usage

### Custom Action (Return Books)
```php
use Filament\Tables\Actions\Action;

Action::make('return')
    ->action(function (Transaction $record) {
        $service = app(TransactionService::class);
        $returned = $service->returnTransaction($record);
        
        Notification::make()
            ->success()
            ->title('Books Returned')
            ->body("Fine: {$returned->formatted_total_fine}")
            ->send();
    })
    ->visible(fn($record) => !$record->returned_date)
```

### Custom Action (Renew)
```php
Action::make('renew')
    ->action(function (Transaction $record) {
        $service = app(TransactionService::class);
        $result = $service->renewTransaction($record);
        
        if ($result['success']) {
            Notification::make()
                ->success()
                ->title('Renewed')
                ->body($result['message'])
                ->send();
        } else {
            Notification::make()
                ->danger()
                ->title('Cannot Renew')
                ->body($result['message'])
                ->send();
        }
    })
    ->visible(fn($record) => $record->canRenew())
```

## 🧪 Testing

### Basic Test
```php
/** @test */
public function it_creates_transaction_with_validation()
{
    $service = app(TransactionService::class);
    $user = User::factory()->create();
    
    $transaction = $service->createTransaction([
        'user_id' => $user->id,
        'borrowed_date' => now(),
        'items' => [
            ['book_id' => 1, 'borrowed_for' => 14]
        ]
    ]);
    
    $this->assertInstanceOf(Transaction::class, $transaction);
}
```

## 📋 Cheat Sheet

### DO ✅
- Use `TransactionService` for all operations
- Check `canBorrowMoreBooks()` before creating
- Use `formatted_fine` for display
- Handle `ValidationException` in UI

### DON'T ❌
- Don't bypass service validation
- Don't manually calculate fines
- Don't duplicate business logic
- Don't use floats for money

## 📚 Related Docs

- **TRANSACTION_SERVICE_GUIDE.md** - Complete service documentation
- **TRANSACTION_FEE_INTEGRATION.md** - Fee calculation details
- **MEMBERSHIP_TYPE_INTEGRATION.md** - Membership rules
- **FIXES_APPLIED.md** - What was changed

## 🆘 Troubleshooting

### Mass Assignment Error
✅ **Fixed** - Removed hidden fields from forms

### Delayed Books Show "N/A"
✅ **Fixed** - Now shows "Current Overdue: $X.XX (Y days late)"

### Can't Renew
Check:
- Is transaction returned? (can't renew)
- Is transaction overdue? (can't renew)
- Reached renewal limit? (check membership type)

### Validation Fails
- User has membership type?
- Within borrowing limit?
- Duration within max days?

## 💡 Pro Tips

1. **Always use the service** - It handles all validation
2. **Check capacity first** - Use `validateBorrowingCapacity()`
3. **Use formatted attributes** - `formatted_fine`, `formatted_total_fine`
4. **Let service update stock** - Don't manually increment/decrement
5. **Test with different memberships** - Each has different limits

---

**Quick Help:**
- Service: `app(TransactionService::class)`
- Fee Calculator: `app(FeeCalculator::class)`
- Check overdue: `$transaction->isOverdue()`
- Get fine: `$transaction->formatted_total_fine`
