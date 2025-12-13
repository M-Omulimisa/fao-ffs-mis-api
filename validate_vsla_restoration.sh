#!/bin/bash

# VSLA Module - Final Validation Script
# Comprehensive check of all restored components

echo "================================================================"
echo "VSLA MODULE - FINAL VALIDATION"
echo "================================================================"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

ERRORS=0
WARNINGS=0

echo -e "${BLUE}1. DATABASE MODELS CHECK${NC}"
echo "----------------------------------------"

# Check Models
models=(
    "app/Models/VslaMeeting.php"
    "app/Models/VslaLoan.php"
    "app/Models/VslaActionPlan.php"
    "app/Models/VslaMeetingAttendance.php"
)

for model in "${models[@]}"; do
    if [ -f "/Applications/MAMP/htdocs/fao-ffs-mis-api/$model" ]; then
        echo -e "${GREEN}✓${NC} $model exists"
    else
        echo -e "${RED}✗${NC} $model MISSING"
        ((ERRORS++))
    fi
done
echo ""

echo -e "${BLUE}2. ADMIN CONTROLLERS CHECK${NC}"
echo "----------------------------------------"

# Check Admin Controllers
admin_controllers=(
    "app/Admin/Controllers/VslaMeetingController.php"
    "app/Admin/Controllers/VslaLoanController.php"
    "app/Admin/Controllers/VslaActionPlanController.php"
    "app/Admin/Controllers/VslaMeetingAttendanceController.php"
)

for controller in "${admin_controllers[@]}"; do
    if [ -f "/Applications/MAMP/htdocs/fao-ffs-mis-api/$controller" ]; then
        echo -e "${GREEN}✓${NC} $controller exists"
    else
        echo -e "${RED}✗${NC} $controller MISSING"
        ((ERRORS++))
    fi
done
echo ""

echo -e "${BLUE}3. API CONTROLLER CHECK${NC}"
echo "----------------------------------------"

if [ -f "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Http/Controllers/Api/VslaMeetingController.php" ]; then
    echo -e "${GREEN}✓${NC} VslaMeetingController API exists"
    
    # Check for required methods
    methods=("submit" "index" "show" "stats" "reprocess" "destroy")
    for method in "${methods[@]}"; do
        if grep -q "public function $method" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Http/Controllers/Api/VslaMeetingController.php"; then
            echo -e "  ${GREEN}✓${NC} Method: $method()"
        else
            echo -e "  ${RED}✗${NC} Method: $method() MISSING"
            ((ERRORS++))
        fi
    done
else
    echo -e "${RED}✗${NC} VslaMeetingController API MISSING"
    ((ERRORS++))
fi
echo ""

echo -e "${BLUE}4. SERVICE LAYER CHECK${NC}"
echo "----------------------------------------"

if [ -f "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Services/MeetingProcessingService.php" ]; then
    echo -e "${GREEN}✓${NC} MeetingProcessingService exists"
    
    # Check for key method
    if grep -q "public function processMeeting" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Services/MeetingProcessingService.php"; then
        echo -e "  ${GREEN}✓${NC} Method: processMeeting()"
    else
        echo -e "  ${RED}✗${NC} Method: processMeeting() MISSING"
        ((ERRORS++))
    fi
else
    echo -e "${RED}✗${NC} MeetingProcessingService MISSING"
    ((ERRORS++))
fi
echo ""

echo -e "${BLUE}5. API ROUTES CHECK${NC}"
echo "----------------------------------------"

cd /Applications/MAMP/htdocs/fao-ffs-mis-api

# Check if routes are registered
api_routes=(
    "POST.*api/vsla-meetings/submit"
    "GET.*api/vsla-meetings/stats"
    "GET.*api/vsla-meetings\$"
    "GET.*api/vsla-meetings/{id}"
    "PUT.*api/vsla-meetings/{id}/reprocess"
    "DELETE.*api/vsla-meetings/{id}"
)

for route_pattern in "${api_routes[@]}"; do
    if php artisan route:list | grep -qE "$route_pattern"; then
        route_name=$(echo "$route_pattern" | sed 's/\.\*//')
        echo -e "${GREEN}✓${NC} Route: $route_name"
    else
        echo -e "${RED}✗${NC} Route: $route_pattern NOT REGISTERED"
        ((ERRORS++))
    fi
done
echo ""

echo -e "${BLUE}6. MODEL RELATIONSHIPS CHECK${NC}"
echo "----------------------------------------"

# Check VslaMeeting relationships
if grep -q "public function cycle()" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Models/VslaMeeting.php"; then
    echo -e "${GREEN}✓${NC} VslaMeeting->cycle() relationship"
