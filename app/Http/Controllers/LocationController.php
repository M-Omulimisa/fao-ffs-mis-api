<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get all districts
     * 
     * GET /api/locations/districts
     */
    public function getDistricts()
    {
        try {
            $districts = Location::where('type', 'District')
                ->orWhere('parent', '<', 1)
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'code']);

            return response()->json([
                'code' => 1,
                'message' => 'Districts retrieved successfully',
                'data' => $districts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve districts: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Get subcounties by district ID
     * 
     * GET /api/locations/subcounties/{districtId}
     */
    public function getSubcounties($districtId)
    {
        try {
            // locations has no parent_id column; keep the key in the response as an alias of `parent`
            $subcounties = Location::where('parent', $districtId)
                ->orderBy('name', 'asc')
                ->select(['id', 'name', 'code', 'parent'])
                ->selectRaw('parent as parent_id')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Subcounties retrieved successfully',
                'data' => $subcounties,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve subcounties: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Get parishes by subcounty ID
     * 
     * GET /api/locations/parishes/{subcountyId}
     */
    public function getParishes($subcountyId)
    {
        try {
            // locations has no parent_id column; keep the key in the response as an alias of `parent`
            $parishes = Location::where('parent', $subcountyId)
                ->orderBy('name', 'asc')
                ->select(['id', 'name', 'code', 'parent'])
                ->selectRaw('parent as parent_id')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Parishes retrieved successfully',
                'data' => $parishes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve parishes: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
