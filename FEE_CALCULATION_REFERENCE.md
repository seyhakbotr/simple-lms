# Fee Calculation Reference

## Storage Format

### Book Prices
- **Stored in:** CENTS (integer)
- **Example:** 9302 = $93.02
- **Database column:** `books.price` (bigint)

### Fee Settings
- **Stored in:** DOLLARS (decimal)
- **Example:** 0.50 = $0.50 per day, 20.00 = $20.00 per day
- **Database:** `settings` table with `payload` field
- **Form input:** `step(0.01)` with `prefix('$')`

---

## Fee Types

### 1. Overdue Fees

**Configuration:**
- `overdue_fee_per_day`: Amount in dollars (e.g., 0.50 = $0.50/day)
- `grace_period_days`: Days before fees start (e.g., 0 = immediate)
- `overdue_fee_max_days`: Maximum days to charge (optional)
- `overdue_fee_max_amount`: Maximum total fee (optional)

**Calculation:**
```
Days Late = Return Date - Due Date
Chargeable Days = Days Late - Grace Period
Fine (dollars) = Chargeable Days × Fee Per Day
Fine (cents) = Fine (dollars) × 100
```

**Example:**
- Due Date: Jan 10
- Return Date: Jan 15
- Days Late: 5 days
- Grace Period: 0 days
- Fee Rate: $0.50/day
- **Calculation:** 5 × $0.50 = $2.50 = 250 cents

**With Grace Period:**
- Due Date: Jan 10
- Return Date: Jan 15
- Days Late: 5 days
- Grace Period: 2 days
- Fee Rate: $0.50/day
- **Calculation:** (5 - 2) × $0.50 = $1.50 = 150 cents

---

### 2. Lost Book Fees

**Configuration:**
- `lost_book_fine_type`: "percentage" or "fixed"
- `lost_book_fine_rate`: Percentage (100 = 100%) or fixed amount
- `lost_book_minimum_fine`: Minimum fee (dollars)
- `lost_book_maximum_fine`: Maximum fee (dollars)

**Calculation (Percentage):**
```
Book Price (cents) → Convert to dollars
Fine (dollars) = (Book Price in dollars × Percentage) / 100
Apply min/max limits
Fine (cents) = Fine (dollars) × 100
```

**Example (Percentage):**
- Book Price: 9302 cents = $93.02
- Lost Book Rate: 100% (full replacement)
- Min Fine: $10
- Max Fine: $100
- **Calculation:** $93.02 × 100% = $93.02 (within limits) = 9302 cents

**Example (Fixed):**
- Lost Book Rate: $50.00
- **Result:** $50.00 = 5000 cents

---

### 3. Damage Fees

**Configuration:**
- Manually entered by staff
- No automatic calculation

**Input:**
- Staff enters amount in dollars (e.g., 5.00)
- System converts to cents (5.00 × 100 = 500 cents)

---

## Complete Fee Flow

### Storage in Database

**Transaction Items Table:**
```sql
overdue_fine   INTEGER  -- in cents
lost_fine      INTEGER  -- in cents
damage_fine    INTEGER  -- in cents
total_fine     INTEGER  -- sum in cents
fine           INTEGER  -- legacy, equals total_fine
```

### Calculation Process

1. **Input Collection**
   - Return date
   - Lost/damaged flags
   - Damage amount (if applicable)

2. **Overdue Calculation**
   ```php
   $daysLate = $dueDate->diffInDays($returnDate);
   $chargeableDays = $daysLate - $gracePeriod;
   $fineInDollars = $chargeableDays * $feePerDay;
   $fineInCents = round($fineInDollars * 100);
   ```

3. **Lost Book Calculation**
   ```php
   $bookPriceInDollars = $bookPriceInCents / 100;
   $fineInDollars = ($bookPriceInDollars * $percentage) / 100;
   $fineInDollars = max($min, min($max, $fineInDollars));
   $fineInCents = round($fineInDollars * 100);
   ```

4. **Damage Fee**
   ```php
   $fineInCents = round($damageAmountInDollars * 100);
   ```

5. **Total**
   ```php
   $totalFine = $overdueFine + $lostFine + $damageFine;
   ```

### Display Format

**From Cents to Dollars:**
```php
public function formatFine(int $amountInCents): string
{
    $amount = $amountInCents / 100;
    return $this->feeSettings->currency_symbol . number_format($amount, 2);
}
```

