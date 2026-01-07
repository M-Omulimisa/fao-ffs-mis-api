# Location API - Quick Reference Guide

## 🚀 Quick Start

### For Backend Developers (PHP/Laravel)

```php
use App\Models\Location;

// Get all districts
$districts = Location::get_districts();

// Get sub-counties for district
$subCounties = Location::get_district_sub_counties($districtId);

// Get parishes for sub-county
$parishes = Location::get_parishes($subCountyId);

// Get location with full hierarchy
$location = Location::with('parent_location', 'children')->find($id);
echo $location->full_path; // "Central Region > Kampala District"
```

### For Mobile Developers (Flutter/Dart)

```dart
import 'package:fao_ffs_mis/services/location_service.dart';

// Get districts for dropdown
List<LocationModel> districts = await LocationService.getDistricts();

// When district selected, get its sub-counties
List<LocationModel> subCounties = 
    await LocationService.getSubCountiesByDistrict(districtId);

// When sub-county selected, get its parishes
List<LocationModel> parishes = 
    await LocationService.getParishesBySubCounty(subCountyId);
```

---

## 📡 API Endpoints

| Method | Endpoint | Description | Response |
|--------|----------|-------------|----------|
| GET | `/api/locations` | Get all locations | `{success, code, status, message, data[]}` |
| GET | `/api/locations/districts` | Get all districts | `{code, message, data[]}` |
| GET | `/api/locations/sub-counties` | Get all sub-counties | `{success, code, status, message, data[]}` |
| GET | `/api/locations/sub-counties/{id}` | Get sub-counties by district | `{success, code, status, message, data[]}` |
| GET | `/api/locations/parishes/{id}` | Get parishes by sub-county | `{success, code, status, message, data[]}` |
| GET | `/api/locations/{id}` | Get location details | `{success, code, status, message, data{}}` |

---

## 📊 Response Formats

### List Response
```json
{
  "success": true,
  "code": 1,
  "status": 1,
  "message": "Successfully retrieved locations",
  "data": [
    {
      "id": 2,
      "name": "Kampala District",
      "parent": 1,
      "type": "District",
      "code": null,
      "name_text": "Central Region, Kampala District"
    }
  ]
}
```

### Details Response (with relationships)
```json
{
  "success": true,
  "code": 1,
  "message": "Successfully retrieved location details",
  "data": {
    "id": 2,
    "name": "Kampala District",
    "parent": 1,
    "type": "District",
    "full_hierarchy": "Central Region > Kampala District",
    "parent_location": {
      "id": 1,
      "name": "Central Region",
      "type": "Region"
    },
    "children": [
      {
        "id": 3,
        "name": "Kawempe Division",
        "type": "Sub-county"
      }
    ]
  }
}
```

---

## 🎨 Flutter UI Examples

### Dropdown Example
```dart
class LocationDropdowns extends StatefulWidget {
  @override
  _LocationDropdownsState createState() => _LocationDropdownsState();
}

class _LocationDropdownsState extends State<LocationDropdowns> {
  List<LocationModel> districts = [];
  List<LocationModel> subCounties = [];
  List<LocationModel> parishes = [];
  
  int? selectedDistrictId;
  int? selectedSubCountyId;
  int? selectedParishId;

  @override
  void initState() {
    super.initState();
    _loadDistricts();
  }

  Future<void> _loadDistricts() async {
    try {
      final data = await LocationService.getDistricts();
      setState(() {
        districts = data;
      });
    } catch (e) {
      print('Error loading districts: $e');
    }
  }

  Future<void> _loadSubCounties(int districtId) async {
    try {
      final data = await LocationService.getSubCountiesByDistrict(districtId);
      setState(() {
        subCounties = data;
        parishes = []; // Clear parishes when district changes
        selectedSubCountyId = null;
        selectedParishId = null;
      });
    } catch (e) {
      print('Error loading sub-counties: $e');
    }
  }

  Future<void> _loadParishes(int subCountyId) async {
    try {
      final data = await LocationService.getParishesBySubCounty(subCountyId);
      setState(() {
        parishes = data;
        selectedParishId = null;
      });
    } catch (e) {
      print('Error loading parishes: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // District Dropdown
        DropdownButtonFormField<int>(
          value: selectedDistrictId,
          decoration: InputDecoration(labelText: 'District'),
          items: districts.map((district) {
            return DropdownMenuItem<int>(
              value: district.id,
              child: Text(district.name),
            );
          }).toList(),
          onChanged: (value) {
            setState(() {
              selectedDistrictId = value;
            });
            if (value != null) {
              _loadSubCounties(value);
            }
          },
        ),
        
        SizedBox(height: 16),
        
        // Sub-county Dropdown
        DropdownButtonFormField<int>(
          value: selectedSubCountyId,
          decoration: InputDecoration(labelText: 'Sub-county'),
          items: subCounties.map((subCounty) {
            return DropdownMenuItem<int>(
              value: subCounty.id,
              child: Text(subCounty.name),
            );
          }).toList(),
          onChanged: selectedDistrictId == null ? null : (value) {
            setState(() {
              selectedSubCountyId = value;
            });
            if (value != null) {
              _loadParishes(value);
            }
          },
        ),
        
        SizedBox(height: 16),
        
        // Parish Dropdown
        DropdownButtonFormField<int>(
          value: selectedParishId,
          decoration: InputDecoration(labelText: 'Parish'),
          items: parishes.map((parish) {
            return DropdownMenuItem<int>(
              value: parish.id,
              child: Text(parish.name),
            );
          }).toList(),
          onChanged: selectedSubCountyId == null ? null : (value) {
            setState(() {
              selectedParishId = value;
            });
          },
        ),
      ],
    );
  }
}
```

