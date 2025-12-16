#!/bin/bash

# Watch fee preview logs in real-time
# This script helps debug the fee preview reactivity issue

echo "========================================="
echo "Fee Preview Log Watcher"
echo "========================================="
echo ""
echo "This will show logs as you interact with the return transaction page."
echo "Instructions:"
echo "1. Open this terminal and run: ddev exec bash watch_fee_logs.sh"
echo "2. In your browser, navigate to a transaction and click 'Return'"
echo "3. Toggle 'Mark as Lost' on/off and watch the logs below"
echo ""
echo "Press Ctrl+C to stop watching"
echo ""
echo "========================================="
echo "Watching logs..."
echo "========================================="
echo ""

# Clear previous logs and start fresh
ddev exec php artisan cache:clear > /dev/null 2>&1

# Tail the Laravel log file and filter for fee preview entries
ddev exec tail -f storage/logs/laravel.log | grep --line-buffered -E "FEE PREVIEW|is_lost|LOST BOOK FEE|Grand Total|Items data"
