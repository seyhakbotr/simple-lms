# Lost Fine Preview Reactivity Fix

## Issue Description

The lost fine preview was not displaying in real-time when the "Mark as Lost" toggle was checked in the Return Transaction form. While the overdue fines worked correctly and updated immediately, the lost book fines would only calculate on form submission but not show in the preview.

## Root Cause

The issue was with **Filament form reactivity**. The `renderFeePreview()` method was using `$this->form->getRawState()` to retrieve form data, which does not establish reactive dependencies in Filament v3.

```php
// ❌ OLD CODE - Does not establish reactive dependencies
$allData = $this->form->getRawState();
$returnDate = $allData["returned_date"] ?? null;
$itemsData = $allData["items"] ?? [];
```

When using `getRawState()`, Filament doesn't track which form fields the preview depends on, so it doesn't know to re-render the preview when the `is_lost` toggle changes.

## Solution

### 1. Use `$get()` to Establish Dependencies

Changed the preview method to use the `$get()` function parameter instead of `getRawState()`:

```php
// ✅ NEW CODE - Establishes reactive dependencies
$returnDate = $get("returned_date");
$itemsData = $get("items") ?? [];
```

When you call `$get("field_name")`, Filament automatically tracks that dependency and knows to re-render the component whenever that field changes.

### 2. Change `reactive()` to `live()`

Updated all form components to use `->live()` instead of the deprecated `->reactive()`:

```php
// Form fields that affect the preview
DatePicker::make("returned_date")
    ->live()  // ✅ Changed from ->reactive()

Repeater::make("items")
    ->live()  // ✅ Changed from ->reactive()

Toggle::make("is_lost")
    ->live()  // ✅ Changed from ->reactive()

Toggle::make("is_damaged")
    ->live()  // ✅ Changed from ->reactive()
```

In Filament v3, `->live()` is the recommended approach for real-time reactivity with better performance.

## Files Modified

1. **Admin Panel**
   - `app/Filament/Admin/Resources/TransactionResource/Pages/ReturnTransaction.php`

2. **Staff Panel**
   - `app/Filament/Staff/Resources/TransactionResource/Pages/ReturnTransaction.php`

## How It Works Now

1. **User checks "Mark as Lost" toggle** → Filament detects the change
2. **Filament re-evaluates dependent components** → Preview `Placeholder` is dependent on `items` field
3. **`renderFeePreview()` is called** → Uses `$get("items")` to get current form state
4. **Lost fine is calculated and displayed** → Shows in real-time without form submission

## Testing Verification

### Before Fix:
```
1. Open Return Transaction page
2. Check "Mark as Lost" toggle
3. Preview shows: ❌ No lost fine displayed (only shows after submission)
```

### After Fix:
```
1. Open Return Transaction page
2. Check "Mark as Lost" toggle
3. Preview shows: ✅ Lost fine displays immediately
   - "Lost Book Fine: $XX.XX" appears
   - "Total Lost Book Fees: $XX.XX" updates
   - Grand total updates in real-time
```

## Data Flow

```
[User checks "Mark as Lost"]
        ↓
[Filament detects live() field change]
        ↓
[Triggers re-evaluation of dependent components]
        ↓
[Placeholder->content() callback runs]
        ↓
[renderFeePreview($get) is called]
        ↓
[Uses $get("items") to read current form state]
        ↓
[if (!empty($itemData["is_lost"]))]
        ↓
[calculateLostBookFine() is called]
        ↓
[Lost fine displays in preview HTML]
```

## Related Components

- **FeeCalculator Service**: Calculates lost book fines based on settings
  ```php
  $lostFine = $feeCalculator->calculateLostBookFine($item->book);
  ```

- **Fee Settings**: Controls calculation method (percentage or fixed)
  - `lost_book_fine_type`: "percentage" or "fixed"
  - `lost_book_fine_rate`: Rate percentage or fixed amount
  - `lost_book_minimum_fine`: Minimum charge
  - `lost_book_maximum_fine`: Maximum charge

- **Book Price**: Used for percentage-based calculations
  ```php
  $fine = ($bookPrice * $lostBookFineRate) / 100;
  ```

## Important Notes

1. **Lost fines ARE saved correctly** - The database always stored lost fines properly. This was purely a UI/preview reactivity issue.

2. **Overdue fines always worked** - Overdue calculation happened on initial render based on dates, so it appeared to work.

3. **Both Admin and Staff panels fixed** - Applied same changes to both panel implementations.

4. **Backward compatible** - Using `$get()` instead of `getRawState()` doesn't break existing functionality.

## Debugging Tips

If preview stops updating:
1. Check browser console for JavaScript errors
2. Verify all toggles/inputs have `->live()` 
3. Ensure `$get()` is used in preview callback, not `getRawState()`
4. Check Livewire Alpine errors in browser dev tools

## Performance Note

Using `->live()` triggers server requests on every change. For better performance on slower connections, you could use `->live(onBlur: true)` or `->live(debounce: 500)` to reduce requests. However, for the return form with typically 1-5 items, the current implementation is fine.