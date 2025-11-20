# Medical Service Request Module - Complete Implementation 🏥

## Overview
A comprehensive medical service request system that allows users to request medical services, track their status, and receive admin feedback. Fully integrated with the insurance module.

---

## Database Schema

### Table: `medical_service_requests`

**Core Fields:**
- `id` - Primary key
- `user_id` - FK to users table (centralized user management)
- `insurance_subscription_id` - FK to insurance_subscriptions (nullable)
- `reference_number` - Unique auto-generated (MSR-XXXXXXXXXX)

**Service Details:**
- `service_type` - consultation, emergency, lab_test, prescription, surgery, dental, optical, physiotherapy, mental_health, maternity, vaccination, other
- `service_category` - general, specialist, dental, optical, etc.
- `urgency_level` - emergency, urgent, normal
- `symptoms_description` - TEXT (required, min 10 chars)
- `additional_notes` - TEXT (optional)

**Preferred Details:**
- `preferred_hospital` - String (nullable)
- `preferred_doctor` - String (nullable)
- `preferred_date` - Date (nullable, must be >= today)
- `preferred_time` - Time (nullable, format: HH:mm)

**Contact Information:**
- `contact_phone` - String (required)
- `contact_email` - String (nullable, email format)
- `contact_address` - TEXT (nullable)

**Request Status:**
- `status` - ENUM: pending, approved, rejected, in_progress, completed, cancelled
- `admin_feedback` - TEXT (nullable)
- `reviewed_by` - FK to users (admin who reviewed)
- `reviewed_at` - Timestamp (when reviewed)

**Hospital Assignment (Admin fills after approval):**
- `assigned_hospital` - String (nullable)
- `assigned_doctor` - String (nullable)
- `scheduled_date` - Date (nullable)
- `scheduled_time` - Time (nullable)
- `appointment_details` - TEXT (nullable)

**Cost & Coverage:**
- `estimated_cost` - DECIMAL(15,2) (nullable)
- `insurance_coverage` - DECIMAL(15,2) (nullable)
- `patient_payment` - DECIMAL(15,2) (nullable)

**Attachments:**
- `attachments` - JSON array (nullable) - medical reports, prescriptions, etc.

**Meta:**
- `ip_address` - String (auto-captured)
- `user_agent` - TEXT (auto-captured)
- `created_at` - Timestamp
- `updated_at` - Timestamp
- `deleted_at` - Timestamp (soft delete)

**Indexes:**
- user_id, insurance_subscription_id, reviewed_by, status, service_type, urgency_level, reference_number, created_at

---

## Model: MedicalServiceRequest

**Location:** `app/Models/MedicalServiceRequest.php`

**Features:**
- ✅ Auto-generates unique reference number (MSR-XXXXXXXXXX)
- ✅ Soft deletes enabled
- ✅ JSON casting for attachments
- ✅ Date casting for preferred_date, scheduled_date
- ✅ Decimal casting for costs (2 decimal places)

**Relationships:**
- `user()` - belongsTo User
- `insuranceSubscription()` - belongsTo InsuranceSubscription
- `reviewer()` - belongsTo User (admin who reviewed)

**Scopes:**
- `pending()` - Get pending requests
- `approved()` - Get approved requests
- `emergency()` - Get emergency requests
- `urgent()` - Get urgent requests
- `forUser($userId)` - Get requests for specific user

**Accessors (Auto-Appended):**
- `status_label` - Human readable status
- `urgency_label` - Human readable urgency
- `service_type_label` - Human readable service type

**Helper Methods:**
- `isPending()` - Check if pending
- `isApproved()` - Check if approved
- `isRejected()` - Check if rejected
- `isCompleted()` - Check if completed
- `isEmergency()` - Check if emergency

---

## API Endpoints

### Base URL: `/api/medical-service-requests`

### 1. **List All Requests (with filters)**
**GET** `/`

