<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComprehensiveProductionProtocolsSeeder extends Seeder
{
    public function run()
    {
        // Clear existing protocols
        DB::table('production_protocols')->delete();

        // Get enterprise IDs
        $poultryId = DB::table('enterprises')->where('name', 'Poultry Farming (Chickens)')->value('id');
        $apiaryId = DB::table('enterprises')->where('name', 'Apiary (Beekeeping)')->value('id');
        $cattleId = DB::table('enterprises')->where('name', 'Cattle Farming')->value('id');
        $goatId = DB::table('enterprises')->where('name', 'Goat Farming')->value('id');
        $pigId = DB::table('enterprises')->where('name', 'Pig Farming')->value('id');
        $turkeyId = DB::table('enterprises')->where('name', 'Turkey Farming')->value('id');
        $rangelandId = DB::table('enterprises')->where('name', 'Rangeland Management')->value('id');
        $beanId = DB::table('enterprises')->where('name', 'Bean Cultivation')->value('id');
        $maizeId = DB::table('enterprises')->where('name', 'Maize Cultivation')->value('id');
        $cabbageId = DB::table('enterprises')->where('name', 'Cabbage Growing')->value('id');
        $greengramId = DB::table('enterprises')->where('name', 'Greengram Cultivation')->value('id');
        $groundnutId = DB::table('enterprises')->where('name', 'Groundnut Farming')->value('id');

        $protocols = [];
        $now = Carbon::now();

        // POULTRY PROTOCOLS (0-18+ weeks)
        if ($poultryId) {
            $protocols[] = [
                'enterprise_id' => $poultryId,
                'activity_name' => 'Chick Stage Management (0-4 weeks)',
                'activity_description' => 'Brooder management, temperature control (32-35°C), starter feed (20-22% protein), vaccination (Marek\'s, Gumboro, Newcastle), clean warm housing, disease prevention',
                'start_time' => 0,
                'end_time' => 4,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $poultryId,
                'activity_name' => 'Grower Stage (5-18 weeks)',
                'activity_description' => 'Grower feed (16-18% protein), vaccination schedule, deworming, light and feed control, clean housing, growth monitoring',
                'start_time' => 5,
                'end_time' => 18,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $poultryId,
                'activity_name' => 'Layer/Broiler Production (19+ weeks)',
                'activity_description' => 'Layer mash with calcium for layers or finisher feed for broilers, routine health checks, egg/meat production management, hygiene maintenance, biosecurity',
                'start_time' => 19,
                'end_time' => 24,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // APIARY PROTOCOLS (0-15+ months)
        if ($apiaryId) {
            $protocols[] = [
                'enterprise_id' => $apiaryId,
                'activity_name' => 'New Colony Establishment (0-3 months)',
                'activity_description' => 'Queen introduction, sugar syrup feeding, clean hive setup, weekly inspections, disease-free source verification, Varroa mite control',
                'start_time' => 0,
                'end_time' => 12,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $apiaryId,
                'activity_name' => 'Developing Hive (3-4 months)',
                'activity_description' => 'Brood expansion monitoring, comb construction, supplementary feeding, pollen storage management, pest prevention (ants, wax moths)',
                'start_time' => 12,
                'end_time' => 16,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $apiaryId,
                'activity_name' => 'Established Hive (5-9 months)',
                'activity_description' => 'Disease control, super addition, swarm prevention, natural forage monitoring, minimal supplementation, Varroa management',
                'start_time' => 16,
                'end_time' => 36,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $apiaryId,
                'activity_name' => 'Productive Hive (15+ months)',
                'activity_description' => 'Honey harvesting, sanitation of tools, continuous water supply, regular treatment for pests, colony health monitoring',
                'start_time' => 36,
                'end_time' => 60,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // CATTLE PROTOCOLS (0-36+ months)
        if ($cattleId) {
            $protocols[] = [
                'enterprise_id' => $cattleId,
                'activity_name' => 'Calf Stage (0-6 months)',
                'activity_description' => 'Colostrum management (first 3 days), milk/replacer feeding, clean warm bedding, vaccination, deworming, hygiene, scours and pneumonia prevention',
                'start_time' => 0,
                'end_time' => 24,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $cattleId,
                'activity_name' => 'Weaner Stage (6-12 months)',
                'activity_description' => 'Calf starter and hay, deworming, creep feeding, clean housing, tagging, weight monitoring, parasite control',
                'start_time' => 24,
                'end_time' => 48,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $cattleId,
                'activity_name' => 'Heifer Stage (12-24 months)',
                'activity_description' => 'Quality forage, supplements, weight monitoring, parasite control, tick control, breeding preparation',
                'start_time' => 48,
                'end_time' => 96,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $cattleId,
                'activity_name' => 'Lactating Cow (36+ months)',
                'activity_description' => 'High protein/energy feed, milking hygiene, teat sanitation, nutritional support, calf care, mastitis prevention, veterinary checks',
                'start_time' => 96,
                'end_time' => 144,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // GOAT PROTOCOLS (0-15+ months)
        if ($goatId) {
            $protocols[] = [
                'enterprise_id' => $goatId,
                'activity_name' => 'Kid Stage (0-3 months)',
                'activity_description' => 'Milk feeding, clean housing, dry bedding, tick control, hygiene, record keeping, pneumonia prevention',
                'start_time' => 0,
                'end_time' => 12,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $goatId,
                'activity_name' => 'Weaner Stage (3-4 months)',
                'activity_description' => 'Milk + pasture, deworming, clean housing, pathogen control, weight monitoring',
                'start_time' => 12,
                'end_time' => 16,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $goatId,
                'activity_name' => 'Grower Stage (5-9 months)',
                'activity_description' => 'Adequate pasture, supplementary feeding, deworming, parasite control, housing hygiene',
                'start_time' => 16,
                'end_time' => 36,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $goatId,
                'activity_name' => 'Nursing Doe (15+ months)',
                'activity_description' => 'High-quality feed, milking hygiene, deworming, nutrition support, clean housing, mastitis prevention',
                'start_time' => 36,
                'end_time' => 60,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // PIG PROTOCOLS (0-15+ months)
        if ($pigId) {
            $protocols[] = [
                'enterprise_id' => $pigId,
                'activity_name' => 'Piglet Stage (0-3 months)',
                'activity_description' => 'Colostrum/milk feeding, warm clean pen, temperature control, vaccination, growth monitoring, hygiene',
                'start_time' => 0,
                'end_time' => 12,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $pigId,
                'activity_name' => 'Weaner Stage (3-4 months)',
                'activity_description' => 'Creep feed + milk, early deworming, weight recording, biosecurity, separate weaning pen',
                'start_time' => 12,
                'end_time' => 16,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $pigId,
                'activity_name' => 'Grower Stage (5-9 months)',
                'activity_description' => 'Protein-rich diet, growth monitoring, deworming, housing hygiene, parasite control',
                'start_time' => 16,
                'end_time' => 36,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $pigId,
                'activity_name' => 'Sow/Boar Stage (15+ months)',
                'activity_description' => 'Lactation/mating feed, daily cleaning, farrowing management, mastitis prevention, waste management',
                'start_time' => 36,
                'end_time' => 60,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // TURKEY PROTOCOLS (0-15+ months)
        if ($turkeyId) {
            $protocols[] = [
                'enterprise_id' => $turkeyId,
                'activity_name' => 'Poult Stage (0-3 months)',
                'activity_description' => 'Starter crumble (28% protein), temperature regulation, brooder sanitation, electrolytes, vaccination',
                'start_time' => 0,
                'end_time' => 12,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $turkeyId,
                'activity_name' => 'Weaner Stage (3-4 months)',
                'activity_description' => 'Grower ration (24% protein), vaccination schedule, growth monitoring, clean drinking systems',
                'start_time' => 12,
                'end_time' => 16,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $turkeyId,
                'activity_name' => 'Grower Stage (5-9 months)',
                'activity_description' => 'Grower-finisher feed (20% protein), biosecurity, weight tracking, ventilated housing',
                'start_time' => 16,
                'end_time' => 36,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $turkeyId,
                'activity_name' => 'Breeder Stage (15+ months)',
                'activity_description' => 'Breeder-specific ration, egg hygiene, light management, breeding stock selection, sanitary collection',
                'start_time' => 36,
                'end_time' => 60,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // RANGELAND PROTOCOLS (0-15+ months)
        if ($rangelandId) {
            $protocols[] = [
                'enterprise_id' => $rangelandId,
                'activity_name' => 'Dormant Phase (0-3 months)',
                'activity_description' => 'Avoid overgrazing, fire risk management, soil erosion prevention, minimal nutritive value period',
                'start_time' => 0,
                'end_time' => 12,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $rangelandId,
                'activity_name' => 'Sprouting Phase (3-4 months)',
                'activity_description' => 'Encourage regrowth, control early weeds, manage new shoots, nutritional quality increases',
                'start_time' => 12,
                'end_time' => 16,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $rangelandId,
                'activity_name' => 'Growth Phase (5-9 months)',
                'activity_description' => 'Rotational grazing, controlled stocking, high forage quality, peak palatability management',
                'start_time' => 16,
                'end_time' => 36,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $rangelandId,
                'activity_name' => 'Post-Maturity Recovery (15+ months)',
                'activity_description' => 'Bush clearing, residue management, soil compaction prevention, recovery planning',
                'start_time' => 36,
                'end_time' => 60,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // BEAN PROTOCOLS (0-12 weeks)
        if ($beanId) {
            $protocols[] = [
                'enterprise_id' => $beanId,
                'activity_name' => 'Emergence/Germination (Week 1)',
                'activity_description' => 'Proper spacing, planting depth, moisture management, two cotyledons emergence, soft stem protection',
                'start_time' => 0,
                'end_time' => 1,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $beanId,
                'activity_name' => 'Seedling Stage (1-2 weeks)',
                'activity_description' => '4-6 green leaves, root nodule formation begins, weed control, moisture sensitive',
                'start_time' => 1,
                'end_time' => 2,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $beanId,
                'activity_name' => 'Vegetative Stage (3-5 weeks)',
                'activity_description' => 'Branch initiation, canopy increase, nodule development, high weed sensitivity, pest control',
                'start_time' => 2,
                'end_time' => 5,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $beanId,
                'activity_name' => 'Flowering & Pod Development (5-9 weeks)',
                'activity_description' => 'Flower management, pod initiation, moisture critical, pest control, flower drop monitoring',
                'start_time' => 5,
                'end_time' => 9,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $beanId,
                'activity_name' => 'Maturity & Harvest (9-12 weeks)',
                'activity_description' => 'Pod filling complete, leaf drying, pod color darkening, seed maturity, proper harvest timing',
                'start_time' => 9,
                'end_time' => 12,
                'is_compulsory' => true,
                'order' => 5,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // MAIZE PROTOCOLS (0-14 weeks)
        if ($maizeId) {
            $protocols[] = [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Emergence (Week 1)',
                'activity_description' => 'Plumule emerges, radical descends, primary leaves yellow, high moisture sensitivity, fertilizer (DAP)',
                'start_time' => 0,
                'end_time' => 1,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Seedling Stage (1-2 weeks)',
                'activity_description' => '4-6 leaves unfolded, weed control, moisture management, nutrient application (P, N)',
                'start_time' => 1,
                'end_time' => 2,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Vegetative Stage (2-5 weeks)',
                'activity_description' => '8 leaves unfolded, prop roots develop, high moisture and nutrient needs (N, P, K, Ca)',
                'start_time' => 2,
                'end_time' => 5,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Flowering & Tasseling (5-10 weeks)',
                'activity_description' => 'Tassel development, silk development, cob initiation, critical water sensitivity, pest control',
                'start_time' => 5,
                'end_time' => 10,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Grain Formation & Maturity (10-14 weeks)',
                'activity_description' => 'Milk grains, grain filling, cob size increase, lower leaves color change, harvest preparation',
                'start_time' => 10,
                'end_time' => 14,
                'is_compulsory' => true,
                'order' => 5,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // CABBAGE PROTOCOLS (0-28 weeks)
        if ($cabbageId) {
            $protocols[] = [
                'enterprise_id' => $cabbageId,
                'activity_name' => 'Cotyledon Stage (0-2 weeks)',
                'activity_description' => 'Seed leaves present, no true leaves, moisture management, flea beetle and cutworm control',
                'start_time' => 0,
                'end_time' => 2,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $cabbageId,
                'activity_name' => 'Seedling Stage (3-9 weeks)',
                'activity_description' => '3-5 true leaves, increased stem size, high moisture and weed sensitivity, pest control',
                'start_time' => 2,
                'end_time' => 9,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $cabbageId,
                'activity_name' => 'Transplant & Vegetative (10-15 weeks)',
                'activity_description' => '6-26 leaves, cupping begins, high moisture needs, diamond back moth and cabbage maggot control',
                'start_time' => 9,
                'end_time' => 15,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $cabbageId,
                'activity_name' => 'Head Formation (16-24 weeks)',
                'activity_description' => 'Head development (3-12" diameter), firm round head, aphid and caterpillar control',
                'start_time' => 15,
                'end_time' => 24,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $cabbageId,
                'activity_name' => 'Maturity & Harvest (25-28 weeks)',
                'activity_description' => 'Maximum head hardness, timely harvest to prevent splitting, quality assessment',
                'start_time' => 24,
                'end_time' => 28,
                'is_compulsory' => true,
                'order' => 5,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // GREENGRAM PROTOCOLS (0-23 weeks)
        if ($greengramId) {
            $protocols[] = [
                'enterprise_id' => $greengramId,
                'activity_name' => 'Planting & Emergence (0-2 weeks)',
                'activity_description' => 'Proper spacing, seed corn maggot control, moisture management, germination monitoring',
                'start_time' => 0,
                'end_time' => 2,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $greengramId,
                'activity_name' => 'Establishment (2-6 weeks)',
                'activity_description' => '2 cotyledons emerge, root formation, yellowish green leaves, weed control, moisture critical',
                'start_time' => 2,
                'end_time' => 6,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $greengramId,
                'activity_name' => 'Vegetative Growth (6-10 weeks)',
                'activity_description' => '>10 leaves, branching onset, high weed sensitivity, beetle and armyworm control',
                'start_time' => 6,
                'end_time' => 10,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $greengramId,
                'activity_name' => 'Flowering & Podding (11-18 weeks)',
                'activity_description' => 'Flower and pod development, moisture critical, pest control (beetles, webworm), pod formation',
                'start_time' => 10,
                'end_time' => 18,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $greengramId,
                'activity_name' => 'Maturity & Harvest (19-23 weeks)',
                'activity_description' => 'Seed color attainment, pod yellowing, desiccation, timely harvest, reduced pest pressure',
                'start_time' => 18,
                'end_time' => 23,
                'is_compulsory' => true,
                'order' => 5,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // GROUNDNUT PROTOCOLS (0-12 weeks)
        if ($groundnutId) {
            $protocols[] = [
                'enterprise_id' => $groundnutId,
                'activity_name' => 'Planting & Emergence (Week 1)',
                'activity_description' => 'Recommended spacing and depth, fertilizer (SSP), seed rate, red ant and millipede control',
                'start_time' => 0,
                'end_time' => 1,
                'is_compulsory' => true,
                'order' => 1,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $groundnutId,
                'activity_name' => 'Establishment (2-3 weeks)',
                'activity_description' => '2 cotyledons, root formation, yellowish green leaves, gap filling, bird control, aphid and whitefly management',
                'start_time' => 1,
                'end_time' => 3,
                'is_compulsory' => true,
                'order' => 2,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $groundnutId,
                'activity_name' => 'Vegetative Growth (4-6 weeks)',
                'activity_description' => '>10 leaves, branching onset, high weed sensitivity, leaf miner and rosette disease control',
                'start_time' => 3,
                'end_time' => 6,
                'is_compulsory' => true,
                'order' => 3,
                'weight' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $groundnutId,
                'activity_name' => 'Flowering & Pegging (6-9 weeks)',
                'activity_description' => 'Flower and peg development, moisture critical, flower beetle control, leaf spot management',
                'start_time' => 6,
                'end_time' => 9,
                'is_compulsory' => true,
                'order' => 4,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $protocols[] = [
                'enterprise_id' => $groundnutId,
                'activity_name' => 'Pod Filling & Maturity (10-12 weeks)',
                'activity_description' => 'Pod development, seed maturity, leaf yellowing, dark shell coloration, rodent control, harvest timing',
                'start_time' => 9,
                'end_time' => 12,
                'is_compulsory' => true,
                'order' => 5,
                'weight' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert all protocols
        if (!empty($protocols)) {
            DB::table('production_protocols')->insert($protocols);
        }
    }
}
