# Weather Module Testing Guide

## Quick Start

### 1. Hot Reload/Restart
After the code changes:
```bash
# In your terminal (if app is running)
r  # Hot reload
R  # Hot restart (recommended for database changes)
```

Or in VS Code:
- Press `Cmd+S` to save all files
- Press `Cmd+Shift+P` → "Flutter: Hot Restart"

### 2. Test Database Initialization
1. Open the app
2. Check console for database initialization logs
3. Should see: "WeatherForecast table created successfully"

### 3. Access Weather Module

**From Home Tab:**
1. Open app → Home tab (default)
2. Scroll to "Quick Actions" section
3. Tap "Weather Updates" button (cloud icon)

**From More Tab:**
1. Open app → More tab (bottom navigation)
2. Scroll to "Weather" section
3. Tap "Weather Forecast" (blue cloud icon)

### 4. Test GPS Location
1. When WeatherScreen opens:
   - Should request location permission (first time only)
   - Grant "Allow While Using App" permission
2. Loading indicator should appear
3. Weather data should load within 2-5 seconds
4. Verify:
   - Location name displayed (e.g., "Kampala, Uganda")
   - Current temperature in Celsius
   - Weather icon loaded
   - Sunrise/sunset times correct
   - 7-day forecast showing

### 5. Test Caching
1. Load weather once (with internet)
2. Turn on airplane mode
3. Close and reopen WeatherScreen
4. Should show cached weather data (< 6 hours old)
5. Pull to refresh → Should show error but keep cached data

### 6. Test Pull-to-Refresh
1. With internet connected
2. Swipe down from top of content area
3. Loading indicator should appear
4. Weather data should refresh
5. Toast: "Weather updated" (green)

### 7. Test Error Handling
1. Turn off WiFi/mobile data
2. Clear app data (or wait 24+ hours for cache expiry)
3. Open WeatherScreen
4. Should show error: "Failed to fetch weather data"
5. "Retry" button should appear
6. Tap Retry → Should try again

## Expected Behavior

### First Load (No Cache)
```
1. Permission dialog appears (location)
2. User grants permission
3. GPS determines location (2-5 seconds)
4. API call to OpenWeather (1-3 seconds)
5. Data saved to SQLite
6. UI displays weather
```

### Subsequent Loads (With Cache < 6 hours)
```
1. Check SQLite cache
2. Data is fresh (< 6 hours)
3. Display cached data immediately (< 100ms)
4. No API call needed
```

### Refresh (Force Update)
```
1. User pulls down to refresh
2. API call to OpenWeather
3. Update SQLite cache
4. Display new data
5. Toast notification
```

## Console Logs to Watch For

### Success Logs
```
✅ WeatherForecast table created successfully
✅ Weather data fetched successfully
✅ Weather data saved to cache
✅ Forecast is fresh, using cached data
📍 Location: Kampala, Uganda (0.3476, 32.5825)
```

### Error Logs
```
❌ Error fetching weather: DioException
❌ Error saving WeatherForecast: ...
❌ Location permission denied
❌ No cached data available
```

## UI Elements to Verify

### Header Section
- [ ] Blue gradient background (0xFF418FDE → 0xFF2C5AA0)
- [ ] Back button (top left)
- [ ] "Weather Forecast" title (centered)
- [ ] Refresh button (top right)
- [ ] Location name with pin icon

### Current Weather Card
- [ ] White card with shadow
- [ ] Large temperature (64px, e.g., "25°C")
- [ ] Weather icon from OpenWeather
- [ ] Condition text (e.g., "Clear sky")
- [ ] Min/max temps (e.g., "18°C / 28°C")

### Weather Details Grid (2×4)
- [ ] Sunrise time with icon
- [ ] Sunset time with icon
- [ ] Wind speed with direction arrow
- [ ] Humidity percentage with droplet icon
- [ ] Cloud cover with cloud icon
- [ ] Precipitation probability with umbrella icon

