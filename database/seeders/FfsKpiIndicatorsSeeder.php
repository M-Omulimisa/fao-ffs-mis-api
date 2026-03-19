<?php

namespace Database\Seeders;

use App\Models\FfsKpiIndicator;
use Illuminate\Database\Seeder;

class FfsKpiIndicatorsSeeder extends Seeder
{
    public function run()
    {
        $indicators = [

            // ── Output 1 — FFS/FBS Established and Strengthened (IP) ─────────
            [
                'output_number'           => 1,
                'output_name'             => 'FFS/FBS Established and Strengthened',
                'type'                    => 'ip',
                'indicator_name'          => 'Number of FFS/FBS established or strengthened',
                'default_target'          => 66,
                'location_config'         => 'group',
                'possible_disaggregations'=> ['Total', 'New', 'Old'],
                'sort_order'              => 10,
            ],
            [
                'output_number'           => 1,
                'output_name'             => 'FFS/FBS Established and Strengthened',
                'type'                    => 'ip',
                'indicator_name'          => 'Train FFS leaders on FBS methodology',
                'default_target'          => 66,
                'location_config'         => 'group',
                'possible_disaggregations'=> ['Total', 'Female', 'Male', 'Youth'],
                'sort_order'              => 20,
            ],
            [
                'output_number'           => 1,
                'output_name'             => 'FFS/FBS Established and Strengthened',
                'type'                    => 'ip',
                'indicator_name'          => 'Farmer networks established/strengthened',
                'default_target'          => 2,
                'location_config'         => 'district_only',
                'possible_disaggregations'=> ['New', 'Old'],
                'sort_order'              => 30,
            ],
            [
                'output_number'           => 1,
                'output_name'             => 'FFS/FBS Established and Strengthened',
                'type'                    => 'ip',
                'indicator_name'          => 'FFS members linked to financial institutions',
                'default_target'          => 1000,
                'location_config'         => 'group',
                'possible_disaggregations'=> ['Total'],
                'sort_order'              => 40,
            ],

            // ── Output 2 — Capacity Building and Livelihood Support (IP) ─────
            [
                'output_number'           => 2,
                'output_name'             => 'Capacity Building and Livelihood Support',
                'type'                    => 'ip',
                'indicator_name'          => 'Number of CEWs trained and active',
                'default_target'          => 40,
                'location_config'         => 'institution',
                'possible_disaggregations'=> ['Total', 'Female', 'Male'],
                'sort_order'              => 50,
            ],
            [
                'output_number'           => 2,
                'output_name'             => 'Capacity Building and Livelihood Support',
                'type'                    => 'ip',
                'indicator_name'          => 'Community animal health workers trained',
                'default_target'          => 30,
                'location_config'         => 'institution',
                'possible_disaggregations'=> ['Total'],
                'sort_order'              => 60,
            ],
            [
                'output_number'           => 2,
                'output_name'             => 'Capacity Building and Livelihood Support',
                'type'                    => 'ip',
                'indicator_name'          => 'Youth trained in agribusiness',
                'default_target'          => 60,
                'location_config'         => 'institution',
                'possible_disaggregations'=> ['Total'],
                'sort_order'              => 70,
            ],
            [
                'output_number'           => 2,
                'output_name'             => 'Capacity Building and Livelihood Support',
                'type'                    => 'ip',
                'indicator_name'          => "Women's groups supported",
                'default_target'          => 2,
                'location_config'         => 'institution',
                'possible_disaggregations'=> ['Number'],
                'sort_order'              => 80,
            ],
            [
                'output_number'           => 2,
                'output_name'             => 'Capacity Building and Livelihood Support',
                'type'                    => 'ip',
                'indicator_name'          => 'Value chains developed with private sector linkages',
                'default_target'          => 2,
                'location_config'         => 'institution',
                'possible_disaggregations'=> ['Number'],
                'sort_order'              => 90,
            ],

            // ── Output 3 — Climate Resilience and Disaster Risk Management ────
            [
                'output_number'           => 3,
                'output_name'             => 'Climate Resilience and Disaster Risk Management',
                'type'                    => 'ip',
                'indicator_name'          => 'Watershed plans reviewed and implemented',
                'default_target'          => 4,
                'location_config'         => 'location_type',
                'possible_disaggregations'=> ['N/A'],
                'sort_order'              => 100,
            ],
            [
                'output_number'           => 3,
                'output_name'             => 'Climate Resilience and Disaster Risk Management',
                'type'                    => 'ip',
                'indicator_name'          => 'Rangeland acres rehabilitated',
                'default_target'          => 10,
                'location_config'         => 'location_type',
                'possible_disaggregations'=> ['N/A'],
                'sort_order'              => 110,
            ],
            [
                'output_number'           => 3,
                'output_name'             => 'Climate Resilience and Disaster Risk Management',
                'type'                    => 'ip',
                'indicator_name'          => 'Nurseries established',
                'default_target'          => 4,
                'location_config'         => 'location_type',
                'possible_disaggregations'=> ['N/A'],
                'sort_order'              => 120,
            ],
            [
                'output_number'           => 3,
                'output_name'             => 'Climate Resilience and Disaster Risk Management',
                'type'                    => 'ip',
                'indicator_name'          => 'Trees planted by households',
                'default_target'          => 10,
                'location_config'         => 'location_type',
                'possible_disaggregations'=> ['N/A'],
                'sort_order'              => 130,
            ],

            // ── Facilitator KPIs — Output 1 ───────────────────────────────────
            [
                'output_number'           => 1,
                'output_name'             => 'FFS/FBS Established and Strengthened',
                'type'                    => 'facilitator',
                'indicator_name'          => 'Number of FFS facilitators trained',
                'default_target'          => 16,
                'location_config'         => 'group',
                'possible_disaggregations'=> ['Female', 'Male'],
                'sort_order'              => 140,
            ],
            [
                'output_number'           => 1,
                'output_name'             => 'FFS/FBS Established and Strengthened',
                'type'                    => 'facilitator',
                'indicator_name'          => 'Farmers participating in FFS',
                'default_target'          => 2000,
                'location_config'         => 'group',
                'possible_disaggregations'=> ['Female', 'Male', 'Youth', 'PWD'],
                'sort_order'              => 150,
            ],

            // ── Facilitator KPIs — Adoption Indicators ────────────────────────
            [
                'output_number'           => 1,
                'output_name'             => 'Adoption Indicators',
                'type'                    => 'facilitator',
                'indicator_name'          => 'Farmers applying Good Agricultural Practices',
                'default_target'          => 1600,
                'location_config'         => 'group',
                'possible_disaggregations'=> ['Female', 'Male', 'Youth', 'PWD'],
                'sort_order'              => 160,
            ],
        ];

        foreach ($indicators as $data) {
            // Encode disaggregations as JSON before storage
            $data['possible_disaggregations'] = json_encode($data['possible_disaggregations']);
            $data['created_at'] = now();
            $data['updated_at'] = now();

            FfsKpiIndicator::updateOrCreate(
                [
                    'indicator_name' => $data['indicator_name'],
                    'output_number'  => $data['output_number'],
                    'type'           => $data['type'],
                ],
                $data
            );
        }

        $this->command->info('FFS KPI Indicators seeded: ' . count($indicators) . ' records.');
    }
}
