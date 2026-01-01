<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;
use Illuminate\Support\Facades\DB;

echo "====== STARTING ENTERPRISE AND PROTOCOL CREATION ======\n\n";

// Clear existing enterprises and protocols
DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::table('production_protocols')->truncate();
DB::table('enterprises')->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo "Cleared existing enterprises and protocols\n\n";

// ============================================
// 1. APIARY (BEEKEEPING)
// ============================================
echo "Creating Apiary (Beekeeping) Enterprise...\n";
$apiary = Enterprise::create([
    'name' => 'Apiary (Beekeeping) Production',
    'description' => 'Comprehensive beekeeping enterprise covering colony establishment, hive development, and honey production. Suitable for small to medium-scale farmers looking to diversify income through honey, beeswax, and pollination services.',
    'type' => 'livestock',
    'duration' => 15,
    'photo' => 'https://images.unsplash.com/photo-1558642084-fd07fae5282e?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'New Colony Establishment (0-3 Months)',
        'activity_description' => 'Introduce queen correctly into new hive, provide sugar syrup feeding and pollen supplements. Monitor colony acceptance with weekly inspections. Ensure clean hive components and disease-free source. Young worker bees and small brood area development. Focus on queen acceptance and colony establishment. Key parameters: brood area size, queen activity, colony acceptance rate.',
        'start_time' => 0,
        'end_time' => 3,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Developing Hive Management (3-4 Months)',
        'activity_description' => 'Weekly inspections for brood expansion and comb construction. Remove burr comb and maintain hive cleanliness. Supplementary feeding with sugar syrup and fresh water. Monitor for chalkbrood and weak queen issues. Brood pattern inspection and queen laying pattern monitoring. Topics include hive development, comb building, and disease control.',
        'start_time' => 3,
        'end_time' => 4,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Established Hive Production (5-9 Months)',
        'activity_description' => 'Full brood pattern with increased bee population. Natural forage with minimal supplements. Brood comb rotation and debris management. Disease control focusing on Varroa mites and viral infections. Super addition and swarm prevention critical. Monitor hive weight and bee population growth. Implement pest management for Varroa and Tropilaelaps mites.',
        'start_time' => 5,
        'end_time' => 9,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Mature Colony Management (9-15 Months)',
        'activity_description' => 'Well-formed colony with presence of drones and wax production. Plan queen replacement and manage hive space. Monitor for disease outbreak during stress periods. Abundant forage required with constant water source. Focus on swarming control and queen rearing. Clean tools and avoid hive disturbance. Track brood-to-worker ratio.',
        'start_time' => 9,
        'end_time' => 15,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Productive Hive & Harvesting (15+ Months)',
        'activity_description' => 'Large brood chamber with multiple combs and high entrance activity. Plan honey harvesting with proper tool sanitation. Maintain storage area hygiene. No feeding necessary if forage available. Post-harvest treatment and hive health maintenance. Focus on pollination services. Monitor honey yield and hive productivity. Regular treatment for persistent pests needed.',
        'start_time' => 15,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 5
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $apiary->id]));
}
echo "✓ Apiary enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 2. CATTLE
// ============================================
echo "Creating Cattle Enterprise...\n";
$cattle = Enterprise::create([
    'name' => 'Cattle Production & Management',
    'description' => 'Complete cattle rearing enterprise from calf to lactating cow, covering dairy and beef production systems. Includes breeding, nutrition, health management, and milk production for smallholder and commercial farmers.',
    'type' => 'livestock',
    'duration' => 36,
    'photo' => 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Calf Management (0-6 Months)',
        'activity_description' => 'Colostrum management critical in first 3 days of life. Provide milk or replacer with clean water ad libitum. Maintain clean, dry bedding with regular disinfection. High susceptibility to scours and pneumonia. Vaccination program essential. Monitor umbilical cord healing and hoof development. Tagging and record keeping. Key parameters: birth weight, daily weight gain, health status, colostrum intake.',
        'start_time' => 0,
        'end_time' => 6,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Weaner Stage (6-12 Months)',
        'activity_description' => 'Transition to calf starter, hay, and minerals. Weaning process and feed transition management. Clean housing with dust control. Deworming and creep feeding programs. Monitor for bloat and internal/external parasites. Weight gain tracking with regular body condition scoring. Horn bud development and milk teeth formation. Feed conversion monitoring.',
        'start_time' => 6,
        'end_time' => 12,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Heifer Development (12-24 Months)',
        'activity_description' => 'Frame growth and body musculature development. High-quality forage with protein supplements. Weight monitoring and parasite control essential. Clean pens with manure management. Monitor for tick-borne diseases. Growth rate tracking and body condition scoring. Prepare for breeding program. Increased grazing exposure management.',
        'start_time' => 12,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Bred Cow Management (24-36 Months)',
        'activity_description' => 'Belly enlargement and udder development monitoring. High energy diet with protein supplements. Veterinary checkups and balanced feeding. Stress management during pregnancy. Monitor for brucellosis and abortion risks. Disinfected calving pens preparation. Fly control and regular deworming. Track body weight and fetal growth. Calving interval optimization.',
        'start_time' => 24,
        'end_time' => 36,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Lactating Cow & Milk Production (36+ Months)',
        'activity_description' => 'Enlarged udder with full teats and clear milk let-down signs. High protein and energy diet with free water access. Clean milking area with teat sanitation protocols. Monitor for mastitis, metritis, and retained placenta. Calf care and postpartum management. Regular tick/dip treatments. Nutritional support for optimal milk production. Track milk yield, udder health, and calf weight.',
        'start_time' => 36,
        'end_time' => 60,
        'is_compulsory' => true,
        'order' => 5
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $cattle->id]));
}
echo "✓ Cattle enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 3. BEANS
// ============================================
echo "Creating Beans Enterprise...\n";
$beans = Enterprise::create([
    'name' => 'Common Beans Production',
    'description' => 'Complete bean crop production from germination to harvest. Covers all growth stages including vegetative development, flowering, pod formation, and maturity. Suitable for smallholder farmers seeking protein-rich food crop with nitrogen-fixing benefits.',
    'type' => 'crop',
    'duration' => 3,
    'photo' => 'https://images.unsplash.com/photo-1572365292919-8e5d0c4c71d3?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Germination & Emergence (Week 1)',
        'activity_description' => 'Two cotyledons appear, pale green to purple. Soft hairy stem develops. Moderate moisture sensitivity. High phosphorus and nitrogen requirements (P++, N+). Pest management for leaf hoppers, cutworms, caterpillars, and nematodes. Disease control for collar rot and bacterial blight. Critical soil moisture management. Germinability tests and seed dressing. Monitor percentage germination and crop data.',
        'start_time' => 0,
        'end_time' => 1,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Seedling Stage (1-2 Weeks)',
        'activity_description' => '4-6 green leaves with 2-3 branch initiation. Darkening of leaves and hairy stem. Root nodule formation begins. Very high moisture and weed sensitivity. Phosphorus (P++), Magnesium, and Potassium requirements. Gap filling and thinning operations. Control cutworms, aphids, leaf hoppers. Manage anthracnose, bacterial wilt, and bacterial blight. Intensive weeding management.',
        'start_time' => 1,
        'end_time' => 2,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Vegetative Growth (3-5 Weeks)',
        'activity_description' => 'Increased leaf number and canopy size. Nodule increase and branch elongation. Very high moisture and weed sensitivity. Heavy potassium, calcium, and magnesium needs (SSP+++). Rouging diseased plants. Control leaf hoppers, caterpillars, thrips, aphids. Manage downy mildew, powdery mildew, root rot, Alternaria leaf spot. Nutrient and soil moisture management critical.',
        'start_time' => 3,
        'end_time' => 5,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Flowering & Pod Initiation (5-8 Weeks)',
        'activity_description' => 'Flowers develop, canopy increases, pod initiation begins. Moderate moisture sensitivity. Calcium, potassium, and magnesium requirements. Monitor flower drop and stem hardening. Control caterpillars, birds, beetles. Disease focus on bacterial blight, Alternaria leaf spot, anthracnose, downy mildew. Weed management continues. Track pest population and natural enemies.',
        'start_time' => 5,
        'end_time' => 8,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Pod Development & Filling (7-9 Weeks)',
        'activity_description' => 'Pod filling with partial yellowing of lower leaves. Falling flowers and stem hardening. Low moisture and weed sensitivity. Calcium and magnesium needs. Disease management for anthracnose and pod/stem blight. Monitor leaf color and firmness of pods. Pest control for termites, beetles, and animals. Reduced watering.',
        'start_time' => 7,
        'end_time' => 9,
        'is_compulsory' => true,
        'order' => 5
    ],
    [
        'activity_name' => 'Maturity & Harvest (9-12 Weeks)',
        'activity_description' => 'Leaf dry off, pod color darkens, seeds mature. Low moisture requirements. Magnesium supplement only. Control animals and herbivores. Alternaria leaf spot management. Timely harvesting critical. Monitor maturity indicators: leaf color, pod firmness, moisture content. Post-harvest handling and marketing preparation. Proper storage to prevent pest damage.',
        'start_time' => 9,
        'end_time' => 12,
        'is_compulsory' => true,
        'order' => 6
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $beans->id]));
}
echo "✓ Beans enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 4. MAIZE
// ============================================
echo "Creating Maize Enterprise...\n";
$maize = Enterprise::create([
    'name' => 'Maize Production',
    'description' => 'Comprehensive maize cultivation from planting to harvest covering all phenological stages. Includes pest and disease management, nutrient requirements, and post-harvest handling. Suitable for food security and commercial production.',
    'type' => 'crop',
    'duration' => 4,
    'photo' => 'https://images.unsplash.com/photo-1603909441814-c9b5b3e2c109?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Emergence (Week 1)',
        'activity_description' => 'Plumule emerges and radicle descends. Two primary yellow leaves visible. High moisture sensitivity. Phosphorus and nitrogen requirements (P+, N+). Seed dressing and proper planting depth critical. Control birds, animals, rats, and termites. Manage seed rot, root rot, and damping off. Soil moisture management and germinability tests essential. Monitor germination percentage and emergence date.',
        'start_time' => 0,
        'end_time' => 1,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Seedling Stage (1-2 Weeks)',
        'activity_description' => '4-6 leaves completely unfolded. Very high weed and moisture sensitivity. DAP and SSP fertilizer application (P++, N++, K+). Gap filling and thinning operations. Control termites, cutworms, and animals. Monitor for maize streak virus, grey leaf spot, and northern leaf blight. Water conservation and management. Intensive weed control begins.',
        'start_time' => 1,
        'end_time' => 2,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Vegetative Growth (2-5 Weeks)',
        'activity_description' => 'Increase in plant height and width. 8 leaves unfolded with visible nodes and leaf sheath. Prop roots develop. Very high moisture and weed sensitivity. Heavy nitrogen, phosphorus, potassium, and calcium needs (N+++, P++, K++, Ca++). Control termites, stem borers, fall army worm, caterpillars, maize streak borer. Manage grey leaf spot and northern leaf blight. Rouging and weed management critical.',
        'start_time' => 2,
        'end_time' => 5,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Flowering & Tasseling (5-10 Weeks)',
        'activity_description' => 'Tassel and silk development. Cob initiation and grain formation begins. Moderate moisture sensitivity. Calcium dominant (Ca+++), with minor N, P, K, Zn. Control stem borers, fall army worm, monkeys, rodents. Disease focus on maize streak virus, grey leaf spot, leaf rust. Monitor pest population and natural enemies. Critical pollination period.',
        'start_time' => 5,
        'end_time' => 10,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Grain Formation & Filling (10-12 Weeks)',
        'activity_description' => 'Milk grains formed with increasing grain and cob size. Lower leaves change color. Silk appearance and pollen shading. Moderate moisture needs. Calcium, zinc, and minor nutrients (Ca++, Zn+, N+, P+). Control stem borers, caterpillars, monkeys, birds, rodents, humans. Manage grey leaf spot, cob and tassel smut, ear rot. Protect from theft and animal damage.',
        'start_time' => 10,
        'end_time' => 12,
        'is_compulsory' => true,
        'order' => 5
    ],
    [
        'activity_name' => 'Maturity & Harvest (13-14 Weeks)',
        'activity_description' => 'Grain attains maximum hardness and size. Lower leaves completely dry. Low moisture requirements. Minor calcium and zinc (Ca+, Zn+). Critical protection from monkeys, humans, termites, and birds. Monitor signs of maturity: grain hardness, moisture content, husk color. Timely harvesting essential. Count cobs per plant. Marketing and post-harvest handling planning.',
        'start_time' => 13,
        'end_time' => 14,
        'is_compulsory' => true,
        'order' => 6
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $maize->id]));
}
echo "✓ Maize enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 5. GOATS
// ============================================
echo "Creating Goats Enterprise...\n";
$goats = Enterprise::create([
    'name' => 'Goat Production & Management',
    'description' => 'Complete goat rearing system from kid to nursing doe, covering meat and milk production. Includes health management, breeding, and nutrition for improved goat production systems suitable for smallholder farmers.',
    'type' => 'livestock',
    'duration' => 15,
    'photo' => 'https://images.unsplash.com/photo-1594497176194-f89c086f6c93?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Kid Management (0-3 Months)',
        'activity_description' => 'Fresh umbilical cord with undeveloped buds and soft hooves. Milk-only feeding with water always available. Clean housing with dry bedding essential. Highly susceptible to pneumonia, ticks, mites, flies, and lice. Record keeping and hygiene critical. Tick control program implementation. Monitor weight, height, and survival rate. Focus on kid survival and disease prevention.',
        'start_time' => 0,
        'end_time' => 3,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Weaner Stage (3-4 Months)',
        'activity_description' => 'Healed navel with developing buds and strong hooves. Milk plus pasture feeding with clean water. Clean weaner housing with pathogen control. Moderate susceptibility to CCPP and anaplasmosis. Vulnerable to worms and mites. Deworming and clean housing protocols. Weaner nutrition and parasite control focus. Monitor growth and health status.',
        'start_time' => 3,
        'end_time' => 4,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Grower Management (5-9 Months)',
        'activity_description' => 'Milk teeth with horn and scrotum development. Weight gain progression. Adequate pasture with supplementary feeding. Regular cleaning and pen disinfection. Moderate susceptibility to bacterial infections. Some parasite resistance develops. Deworming and housing hygiene critical. Monitor weight gain, horn/scrotum development. Health and growth optimization.',
        'start_time' => 5,
        'end_time' => 9,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'In-kid/Pregnancy Management (9-15 Months)',
        'activity_description' => 'Belly increase with beard and udder development. Steaming up for fetal development with rich forage. Clean housing with stress minimization. Increased disease susceptibility with abortion risks. High endo/ecto-parasite risk. Nutrition optimization and veterinary follow-up. Pregnancy management and disease prevention focus. Track body weight and kidding interval.',
        'start_time' => 9,
        'end_time' => 15,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Nursing Doe & Milk Production (15+ Months)',
        'activity_description' => 'Pronounced mammary glands with stabilized body mass. High-quality feed with plenty of clean water and supplements. Sanitation of milking area with daily cleaning. Monitor for mastitis, milk fever, and infections. Critical ecto/endo-parasite management. Milking hygiene and kid development. Deworming and nutrition programs. Track milk yield and kid growth rates.',
        'start_time' => 15,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 5
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $goats->id]));
}
echo "✓ Goats enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 6. PIGS
// ============================================
echo "Creating Pigs Enterprise...\n";
$pigs = Enterprise::create([
    'name' => 'Pig Production & Management',
    'description' => 'Complete pig rearing enterprise from piglet to breeding sow/boar. Covers growth, health management, breeding, and farrowing for commercial pork production and breeding stock development.',
    'type' => 'livestock',
    'duration' => 15,
    'photo' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Piglet Management (0-3 Months)',
        'activity_description' => 'Fresh umbilical cord, closed eyes at birth, thin weak legs, fine body hair. Colostrum then sow\'s milk with clean water. Warm, clean pen with dry bedding and disinfection. Highly susceptible to diarrhea, pneumonia, and hypothermia. External parasites including mange mites and lice. Warmth provision, clean bedding, and feed intake monitoring. Track birth weight, survival rate, and daily weight gain.',
        'start_time' => 0,
        'end_time' => 3,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Weaner Management (3-4 Months)',
        'activity_description' => 'Erect ears, increased activity, visible teeth, fast weight gain. Creep feed plus milk with fresh water. Separate weaning pen with sanitation and parasite prevention. Stress-related infections and respiratory diseases. Internal worms and mange. Early deworming, weight recording, biosecurity measures. Focus on weaning practices, nutrition, and water quality. Monitor weight gain and feed conversion ratio.',
        'start_time' => 3,
        'end_time' => 4,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Grower Stage (5-9 Months)',
        'activity_description' => 'Developing teats and genitalia, body length increase, stronger hooves. Grower feed (protein-rich) with water always available. Frequent cleaning, deworming, reduced stocking density. Moderate susceptibility to swine fever and parasitic diseases. Roundworms, tapeworms, and reduced parasite resistance. Growth monitoring, housing hygiene. Track body length, girth, and feed efficiency.',
        'start_time' => 5,
        'end_time' => 9,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Gilt/Boar Development (9-15 Months)',
        'activity_description' => 'Puberty signs, teat enlargement (gilts), testicle development (boars). High-energy feed with water ad libitum and mineral supplements. Proper drainage, heat control, clean mating areas. Reproductive diseases with abortion and prolapse risks. High endo/ecto-parasite risk requiring regular deworming. Breeding records, nutrition monitoring, vet support. Heat detection and reproductive health focus. Track age at puberty and body condition score.',
        'start_time' => 9,
        'end_time' => 15,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Sow/Boar & Breeding (15+ Months)',
        'activity_description' => 'Large body mass, full udder or testicles, signs of estrus. Lactation or mating feed with extra nutrition for pregnant/lactating sows. Unlimited water. Daily cleaning, sanitation during farrowing, waste management. Monitor for mastitis and farrowing complications. Constant parasite control with regular inspection. Farrowing preparation, sow comfort, milking hygiene. Track litter size, milk production, and sow longevity.',
        'start_time' => 15,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 5
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $pigs->id]));
}
echo "✓ Pigs enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 7. POULTRY (CHICKENS)
// ============================================
echo "Creating Poultry Enterprise...\n";
$poultry = Enterprise::create([
    'name' => 'Poultry (Chicken) Production',
    'description' => 'Complete chicken production system for layers and broilers. Covers chick brooding, grower management, and production phases for both egg and meat production suitable for small and commercial scale operations.',
    'type' => 'livestock',
    'duration' => 6,
    'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Chick Brooding (0-4 Weeks)',
        'activity_description' => 'Covered with downy feathers, small body, weak legs and beak. Starter feed (20-22% protein) with ad lib clean water. Clean, warm brooder with disinfected feeders/drinkers. Very susceptible to coccidiosis, Newcastle, and Gumboro. Susceptible to worms and mites. Brooder management, temperature control, vaccination program critical. Monitor weight gain, mortality, and behavior. Chick nutrition and survival focus.',
        'start_time' => 0,
        'end_time' => 1,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Grower Management (5-18 Weeks)',
        'activity_description' => 'Growing feathers, increased body weight, comb and wattles beginning to appear. Grower feed (16-18% protein) with ad lib water. Dry litter, clean housing, regular disinfection. Susceptible to Marek\'s and Fowl Pox. Vulnerable to ecto and endoparasites. Vaccination schedule, deworming, feed and light control. Monitor weight gain, disease signs, and feathering development.',
        'start_time' => 5,
        'end_time' => 18,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Layer/Broiler Production (19+ Weeks)',
        'activity_description' => 'Fully developed features with developed comb/wattles. Broilers show muscular development. Layers receive layer mash with calcium; broilers get finisher feed. Unlimited clean water. Clean nests, manure removal, biosecurity enforcement. Highly susceptible to Avian Influenza and egg peritonitis. Regular health checks and hygiene protocols. Monitor egg production rate, feed conversion, and body weight for layers. For broilers track market weight.',
        'start_time' => 19,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 3
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $poultry->id]));
}
echo "✓ Poultry enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 8. RANGELAND MANAGEMENT
// ============================================
echo "Creating Rangeland Management Enterprise...\n";
$rangeland = Enterprise::create([
    'name' => 'Rangeland Management',
    'description' => 'Comprehensive rangeland and pasture management system covering all phenological phases from dormancy to recovery. Essential for livestock farmers to optimize forage production, maintain pasture health, and prevent degradation.',
    'type' => 'livestock',
    'duration' => 15,
    'photo' => 'https://images.unsplash.com/photo-1500595046743-cd271d694d30?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Dormant Phase (0-3 Months)',
        'activity_description' => 'Dry brown grasses with leaf fall and minimal ground cover. Low nutritive value and limited palatability. Avoid overgrazing as fire risk is high. Highly susceptible to soil erosion. Opportunistic weeds and woody encroachment. Fire control and soil conservation critical. Dormancy mapping and dry season planning. Monitor ground cover extent and soil exposure levels.',
        'start_time' => 0,
        'end_time' => 3,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Sprouting Phase (3-4 Months)',
        'activity_description' => 'New shoots appear with green flush and basal growth. Nutritional quality increases, suitable for early grazers. Encourage regrowth and control early weeds. Moderate susceptibility with disturbance affecting seedlings. Invasive seed germination risks. Controlled access and seeding/reseeding operations. Early season indicators and soil moisture management. Track plant density and species composition.',
        'start_time' => 3,
        'end_time' => 4,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Growth Phase (5-9 Months)',
        'activity_description' => 'Lush green pasture with rapid stem elongation and high biomass. Peak forage quality and palatability. Rotational grazing with controlled stocking. Low degradation susceptibility, can sustain moderate grazing. Dense canopy inhibits invaders. Monitor carrying capacity and implement adaptive grazing. Forage biomass estimation and grazing pressure control. Track biomass yield and nutritional value.',
        'start_time' => 5,
        'end_time' => 9,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Flowering/Seeding (9-15 Months)',
        'activity_description' => 'Seed heads develop, plant height at maximum, canopy closure. Declining nutrient value with increased fiber content. Seed dispersal management and rest period planning. Increased vulnerability to compaction and trampling. Weed emergence and bush encroachment. Seed retention and deferred grazing strategies. Monitor flowering ratio and grazing impact.',
        'start_time' => 9,
        'end_time' => 15,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Post-Maturity/Recovery (15+ Months)',
        'activity_description' => 'Leaf senescence with standing dead material. Recovery after grazing begins. Low quality, roughage source only. Soil compaction and bare patches with overgrazing risk. Residual invasives and seed bank buildup. Rehabilitation planning and manure/compost application. Bush clearing and residue management. Pasture rejuvenation and brush control. Monitor regrowth rate and degradation extent.',
        'start_time' => 15,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 5
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $rangeland->id]));
}
echo "✓ Rangeland enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 9. TURKEY
// ============================================
echo "Creating Turkey Enterprise...\n";
$turkey = Enterprise::create([
    'name' => 'Turkey Production & Management',
    'description' => 'Comprehensive turkey production from day-old poults to breeding stock. Covers meat production and breeding for commercial turkey farms. Includes all growth stages, disease management, and optimal production practices.',
    'type' => 'livestock',
    'duration' => 18,
    'photo' => 'https://images.unsplash.com/photo-1542838309-3c0d4e62b1e4?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Poult Stage (0-3 Months)',
        'activity_description' => 'Down feathers, closed eyes, weak legs, high-pitched vocalizations. Starter crumble feed (28% protein) with electrolytes and clean water. Brooder sanitation, litter management, warm draft-free housing. Highly vulnerable to omphalitis and coccidiosis. Intestinal worms and external parasites. Temperature regulation and colostrum-like nutrition. Monitor body temperature and poult survival rate.',
        'start_time' => 0,
        'end_time' => 3,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Weaner Stage (3-4 Months)',
        'activity_description' => 'Feathers replacing down, leg strength increases, head becomes more defined. Grower ration (24% protein) with continuous water and grit for digestion. Clean drinking systems and frequent bedding changes. Moderate susceptibility to histomoniasis (blackhead) and Newcastle. Cecal worms, lice, and mites. Vaccination schedule and growth monitoring. Track weight gain and feather development.',
        'start_time' => 3,
        'end_time' => 4,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Grower Stage (5-9 Months)',
        'activity_description' => 'Full feather coverage, caruncle and snood development, body mass increase. Grower-finisher feed (20% protein) with ample clean water and controlled feed intake. Ventilated housing with periodic disinfection. Respiratory diseases and fowl cholera risks. High risk of blackhead via Heterakis. Biosecurity enforcement and weight tracking. Monitor FCR and muscle growth with immune response.',
        'start_time' => 5,
        'end_time' => 9,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Pre-Breeder Development (9-15 Months)',
        'activity_description' => 'Sexual dimorphism apparent with tail fan and beard in males. Puberty signs visible. Transition to breeder diet (16-18% protein) with calcium supplementation. Nest sanitation, clean roosts, pest exclusion. Reproductive tract infections and egg peritonitis. Mite and flea infestations. Light management and breeding stock selection. Track age at sexual maturity and body conformation.',
        'start_time' => 9,
        'end_time' => 15,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Breeder & Production (15+ Months)',
        'activity_description' => 'Fully matured physical traits, prominent comb/snood/wattles, active reproductive organs. Breeder-specific ration with ample water and nutrient-dense feed. Egg hygiene and sanitary collection/storage. Monitor mycoplasmosis, salmonellosis, increased reproductive stress. Persistent ecto/endo-parasites requiring strategic deworming. Fertility evaluation, nest box management, health screening. Track fertility, hatchability, and laying rate.',
        'start_time' => 15,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 5
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $turkey->id]));
}
echo "✓ Turkey enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 10. CABBAGE
// ============================================
echo "Creating Cabbage Enterprise...\n";
$cabbage = Enterprise::create([
    'name' => 'Cabbage Production',
    'description' => 'Complete cabbage production from seedling nursery through head maturity and harvest. Covers all growth stages, pest and disease management, and post-harvest handling for commercial vegetable production.',
    'type' => 'crop',
    'duration' => 7,
    'photo' => 'https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Cotyledon Stage (0-2 Weeks)',
        'activity_description' => 'Seed leaves with no true leaves present. Moderate moisture and weed sensitivity. Control flea beetles and cutworms heavily. Manage black leg and black rot. Critical pest and disease control with soil moisture management. Focus on ecological relationships and nursery pest control. Introduce AESA monitoring. Monitor seedling establishment and nursery conditions.',
        'start_time' => 0,
        'end_time' => 2,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Seedling Development (3-9 Weeks)',
        'activity_description' => '3-5 true leaves with increased stem size. Moderate moisture and weed sensitivity. Control flea beetles, cutworms, diamond back moth, cabbage root fly, and wirestem. Heavy black leg and black rot pressure. Spraying, watering, protection from animals, weeding operations. Conduct AESA and nursery management. Hardening off before transplanting. Safe use of agro-chemicals.',
        'start_time' => 3,
        'end_time' => 9,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Transplant Establishment (10-12 Weeks)',
        'activity_description' => '6-8 leaves with base stem visible. High moisture and weed sensitivity. Control cutworms, flea beetles, diamond back moth. Manage black leg and black rot. Temporal shading, watering, protection from animals. Phosphate fertilizer application (SSP). Transplanting techniques and crop spacing. Monitor plant establishment and early growth.',
        'start_time' => 10,
        'end_time' => 12,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Vegetative/Cupping Stage (13-15 Weeks)',
        'activity_description' => '13-26 true leaves with innermost heart leaves growing upright. Very high moisture and weed sensitivity. Control thrips, cabbage caterpillars, whitefly, maggot, aphids, ladybird beetles. Black leg and black rot management. Pest and disease control, rouging, watering. Mulching and weed management. Identify pest and disease effects.',
        'start_time' => 13,
        'end_time' => 15,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Early Head Formation (16-18 Weeks)',
        'activity_description' => '2.5-4 inch diameter head. Ball-like structure develops quickly. Very high moisture and weed sensitivity. Control thrips, caterpillars, whitefly, maggot. Manage black rot heavily. Weed control and watering critical. Monitor head development. Field inspections for pest and disease.',
        'start_time' => 16,
        'end_time' => 18,
        'is_compulsory' => true,
        'order' => 5
    ],
    [
        'activity_name' => 'Head Fill (19-24 Weeks)',
        'activity_description' => '3-8 inch diameter head. Firm round head visible within wrapper leaves. Moderate moisture and weed sensitivity. Control thrips, lepidoptera, aphids, ladybird beetles. Manage downy mildew, sclerotinia, black rot, Alternaria. Weed control and head pest/disease control. Monitor maturity indicators.',
        'start_time' => 19,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 6
    ],
    [
        'activity_name' => 'Maturity & Harvest (25-28 Weeks)',
        'activity_description' => '6-12 inch diameter head. Maximum hardness and size attained. No more leaf production. Low moisture requirements. Control thrips only. Manage downy mildew, sclerotinia, and Alternaria. Timely harvesting critical to prevent head splitting. Grading, proper handling, minimizing post-harvest losses. Drying and storage techniques.',
        'start_time' => 25,
        'end_time' => 28,
        'is_compulsory' => true,
        'order' => 7
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $cabbage->id]));
}
echo "✓ Cabbage enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 11. GREENGRAM
// ============================================
echo "Creating Greengram Enterprise...\n";
$greengram = Enterprise::create([
    'name' => 'Greengram (Mung Bean) Production',
    'description' => 'Complete greengram production from planting to harvest. Fast-growing legume crop with high nutritional value. Suitable for smallholder farmers seeking short-duration protein crop with nitrogen-fixing benefits.',
    'type' => 'crop',
    'duration' => 6,
    'photo' => 'https://images.unsplash.com/photo-1596040033229-a0b44d2f6b93?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Planting & Germination (Week 0-1)',
        'activity_description' => 'Seed establishment with root formation initiation. Low moisture and weed sensitivity. Control seed corn maggot heavily. Manage seed rot. Recommended spacing and planting depth critical. Fertilizer application (SSP). Use recommended seed rate. Soil and water conservation practices. Seed selection and planting methods. Monitor field preparation.',
        'start_time' => 0,
        'end_time' => 1,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Emergence (2-5 Weeks)',
        'activity_description' => 'Two cotyledons emerge, 2cm high with yellowish green leaves. Root formation active. Very high moisture and weed sensitivity. Control seed corn maggot. Manage seedling damping off, charcoal rot, Pythophthera root rot. Gap filling and pest/disease control. Safe use of agro-chemicals and weed control. Identify pests and diseases early.',
        'start_time' => 2,
        'end_time' => 5,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Vegetative Growth (6-10 Weeks)',
        'activity_description' => 'Increased leaf number (>10) with onset of branching. Very high moisture and weed sensitivity. Control green leaf beetle, American bean beetle, blister beetle, fall armyworm, garden webworm. Manage charcoal rot, brown spot, downy mildew, bud blight, bacterial blight. Spraying, rouging, weeding operations. Monitor pest and disease effects.',
        'start_time' => 6,
        'end_time' => 10,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Flowering (11-14 Weeks)',
        'activity_description' => 'Flowers appear with bloom development. Branch spreading active. Very high moisture sensitivity. Moderate weed effects. Control beetles, fall armyworm, garden webworm. Manage bacterial blight, brown spot, downy mildew, purple seed stain. Rouging, hand pulling weeds, crop protection at flowering. Monitor flowering intensity.',
        'start_time' => 11,
        'end_time' => 14,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Blooming & Podding (15-18 Weeks)',
        'activity_description' => 'Visible pods with leaf thickening and height increase. Pod formation active. High moisture sensitivity. Low weed effects. Control leaf beetles and armyworms moderately. Manage brown spot, downy mildew, and purple seed stain. Rouging and weeding continue. Monitor pod development.',
        'start_time' => 15,
        'end_time' => 18,
        'is_compulsory' => true,
        'order' => 5
    ],
    [
        'activity_name' => 'Pod Filling (19-23 Weeks)',
        'activity_description' => 'Pods with immature seed. Fully developed seeds forming. Yellowing and shedding of leaves begins. Seed attains variety color. Pale yellow pod coloration. High moisture sensitivity. Very low weed sensitivity. Light pest pressure. Manage downy mildew and purple seed stain. Rouging, weeding, and pest/disease control.',
        'start_time' => 19,
        'end_time' => 23,
        'is_compulsory' => true,
        'order' => 6
    ],
    [
        'activity_name' => 'Maturity & Harvest (Week 23+)',
        'activity_description' => 'Reduced foliage due to desiccation. No more significant foliar growth. Seeds fully mature. Low moisture requirements. No weed issues. Minimal pest pressure. Timely harvesting essential. Proper harvesting methods. Monitor maturity indicators: pod color, seed firmness, leaf desiccation. Post-harvest handling and storage.',
        'start_time' => 23,
        'end_time' => 24,
        'is_compulsory' => true,
        'order' => 7
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $greengram->id]));
}
echo "✓ Greengram enterprise created with " . count($protocols) . " protocols\n\n";