### 7-Day Forecast
- [ ] Scrollable list of cards
- [ ] Today, Tomorrow, then day names
- [ ] Date for each day
- [ ] Weather icon
- [ ] High/Low temperatures
- [ ] Proper spacing between cards

## Common Issues & Solutions

### Issue: "Location permission denied"
**Solution**: Go to Settings → Apps → FAO FFS MIS → Permissions → Location → Allow

### Issue: Weather icons not loading
**Solution**: Check internet connection, icons are cached after first load

### Issue: Temperature shows 298°C
**Solution**: Temperature conversion error - should be `temp - 273.15`, not `temp - 273`

### Issue: "No cached data available"
**Solution**: First load requires internet, try with WiFi/mobile data enabled

### Issue: App crashes on weather screen
**Solution**: Check console for errors, likely database initialization issue

### Issue: Weather data is old
**Solution**: Pull to refresh, or wait > 6 hours for auto-refresh

## Testing Checklist

### Basic Functionality
- [ ] App launches without errors
- [ ] Database initializes correctly
- [ ] Navigation from home tab works
- [ ] Navigation from more tab works
- [ ] Location permission request appears
- [ ] GPS location detected correctly
- [ ] Weather data loads successfully
- [ ] Temperature in Celsius (not Kelvin)
- [ ] Weather icons display correctly
- [ ] 7-day forecast shows all days

### Caching
- [ ] First load saves to SQLite
- [ ] Second load uses cached data (< 6 hours)
- [ ] Offline mode shows cached data
- [ ] Old cache (> 24 hours) is deleted
- [ ] Stale cache (6-24 hours) shows with update attempt

### UI/UX
- [ ] Loading indicator appears during fetch
- [ ] Error state shows with retry button
- [ ] Pull-to-refresh works smoothly
- [ ] Location picker button shows toast
- [ ] Back button returns to previous screen
- [ ] Refresh button updates data
- [ ] Smooth scrolling in forecast list
- [ ] Proper spacing and alignment
- [ ] Colors match FAO theme

### Error Handling
- [ ] No internet: Shows error, keeps cached data
- [ ] Permission denied: Shows error message
- [ ] Invalid coordinates: Shows error
- [ ] API error: Shows error with retry
- [ ] Database error: Graceful handling

### Performance
- [ ] Initial load < 5 seconds (with GPS)
- [ ] Cache hit < 100ms
- [ ] Smooth animations
- [ ] No lag when scrolling
- [ ] Images load efficiently

## Device-Specific Testing

### Android
- [ ] Location permission dialog (Android 10+)
- [ ] Background location (if needed)
- [ ] Different screen sizes
- [ ] Different Android versions

### iOS
- [ ] Location permission dialog (iOS 13+)
- [ ] "While Using" vs "Always"
- [ ] Different iPhone models
- [ ] Different iOS versions

## Production Readiness

Before releasing to production:
1. [ ] Test on multiple devices
2. [ ] Test with poor network conditions
3. [ ] Test offline mode thoroughly
4. [ ] Verify API key is valid
5. [ ] Check API usage limits
6. [ ] Test permission flows
7. [ ] Verify all UI elements
8. [ ] Test error scenarios
9. [ ] Check performance metrics
10. [ ] Get user feedback

## Monitoring

After release, monitor:
- API call frequency (should be < once per 6 hours per user)
- Cache hit rate (should be > 80%)
- Error rates
- User feedback on accuracy
- Performance metrics

## Next Steps After Testing

1. **If all tests pass**: Mark module as production-ready
2. **If issues found**: Document and fix before release
3. **User feedback**: Gather and prioritize enhancement requests
4. **Location picker**: Implement manual location selection
5. **Weather alerts**: Add severe weather notifications
6. **Farming advice**: Integrate weather-based recommendations

---

**Happy Testing! 🌤️**