**Examples:**
- 250 cents → "$2.50"
- 5000 cents → "$50.00"
- 9302 cents → "$93.02"

---

## Recommended Fee Settings

### Conservative (Public Library)
- Overdue Fee: $0.25/day
- Grace Period: 2 days
- Max Overdue: $10.00
- Lost Book: 100% of price, min $5, max $50

### Standard (School/University)
- Overdue Fee: $0.50/day
- Grace Period: 0 days
- Max Overdue: $25.00
- Lost Book: 100% of price, min $10, max $100

### Strict (Professional Library)
- Overdue Fee: $1.00/day
- Grace Period: 0 days
- Max Overdue: $50.00
- Lost Book: 100% of price, min $25, max $200

---

## Testing Fee Calculations

### Test Case 1: Simple Overdue
```
Book: $25.00 book (2500 cents)
Due: Jan 10
Returned: Jan 15 (5 days late)
Fee Rate: $0.50/day
Grace: 0 days

Expected: 5 × $0.50 = $2.50 = 250 cents
```

### Test Case 2: Overdue with Grace Period
```
Book: $25.00 book
Due: Jan 10
Returned: Jan 13 (3 days late)
Fee Rate: $0.50/day
Grace: 2 days

Expected: (3 - 2) × $0.50 = $0.50 = 50 cents
```

### Test Case 3: Lost Book
```
Book: $93.02 (9302 cents)
Lost Book Rate: 100%
Min: $10, Max: $100

Expected: $93.02 = 9302 cents
```

### Test Case 4: Multiple Fees
```
Book: $50.00 (5000 cents)
Due: Jan 10
Returned: Jan 15 (5 days late)
Damaged: Yes, $3.50 damage
Fee Rate: $0.50/day

Overdue: 5 × $0.50 = $2.50 = 250 cents
Damage: $3.50 = 350 cents
Total: 250 + 350 = 600 cents = $6.00
```

---

## Common Issues

### Issue: Fees not calculating
**Check:**
1. Is `overdue_fee_enabled` = true?
2. Is fee rate > 0?
3. Is grace period less than days late?
4. Is return date after due date?

### Issue: Fees too high
**Check:**
1. Fee rate in settings (should be like 0.50, not 50)
2. Max amount cap is set
3. Grace period is configured

### Issue: Lost book fee wrong
**Check:**
1. Book price is in cents
2. Lost book percentage/fixed setting
3. Min/max limits

---

## Database Queries

### Check Current Fee Settings
```sql
SELECT name, payload 
FROM settings 
WHERE name LIKE '%fee%' OR name LIKE '%fine%';
```

### Check Book Prices
```sql
SELECT id, title, price, price/100 as price_in_dollars 
FROM books 
LIMIT 10;
```

### Check Transaction Fees
```sql
SELECT 
    t.id,
    t.reference_no,
    ti.overdue_fine,
    ti.lost_fine,
    ti.damage_fine,
    ti.total_fine,
    ti.total_fine / 100 as total_in_dollars
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id = t.id
WHERE t.returned_date IS NOT NULL
LIMIT 10;
```

### Update Fee Rate
```sql
-- Set to $0.50 per day
UPDATE settings 
SET payload = '0.50' 
WHERE name = 'overdue_fee_per_day';
```

---

## Code Reference

### FeeCalculator Methods

**calculateOverdueFine(TransactionItem $item, ?Carbon $returnDate): int**
- Returns: Fee in cents
- Input: Fee settings in dollars
- Conversion: dollars × 100 = cents

**calculateLostBookFine(Book $book): int**
- Returns: Fee in cents
- Input: Book price in cents, settings in dollars
- Conversion: dollars × 100 = cents

**formatFine(int $amountInCents): string**
- Input: Amount in cents
- Output: Formatted string (e.g., "$2.50")
- Conversion: cents / 100 = dollars

---

## Summary

✅ **ALWAYS store fees in CENTS in database**  
✅ **Fee settings are in DOLLARS for user input**  
✅ **Book prices are in CENTS**  
✅ **Convert dollars to cents: × 100**  
✅ **Convert cents to dollars: / 100**  
✅ **Use formatFine() for display**  

**Remember:** Cents for storage, dollars for display!

---

**Last Updated:** January 2025  
**Version:** 2.0