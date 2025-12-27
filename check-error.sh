#!/bin/bash

# Script untuk melihat error terbaru dari Laravel log
# Usage: ./check-error.sh

echo "=== Checking Latest Laravel Errors ==="
echo ""

# Cek apakah file log ada
if [ ! -f "storage/logs/laravel.log" ]; then
    echo "Error: storage/logs/laravel.log not found"
    exit 1
fi

# Tampilkan 100 baris terakhir dengan konteks error
echo "--- Last 100 lines of log (showing recent errors) ---"
tail -n 100 storage/logs/laravel.log | grep -A 20 -B 5 -i "error\|exception\|fatal\|warning" | tail -n 50

echo ""
echo "--- Full last error entry ---"
# Ambil error terakhir (dari [ sampai {main})
tail -n 200 storage/logs/laravel.log | grep -A 100 "\[" | tail -n 60

echo ""
echo "=== To see real-time errors, run: ==="
echo "tail -f storage/logs/laravel.log"





