# Fee Management Module

## Overview

The Fee Management module provides comprehensive control over all library fees and fines. Administrators can configure overdue fees, lost book fines, grace periods, and payment options through an intuitive settings interface.

## Features

### 1. Overdue Fee Settings

Configure how the library charges fees for books returned after the due date.

#### Available Options:

- **Enable/Disable Overdue Fees**: Toggle overdue fee calculation on/off system-wide
- **Fee Per Day**: Amount charged per day for overdue books (default: $10.00)
- **Maximum Days to Charge**: Optional cap on the number of days to charge fees
- **Maximum Fee Cap**: Optional maximum amount for overdue fees
- **Grace Period**: Number of days after due date before fees start accumulating

#### Example Scenarios:

**Scenario 1: Standard Configuration**
- Fee Per Day: $10.00
- Grace Period: 0 days
- Max Days: None
- Max Amount: None

Result: Member returns book 5 days late → $50.00 fine

**Scenario 2: With Grace Period**
- Fee Per Day: $10.00
- Grace Period: 2 days
- Max Days: None
- Max Amount: None

Result: Member returns book 5 days late → $30.00 fine (5 - 2 grace days = 3 chargeable days)

**Scenario 3: With Maximum Cap**
- Fee Per Day: $10.00
- Grace Period: 0 days
- Max Days: 10 days
- Max Amount: $75.00

Result: Member returns book 15 days late → $75.00 fine (capped at max amount, even though 10 days × $10 = $100)

### 2. Lost Book Fine Settings

Configure penalties when books are lost or severely damaged.

#### Calculation Types:

**Percentage of Book Price**
- Calculate fine as a percentage of the book's original price
- Default: 100% (full replacement cost)
- Optional minimum and maximum fine amounts
- Ideal for ensuring members pay fair replacement costs

**Fixed Amount**
- Charge a flat fee regardless of book price
- Simpler for administration
- Ideal for standardized collections

#### Example Scenarios:

**Percentage Method:**
- Fine Rate: 100%
- Minimum Fine: $10.00
- Maximum Fine: $100.00

Results:
- $5 book → $10.00 fine (minimum applied)
- $50 book → $50.00 fine (full price)
- $200 book → $100.00 fine (maximum applied)

**Fixed Method:**
- Fine Rate: $50.00

Results:
- Any lost book → $50.00 fine

### 3. Payment Settings

#### Currency Configuration
- **Currency Symbol**: Display symbol (e.g., $, €, £)
- **Currency Code**: ISO code (e.g., USD, EUR, GBP)

#### Payment Options
- **Allow Partial Payments**: Enable installment payments for fines
- **Auto-waive Small Amounts**: Automatically forgive fees below threshold
- **Small Amount Threshold**: Amount below which fees are waived (default: $1.00)

### 4. Notification Settings

- **Send Overdue Notifications**: Enable/disable automatic notifications
- **Notification Timing**: Days after due date before sending first notice (default: 3 days)

## Accessing Fee Management

### Admin Panel
1. Log in as an administrator
2. Navigate to **Settings** → **Fee Management**
3. Configure fee settings as needed
4. Click **Save** to apply changes

### Staff Access
Staff members can view current fee settings but cannot modify them. Fee information is displayed when processing transactions.

## How Fees Are Calculated

### Overdue Fees

The system automatically calculates overdue fees when a book is returned:

1. **Calculate Days Late**: Return date - Due date
2. **Apply Grace Period**: Subtract grace period days (if configured)
3. **Apply Day Cap**: Limit to maximum days if configured
4. **Calculate Fee**: Days late × Fee per day
5. **Apply Amount Cap**: Limit to maximum amount if configured
6. **Check Waiver**: Auto-waive if below threshold

### Lost Book Fines

When marking a book as lost:

**Percentage Method:**
1. Get book's price
2. Calculate: Price × (Percentage / 100)
3. Apply minimum fine if result is too low
4. Apply maximum fine if result is too high

**Fixed Method:**
1. Apply fixed fine amount directly

## Integration with Membership Types

The Fee Management module works alongside the Membership Type system:

- **Membership Types** define the **Fine Rate per Day** for each membership level
- **Fee Management** defines the **default system-wide overdue fee**
- Individual membership types can override the default fee rate
- All other settings (grace period, caps, etc.) apply system-wide

### Example:

**Fee Management Settings:**
- Default Overdue Fee: $10/day
- Grace Period: 2 days

**Membership Types:**
- Basic: $10/day (uses default)
- Premium: $5/day (custom rate)
- Student: $3/day (custom rate)

Result: Premium and Student members pay lower daily fees, but all members benefit from the 2-day grace period.

## Best Practices

### 1. Setting Overdue Fees

**Recommended Approach:**
- Start with a reasonable daily fee ($5-$15)
- Implement a grace period (1-3 days) to account for weekends/holidays
- Consider a maximum cap to avoid excessive fines (e.g., 30 days or $150)

