# Transaction Service Guide

## Overview

The `TransactionService` centralizes all transaction-related business logic, providing a clean separation between UI concerns (Filament forms) and business rules (validation, creation, calculations).

## Why TransactionService?

### Before (Problems)
- ❌ Business logic scattered across models, forms, and pages
- ❌ Validation duplicated in multiple places
- ❌ Hard to test transaction workflows
- ❌ Tight coupling between UI and business logic
- ❌ Inconsistent error handling

### After (Solution)
- ✅ Single source of truth for transaction operations
- ✅ Centralized validation logic
- ✅ Easy to test and mock
- ✅ Clean separation of concerns
- ✅ Consistent error handling and responses

## Architecture

```
┌─────────────────────────────────────┐
│   Filament UI (Admin/Staff)         │
│   - TransactionResource              │
│   - CreateTransaction Page           │
└──────────────┬──────────────────────┘
               │ uses
               ↓
┌─────────────────────────────────────┐
│   TransactionService                 │ ← Business Logic Layer
│   - Validates membership limits      │
│   - Creates transactions             │
│   - Returns books & calculates fees  │
│   - Handles renewals                 │
└──────────────┬──────────────────────┘
               │ uses
               ↓
┌─────────────────────────────────────┐
│   Domain Models                      │
│   - Transaction                      │
│   - TransactionItem                  │
│   - User                             │
│   - Book                             │
└──────────────┬──────────────────────┘
               │ uses
               ↓
┌─────────────────────────────────────┐
│   Supporting Services                │
│   - FeeCalculator                    │
└─────────────────────────────────────┘
```

## Core Methods

### 1. Validation Methods

#### `validateBorrowingCapacity(User $user, int $booksCount): array`

Checks if a user can borrow the requested number of books.

**Parameters:**
- `$user` - The user attempting to borrow
- `$booksCount` - Number of books they want to borrow

**Returns:**
```php
[
    'can_borrow' => bool,
    'message' => string,
    'details' => [
        'current_count' => int,
        'max_allowed' => int,
        'requesting' => int,
        'total_after' => int,
        'remaining_after' => int,
        'membership_type' => string
    ]
]
```

**Example:**
```php
$service = app(TransactionService::class);
$user = User::find(1);

$validation = $service->validateBorrowingCapacity($user, 3);

if (!$validation['can_borrow']) {
    echo $validation['message'];
    // "User has 2 book(s) borrowed. Their membership type (Student) 
    //  allows maximum 5 book(s). Cannot borrow 3 more book(s)."
}
```

#### `validateBorrowDuration(User $user, int $borrowDays): array`

Validates if the borrow duration is within the user's membership limits.

**Returns:**
```php
[
    'valid' => bool,
    'message' => string,
    'max_days' => int,
    'requested_days' => int
]
```

**Example:**
```php
$validation = $service->validateBorrowDuration($user, 45);

if (!$validation['valid']) {
    echo $validation['message'];
    // "Borrow duration (45 days) exceeds the maximum of 30 days 
    //  allowed for Premium membership."
}
```

### 2. Transaction Operations

#### `createTransaction(array $data): Transaction`

Creates a new transaction with complete validation.

**Parameters:**
```php
[
    'user_id' => int,              // Required
    'borrowed_date' => Carbon|string, // Required
    'items' => [                   // Required
        [
            'book_id' => int,
            'borrowed_for' => int  // Days
        ],
        // ... more items
    ],
    'status' => BorrowedStatus,    // Optional (defaults to Borrowed)
    'returned_date' => Carbon|string|null, // Optional
    'due_date' => Carbon|string|null // Optional (auto-calculated)
]
```

**What It Does:**
1. Validates user exists and has membership type
2. Validates borrowing capacity
3. Validates each item's borrow duration
4. Creates transaction and items in a database transaction
5. Updates book stock (decrements)
6. Returns the created transaction with relationships loaded

**Throws:** `ValidationException` if any validation fails

**Example:**
```php
use App\Services\TransactionService;
use Illuminate\Validation\ValidationException;

$service = app(TransactionService::class);

try {
    $transaction = $service->createTransaction([
        'user_id' => 5,
        'borrowed_date' => now(),
        'items' => [
            ['book_id' => 10, 'borrowed_for' => 14],
            ['book_id' => 15, 'borrowed_for' => 21],
        ]
    ]);
    
    echo "Transaction #{$transaction->id} created successfully!";
    
} catch (ValidationException $e) {
    foreach ($e->errors() as $field => $errors) {
        echo "{$field}: " . implode(', ', $errors) . "\n";
    }
}
```

#### `returnTransaction(Transaction $transaction, $returnDate = null): Transaction`

Processes a book return, calculates fines, and updates stock.

**Parameters:**
- `$transaction` - The transaction to return
- `$returnDate` - Optional return date (defaults to now)

**What It Does:**
1. Updates transaction with return date
2. Sets status to Returned or Delayed (if overdue)
3. Calculates and stores fines for each item
4. Restores book stock (increments)
5. Returns the updated transaction

