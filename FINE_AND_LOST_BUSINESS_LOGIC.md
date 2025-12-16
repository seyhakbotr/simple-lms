# Fine and Lost Book Business Logic

## Overview

The Library System calculates and manages three types of fines:
1. **Overdue Fines** - Charged when books are returned late
2. **Lost Book Fines** - Charged when books are not returned (marked as lost)
3. **Damage Fines** - Manually set when books are returned damaged

All fines are stored in **cents** in the database but displayed and calculated in **dollars** using the `MoneyCast` custom cast.

---

## 1. Overdue Fines

### Calculation Logic

```
IF overdue_fee_enabled = false THEN
    Overdue Fine = $0
ELSE
    Days Late = Return Date - Due Date (in whole days)
    
    IF Days Late <= 0 THEN
        Overdue Fine = $0
    ELSE
        // Apply Grace Period
        Chargeable Days = Days Late - grace_period_days
        
        IF Chargeable Days <= 0 THEN
            Overdue Fine = $0
        ELSE
            // Apply Maximum Days Cap (if set)
            IF overdue_fee_max_days is set THEN
                Chargeable Days = MIN(Chargeable Days, overdue_fee_max_days)
            END IF
            
            // Calculate Fine
            Fine = Chargeable Days × overdue_fee_per_day
            
            // Apply Maximum Amount Cap (if set)
            IF overdue_fee_max_amount is set THEN
                Fine = MIN(Fine, overdue_fee_max_amount)
            END IF
            
            // Apply Small Amount Waiver (if enabled)
            IF waive_small_amounts = true AND Fine < small_amount_threshold THEN
                Fine = $0
            END IF
            
            Overdue Fine = Fine
        END IF
    END IF
END IF
```

### Settings

| Setting | Type | Description | Example |
|---------|------|-------------|---------|
| `overdue_fee_enabled` | Boolean | Enable/disable overdue fines | `true` |
| `overdue_fee_per_day` | Decimal | Fee per day late | `$0.50` |
| `grace_period_days` | Integer | Days before charging starts | `3` days |
| `overdue_fee_max_days` | Integer (nullable) | Maximum days to charge | `30` days |
| `overdue_fee_max_amount` | Decimal (nullable) | Maximum overdue fee cap | `$50.00` |
| `waive_small_amounts` | Boolean | Auto-waive small fines | `true` |
| `small_amount_threshold` | Decimal | Threshold for waiving | `$1.00` |

### Example Scenarios

#### Scenario 1: Standard Overdue
```
Settings:
- overdue_fee_per_day = $0.50
- grace_period_days = 3
- overdue_fee_max_days = null
- overdue_fee_max_amount = null

Borrowed: Jan 1, 2025
Due Date: Jan 14, 2025
Returned: Jan 20, 2025

Calculation:
Days Late = 20 - 14 = 6 days
Chargeable Days = 6 - 3 (grace) = 3 days
Fine = 3 × $0.50 = $1.50
```

#### Scenario 2: With Maximum Days Cap
```
Settings:
- overdue_fee_per_day = $1.00
- grace_period_days = 0
- overdue_fee_max_days = 30
- overdue_fee_max_amount = null

Borrowed: Jan 1, 2025
Due Date: Jan 14, 2025
Returned: Mar 1, 2025 (46 days late)

Calculation:
Days Late = 46 days
Chargeable Days = MIN(46, 30) = 30 days
Fine = 30 × $1.00 = $30.00
```

#### Scenario 3: Small Amount Waived
```
Settings:
- overdue_fee_per_day = $0.25
- grace_period_days = 5
- waive_small_amounts = true
- small_amount_threshold = $1.00

Borrowed: Jan 1, 2025
Due Date: Jan 14, 2025
Returned: Jan 16, 2025 (2 days late)

Calculation:
Days Late = 2 days
Chargeable Days = 2 - 5 (grace) = -3 days
Since -3 <= 0, Fine = $0.00 (within grace period)
```

---

## 2. Lost Book Fines

### Calculation Logic

Lost book fines can be calculated using two methods:

#### Method A: Percentage of Book Price

```
IF lost_book_fine_type = "percentage" THEN
    Base Fine = (Book Price × lost_book_fine_rate) / 100
    
    // Apply Minimum Fine Floor
    IF lost_book_minimum_fine is set AND Base Fine < lost_book_minimum_fine THEN
        Fine = lost_book_minimum_fine
    ELSE
        Fine = Base Fine
    END IF
    
    // Apply Maximum Fine Ceiling
    IF lost_book_maximum_fine is set AND Fine > lost_book_maximum_fine THEN
        Fine = lost_book_maximum_fine
    END IF
    
    Lost Fine = Fine
END IF
```