**Query Parameters:**
- `user_id` - Filter by user
- `status` - Filter by status (pending, approved, rejected, etc.)
- `urgency_level` - Filter by urgency (emergency, urgent, normal)
- `service_type` - Filter by service type
- `search` - Search in reference_number, symptoms, hospital
- `from_date` - Date range start
- `to_date` - Date range end
- `per_page` - Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "message": "Medical service requests retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 100,
    "per_page": 15
  }
}
```

---

### 2. **Create New Request**
**POST** `/`

**Required Fields:**
```json
{
  "user_id": 1,
  "service_type": "consultation",
  "urgency_level": "normal",
  "symptoms_description": "Description of symptoms (min 10 chars)",
  "contact_phone": "+256700000000"
}
```

**Optional Fields:**
- insurance_subscription_id
- service_category
- additional_notes
- preferred_hospital
- preferred_doctor
- preferred_date (YYYY-MM-DD, >= today)
- preferred_time (HH:mm format)
- contact_email
- contact_address
- attachments (array)

**Response:**
```json
{
  "success": true,
  "message": "Medical service request submitted successfully",
  "data": {
    "id": 1,
    "reference_number": "MSR-FECRBCZXEI",
    "status": "pending",
    "status_label": "Pending Review",
    "user": {...},
    "insurance_subscription": {...}
  }
}
```

---

### 3. **Get Single Request**
**GET** `/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Medical service request retrieved successfully",
  "data": {
    "id": 1,
    "reference_number": "MSR-FECRBCZXEI",
    "status": "pending",
    "user": {...},
    "insurance_subscription": {...},
    "reviewer": {...}
  }
}
```

---

### 4. **Get by Reference Number**
**GET** `/reference/{reference}`

Example: `/reference/MSR-FECRBCZXEI`

**Response:** Same as Get Single Request

---

### 5. **Get User's Requests**
**GET** `/user/{userId}`

**Response:** Paginated list of user's requests

---

### 6. **Update Request (User - Only if Pending)**
**PUT** `/{id}` or **PATCH** `/{id}`

**Allowed Fields:** (Only if status is 'pending')
- service_type
- service_category
- urgency_level
- symptoms_description
- additional_notes
- preferred_hospital
- preferred_doctor
- preferred_date
- preferred_time
- contact_phone
- contact_email
- contact_address
- attachments

**Response:**
```json
{
  "success": true,
  "message": "Medical service request updated successfully",
  "data": {...}
}
```

**Error Response (if not pending):**
```json
{
  "success": false,
  "message": "Cannot update request after it has been reviewed"
}
```

---

### 7. **Review Request (Admin Only)**
**POST** `/{id}/review`

**Required Fields:**
```json
{
  "status": "approved",
  "admin_feedback": "Feedback message (min 10 chars)",
  "reviewed_by": 1
}
```

**Optional Fields:**
- assigned_hospital
- assigned_doctor
- scheduled_date
- scheduled_time
- appointment_details
- estimated_cost
- insurance_coverage
- patient_payment

**Status Options:** approved, rejected, in_progress, completed

**Response:**
```json
{
  "success": true,
  "message": "Medical service request reviewed successfully",
  "data": {
    "id": 1,
    "status": "approved",
    "admin_feedback": "...",
    "reviewed_at": "2025-10-28 07:00:00",
    "reviewer": {...}
  }
}
```

---

### 8. **Cancel Request (User)**
**POST** `/{id}/cancel`

**Conditions:** Cannot cancel if status is 'completed' or 'cancelled'

**Response:**
```json
{
  "success": true,
  "message": "Medical service request cancelled successfully",
  "data": {...}
}
```

---

### 9. **Delete Request**
**DELETE** `/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Medical service request deleted successfully"
}
```

---

### 10. **Get Statistics**
**GET** `/stats`

**Query Parameters:**
- `user_id` - Filter stats for specific user

**Response:**
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "total_requests": 100,
    "pending": 25,
    "approved": 40,
    "rejected": 5,
    "in_progress": 15,
    "completed": 10,
    "cancelled": 5,
    "emergency": 10,
    "urgent": 30,
    "normal": 60
  }
}
```

---

## Service Types & Labels

### Available Service Types:
1. **consultation** → "Medical Consultation"
2. **emergency** → "Emergency Service"
3. **lab_test** → "Laboratory Test"
4. **prescription** → "Prescription Refill"
5. **surgery** → "Surgery"
6. **dental** → "Dental Service"
7. **optical** → "Optical Service"
8. **physiotherapy** → "Physiotherapy"
9. **mental_health** → "Mental Health Service"
10. **maternity** → "Maternity Service"
11. **vaccination** → "Vaccination"
12. **other** → "Other Service"

---

## Status Flow

```
User Creates Request
        ↓
    [PENDING] ← Initial status
        ↓
    Admin Reviews
        ↓
   ┌────┴────┬────────┬─────────┐
   ↓         ↓        ↓         ↓
[APPROVED] [REJECTED] [IN_PROGRESS] [COMPLETED]
   ↓
User Can Cancel (if not completed/cancelled)
   ↓
[CANCELLED]
```

**Status Descriptions:**
- **pending** - Waiting for admin review
- **approved** - Admin approved, appointment scheduled
- **rejected** - Admin rejected with feedback
- **in_progress** - Service currently being provided
- **completed** - Service completed successfully
- **cancelled** - User cancelled the request