**Example:**
```php
$transaction = Transaction::find(10);

// Return today
$returned = $service->returnTransaction($transaction);

// Return with specific date
$returned = $service->returnTransaction($transaction, '2024-01-15');

echo "Total fines: " . $returned->formatted_total_fine;
```

#### `renewTransaction(Transaction $transaction): array`

Attempts to renew a transaction.

**Returns:**
```php
[
    'success' => bool,
    'message' => string,
    'transaction' => Transaction|null,
    'renewed_count' => int,         // If success
    'new_due_date' => Carbon,       // If success
    'days_added' => int,            // If success
    'reasons' => array              // If failed
]
```

**Example:**
```php
$transaction = Transaction::find(5);
$result = $service->renewTransaction($transaction);

if ($result['success']) {
    echo "Renewed! New due date: " . $result['new_due_date']->format('Y-m-d');
    echo "Extended by {$result['days_added']} days";
} else {
    echo "Cannot renew: " . $result['message'];
    foreach ($result['reasons'] as $reason) {
        echo "- {$reason}\n";
    }
}
```

### 3. Information Methods

#### `getCurrentOverdueFine(Transaction $transaction): array`

Gets the current overdue fine preview or final amount.

**Returns:**
```php
[
    'total' => int,              // Amount in cents
    'formatted' => string,       // Formatted with currency
    'items' => array,           // Per-item breakdown
    'is_preview' => bool,       // True if not yet returned
    'days_overdue' => int       // If overdue
]
```

**Example:**
```php
$fineInfo = $service->getCurrentOverdueFine($transaction);

if ($fineInfo['is_preview']) {
    echo "Current overdue (if returned today): " . $fineInfo['formatted'];
    echo "Days overdue: " . $fineInfo['days_overdue'];
} else {
    echo "Final fine: " . $fineInfo['formatted'];
}

// Show per-item breakdown
foreach ($fineInfo['items'] as $item) {
    echo "{$item['book_title']}: {$item['formatted']}\n";
}
```

#### `getTransactionSummary(Transaction $transaction): array`

Gets complete transaction information in one call.

**Returns:**
```php
[
    'id' => int,
    'user' => [...],
    'dates' => [...],
    'status' => [...],
    'renewal' => [...],
    'items' => [...],
    'fines' => [...]
]
```

**Example:**
```php
$summary = $service->getTransactionSummary($transaction);

echo "Transaction #{$summary['id']}\n";
echo "User: {$summary['user']['name']} ({$summary['user']['membership_type']})\n";
echo "Borrowed: {$summary['dates']['borrowed']}\n";
echo "Due: {$summary['dates']['due']}\n";
echo "Status: {$summary['status']['label']}\n";

if ($summary['status']['is_overdue']) {
    echo "⚠️ OVERDUE by {$summary['status']['days_overdue']} days\n";
}

echo "Renewals: {$summary['renewal']['count']}/{$summary['renewal']['max_allowed']}\n";
echo "Can renew: " . ($summary['renewal']['can_renew'] ? 'Yes' : 'No') . "\n";

echo "\nBooks:\n";
foreach ($summary['items'] as $item) {
    echo "- {$item['book_title']} ({$item['borrowed_for']} days)\n";
}

echo "\nFines: {$summary['fines']['formatted']}\n";
```

#### `getUserActiveTransactionsSummary(User $user): array`

Gets overview of a user's borrowing activity.

**Returns:**
```php
[
    'user_id' => int,
    'user_name' => string,
    'membership_type' => string,
    'total_books_borrowed' => int,
    'max_books_allowed' => int,
    'remaining_capacity' => int,
    'can_borrow_more' => bool,
    'active_transactions_count' => int,
    'has_overdue' => bool,
    'total_current_fines' => int
]
```

**Example:**
```php
$user = User::find(10);
$summary = $service->getUserActiveTransactionsSummary($user);

echo "{$summary['user_name']} ({$summary['membership_type']})\n";
echo "Books out: {$summary['total_books_borrowed']}/{$summary['max_books_allowed']}\n";
echo "Can borrow: {$summary['remaining_capacity']} more book(s)\n";

if ($summary['has_overdue']) {
    echo "⚠️ Has overdue books!\n";
    echo "Current fines: $" . ($summary['total_current_fines'] / 100) . "\n";
}
```

## Usage in Filament

### CreateTransaction Page

```php
use App\Services\TransactionService;
use Illuminate\Validation\ValidationException;

protected function handleRecordCreation(array $data): Model
{
    $service = app(TransactionService::class);
    
    try {
        // Transform form data if needed
        if (isset($data['transactions'])) {
            $data['items'] = $data['transactions'];
            unset($data['transactions']);
        }
        
        // Create transaction with full validation
        $transaction = $service->createTransaction($data);
        
        // Success notification
        Notification::make()
            ->success()
            ->title('Transaction Created')
            ->send();
            
        return $transaction;
        
    } catch (ValidationException $e) {
        // Let Filament handle validation errors
        throw $e;
    }
}
```