#### Method B: Fixed Amount

```
IF lost_book_fine_type = "fixed" THEN
    Lost Fine = lost_book_fine_rate
END IF
```

### Settings

| Setting | Type | Description | Example |
|---------|------|-------------|---------|
| `lost_book_fine_type` | String | "percentage" or "fixed" | `"percentage"` |
| `lost_book_fine_rate` | Decimal | Percentage rate or fixed amount | `100` (100%) or `$25.00` |
| `lost_book_minimum_fine` | Decimal (nullable) | Minimum charge for lost books | `$5.00` |
| `lost_book_maximum_fine` | Decimal (nullable) | Maximum charge for lost books | `$100.00` |

### Example Scenarios

#### Scenario 1: Percentage-Based (100% of book price)
```
Settings:
- lost_book_fine_type = "percentage"
- lost_book_fine_rate = 100
- lost_book_minimum_fine = $5.00
- lost_book_maximum_fine = $100.00

Book Price: $25.00

Calculation:
Base Fine = ($25.00 × 100) / 100 = $25.00
Fine = $25.00 (within min/max range)
Lost Fine = $25.00
```

#### Scenario 2: Percentage-Based with Minimum Floor
```
Settings:
- lost_book_fine_type = "percentage"
- lost_book_fine_rate = 100
- lost_book_minimum_fine = $10.00
- lost_book_maximum_fine = null

Book Price: $3.50

Calculation:
Base Fine = ($3.50 × 100) / 100 = $3.50
Since $3.50 < $10.00 (minimum)
Lost Fine = $10.00
```

#### Scenario 3: Percentage-Based with Maximum Ceiling
```
Settings:
- lost_book_fine_type = "percentage"
- lost_book_fine_rate = 150
- lost_book_minimum_fine = null
- lost_book_maximum_fine = $50.00

Book Price: $75.00

Calculation:
Base Fine = ($75.00 × 150) / 100 = $112.50
Since $112.50 > $50.00 (maximum)
Lost Fine = $50.00
```

#### Scenario 4: Fixed Amount
```
Settings:
- lost_book_fine_type = "fixed"
- lost_book_fine_rate = $20.00

Book Price: (any price - doesn't matter)

Calculation:
Lost Fine = $20.00 (fixed rate)
```

---

## 3. Damage Fines

### Business Logic

Damage fines are **manually entered** by the librarian when processing a return. There is no automatic calculation - the staff member assesses the damage and enters an appropriate amount.

```
IF is_damaged = true THEN
    Damage Fine = [Manually entered amount by staff]
    Damage Notes = [Optional text description]
ELSE
    Damage Fine = $0
END IF
```

### Process Flow

1. Staff member inspects returned book
2. If damaged, toggles "Mark as Damaged" checkbox
3. Enters damage fine amount (e.g., $5.00 for torn page, $15.00 for water damage)
4. Optionally adds notes describing the damage
5. System stores the damage fine and notes

---

## 4. Combined Fine Calculation

When a book is returned, the system calculates the **total fine** for each item:

```
Total Fine = Overdue Fine + Lost Fine + Damage Fine
```

### Important Rules

1. **Lost books can still have overdue fines**
   - If a book is marked as lost after being overdue, both fines apply
   - Example: 10 days late ($5 overdue) + lost ($25) = $30 total

2. **Damage fines are independent**
   - Can combine with overdue fines
   - Example: 5 days late ($2.50) + damaged ($10) = $12.50 total

3. **Lost books don't return to stock**
   - When `item_status = "lost"`, the book is not returned to inventory
   - The book quantity remains decremented

4. **Transaction status updates**
   - If any item is lost → Transaction status = "lost"
   - If all returned with no issues → Transaction status = "completed"
   - If late but returned → Transaction status = "delayed"

---

## 5. Data Storage

### Database Schema

All fines are stored as **integers in cents** in the database:

```sql
-- transaction_items table
overdue_fine    INTEGER  -- e.g., 150 (cents) = $1.50
lost_fine       INTEGER  -- e.g., 2500 (cents) = $25.00
damage_fine     INTEGER  -- e.g., 1000 (cents) = $10.00
total_fine      INTEGER  -- e.g., 3650 (cents) = $36.50
fine            INTEGER  -- legacy field, equals total_fine
```

### MoneyCast Conversion

The `MoneyCast` class handles automatic conversion:

