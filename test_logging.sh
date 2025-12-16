#!/bin/bash

# Quick test to verify logging is working

echo "========================================="
echo "Fee Preview Logging Test"
echo "========================================="
echo ""

# Clear logs
echo "Clearing old logs..."
ddev exec truncate -s 0 storage/logs/laravel.log

echo "✅ Logs cleared"
echo ""
echo "Now:"
echo "1. Go to your browser"
echo "2. Navigate to a transaction and click 'Return'"
echo "3. Toggle 'Mark as Lost' ON"
echo "4. Press Enter here to see the logs"
echo ""
read -p "Press Enter after you've toggled 'Mark as Lost'..."

echo ""
echo "========================================="
echo "Log Output:"
echo "========================================="
echo ""

# Show the logs
ddev exec tail -200 storage/logs/laravel.log | grep -E "FEE PREVIEW|is_lost|LOST BOOK FEE|Grand Total" -A 2

echo ""
echo "========================================="
echo "Analysis:"
echo "========================================="

# Count how many times preview was called
PREVIEW_COUNT=$(ddev exec tail -200 storage/logs/laravel.log | grep -c "FEE PREVIEW RENDER CALLED" || echo "0")
echo "Preview rendered: $PREVIEW_COUNT time(s)"

# Check if lost fee was calculated
LOST_FEE_COUNT=$(ddev exec tail -200 storage/logs/laravel.log | grep -c "LOST BOOK FEE CALCULATED" || echo "0")
if [ "$LOST_FEE_COUNT" -gt 0 ]; then
    echo "✅ Lost fee WAS calculated ($LOST_FEE_COUNT time(s))"
else
    echo "❌ Lost fee was NOT calculated"
fi

# Show grand total
GRAND_TOTAL=$(ddev exec tail -200 storage/logs/laravel.log | grep "Grand Total" | tail -1)
if [ -n "$GRAND_TOTAL" ]; then
    echo "$GRAND_TOTAL"
else
    echo "❌ No grand total found in logs"
fi

echo ""
echo "========================================="
echo "Next Steps:"
echo "========================================="

if [ "$PREVIEW_COUNT" -eq 0 ]; then
    echo "⚠️  Preview never rendered - reactivity is broken"
    echo "   Check that Repeater and Toggles have ->live()"
elif [ "$LOST_FEE_COUNT" -eq 0 ]; then
    echo "⚠️  Preview rendered but no lost fee calculated"
    echo "   The is_lost toggle state isn't being read"
    echo "   Check the \$get() path in renderFeePreview"
else
    echo "✅ Everything appears to be working in the backend"
    echo "   If the browser doesn't show the updated total,"
    echo "   check browser console for JavaScript errors"
fi

echo ""
