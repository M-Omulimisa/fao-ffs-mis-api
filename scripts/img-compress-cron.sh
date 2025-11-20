#!/bin/bash

# BlitXpress Image Compression Cron Job
# This script pings the img-compress endpoint every 5 minutes
# Author: GitHub Copilot
# Created: $(date)

# Configuration
URL="https://blit.blitxpress.com/img-compress"
LOG_DIR="/Applications/MAMP/htdocs/blitxpress/storage/logs"
LOG_FILE="$LOG_DIR/img-compress-cron.log"
ERROR_LOG="$LOG_DIR/img-compress-cron-errors.log"
TIMEOUT=300  # 5 minutes timeout
RETRY_COUNT=3

# Create log directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Function to log messages
log_message() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $message" >> "$LOG_FILE"
}

# Function to log errors
log_error() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] ERROR: $message" >> "$ERROR_LOG"
    echo "[$timestamp] ERROR: $message" >> "$LOG_FILE"
}

# Function to ping the endpoint
ping_endpoint() {
    local attempt=1
    local success=false
    
    while [ $attempt -le $RETRY_COUNT ] && [ "$success" = false ]; do
        log_message "Attempt $attempt/$RETRY_COUNT - Pinging $URL"
        
        # Capture start time
        start_time=$(date +%s)
        
        # Make the HTTP request with timeout
        response=$(curl -s -w "\n%{http_code}\n%{time_total}" \
            --max-time $TIMEOUT \
            --connect-timeout 30 \
            --insecure \
            --user-agent "BlitXpress-Cron-Job/1.0" \
            "$URL" 2>&1)
        
        curl_exit_code=$?
        end_time=$(date +%s)
        duration=$((end_time - start_time))
        
        if [ $curl_exit_code -eq 0 ]; then
            # Extract HTTP status code and response time from curl output
            http_code=$(echo "$response" | tail -n 2 | head -n 1)
            response_time=$(echo "$response" | tail -n 1)
            
            if [ "$http_code" = "200" ]; then
                log_message "SUCCESS: HTTP $http_code in ${response_time}s (attempt $attempt)"
                success=true
                
                # Log basic stats
                response_body=$(echo "$response" | sed '$d' | sed '$d')
                response_size=$(echo "$response_body" | wc -c)
                log_message "Response size: ${response_size} bytes"
                
                # Check if response contains expected content
                if echo "$response_body" | grep -q "Image Compression"; then
                    log_message "Response validation: PASSED (contains expected content)"
                else
                    log_message "Response validation: WARNING (unexpected content)"
                fi
                
            else
                log_error "HTTP error: $http_code (attempt $attempt)"
            fi
        else
            case $curl_exit_code in
                6)  log_error "Could not resolve host (attempt $attempt)" ;;
                7)  log_error "Failed to connect to host (attempt $attempt)" ;;
                28) log_error "Operation timeout after ${TIMEOUT}s (attempt $attempt)" ;;
                *)  log_error "Curl failed with exit code $curl_exit_code (attempt $attempt)" ;;
            esac
        fi
        
        if [ "$success" = false ] && [ $attempt -lt $RETRY_COUNT ]; then
            sleep_time=$((attempt * 10))  # Progressive backoff: 10s, 20s, 30s
            log_message "Waiting ${sleep_time}s before retry..."
            sleep $sleep_time
        fi
        
        attempt=$((attempt + 1))
    done
    
    if [ "$success" = false ]; then
        log_error "All $RETRY_COUNT attempts failed"
        return 1
    fi
    
    return 0
}

# Main execution
log_message "==================== CRON JOB START ===================="
log_message "Script: $0"
log_message "PID: $$"
log_message "User: $(whoami)"
log_message "Working directory: $(pwd)"

# Check network connectivity first
if ! ping -c 1 google.com > /dev/null 2>&1; then
    log_error "No internet connection detected"
    exit 1
fi

# Check if URL is reachable
if ! curl -s --head --max-time 10 "$URL" > /dev/null 2>&1; then
    log_error "URL $URL appears to be unreachable"
fi

# Execute the main ping function
if ping_endpoint; then
    log_message "Image compression endpoint ping completed successfully"
    exit 0
else
    log_error "Image compression endpoint ping failed after all retries"
    exit 1
fi