### Custom Actions

```php
use Filament\Tables\Actions\Action;

Action::make('return')
    ->label('Return Books')
    ->action(function (Transaction $record) {
        $service = app(TransactionService::class);
        $returned = $service->returnTransaction($record);
        
        Notification::make()
            ->success()
            ->title('Books Returned')
            ->body("Total fine: {$returned->formatted_total_fine}")
            ->send();
    })
    ->requiresConfirmation()
    ->visible(fn($record) => !$record->returned_date),

Action::make('renew')
    ->label('Renew')
    ->action(function (Transaction $record) {
        $service = app(TransactionService::class);
        $result = $service->renewTransaction($record);
        
        if ($result['success']) {
            Notification::make()
                ->success()
                ->title('Transaction Renewed')
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

## Testing

The service is easy to mock and test:

```php
use App\Services\TransactionService;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    protected TransactionService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransactionService::class);
    }
    
    /** @test */
    public function it_validates_borrowing_capacity()
    {
        $user = User::factory()
            ->has(MembershipType::factory()->state([
                'max_books_allowed' => 3
            ]))
            ->create();
        
        // User has 2 books out, tries to borrow 2 more
        Transaction::factory()
            ->for($user)
            ->has(TransactionItem::factory()->count(2))
            ->create();
        
        $validation = $this->service->validateBorrowingCapacity($user, 2);
        
        $this->assertFalse($validation['can_borrow']);
        $this->assertEquals(3, $validation['details']['max_allowed']);
        $this->assertEquals(2, $validation['details']['current_count']);
    }
    
    /** @test */
    public function it_creates_transaction_successfully()
    {
        $user = User::factory()
            ->has(MembershipType::factory())
            ->create();
        
        $books = Book::factory()->count(2)->create();
        
        $transaction = $this->service->createTransaction([
            'user_id' => $user->id,
            'borrowed_date' => now(),
            'items' => [
                ['book_id' => $books[0]->id, 'borrowed_for' => 14],
                ['book_id' => $books[1]->id, 'borrowed_for' => 14],
            ]
        ]);
        
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(2, $transaction->items()->count());
    }
}
```

## Benefits

### 1. **Cleaner Controllers/Pages**
- No business logic in UI layer
- Just transform data and call service
- Easy to read and maintain

### 2. **Testable**
- Service methods can be unit tested
- Mock the service in integration tests
- No need to set up entire UI

### 3. **Reusable**
- Use in Filament resources
- Use in API controllers
- Use in console commands
- Use in jobs/queues

### 4. **Consistent**
- Same validation everywhere
- Same error messages
- Same business rules

### 5. **Maintainable**
- Change logic in one place
- Clear method signatures
- Self-documenting code

## Best Practices

### DO ✅

1. **Always use the service for transaction operations**
   ```php
   $service = app(TransactionService::class);
   $transaction = $service->createTransaction($data);
   ```

2. **Handle ValidationException in UI layer**
   ```php
   try {
       $service->createTransaction($data);
   } catch (ValidationException $e) {
       // Show errors to user
   }
   ```

3. **Use service methods for validation before showing forms**
   ```php
   $validation = $service->validateBorrowingCapacity($user, 5);
   if (!$validation['can_borrow']) {
       // Disable form or show warning
   }
   ```

### DON'T ❌

1. **Don't bypass the service**
   ```php
   // BAD - bypasses validation
   Transaction::create([...]);
   
   // GOOD - uses service
   $service->createTransaction([...]);
   ```

2. **Don't duplicate validation logic**
   ```php
   // BAD - logic in controller
   if ($user->getCurrentBorrowedBooksCount() >= $max) {
       // ...
   }
   
   // GOOD - use service
   $validation = $service->validateBorrowingCapacity($user, $count);
   ```

3. **Don't handle business logic in forms**
   ```php
   // BAD - business logic in form
   ->afterStateUpdated(function ($state) {
       // Complex validation logic here
   })
   
   // GOOD - validate in service, use in form
   ->rules([
       function () {
           return function ($attribute, $value, $fail) use ($service) {
               $validation = $service->validateBorrowDuration(...);
               if (!$validation['valid']) {
                   $fail($validation['message']);
               }
           };
       }
   ])
   ```

## Related Documentation

- [Transaction & Fee Integration](TRANSACTION_FEE_INTEGRATION.md)
- [Membership Type Integration](MEMBERSHIP_TYPE_INTEGRATION.md)
- [Fee Calculator Service](FEE_MANAGEMENT.md)

## Summary

The `TransactionService` provides a clean, testable, and maintainable way to handle all transaction-related operations. By centralizing business logic, it ensures consistency across the application and makes the codebase easier to understand and modify.

**Key Points:**
- ✅ Single source of truth for transaction operations
- ✅ Comprehensive validation built-in
- ✅ Easy to test and mock
- ✅ Clean separation of concerns
- ✅ Consistent error handling
- ✅ Works seamlessly with FeeCalculator

Use the service for all transaction operations to maintain code quality and ensure business rules are enforced consistently.