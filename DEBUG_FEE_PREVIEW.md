# Debug Guide: Fee Preview Not Updating

## Problem
The "Mark as Lost" toggle doesn't update the grand total in the fee preview in real-time.

## What We Added
Comprehensive logging to track exactly what's happening when you interact with the form.

## Step-by-Step Debugging

### Step 1: Clear Previous Logs
```bash
ddev exec bash clear_logs.sh
```

This clears old logs so you only see fresh data.

### Step 2: Open the Return Transaction Page
1. In your browser, log in to the admin/staff panel
2. Navigate to Transactions
3. Find a BORROWED transaction
4. Click the "Return" action button

### Step 3: Watch Logs in Real-Time (Option 1)
Open a terminal and run:
```bash
ddev exec bash watch_fee_logs.sh
```

This will show logs as they happen. Keep this terminal visible while testing.

### Step 4: Toggle "Mark as Lost"
In your browser:
1. Check the "Mark as Lost" toggle for any item
2. Watch the terminal - you should see logs appear
3. Uncheck the toggle
4. Check it again

### Step 5: Analyze the Logs

Look for these key indicators:

#### ✅ GOOD - Preview is Re-rendering
```
=== FEE PREVIEW RENDER CALLED ===
```
This should appear **every time** you toggle "Mark as Lost". If it doesn't appear, the preview isn't re-rendering.

#### ✅ GOOD - Toggle State is Being Read
```
  - is_lost from form: true
```
When you check the toggle, this should show `true`. When unchecked, it should show `false`.

#### ✅ GOOD - Lost Fee is Being Calculated
```
  - LOST BOOK FEE CALCULATED: 25.00
```
This should appear when `is_lost` is true.

#### ❌ BAD - Lost Fee is Being Ignored
```
  - is_lost is empty or false, no lost fee
```
If this appears when you've checked the toggle, the form state isn't being read correctly.

#### ✅ GOOD - Grand Total Includes Lost Fee
```
PREVIEW TOTALS:
  - Total Overdue: 10.00
  - Total Lost: 25.00
  - Total Damage: 0.00
  - Grand Total: 35.00
```

### Step 6: Check Logs Manually (Option 2)
If the watch script doesn't work, check logs manually:
```bash
ddev exec tail -100 storage/logs/laravel.log | grep 'FEE PREVIEW' -A 50
```

## Common Issues and Solutions

### Issue 1: "FEE PREVIEW RENDER CALLED" Doesn't Appear When Toggling
**Problem:** The preview isn't re-rendering at all.

**Possible Causes:**
1. The Repeater doesn't have `->live()`
2. The toggle doesn't have `->live()`
3. Browser cache is stale

**Solution:**
```bash
# Clear Laravel cache
ddev exec php artisan cache:clear

# Clear Filament cache
ddev exec php artisan filament:cache-components

# In browser: Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)
```

### Issue 2: "is_lost from form: false" Even When Checked
**Problem:** The form state isn't being passed to the preview.

**Possible Causes:**
1. The `$get()` path is wrong
2. Form data isn't synchronized

**What to Check:**
Look at the "Items data from $get" log line. It should show something like:
```
Items data from $get: [{"book_title":"Some Book","is_lost":true}]
```

If `is_lost` is missing or always false, the path is wrong.

### Issue 3: Preview Re-renders But Total Doesn't Change in Browser
**Problem:** Backend is calculating correctly but UI isn't updating.

**Possible Causes:**
1. Browser rendering issue
2. Alpine.js/Livewire state mismatch
3. Filament component caching

**Solution:**
```bash
# Check if logs show correct totals
ddev exec tail -100 storage/logs/laravel.log | grep 'Grand Total'

# If logs show correct total but browser doesn't, try:
ddev exec php artisan view:clear
ddev exec php artisan config:clear

# Hard refresh browser
```

## What the Logs Tell You

### Scenario A: Everything Working ✅
```
=== FEE PREVIEW RENDER CALLED ===
Return Date from $get: 2024-01-15
Items data from $get: [{"book_title":"Book","is_lost":true}]
Item 0: Book Title
  - is_lost from form: true
  - Overdue Fine: 10.00
  - LOST BOOK FEE CALCULATED: 25.00
  - Total Fine for this item: 35.00
PREVIEW TOTALS:
  - Grand Total: 35.00
=== END FEE PREVIEW ===
```

**Result:** Lost fee is calculated and included in grand total.

### Scenario B: Not Re-rendering ❌
(No logs appear when you toggle)

**Result:** Reactivity is broken. The preview component isn't being triggered to update.

### Scenario C: Re-renders But No Lost Fee ❌
```
=== FEE PREVIEW RENDER CALLED ===
Items data from $get: [{"book_title":"Book"}]
Item 0: Book Title
  - is_lost from form: false
  - is_lost is empty or false, no lost fee
  - Overdue Fine: 10.00
  - Total Fine for this item: 10.00
PREVIEW TOTALS:
  - Grand Total: 10.00
=== END FEE PREVIEW ===
```

**Result:** Preview is re-rendering, but the toggle state isn't being passed. Check the `$get()` path.

### Scenario D: Calculates Correctly But Browser Shows Wrong Total ❌
```
PREVIEW TOTALS:
  - Grand Total: 35.00
```

But browser shows $10.00.

**Result:** Backend is correct, frontend rendering issue. Check browser console for errors.

## Filament Reactivity Troubleshooting

### Understanding $get() Paths

The `fee_preview` Placeholder is inside a Section. The form structure is:
```
Form Root
└── Grid
    └── Section "Return Details"
        ├── DatePicker "returned_date"
        ├── Repeater "items"
        │   └── [item fields including is_lost]
        └── Placeholder "fee_preview"
```

From the Placeholder's perspective:
- `$get('../../returned_date')` - Go up 2 levels to Section, then to Grid, then access returned_date
- `$get('../../items')` - Same, but get items array

**Test if path is correct:**
Add this temporarily to renderFeePreview:
```php
\Log::info("Testing paths:");
\Log::info("returned_date: " . json_encode($get('../../returned_date')));
\Log::info("items: " . json_encode($get('../../items')));
\Log::info("Direct access: " . json_encode($get('../items'))); // Try different paths
```

## Next Steps Based on Logs

### If "FEE PREVIEW RENDER CALLED" appears every toggle:
✅ Reactivity is working! Check if `is_lost` value is correct.

### If it only appears on page load:
❌ Reactivity is broken. Check:
1. Repeater has `->live()`
2. Toggle has `->live()` and `->afterStateUpdated(fn() => null)`
3. Placeholder has `->live()`

### If `is_lost` is always false:
❌ Form state isn't being passed. Check:
1. The `$get()` path in renderFeePreview
2. Items data structure in logs

### If everything logs correctly but browser doesn't update:
❌ Frontend issue. Check:
1. Browser console for JS errors
2. Network tab - is the Livewire request happening?
3. Try different browser or incognito mode

## Contact Information

If you're still stuck after trying the above:
1. Share the output of `ddev exec tail -100 storage/logs/laravel.log | grep 'FEE PREVIEW' -A 100`
2. Share a screenshot of the browser showing the preview
3. Share browser console errors (F12 → Console tab)
4. Describe exactly what you're doing (step by step)

---

**Quick Test Command:**
```bash
# Clear logs, watch in real-time
ddev exec bash clear_logs.sh && ddev exec bash watch_fee_logs.sh
```

Then go toggle "Mark as Lost" and watch what happens!