```php
// When SAVING to database
$item->lost_fine = 25.50;  // Input: $25.50 (float)
// Stored as: 2550 (integer cents)

// When READING from database
echo $item->lost_fine;  // Output: 25.5 (float dollars)
echo $item->formatted_lost_fine;  // Output: "$25.50" (formatted string)
```

---

## 6. Return Transaction Flow

### Preview Phase (Before Saving)

1. User selects return date
2. User checks "Mark as Lost" for lost books
3. User checks "Mark as Damaged" and enters amount for damaged books
4. **Preview updates in real-time** showing:
   - Overdue fine for each book
   - Lost fine for marked-lost books
   - Damage fine for marked-damaged books
   - Total per book
   - Grand total for transaction

### Saving Phase

When "Process Return" is clicked:

```php
foreach ($items as $item) {
    // Calculate overdue
    $overdueFine = FeeCalculator::calculateOverdueFine($item, $returnDate);
    
    // Calculate lost (if marked)
    $lostFine = 0;
    if ($item->is_lost) {
        $lostFine = FeeCalculator::calculateLostBookFine($item->book);
        $itemStatus = "lost";
    }
    
    // Get damage fine (if marked)
    $damageFine = $item->is_damaged ? $item->damage_fine : 0;
    
    // Calculate total
    $totalFine = $overdueFine + $lostFine + $damageFine;
    
    // Save to database
    $item->update([
        'item_status' => $itemStatus,
        'overdue_fine' => $overdueFine,
        'lost_fine' => $lostFine,
        'damage_fine' => $damageFine,
        'total_fine' => $totalFine,
        'fine' => $totalFine  // Legacy field
    ]);
    
    // Return to stock (unless lost)
    if ($itemStatus !== 'lost') {
        $item->book->increment('stock');
    }
}

// Update transaction
$transaction->update([
    'returned_date' => $returnDate,
    'status' => determineStatus()  // completed, delayed, or lost
]);
```

---

## 7. Common Business Scenarios

### Scenario: Book Returned Late
```
Book borrowed: Jan 1
Due: Jan 15
Returned: Jan 22 (7 days late)

Settings: $0.50/day, 3-day grace period

Calculation:
- Overdue: (7 - 3) × $0.50 = $2.00
- Lost: $0
- Damage: $0
- Total: $2.00
```

### Scenario: Book Lost
```
Book borrowed: Jan 1
Due: Jan 15
Never returned, marked lost: Feb 1

Book price: $30.00
Settings: 100% of price, $5 min, $50 max
         $0.50/day overdue, 3-day grace

Calculation:
- Overdue: (17 - 3) × $0.50 = $7.00 (Jan 15 to Feb 1)
- Lost: $30.00 (100% of price)
- Damage: $0
- Total: $37.00
```

### Scenario: Book Returned Damaged and Late
```
Book borrowed: Jan 1
Due: Jan 15
Returned: Jan 18 (3 days late), water damaged

Settings: $0.50/day, 0-day grace

Calculation:
- Overdue: 3 × $0.50 = $1.50
- Lost: $0
- Damage: $12.00 (staff assessed)
- Total: $13.50
```

---

## 8. Administrative Controls

Administrators can configure all fee settings through the admin panel at:
**Settings > Fee Management**

### Typical Configuration Profiles

#### Conservative Library
```
- Overdue: $0.25/day, 5-day grace, $20 max
- Lost: 100% of price, $5 min, $50 max
- Waive amounts under $1.00
```

#### Standard Library
```
- Overdue: $0.50/day, 3-day grace, $30 max
- Lost: 100% of price, $10 min, $100 max
- Waive amounts under $0.50
```

#### Strict Library
```
- Overdue: $1.00/day, 0-day grace, no max
- Lost: 150% of price, $15 min, $200 max
- No waivers
```

---

## 9. Key Points

✅ **Lost fines ARE calculated and stored correctly**
✅ **Lost fines DO appear in the preview** (after the reactivity fix)
✅ **Both overdue and lost fines can apply to the same book**
✅ **All amounts stored in cents, displayed in dollars**
✅ **Grace periods only apply to overdue, not lost books**
✅ **Lost books don't return to inventory**
✅ **Damage fines are manually assessed by staff**

---

## 10. Technical References

- **Fee Calculator Service**: `app/Services/FeeCalculator.php`
- **Fee Settings**: `app/Settings/FeeSettings.php`
- **Transaction Item Model**: `app/Models/TransactionItem.php`
- **Money Cast**: `app/Casts/MoneyCast.php`
- **Return Transaction Page**: `app/Filament/Admin/Resources/TransactionResource/Pages/ReturnTransaction.php`