// ============================================
// 12. GROUNDNUT
// ============================================
echo "Creating Groundnut Enterprise...\n";
$groundnut = Enterprise::create([
    'name' => 'Groundnut (Peanut) Production',
    'description' => 'Complete groundnut production from planting through pegging to harvest. Covers all growth stages, disease management, and post-harvest handling for oil and food production. Suitable for smallholder and commercial farmers.',
    'type' => 'crop',
    'duration' => 4,
    'photo' => 'https://images.unsplash.com/photo-1610440042657-74c7024d567f?w=800',
    'is_active' => 1
]);

$protocols = [
    [
        'activity_name' => 'Planting (Week 1)',
        'activity_description' => 'Seed establishment phase. Low moisture and weed sensitivity. Control red ants and millipedes. Manage seed rot. Recommended spacing and planting depth. Fertilizer application (SSP). Use recommended seed rate. Soil and water conservation. Seed selection and planting methods. Monitor field preparation quality.',
        'start_time' => 0,
        'end_time' => 1,
        'is_compulsory' => true,
        'order' => 1
    ],
    [
        'activity_name' => 'Emergence (2-3 Weeks)',
        'activity_description' => 'Two cotyledons emerge, 2cm high with yellowish green leaves. Root formation active. Very high moisture and weed sensitivity. Control birds heavily, aphids, and white flies. Manage seedling damping off. Scaring off birds and gap filling. Control white flies. Safe use of agro-chemicals.',
        'start_time' => 2,
        'end_time' => 3,
        'is_compulsory' => true,
        'order' => 2
    ],
    [
        'activity_name' => 'Establishment (4-6 Weeks)',
        'activity_description' => 'Increased leaf number (>10) with onset of branching. Very high moisture and weed sensitivity. Control leaf miner heavily, aphids, and white flies. Manage rosette and early leaf spots. Spraying, rouging, and intensive weeding operations. Monitor pest and disease development.',
        'start_time' => 4,
        'end_time' => 6,
        'is_compulsory' => true,
        'order' => 3
    ],
    [
        'activity_name' => 'Flowering (6-8 Weeks)',
        'activity_description' => 'Flowers appear with peg development. Branch spreading. Very high moisture sensitivity. Moderate weed sensitivity. Control leaf miner, aphids, white flies, and flower beetles. Manage white mold, rosette, and early leaf spot. Rouging and hand pulling weeds. Critical flowering protection. Monitor flower beetle effects.',
        'start_time' => 6,
        'end_time' => 8,
        'is_compulsory' => true,
        'order' => 4
    ],
    [
        'activity_name' => 'Pegging & Podding (8-9 Weeks)',
        'activity_description' => 'Visible pegs with leaf thickening and height increase. Peg elongation and pod formation. Very high moisture sensitivity. Low weed sensitivity. Light pest pressure from termites, aphids, rodents. Manage leaf spot and rosette. Hand pulling weeds and rouging. Monitor peg penetration into soil.',
        'start_time' => 8,
        'end_time' => 9,
        'is_compulsory' => true,
        'order' => 5
    ],
    [
        'activity_name' => 'Pod Filling (10-11 Weeks)',
        'activity_description' => 'Pods with immature seed. Fully developed seeds forming. Yellowing and shedding of leaves. Seed attains variety color. Dark pod coloration develops. High moisture sensitivity. Very low weed sensitivity. Control rodents. Manage late leaf spot. Rouging operations. Monitor pod filling progress.',
        'start_time' => 10,
        'end_time' => 11,
        'is_compulsory' => true,
        'order' => 6
    ],
    [
        'activity_name' => 'Maturity & Harvest (Week 12+)',
        'activity_description' => 'Reduced foliage due to desiccation. No more significant foliar growth. Seeds fully mature. Low moisture requirements. No weed or disease issues. Control rodents. Timely harvesting critical. Proper harvesting methods to avoid pod breakage. Monitor maturity indicators: shell color, seed development, leaf desiccation. Post-harvest handling and drying.',
        'start_time' => 12,
        'end_time' => 13,
        'is_compulsory' => true,
        'order' => 7
    ]
];

foreach ($protocols as $protocol) {
    ProductionProtocol::create(array_merge($protocol, ['enterprise_id' => $groundnut->id]));
}
echo "✓ Groundnut enterprise created with " . count($protocols) . " protocols\n\n";

// Final Summary
echo "\n====== CREATION COMPLETE ======\n";
echo "Total Enterprises Created: " . Enterprise::count() . "\n";
echo "Total Protocols Created: " . ProductionProtocol::count() . "\n\n";

echo "Breakdown by Enterprise:\n";
$enterprises = Enterprise::with('productionProtocols')->get();
foreach ($enterprises as $enterprise) {
    echo "- {$enterprise->name}: {$enterprise->productionProtocols->count()} protocols\n";
}

echo "\n====== ALL ENTERPRISES AND PROTOCOLS SUCCESSFULLY CREATED ======\n";