---

## Urgency Levels

1. **emergency** 🚨 - Immediate attention required
2. **urgent** ⚠️ - Priority service
3. **normal** ✅ - Standard service

---

## Integration with Insurance Module

### Automatic Linking:
- When creating a request, include `insurance_subscription_id`
- System automatically calculates coverage if user has active subscription
- `insurance_coverage` and `patient_payment` calculated by admin during review

### Insurance Coverage Flow:
1. User has active insurance subscription
2. Requests medical service with `insurance_subscription_id`
3. Admin reviews and sets:
   - `estimated_cost` - Total service cost
   - `insurance_coverage` - Amount covered by insurance
   - `patient_payment` - Amount patient must pay

---

## Usage Examples

### Example 1: Emergency Request
```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/medical-service-requests" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "insurance_subscription_id": 1,
    "service_type": "emergency",
    "urgency_level": "emergency",
    "symptoms_description": "Severe chest pain, difficulty breathing",
    "contact_phone": "+256700000000"
  }'
```

### Example 2: Admin Approval
```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/medical-service-requests/1/review" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "approved",
    "admin_feedback": "Request approved. Please visit the assigned hospital.",
    "assigned_hospital": "Mulago Hospital",
    "assigned_doctor": "Dr. John Doe",
    "scheduled_date": "2025-10-30",
    "scheduled_time": "10:00",
    "estimated_cost": 150000,
    "insurance_coverage": 120000,
    "patient_payment": 30000,
    "reviewed_by": 1
  }'
```

### Example 3: Get Pending Requests
```bash
curl -X GET "http://localhost:8888/dtehm-insurance-api/api/medical-service-requests?status=pending&urgency_level=emergency"
```

---

## Flutter Integration (Next Steps)

### Models to Create:
1. `MedicalServiceRequest` model
2. `ServiceTypeModel` enum
3. `UrgencyLevelModel` enum

### Screens to Create:
1. **MedicalServiceRequestListScreen** - View all requests
2. **MedicalServiceRequestCreateScreen** - Create new request
3. **MedicalServiceRequestDetailsScreen** - View single request details
4. **MedicalServiceRequestStatusScreen** - Track request status

### Integration Points:
- Add "Request Medical Service" button to insurance dashboard
- Show active requests in dashboard
- Display request statistics
- Notification when request status changes

---

## Testing Checklist

### Backend Tests:
- ✅ Create request with all fields
- ✅ Create request with minimum fields
- ✅ Retrieve all requests
- ✅ Filter by user_id
- ✅ Filter by status
- ✅ Search by reference number
- ✅ Update pending request
- ✅ Cannot update reviewed request
- ✅ Admin review/approve
- ✅ Admin reject
- ✅ User cancel
- ✅ Get statistics
- ✅ Get user requests

### Frontend Tests (Pending):
- [ ] Create request form
- [ ] View request list
- [ ] View request details
- [ ] Track request status
- [ ] Cancel request
- [ ] Admin review interface

---

## Security Considerations

1. **Authorization:**
   - Only request owner can update/cancel their requests
   - Only admins can review requests
   - Check user permissions before operations

2. **Validation:**
   - All inputs validated server-side
   - Date validations (preferred_date >= today)
   - Required fields enforced
   - Email format validation

3. **Data Protection:**
   - Soft deletes preserve data
   - Sensitive medical info protected
   - IP address and user agent logged for audit

---

## Performance Optimizations

1. **Database:**
   - Indexes on frequently queried columns
   - Eager loading relationships (with clause)
   - Pagination for large result sets

2. **API:**
   - Filter options to reduce payload
   - Efficient queries with query builder
   - Response caching (future enhancement)

---

## Future Enhancements

1. **File Upload:** Support for medical document attachments
2. **Real-time Notifications:** Push notifications on status changes
3. **Doctor Portal:** Separate interface for doctors
4. **Appointment Reminders:** SMS/Email reminders
5. **Telemedicine:** Video consultation integration
6. **Medical History:** Link with patient medical records
7. **Analytics Dashboard:** Admin analytics and reports
8. **Rating System:** User feedback on services

---

## System Status

**Backend:** ✅ 100% Complete
**Database:** ✅ Created & Tested
**API Endpoints:** ✅ All Working
**Flutter Models:** ⏳ Pending
**Flutter Screens:** ⏳ Pending
**Integration:** ⏳ Pending

---

**Created:** 2025-10-28
**Version:** 1.0.0
**Status:** Production Ready (Backend)