**Considerations:**
- Balance revenue needs with member retention
- Consider your community demographics (students, seniors, etc.)
- Review and adjust quarterly based on return patterns

### 2. Lost Book Fines

**Recommended Approach:**
- Use percentage method (100-150% of book price)
- Set minimum fine to cover processing costs ($10-$20)
- Set maximum fine to avoid shocking members ($100-$200)

**Considerations:**
- Account for out-of-print books that may be expensive to replace
- Consider condition and age of books in your collection
- Offer alternative options (member finds replacement, etc.)

### 3. Grace Periods

**Recommended Settings:**
- Standard: 1-2 days for regular books
- Consider longer grace periods for:
  - Reference materials
  - Special collections
  - First-time borrowers

### 4. Communication

**Best Practices:**
- Display current fee structure prominently in the library
- Include fee information in membership agreements
- Send reminder emails before due dates
- Notify members when fees are assessed

## Transaction Display

### In Transaction Records

When viewing transactions, the system displays:

- **Current Fee Structure**: Dynamic label showing current per-day rate
- **Grace Period**: If applicable, shown in parentheses
- **Total Fine**: Calculated based on current settings
- **Currency**: Uses configured currency symbol

Example Display:
```
Fine: $10.00 Per Day (After 2 Day Grace Period)
Total: $30.00
```

## Technical Details

### Files Modified/Created

**New Files:**
- `app/Settings/FeeSettings.php` - Settings class
- `app/Filament/Admin/Pages/ManageFees.php` - Admin settings page
- `app/Services/FeeCalculator.php` - Fee calculation service
- `database/settings/2025_12_15_154705_create_fee_settings.php` - Settings migration

**Modified Files:**
- `app/Models/TransactionItem.php` - Updated calculateFine() method
- `app/Filament/Admin/Resources/TransactionResource.php` - Dynamic fee display
- `app/Filament/Staff/Resources/TransactionResource.php` - Dynamic fee display

### Database

Settings are stored in the `settings` table with the group prefix `fees.*`:

```php
fees.overdue_fee_per_day
fees.overdue_fee_enabled
fees.overdue_fee_max_days
fees.overdue_fee_max_amount
fees.lost_book_fine_rate
fees.lost_book_fine_type
// ... etc
```

### Fee Calculator Service

Use the `FeeCalculator` service in your code:

```php
use App\Services\FeeCalculator;

// Inject or resolve the service
$calculator = app(FeeCalculator::class);

// Calculate overdue fine for a transaction item
$fine = $calculator->calculateOverdueFine($item);

// Calculate lost book fine
$fine = $calculator->calculateLostBookFine($book);

// Format fine for display
$formatted = $calculator->formatFine($amountInCents);

// Get fee summary
$summary = $calculator->getFeeSummary();

// Calculate total user fines
$total = $calculator->calculateUserTotalFines($user);
```

## Migration and Updates

### Updating from Previous Version

If you're updating from a version without Fee Management:

1. The migration will automatically set default values:
   - Overdue Fee: $10/day
   - Grace Period: 0 days
   - Lost Book Fine: 100% of book price
   - Currency: USD ($)

2. Review and adjust settings in Fee Management page

3. Existing fines are preserved and not recalculated

### Future Compatibility

The module is designed to be:
- **Extensible**: Easy to add new fee types
- **Backward Compatible**: Existing transactions remain unchanged
- **Configurable**: All settings can be modified without code changes

## Troubleshooting

### Fines Not Calculating

**Check:**
1. Is "Enable Overdue Fees" toggled on?
2. Has the book actually been returned late?
3. Is there a grace period hiding the fee?
4. Is the fee below the auto-waive threshold?

### Incorrect Fine Amounts

**Verify:**
1. Current fee per day setting
2. Grace period configuration
3. Maximum day/amount caps
4. Whether small amount waiver is enabled

### Display Issues

**Ensure:**
1. Currency settings are correct
2. Browser cache is cleared
3. Settings were saved properly

## Support and Customization

### Common Customizations

**Adding New Fee Types:**
1. Add fields to `FeeSettings.php`
2. Update settings migration
3. Add UI fields in `ManageFees.php`
4. Implement calculation in `FeeCalculator.php`

**Changing Default Values:**
Edit `database/settings/2025_12_15_154705_create_fee_settings.php`

**Custom Fee Rules:**
Extend `FeeCalculator` service with custom methods

## Changelog

### Version 1.0.0 (Current)
- Initial release
- Overdue fee configuration
- Lost book fine configuration
- Payment settings
- Notification settings
- Dynamic fee display in transactions
- Fee calculator service

## Future Enhancements

Planned features for future releases:
- Payment tracking and history
- Fine payment gateway integration
- Automated dunning notices
- Fine waiver requests and approvals
- Reporting and analytics dashboard
- Multi-tier fine schedules
- Seasonal fee adjustments
- Member fine payment portal