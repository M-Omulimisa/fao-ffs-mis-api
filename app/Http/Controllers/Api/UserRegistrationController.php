<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserRegistrationController extends Controller
{
    /**
     * Register a new user with role-based profiling
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $role = $request->input('role', 'Farmer');

            // Base validation rules (common fields)
            $rules = [
                'role' => 'required|string',
                'name' => 'required|string|min:3|max:255',
                'phone_number' => 'required|string|unique:users,phone_number',
                'email' => 'nullable|email|unique:users,email',
                'password' => 'required|string|min:4|confirmed',
                'sex' => 'nullable|in:Male,Female',
                'country' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500',
                'district' => 'nullable|string|max:100',
                'subcounty' => 'nullable|string|max:100',
                'parish' => 'nullable|string|max:100',
                'village' => 'nullable|string|max:100',
            ];

            // Add role-specific validation rules
            switch ($role) {
                case 'Farmer':
                    $rules = array_merge($rules, [
                        'farm_size' => 'nullable|numeric|min:0',
                        'crops_grown' => 'nullable|string|max:500',
                        'livestock_owned' => 'nullable|string|max:500',
                    ]);
                    break;

                case 'Service Provider':
                    $rules = array_merge($rules, [
                        'services_offered' => 'required|string|max:1000',
                        'certification' => 'nullable|string|max:255',
                        'years_experience' => 'nullable|integer|min:0',
                        'business_name' => 'required|string|max:255',
                        'business_license_number' => 'nullable|string|max:100',
                        'business_phone_number' => 'nullable|string|max:20',
                        'business_email' => 'nullable|email|max:255',
                    ]);
                    break;

                case 'Input Dealer':
                    $rules = array_merge($rules, [
                        'products_sold' => 'required|string|max:1000',
                        'supplier_license' => 'nullable|string|max:100',
                        'business_name' => 'required|string|max:255',
                        'business_license_number' => 'nullable|string|max:100',
                        'business_phone_number' => 'nullable|string|max:20',
                        'business_email' => 'nullable|email|max:255',
                    ]);
                    break;

                case 'Equipment Provider':
                    $rules = array_merge($rules, [
                        'equipment_types' => 'required|string|max:1000',
                        'rental_rates' => 'nullable|string|max:500',
                        'coverage_area' => 'nullable|string|max:255',
                        'business_name' => 'required|string|max:255',
                        'business_license_number' => 'nullable|string|max:100',
                        'business_phone_number' => 'nullable|string|max:20',
                        'business_email' => 'nullable|email|max:255',
                    ]);
                    break;

                case 'Output Market':
                    $rules = array_merge($rules, [
                        'market_type' => 'required|in:Retail,Wholesale,Export,Processing',
                        'capacity' => 'nullable|numeric|min:0',
                        'operating_hours' => 'nullable|string|max:255',
                        'business_name' => 'required|string|max:255',
                        'business_license_number' => 'nullable|string|max:100',
                        'business_phone_number' => 'nullable|string|max:20',
                        'business_email' => 'nullable|email|max:255',
                    ]);
                    break;

                case 'Aggregator':
                    $rules = array_merge($rules, [
                        'storage_capacity' => 'nullable|numeric|min:0',
                        'collection_points' => 'nullable|string|max:500',
                        'value_chains' => 'nullable|string|max:500',
                        'business_name' => 'required|string|max:255',
                        'business_license_number' => 'nullable|string|max:100',
                        'business_phone_number' => 'nullable|string|max:20',
                        'business_email' => 'nullable|email|max:255',
                    ]);
                    break;

                case 'Bulking Agent':
                    $rules = array_merge($rules, [
                        'warehouse_location' => 'required|string|max:255',
                        'handling_capacity' => 'nullable|numeric|min:0',
                        'business_name' => 'required|string|max:255',
                        'business_license_number' => 'nullable|string|max:100',
                        'business_phone_number' => 'nullable|string|max:20',
                        'business_email' => 'nullable|email|max:255',
                    ]);
                    break;

                case 'Extension Officer':
                    $rules = array_merge($rules, [
                        'assigned_area' => 'required|string|max:255',
                        'specialization' => 'nullable|string|max:255',
                        'qualifications' => 'nullable|string|max:1000',
                    ]);
                    break;

                case 'Admin':
                    $rules = array_merge($rules, [
                        'department' => 'required|string|max:255',
                        'permissions_level' => 'nullable|in:Admin,Super Admin,Moderator',
                    ]);
                    break;
            }

            // Validate the request
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => $validator->errors()->first(),
                    'data' => null,
                ], 422);
            }

            // Create the user
            $userData = [
                'name' => $request->input('name'),
                'phone_number' => $request->input('phone_number'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'user_type' => $role,
                'sex' => $request->input('sex'),
                'country' => $request->input('country', 'Uganda'),
                'address' => $request->input('address'),
                'village' => $request->input('village'),
                'status' => 'Active',
                'remember_token' => Str::random(60),
            ];

            // Add role-specific fields based on role
            switch ($role) {
                case 'Farmer':
                    // Farmer-specific fields can be stored in profile or additional table
                    $userData['occupation'] = 'Farmer';
                    $userData['about'] = $this->buildFarmerAbout($request);
                    break;

                case 'Service Provider':
                case 'Input Dealer':
                case 'Equipment Provider':
                case 'Output Market':
                case 'Aggregator':
                case 'Bulking Agent':
                    $userData['business_name'] = $request->input('business_name');
                    $userData['business_license_number'] = $request->input('business_license_number');
                    $userData['business_phone_number'] = $request->input('business_phone_number');
                    $userData['business_email'] = $request->input('business_email');
                    $userData['occupation'] = $role;
                    $userData['about'] = $this->buildBusinessAbout($request, $role);
                    break;

                case 'Extension Officer':
                    $userData['occupation'] = 'Extension Officer';
                    $userData['about'] = $this->buildExtensionOfficerAbout($request);
                    break;

                case 'Admin':
                    $userData['occupation'] = 'Admin';
                    $userData['is_admin'] = 'Yes';
                    $userData['about'] = $this->buildAdminAbout($request);
                    break;
            }

            // Create the user
            $user = User::create($userData);

            if (!$user) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Failed to create user account',
                    'data' => null,
                ], 500);
            }

            // Store additional role-specific data in user_profile_data or custom tables
            $this->storeRoleSpecificData($user, $request, $role);

            // Return success response
            return response()->json([
                'code' => 1,
                'message' => 'Registration successful! Welcome to FAO FFS-MIS.',
                'data' => [
                    'user' => $user->fresh(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Registration failed: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Build about text for Farmer
     */
    private function buildFarmerAbout(Request $request)
    {
        $parts = [];

        if ($request->filled('farm_size')) {
            $parts[] = "Farm Size: {$request->input('farm_size')} acres";
        }

        if ($request->filled('crops_grown')) {
            $parts[] = "Crops: {$request->input('crops_grown')}";
        }

        if ($request->filled('livestock_owned')) {
            $parts[] = "Livestock: {$request->input('livestock_owned')}";
        }

        return !empty($parts) ? implode(' | ', $parts) : 'Registered Farmer';
    }

    /**
     * Build about text for business roles
     */
    private function buildBusinessAbout(Request $request, string $role)
    {
        $parts = [$role];

        switch ($role) {
            case 'Service Provider':
                if ($request->filled('services_offered')) {
                    $parts[] = "Services: {$request->input('services_offered')}";
                }
                if ($request->filled('years_experience')) {
                    $parts[] = "{$request->input('years_experience')} years experience";
                }
                break;

            case 'Input Dealer':
                if ($request->filled('products_sold')) {
                    $parts[] = "Products: {$request->input('products_sold')}";
                }
                break;

            case 'Equipment Provider':
                if ($request->filled('equipment_types')) {
                    $parts[] = "Equipment: {$request->input('equipment_types')}";
                }
                break;

            case 'Output Market':
                if ($request->filled('market_type')) {
                    $parts[] = "Type: {$request->input('market_type')}";
                }
                if ($request->filled('capacity')) {
                    $parts[] = "Capacity: {$request->input('capacity')} tons/month";
                }
                break;

            case 'Aggregator':
                if ($request->filled('value_chains')) {
                    $parts[] = "Value Chains: {$request->input('value_chains')}";
                }
                if ($request->filled('storage_capacity')) {
                    $parts[] = "Storage: {$request->input('storage_capacity')} tons";
                }
                break;

            case 'Bulking Agent':
                if ($request->filled('warehouse_location')) {
                    $parts[] = "Location: {$request->input('warehouse_location')}";
                }
                if ($request->filled('handling_capacity')) {
                    $parts[] = "Capacity: {$request->input('handling_capacity')} tons";
                }
                break;
        }

        return implode(' | ', $parts);
    }

    /**
     * Build about text for Extension Officer
     */
    private function buildExtensionOfficerAbout(Request $request)
    {
        $parts = ['Extension Officer'];

        if ($request->filled('assigned_area')) {
            $parts[] = "Area: {$request->input('assigned_area')}";
        }

        if ($request->filled('specialization')) {
            $parts[] = "Specialization: {$request->input('specialization')}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Build about text for Admin
     */
    private function buildAdminAbout(Request $request)
    {
        $parts = ['System Administrator'];

        if ($request->filled('department')) {
            $parts[] = "Department: {$request->input('department')}";
        }

        if ($request->filled('permissions_level')) {
            $parts[] = "Level: {$request->input('permissions_level')}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Store additional role-specific data
     * This can be extended to store in separate tables for better data structure
     */
    private function storeRoleSpecificData($user, Request $request, string $role)
    {
        // Create a JSON field or separate table to store extended profile data
        $profileData = [];

        switch ($role) {
            case 'Farmer':
                $profileData = [
                    'farm_size' => $request->input('farm_size'),
                    'crops_grown' => $request->input('crops_grown'),
                    'livestock_owned' => $request->input('livestock_owned'),
                ];
                break;

            case 'Service Provider':
                $profileData = [
                    'services_offered' => $request->input('services_offered'),
                    'certification' => $request->input('certification'),
                    'years_experience' => $request->input('years_experience'),
                ];
                break;

            case 'Input Dealer':
                $profileData = [
                    'products_sold' => $request->input('products_sold'),
                    'supplier_license' => $request->input('supplier_license'),
                ];
                break;

            case 'Equipment Provider':
                $profileData = [
                    'equipment_types' => $request->input('equipment_types'),
                    'rental_rates' => $request->input('rental_rates'),
                    'coverage_area' => $request->input('coverage_area'),
                ];
                break;

            case 'Output Market':
                $profileData = [
                    'market_type' => $request->input('market_type'),
                    'capacity' => $request->input('capacity'),
                    'operating_hours' => $request->input('operating_hours'),
                ];
                break;

            case 'Aggregator':
                $profileData = [
                    'storage_capacity' => $request->input('storage_capacity'),
                    'collection_points' => $request->input('collection_points'),
                    'value_chains' => $request->input('value_chains'),
                ];
                break;

            case 'Bulking Agent':
                $profileData = [
                    'warehouse_location' => $request->input('warehouse_location'),
                    'handling_capacity' => $request->input('handling_capacity'),
                ];
                break;

            case 'Extension Officer':
                $profileData = [
                    'assigned_area' => $request->input('assigned_area'),
                    'specialization' => $request->input('specialization'),
                    'qualifications' => $request->input('qualifications'),
                ];
                break;

            case 'Admin':
                $profileData = [
                    'department' => $request->input('department'),
                    'permissions_level' => $request->input('permissions_level'),
                ];
                break;
        }

        // Store in user's intro field or create separate profile table
        if (!empty($profileData)) {
            $user->update([
                'intro' => json_encode($profileData),
            ]);
        }
    }

    /**
     * List all registered users with filtering and search
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // IP scoping: only show users from user's IP
            $authUser = auth('api')->user() ?? auth()->user();
            if ($authUser && $authUser->ip_id) {
                $query->where('ip_id', $authUser->ip_id);
            }

            // Filter by role/user_type
            if ($request->has('role') && !empty($request->role)) {
                $query->where('user_type', $request->role);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Search by name, phone, or email
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('business_name', 'like', "%{$search}%");
                });
            }

            // Filter by district
            if ($request->has('district') && !empty($request->district)) {
                $query->where('district_id', $request->district);
            }

            // Filter by verification status
            if ($request->has('verified') && !empty($request->verified)) {
                if ($request->verified === 'yes') {
                    $query->whereNotNull('phone_number_verified_at');
                } else {
                    $query->whereNull('phone_number_verified_at');
                }
            }

            // Order by
            $orderBy = $request->input('order_by', 'created_at');
            $orderDir = $request->input('order_dir', 'desc');
            $query->orderBy($orderBy, $orderDir);

            // Pagination
            $perPage = $request->input('per_page', 50);
            $users = $query->paginate($perPage);

            // Get summary counts
            $summary = [
                'total' => User::count(),
                'active' => User::where('status', 'Active')->count(),
                'inactive' => User::where('status', '!=', 'Active')->count(),
                'verified' => User::whereNotNull('phone_number_verified_at')->count(),
                'by_role' => User::selectRaw('user_type, COUNT(*) as count')
                    ->groupBy('user_type')
                    ->pluck('count', 'user_type'),
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single user details
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * Update user status (verify, activate, deactivate)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:verify,activate,deactivate',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $user = User::findOrFail($id);
            $action = $request->input('action');

            switch ($action) {
                case 'verify':
                    $user->update([
                        'phone_number_verified_at' => now(),
                    ]);
                    $message = 'User verified successfully';
                    break;

                case 'activate':
                    $user->update([
                        'status' => 'Active',
                    ]);
                    $message = 'User activated successfully';
                    break;

                case 'deactivate':
                    $user->update([
                        'status' => 'Inactive',
                    ]);
                    $message = 'User deactivated successfully';
                    break;
            }

            return response()->json([
                'code' => 1,
                'message' => $message,
                'data' => $user->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get filter options for user list
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        try {
            $roles = [
                'Farmer',
                'Service Provider',
                'Input Dealer',
                'Equipment Provider',
                'Output Market',
                'Aggregator',
                'Bulking Agent',
                'Extension Officer',
                'Admin',
            ];

            $statuses = ['Active', 'Inactive', 'Pending', 'Suspended'];

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => [
                    'roles' => $roles,
                    'statuses' => $statuses,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
