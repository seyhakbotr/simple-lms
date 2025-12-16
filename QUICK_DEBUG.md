# Quick Debug Reference: Fee Preview Not Updating

## The Problem
"Mark as Lost" toggle doesn't update the grand total in fee preview.

## Quick Test (60 seconds)

```bash
ddev exec bash test_logging.sh
```

Then follow the prompts. It will tell you what's wrong.

## Manual Debug

### Step 1: Clear Logs
```bash
ddev exec bash clear_logs.sh
```

### Step 2: Test in Browser
1. Go to Transactions
2. Click "Return" on a borrowed transaction
3. Toggle "Mark as Lost" ON

### Step 3: Check Logs
```bash
ddev exec tail -100 storage/logs/laravel.log | grep 'FEE PREVIEW' -A 50
```

## What to Look For

### ✅ Working
```
=== FEE PREVIEW RENDER CALLED ===
Items data from $get: [{"book_title":"X","is_lost":true}]
  - is_lost from form: true
  - LOST BOOK FEE CALCULATED: 25.00
  - Grand Total: 35.00
```

### ❌ Preview Not Re-rendering
```
(No logs appear when you toggle)
```
**Fix:** Check `->live()` on Repeater and Toggles. Clear cache.

### ❌ State Not Being Passed
```
=== FEE PREVIEW RENDER CALLED ===
Items data from $get: [{"book_title":"X"}]  ← is_lost missing!
  - is_lost from form: false
  - Grand Total: 10.00
```
**Fix:** The `$get()` path is wrong or field name doesn't match.

### ❌ Backend OK, Browser Shows Wrong Value
```
Logs show: Grand Total: 35.00
Browser shows: $10.00
```
**Fix:** Frontend issue. Check browser console (F12) for errors.

## Quick Fixes

### Clear Everything
```bash
ddev exec php artisan cache:clear
ddev exec php artisan view:clear
ddev exec php artisan config:clear
```
Then hard refresh browser (Ctrl+Shift+R).

### Watch Logs Live
```bash
ddev exec bash watch_fee_logs.sh
```
Keep this running while you toggle the checkbox.

## Files with Logging
- `app/Filament/Admin/Resources/TransactionResource/Pages/ReturnTransaction.php`
- `app/Filament/Staff/Resources/TransactionResource/Pages/ReturnTransaction.php`

## What I Need to Help You
1. Output of: `ddev exec bash test_logging.sh`
2. Screenshot of the fee preview in browser
3. Browser console errors (F12 → Console tab)

## Common Causes
1. **No reactivity** → Missing `->live()` on Repeater
2. **Wrong path** → `$get("../../items")` might need adjustment
3. **Cache** → Old cached views/components
4. **Browser** → JS error preventing UI update

---

**Start here:** `ddev exec bash test_logging.sh`