### Search/Filter Example
```dart
Future<void> searchLocations(String query) async {
  try {
    final allLocations = await LocationService.getAllLocations();
    
    final filtered = allLocations.where((location) {
      return location.name.toLowerCase().contains(query.toLowerCase()) ||
             (location.nameText?.toLowerCase().contains(query.toLowerCase()) ?? false);
    }).toList();
    
    setState(() {
      searchResults = filtered;
    });
  } catch (e) {
    print('Error searching locations: $e');
  }
}
```

---

## 🧪 Testing

### cURL Tests
```bash
# Test 1: Get all locations
curl http://localhost:8888/fao-ffs-mis-api/public/api/locations

# Test 2: Get districts
curl http://localhost:8888/fao-ffs-mis-api/public/api/locations/districts

# Test 3: Get sub-counties for Kampala (ID: 2)
curl http://localhost:8888/fao-ffs-mis-api/public/api/locations/sub-counties/2

# Test 4: Get parishes for Kawempe (ID: 3)
curl http://localhost:8888/fao-ffs-mis-api/public/api/locations/parishes/3

# Test 5: Get location details
curl http://localhost:8888/fao-ffs-mis-api/public/api/locations/2
```

### Flutter Unit Tests
```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:fao_ffs_mis/services/location_service.dart';

void main() {
  group('LocationService Tests', () {
    test('Get districts should return list of locations', () async {
      final districts = await LocationService.getDistricts();
      expect(districts, isNotEmpty);
      expect(districts.first.type, equals('District'));
    });

    test('Get sub-counties by district should return filtered list', () async {
      final subCounties = await LocationService.getSubCountiesByDistrict(2);
      expect(subCounties, isNotEmpty);
      expect(subCounties.first.parent, equals(2));
    });
  });
}
```

---

## 🐛 Common Issues & Solutions

### Issue 1: Empty Response
**Problem:** API returns `{data: []}`
**Solution:** 
- Check if locations exist in database
- Verify location types match exactly (case-sensitive)
- Run migration script if needed

### Issue 2: Type Mismatch Error
**Problem:** Flutter throws type error parsing response
**Solution:**
- Check LocationModel matches API response structure
- Verify field names (use `parent` not `parent_id`)
- Ensure nullable fields are marked with `?`

### Issue 3: Cascade Dropdowns Not Working
**Problem:** Sub-county dropdown doesn't populate when district selected
**Solution:**
- Ensure `onChanged` callback calls load method
- Clear dependent dropdowns when parent changes
- Check API endpoint returns data for selected parent

### Issue 4: "No such table" Error
**Problem:** Database query fails
**Solution:**
- Verify `locations` table exists
- Check table name in Location model matches database
- Run migrations if needed

---

## 📚 Additional Resources

### Documentation Files
- `LOCATION_API_TEST_RESULTS.md` - Full API test results
- `LOCATION_SYSTEM_INTEGRATION_COMPLETE.md` - Complete implementation details
- Location Model: `/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Models/Location.php`
- Location Service: `/Users/mac/Desktop/github/fao-ffs-mis-mobo/lib/services/location_service.dart`

### Database
- Table: `locations` (6 locations currently)
- Backup: `locations_backup_20260101_225251` (1797 locations)
- Mapping: `location_uuid_to_int_mapping_20260101_225251.json`

---

## ✅ Checklist for Using Location API

Backend Integration:
- [ ] Import Location model: `use App\Models\Location;`
- [ ] Use appropriate static method for your use case
- [ ] Handle empty results gracefully
- [ ] Cache results if querying frequently

Frontend Integration:
- [ ] Import LocationService and LocationModel
- [ ] Add loading states for async operations
- [ ] Implement error handling
- [ ] Clear dependent dropdowns on parent change
- [ ] Show user-friendly error messages
- [ ] Cache location data to reduce API calls

---

**Need Help?** Check the full documentation in `LOCATION_SYSTEM_INTEGRATION_COMPLETE.md`