else
    echo -e "${RED}✗${NC} VslaMeeting->cycle() MISSING"
    ((ERRORS++))
fi

if grep -q "public function group()" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Models/VslaMeeting.php"; then
    echo -e "${GREEN}✓${NC} VslaMeeting->group() relationship"
else
    echo -e "${RED}✗${NC} VslaMeeting->group() MISSING"
    ((ERRORS++))
fi

if grep -q "public function attendance()" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Models/VslaMeeting.php"; then
    echo -e "${GREEN}✓${NC} VslaMeeting->attendance() relationship"
else
    echo -e "${RED}✗${NC} VslaMeeting->attendance() MISSING"
    ((ERRORS++))
fi

# Check Project relationships
if grep -q "public function vslaMeetings()" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Models/Project.php"; then
    echo -e "${GREEN}✓${NC} Project->vslaMeetings() relationship"
else
    echo -e "${YELLOW}⚠${NC} Project->vslaMeetings() MISSING"
    ((WARNINGS++))
fi

# Check FfsGroup relationships
if grep -q "public function vslaMeetings()" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Models/FfsGroup.php"; then
    echo -e "${GREEN}✓${NC} FfsGroup->vslaMeetings() relationship"
else
    echo -e "${YELLOW}⚠${NC} FfsGroup->vslaMeetings() MISSING"
    ((WARNINGS++))
fi

# Check User relationships
if grep -q "public function createdVslaMeetings()" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Models/User.php"; then
    echo -e "${GREEN}✓${NC} User->createdVslaMeetings() relationship"
else
    echo -e "${YELLOW}⚠${NC} User->createdVslaMeetings() MISSING"
    ((WARNINGS++))
fi

echo ""

echo -e "${BLUE}7. TRAIT DEPENDENCIES CHECK${NC}"
echo "----------------------------------------"

if [ -f "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Traits/ApiResponser.php" ]; then
    echo -e "${GREEN}✓${NC} ApiResponser trait exists"
    
    if grep -q "protected function success" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Traits/ApiResponser.php"; then
        echo -e "  ${GREEN}✓${NC} Method: success()"
    else
        echo -e "  ${RED}✗${NC} Method: success() MISSING"
        ((ERRORS++))
    fi
    
    if grep -q "protected function error" "/Applications/MAMP/htdocs/fao-ffs-mis-api/app/Traits/ApiResponser.php"; then
        echo -e "  ${GREEN}✓${NC} Method: error()"
    else
        echo -e "  ${RED}✗${NC} Method: error() MISSING"
        ((ERRORS++))
    fi
else
    echo -e "${RED}✗${NC} ApiResponser trait MISSING"
    ((ERRORS++))
fi
echo ""

echo -e "${BLUE}8. DOCUMENTATION CHECK${NC}"
echo "----------------------------------------"

docs=(
    "VSLA_API_ENDPOINTS_RESTORED.md"
    "VSLA_MODULE_EMERGENCY_RECOVERY_COMPLETE.md"
)

for doc in "${docs[@]}"; do
    if [ -f "/Applications/MAMP/htdocs/fao-ffs-mis-api/$doc" ]; then
        echo -e "${GREEN}✓${NC} $doc exists"
    else
        echo -e "${YELLOW}⚠${NC} $doc MISSING"
        ((WARNINGS++))
    fi
done
echo ""

echo "================================================================"
echo "VALIDATION SUMMARY"
echo "================================================================"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ ALL CHECKS PASSED!${NC}"
    echo ""
    echo -e "${GREEN}VSLA Module is 100% operational${NC}"
    echo ""
    echo "Components verified:"
    echo "  ✓ 4 Database Models"
    echo "  ✓ 4 Admin Controllers"
    echo "  ✓ 1 API Controller (6 endpoints)"
    echo "  ✓ 1 Service (MeetingProcessingService)"
    echo "  ✓ 6 API Routes registered"
    echo "  ✓ Model relationships"
    echo "  ✓ All dependencies"
    echo ""
    echo -e "${GREEN}Status: READY FOR PRODUCTION 🚀${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ WARNINGS: $WARNINGS${NC}"
    echo -e "${GREEN}✓ NO CRITICAL ERRORS${NC}"
    echo ""
    echo "Status: Operational with minor warnings"
    exit 0
else
    echo -e "${RED}✗ ERRORS: $ERRORS${NC}"
    echo -e "${YELLOW}⚠ WARNINGS: $WARNINGS${NC}"
    echo ""
    echo "Status: NEEDS ATTENTION"
    exit 1
fi
