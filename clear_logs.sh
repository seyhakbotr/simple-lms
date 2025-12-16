#!/bin/bash

# Clear logs and prepare for fee preview testing

echo "========================================="
echo "Clearing Laravel Logs"
echo "========================================="
echo ""

# Clear the Laravel log file
ddev exec truncate -s 0 storage/logs/laravel.log

echo "✅ Logs cleared!"
echo ""
echo "Now you can:"
echo "1. Go to your browser and navigate to a transaction"
echo "2. Click 'Return' action"
echo "3. Toggle 'Mark as Lost' checkbox"
echo "4. Check logs with: ddev exec tail -100 storage/logs/laravel.log | grep 'FEE PREVIEW' -A 50"
echo ""
echo "Or run the log watcher: ddev exec bash watch_fee_logs.sh"
echo ""
echo "========================================="
