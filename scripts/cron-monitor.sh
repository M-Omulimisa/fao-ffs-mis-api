#!/bin/bash

# BlitXpress Cron Job Monitor
# Displays the status and recent activity of the img-compress cron job

LOG_FILE="/Applications/MAMP/htdocs/blitxpress/storage/logs/img-compress-cron.log"
ERROR_LOG="/Applications/MAMP/htdocs/blitxpress/storage/logs/img-compress-cron-errors.log"

echo "🗜️ BlitXpress Image Compression Cron Job Monitor"
echo "=================================================="

# Check if cron job is installed
echo "📅 Cron Job Status:"
if crontab -l | grep -q "img-compress-cron.sh"; then
    echo "✅ Cron job is installed and active"
    echo "   Schedule: Every 5 minutes (*/5 * * * *)"
else
    echo "❌ Cron job is NOT installed"
fi

echo ""

# Check if log files exist
echo "📝 Log Files:"
if [ -f "$LOG_FILE" ]; then
    log_size=$(du -h "$LOG_FILE" | cut -f1)
    echo "✅ Main log: $LOG_FILE ($log_size)"
else
    echo "❌ Main log file not found"
fi

if [ -f "$ERROR_LOG" ]; then
    error_size=$(du -h "$ERROR_LOG" | cut -f1)
    error_count=$(wc -l < "$ERROR_LOG")
    echo "⚠️  Error log: $ERROR_LOG ($error_size, $error_count entries)"
else
    echo "✅ No error log (good!)"
fi

echo ""

# Show recent activity
echo "🔄 Recent Activity (Last 10 entries):"
echo "======================================"
if [ -f "$LOG_FILE" ]; then
    tail -10 "$LOG_FILE" | while read line; do
        if echo "$line" | grep -q "SUCCESS"; then
            echo "✅ $line"
        elif echo "$line" | grep -q "ERROR"; then
            echo "❌ $line"
        elif echo "$line" | grep -q "CRON JOB START"; then
            echo "🚀 $line"
        else
            echo "ℹ️  $line"
        fi
    done
else
    echo "No log file found"
fi

echo ""

# Show recent errors if any
if [ -f "$ERROR_LOG" ] && [ -s "$ERROR_LOG" ]; then
    echo "⚠️  Recent Errors (Last 5):"
    echo "=========================="
    tail -5 "$ERROR_LOG"
    echo ""
fi

# Show statistics
echo "📊 Statistics:"
echo "=============="
if [ -f "$LOG_FILE" ]; then
    total_runs=$(grep -c "CRON JOB START" "$LOG_FILE")
    success_count=$(grep -c "SUCCESS:" "$LOG_FILE")
    error_count=0
    if [ -f "$ERROR_LOG" ]; then
        error_count=$(wc -l < "$ERROR_LOG")
    fi
    
    echo "Total runs: $total_runs"
    echo "Successful: $success_count"
    echo "Errors: $error_count"
    
    if [ $total_runs -gt 0 ]; then
        success_rate=$(echo "scale=1; $success_count * 100 / $total_runs" | bc -l 2>/dev/null || echo "N/A")
        echo "Success rate: $success_rate%"
    fi
    
    # Show last successful run
    last_success=$(grep "SUCCESS:" "$LOG_FILE" | tail -1 | cut -d']' -f1 | tr -d '[')
    if [ -n "$last_success" ]; then
        echo "Last successful run: $last_success"
    fi
fi

echo ""
echo "🔧 Commands:"
echo "==========="
echo "View live logs: tail -f $LOG_FILE"
echo "View errors: cat $ERROR_LOG"
echo "Test manually: /Applications/MAMP/htdocs/blitxpress/scripts/img-compress-cron.sh"
echo "Check cron status: crontab -l"