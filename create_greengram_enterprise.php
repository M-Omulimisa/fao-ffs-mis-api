<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;

// Create Greengram (Mung Bean) Production Enterprise
$enterprise = Enterprise::create([
    'name' => 'Greengram (Mung Bean) Production',
    'description' => 'Complete phenological guide for greengram (mung bean) cultivation, covering all growth stages from planting to harvest. Greengram is a fast-growing, drought-tolerant legume crop valued for its nutritious seeds, nitrogen fixation capacity, and suitability for intercropping systems. This guide covers the complete 3-4 month production cycle with emphasis on pest management, disease control, and optimal harvesting practices for maximum yield and quality.',
    'type' => 'crop',
    'duration' => 4,
    'photo' => 'https://images.unsplash.com/photo-1589621316382-008455b857cd?w=800',
    'is_active' => 1
]);

echo "✓ Created Greengram Enterprise (ID: {$enterprise->id})\n\n";

// Protocol 1: Planting Stage (Week 0)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Planting Stage & Establishment',
    'activity_description' => 'The initial stage of greengram production involves proper seed selection, land preparation, and optimal planting practices. This stage sets the foundation for successful crop establishment and subsequent growth.

MORPHOLOGY:
Seeds are small, cylindrical to oval-shaped, green to dull green in color. Each seed weighs approximately 20-80g per 1000 seeds. Proper seed selection ensures uniform germination and vigorous seedling growth.

MOISTURE SENSITIVITY:
Moderate moisture requirement (++). Soil should be moist but not waterlogged. Adequate moisture at planting ensures rapid germination within 4-6 days. Excess moisture can lead to seed rot and poor stand establishment.

WEED EFFECTS:
Minimal weed pressure at planting stage (-). However, land should be properly prepared and free from weed seeds. Clean seedbed preparation is critical for uniform crop emergence and early growth.

SUSCEPTIBILITY TO PESTS:
High susceptibility (+++). Seed corn maggot is the primary pest at planting, attacking seeds before germination. Other pests include cutworms and wireworms that damage emerging seedlings.

SUSCEPTIBILITY TO DISEASES:
Moderate susceptibility (+). Seed rot caused by various fungi (Pythophthora, Rhizoctonia) can occur in waterlogged conditions. Seed treatment with fungicides is recommended for disease prevention.

CRITICAL MANAGEMENT:
• Recommended spacing: 30-45cm between rows, 10-15cm within rows (plant population: 200,000-400,000 plants/ha)
• Planting depth: 3-5cm for optimal emergence
• Seed rate: 15-25kg/ha depending on variety and spacing
• Fertilizer application: Apply basal fertilizer (SSP) at 60kg/ha or DAP at 50kg/ha
• Use certified seed for better germination and disease resistance
• Treat seeds with Rhizobium inoculant for nitrogen fixation
• Consider seed treatment with fungicides (Thiram/Captan) to prevent seed-borne diseases

AESA PARAMETERS:
• Germination percentage (target: >85%)
• Days to emergence (target: 4-6 days)
• Seed spacing uniformity
• Soil moisture level
• Presence of soil pests

TRAINING TOPICS:
• Soil preparation and conservation practices
• Seed selection and quality assessment
• Optimal planting time and methods
• Spacing and seed rate calculation
• Fertilizer application techniques
• Importance of seed treatment
• Rhizobium inoculation benefits',
    'start_time' => 0,
    'end_time' => 0,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=800',
    'order' => 1,
    'is_active' => 1
]);

// Protocol 2: Emergence Stage (Week 0-1)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Emergence Stage (Week 0-1)',
    'activity_description' => 'During this critical stage, seeds germinate and seedlings emerge from the soil. Proper management ensures uniform stand establishment and healthy seedling development.

MORPHOLOGY:
• Two cotyledons emerge from the soil (epigeal germination)
• Seedlings are approximately 2cm high with yellowish-green color
• Primary leaves begin to unfold
• Root system starts development with visible nodulation beginning

MOISTURE SENSITIVITY:
High moisture requirement (++). Consistent soil moisture is critical for uniform emergence. Water stress at this stage can result in poor stand establishment and uneven growth.

WEED EFFECTS:
Minimal competition (-). However, early weed emergence should be monitored as greengram seedlings are sensitive to competition during establishment.

SUSCEPTIBILITY TO PESTS:
Very high susceptibility (+++). Seed corn maggot attacks germinating seeds. Green leaf beetle and cutworms can damage emerging cotyledons and young seedlings. Early monitoring and intervention are critical.

SUSCEPTIBILITY TO DISEASES:
Moderate susceptibility (+). Seedling damping-off caused by Pythophthora and Rhizoctonia can occur in wet, poorly drained soils. Charcoal rot may affect seedlings in hot, dry conditions.

CRITICAL MANAGEMENT:
• Gap filling: Replace missing plants within 7-10 days after emergence
• Ensure adequate soil moisture through light irrigation if needed
• Monitor for pest and disease incidence
• Begin weed monitoring and early control
• Protect seedlings from birds and rodents
• Assess stand uniformity and plant population

AESA PARAMETERS:
• Percentage emergence and stand establishment
• Seedling vigor and health assessment
• Cotyledon color and size
• Root development and nodulation
• Pest and disease incidence
• Soil moisture status

TRAINING TOPICS:
• Identification of pests and diseases at emergence
• Gap filling techniques and timing
• Early weed identification
• Importance of uniform stand establishment
• Water management at seedling stage',
    'start_time' => 0,
    'end_time' => 1,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=800',
    'order' => 2,
    'is_active' => 1
]);

// Protocol 3: Vegetative Growth Stage (Week 2-5)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Vegetative Growth Stage (Week 2-5)',
    'activity_description' => 'The vegetative stage is characterized by rapid leaf and stem development, establishment of the root system, and active nitrogen fixation. Proper management during this stage determines plant vigor and yield potential.

MORPHOLOGY:
• Significant increase in number of leaves (>10 leaves per plant)
• Onset of branching with lateral shoot development
• Plant height increases to 30-45cm
• Trifoliate leaves fully expanded
• Well-developed root system with active nodulation (pink nodules indicate active nitrogen fixation)
• Green, vigorous plant canopy

MOISTURE SENSITIVITY:
Moderate to high moisture requirement (++). Consistent moisture is needed for vigorous vegetative growth and active nitrogen fixation. Water stress can reduce leaf area and branching, affecting yield potential.

WEED EFFECTS:
High weed competition (++). This is the critical period for weed-crop competition. Weeds compete for nutrients, water, and light, significantly reducing yield if not controlled. Timely weeding is essential.

SUSCEPTIBILITY TO PESTS:
Very high susceptibility (+++). Major pests include:
• Seed corn maggot (moderate, ++)
• Green leaf beetle (very high, +++) - causes defoliation
• American bean beetle (very high, +++) - damages leaves
• Blister beetle (very high, +++) - causes severe defoliation
• Fall armyworm (very high, +++) - leaf and stem damage
• Garden webworm (very high, +++) - leaf webbing and feeding

SUSCEPTIBILITY TO DISEASES:
Moderate susceptibility (+). Diseases include:
• Charcoal rot - affects roots and stems in hot, dry conditions
• Brown spot - circular lesions on leaves
• Downy mildew - affects leaves in humid conditions
• Bud blight - terminal bud damage
• Bacterial blight - water-soaked lesions on leaves

CRITICAL MANAGEMENT:
• First weeding at 2-3 weeks after emergence (hand weeding or herbicide)
• Second weeding at 4-5 weeks if necessary
• Roguing of diseased and off-type plants
• Top-dressing with nitrogen fertilizer only if nodulation is poor (20-30kg N/ha)
• Monitor pest and disease levels regularly
• Apply appropriate pest and disease control measures based on AESA
• Ensure adequate soil moisture through irrigation in dry spells
• Crop protection at flowering to maximize yield

AESA PARAMETERS:
• Plant height and vigor assessment
• Leaf number and color (nitrogen status)
• Branching pattern and density
• Nodulation status (number and color of nodules)
• Pest damage levels and economic threshold
• Disease incidence and severity
• Weed density and species composition
• Soil moisture levels

TRAINING TOPICS:
• Safe use of agrochemicals and IPM principles
• Weed identification and control methods
• Pest scouting and economic threshold concepts
• Disease recognition and management
• Nodulation assessment and significance
• Water management practices
• Crop nutrition and fertilizer use efficiency',
    'start_time' => 2,
    'end_time' => 5,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=800',
    'order' => 3,
    'is_active' => 1
]);

// Protocol 4: Flowering Stage (Week 6-10)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Flowering Stage (Week 6-10)',
    'activity_description' => 'The flowering stage is crucial for yield determination. Flowers appear on racemes and develop into pods. Proper management during flowering ensures good pod set and seed development.

MORPHOLOGY:
• Flowers appear in clusters (racemes)
• Yellow flowers develop at leaf axils
• Horns (young pods) begin to develop after successful pollination
• Spreading of branches increases plant canopy
• Plant height reaches 45-60cm at peak flowering

MOISTURE SENSITIVITY:
Very high moisture requirement (++). Adequate moisture during flowering is critical for flower retention, pollination, and pod set. Water stress can cause flower drop and reduce yield significantly.

WEED EFFECTS:
Moderate weed competition (++). Weeds should be controlled before flowering to avoid competition during this critical yield determination phase.

SUSCEPTIBILITY TO PESTS:
Very high susceptibility (+++). Major pests include:
• Seed corn maggot (low, +)
• Green leaf beetle (high, +++)
• American bean beetle (very high, +++) - attacks flowers and pods
• Blister beetle (very high, +++) - damages flowers
• Fall armyworm (very high, +++) - feeds on flowers and young pods
• Garden webworm (very high, +++)

SUSCEPTIBILITY TO DISEASES:
High susceptibility (++). Critical diseases include:
• Brown spot (++) - affects leaves and pods
• Downy mildew (++) - reduces photosynthetic capacity
• Purple seed stain (++) - affects developing seeds
• Anthracnose - causes pod rot in humid conditions

CRITICAL MANAGEMENT:
• Maintain optimal soil moisture through regular irrigation
• Hand pulling of weeds to avoid root disturbance
• Implement crop protection measures at flowering stage
• Monitor for pest damage and apply control measures
• Ensure good pollination (mainly self-pollinated, but some cross-pollination)
• Apply foliar nutrients if deficiency symptoms appear
• Protect crop from extreme weather (heavy rain, strong winds)

AESA PARAMETERS:
• Flower density and distribution
• Flower drop percentage
• Pod setting rate
• Pest incidence on flowers
• Disease symptoms on leaves and flowers
• Plant vigor and canopy color
• Moisture stress indicators

TRAINING TOPICS:
• Weed control methods at flowering stage
• Effect of pests and diseases on yield
• Importance of moisture management
• Flower biology and pollination
• Economic threshold for pest control
• Integrated pest management at flowering',
    'start_time' => 6,
    'end_time' => 10,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=800',
    'order' => 4,
    'is_active' => 1
]);

// Protocol 5: Blooming & Podding Stage (Week 11-14)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Blooming & Podding Stage (Week 11-14)',
    'activity_description' => 'During this stage, pods develop and fill with seeds. This is the most critical period for final yield determination as seeds develop and mature within the pods.

MORPHOLOGY:
• Visible pods develop from fertilized flowers
• Thickening of the leaves and increase in plant height (60-75cm)
• Pods are green, elongated, and cylindrical (6-12cm long)
• Each pod contains 10-15 seeds
• Pod formation progresses from lower to upper parts of plant
• Leaves remain green and photosynthetically active

MOISTURE SENSITIVITY:
Extremely high moisture requirement (+++). Peak water demand occurs during pod filling. Adequate moisture ensures proper seed development, size, and weight. Water stress reduces seed size and yield.

WEED EFFECTS:
Low weed competition (+). Crop canopy is well established, providing good weed suppression. However, existing weeds should be removed to prevent competition for moisture and nutrients.

SUSCEPTIBILITY TO PESTS:
Very high susceptibility (+++). Major pests include:
• Green leaf beetle (moderate, ++) - continues feeding on leaves
• American bean beetle (very high, +++) - attacks pods and seeds
• Blister beetle (very high, +++) - damages pods
• Fall armyworm (very high, +++) - bores into pods
• Garden webworm (very high, +++) - damages pods
• Pod borers - cause direct yield loss

SUSCEPTIBILITY TO DISEASES:
High susceptibility (++). Critical diseases include:
• Brown spot (++) - affects pods and reduces quality
• Downy mildew (+) - affects late-formed leaves
• Purple seed stain (++) - discolors seeds and reduces market value
• Cercospora leaf spot - affects photosynthetic capacity
• Pod rot diseases in humid conditions

CRITICAL MANAGEMENT:
• Maintain consistent soil moisture through regular irrigation
• Continuous weeding to eliminate competition
• Intensive pest and disease control to protect developing pods
• Monitor for pod-boring insects and apply targeted controls
• Rogue diseased plants to prevent spread
• Apply foliar sprays for micronutrient deficiencies if needed
• Protect crop from bird damage

AESA PARAMETERS:
• Pod number per plant (target: 15-30 pods)
• Pod length and filling status
• Pest damage on pods (bore holes, feeding marks)
• Disease symptoms on pods and leaves
• Seed development within pods
• Plant vigor and leaf health
• Moisture stress symptoms

TRAINING TOPICS:
• Critical nature of moisture management during pod filling
• Identification and control of pod-boring pests
• Disease management to protect pod quality
• Yield estimation techniques
• Importance of protecting crop at this stage
• Pre-harvest planning and timing',
    'start_time' => 11,
    'end_time' => 14,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1587735243615-c03f25aaff15?w=800',
    'order' => 5,
    'is_active' => 1
]);

// Protocol 6: Pod Filling Stage (Week 13-14)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Pod Filling Stage (Week 13-14)',
    'activity_description' => 'Pods reach full size and seeds continue to fill and mature. Proper management ensures maximum seed weight and quality for optimal yield and market value.

MORPHOLOGY:
• Pods contain immature seeds that are still developing
• Seeds are filling and increasing in size
• Pods remain green but begin to show early signs of maturity
• Some lower pods may start to change color
• Plant continues photosynthesis to supply nutrients to developing seeds

MOISTURE SENSITIVITY:
Very high moisture requirement (+++). Continued adequate moisture is essential for complete seed filling and achieving maximum seed weight. Begin reducing irrigation as harvest approaches.

WEED EFFECTS:
Minimal weed competition (+). Crop canopy provides complete ground cover. Focus on preventing seed production in any remaining weeds.

SUSCEPTIBILITY TO PESTS:
High susceptibility (+++). Major pests include:
• Green leaf beetle (moderate, ++)
• American bean beetle (high, ++)
• Blister beetle (high, ++)
• Fall armyworm (high, ++)
• Garden webworm (high, ++)
• Pod-sucking bugs - reduce seed quality

SUSCEPTIBILITY TO DISEASES:
Moderate susceptibility (++). Diseases include:
• Downy mildew (+) - less critical at this late stage
• Purple seed stain (++) - affects seed quality and marketability
• Pod rots - in wet conditions
• Leaf spots - reduce photosynthetic capacity

CRITICAL MANAGEMENT:
• Monitor pods daily for maturity indicators
• Reduce irrigation frequency as pods mature
• Continue pest monitoring and control
• Apply final protective sprays if pest pressure remains high
• Prevent waterlogging which causes pod rots
• Plan harvest logistics and drying facilities
• Monitor weather for optimal harvest timing

AESA PARAMETERS:
• Pod maturity assessment (color change)
• Seed size and development within pods
• Pest damage on mature pods
• Disease incidence on pods
• Moisture content of seeds
• Percentage of pods ready for harvest
• Plant senescence indicators

TRAINING TOPICS:
• Pod maturity assessment techniques
• Pest and disease control measures
• Harvest timing indicators
• Weather monitoring for harvest planning
• Post-harvest handling preparation',
    'start_time' => 13,
    'end_time' => 14,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1592984645643-e5f4ddaa8c0e?w=800',
    'order' => 6,
    'is_active' => 1
]);

// Protocol 7: Maturity Stage (Week 15-16)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Maturity & Harvest Stage (Week 15-16)',
    'activity_description' => 'The final stage where seeds reach physiological maturity and the crop is ready for harvest. Proper harvest timing and handling ensure maximum yield, quality, and market value.

MORPHOLOGY:
• Fully developed seeds with green to yellowish-green color
• Yellowing and shedding of lower leaves (natural senescence)
• Seeds attain variety-specific color (green, golden-yellow, or dull green)
• Seeds become hard and rattle inside pods when shaken
• Pale yellow coloration visible in mature pods
• No more significant foliar growth

MOISTURE SENSITIVITY:
Low moisture requirement (+). Stop irrigation 7-10 days before harvest to allow pods to dry. Excess moisture at maturity delays harvest and can cause seed discoloration.

WEED EFFECTS:
Minimal weed impact (-). Crop has completed its growth cycle. However, clean fields facilitate easier harvesting.

SUSCEPTIBILITY TO PESTS:
Moderate susceptibility (+). Late-season pests include:
• Green leaf beetle (+) - minimal impact at this stage
• American bean beetle (+) - reduced activity
• Blister beetle (+) - minimal
• Fall armyworm (+) - reduced activity
• Garden webworm (+) - minimal
• Storage pests may attack if harvest is delayed

SUSCEPTIBILITY TO DISEASES:
Low susceptibility (+). Late-season diseases include:
• Downy mildew (+) - minimal impact on yield
• Purple seed stain (+) - affects only appearance
• Pod shattering - in overripe pods

CRITICAL MANAGEMENT:
• Timely harvesting when 80-90% of pods are mature (avoid over-maturity which causes shattering)
• Harvest in early morning or late evening to minimize pod shattering
• Proper harvesting technique: pull plants or cut at ground level
• Reduce foliage due to desiccation (natural drying)
• Thresh immediately or within 2-3 days after harvest
• Ensure proper drying of seeds to 12-14% moisture content
• Clean seeds and remove debris
• Proper storage in dry, cool, pest-free facilities
• Grade seeds for market quality
• No more significant foliar growth should occur

AESA PARAMETERS:
• Percentage of mature pods (target: >80%)
• Pod shattering tendency
• Seed moisture content (target: 12-14% for storage)
• Seed color and uniformity
• Presence of immature seeds
• Weather conditions for harvest
• Field drying status

TRAINING TOPICS:
• Indicators of crop maturity and harvest readiness
• Importance of timely harvesting to prevent losses
• Methods of harvesting (manual vs. mechanical)
• Proper post-harvest handling procedures
• Threshing techniques and equipment
• Seed drying methods and target moisture levels
• Storage management and pest prevention
• Seed grading and quality assessment
• Market preparation and value addition',
    'start_time' => 15,
    'end_time' => 16,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1599909533144-7d77de19edd3?w=800',
    'order' => 7,
    'is_active' => 1
]);

echo "✓ Created Protocols 1-7 (All Growth Stages)\n\n";

// Now create management protocols
// Protocol 8: Integrated Pest Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Integrated Pest Management for Greengram',
    'activity_description' => 'Comprehensive pest management strategy combining cultural, biological, and chemical controls to minimize pest damage while protecting beneficial organisms and the environment.

MAJOR PESTS AND THEIR MANAGEMENT:

1. SEED CORN MAGGOT (Delia platura):
• Damage: Attacks seeds and seedlings, causing poor germination and stand
• Management: Seed treatment, proper land preparation, avoid planting in cold wet soils
• Chemical control: Thiamethoxam seed treatment

2. GREEN LEAF BEETLE (Chrysomelidae family):
• Damage: Defoliation at all growth stages, can cause severe yield loss
• Management: Early detection, handpicking, biological control with parasitoids
• Chemical control: Lambda-cyhalothrin, Cypermethrin when threshold exceeded

3. AMERICAN BEAN BEETLE (Epilachna varivestis):
• Damage: Both adults and larvae feed on leaves, flowers, and pods
• Management: Crop rotation, early planting, removal of crop residues
• Chemical control: Dimethoate, Malathion at economic threshold

4. BLISTER BEETLE (Meloidae family):
• Damage: Severe defoliation, can destroy entire plants quickly
• Management: Handpicking and destruction, avoid crushing on skin
• Chemical control: Pyrethroids, but use cautiously due to beneficial predators

5. FALL ARMYWORM (Spodoptera frugiperda):
• Damage: Feeds on leaves, flowers, and bores into pods
• Management: Early detection, biological control (Bacillus thuringiensis), pheromone traps
• Chemical control: Chlorantraniliprole, Emamectin benzoate

6. GARDEN WEBWORM (Achyra rantalis):
• Damage: Larvae web leaves together and feed, reducing photosynthetic area
• Management: Remove and destroy webbed leaves, encourage natural enemies
• Chemical control: Spinosad, Indoxacarb

7. POD BORERS (Helicoverpa armigera, Maruca vitrata):
• Damage: Larvae bore into pods and feed on developing seeds - direct yield loss
• Management: Monitor flowering stage, pheromone traps, biological controls
• Chemical control: Profenofos + Cypermethrin, Chlorantraniliprole

8. APHIDS (Aphis craccivora):
• Damage: Suck plant sap, transmit viral diseases, excrete honeydew
• Management: Encourage natural predators (ladybirds), reflective mulches
• Chemical control: Imidacloprid, Thiamethoxam (if threshold exceeded)

9. THRIPS (Megalurothrips sjostedti):
• Damage: Feed on flowers causing flower drop and pod malformation
• Management: Blue sticky traps, removal of alternate hosts
• Chemical control: Fipronil, Spinosad

10. WHITEFLIES (Bemisia tabaci):
• Damage: Suck sap, transmit viruses, cause sooty mold
• Management: Yellow sticky traps, biological control with parasitoids
• Chemical control: Spiromesifen, Pyriproxyfen

INTEGRATED PEST MANAGEMENT STRATEGIES:

CULTURAL CONTROLS:
• Proper land preparation and removal of previous crop residues
• Optimal planting time to avoid peak pest populations
• Adequate spacing for air circulation
• Crop rotation with non-legume crops
• Intercropping with pest-repellent crops
• Timely weeding to eliminate alternate hosts
• Destruction of infected plant materials
• Deep plowing after harvest to expose soil pests

MECHANICAL/PHYSICAL CONTROLS:
• Handpicking of visible pests (beetles, caterpillars)
• Installation of light traps for moth pests
• Use of pheromone traps for monitoring and mass trapping
• Bird perches to encourage insectivorous birds
• Sticky traps (yellow for whiteflies, blue for thrips)

BIOLOGICAL CONTROLS:
• Conservation of natural enemies (predators and parasitoids)
• Release of Trichogramma wasps for pod borer control
• Application of Bacillus thuringiensis (Bt) for caterpillar pests
• Use of nuclear polyhedrosis virus (NPV) for armyworm control
• Encourage ladybird beetles and lacewings for aphid control
• Avoid broad-spectrum pesticides that harm beneficials

CHEMICAL CONTROLS (Last Resort):
• Apply only when pest populations exceed economic threshold
• Use selective, less toxic pesticides
• Rotate pesticide classes to prevent resistance
• Follow pre-harvest intervals strictly
• Apply at recommended rates and timings
• Use proper application equipment and techniques
• Spot treatment rather than blanket application
• Prefer bio-pesticides and botanical insecticides when possible

ECONOMIC THRESHOLDS:
• Leaf feeders: 2-3 beetles/plant or 20% defoliation
• Pod borers: 1-2 larvae per 5 plants or 5% pod damage
• Aphids: 50-60 aphids per plant tip
• Thrips: 5-10 thrips per flower
• Whiteflies: 5-10 adults per leaf

AESA PARAMETERS:
• Weekly pest scouting and population monitoring
• Damage assessment (% defoliation, pod damage)
• Natural enemy populations
• Weather conditions affecting pest development
• Crop growth stage and vulnerability
• Economic threshold levels

TRAINING TOPICS:
• Pest identification and lifecycle understanding
• Scouting techniques and record keeping
• Economic threshold concepts
• Safe pesticide handling and application
• Biological control and natural enemy conservation
• IPM decision-making process
• Sprayer calibration and maintenance
• Personal protective equipment use
• Pesticide resistance management',
    'start_time' => 0,
    'end_time' => 16,
    'is_compulsory' => 0,
    'photo' => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=800',
    'order' => 8,
    'is_active' => 1
]);

// Protocol 9: Disease Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Comprehensive Disease Management for Greengram',
    'activity_description' => 'Integrated disease management strategies for greengram covering major fungal, bacterial, and viral diseases with emphasis on prevention, early detection, and appropriate control measures.

MAJOR DISEASES AND THEIR MANAGEMENT:

1. DOWNY MILDEW (Peronospora manshurica):
• Symptoms: Yellow spots on upper leaf surface, grayish-white fungal growth on lower surface, leaf curling
• Favorable conditions: High humidity (>80%), moderate temperature (20-25°C), prolonged leaf wetness
• Management: 
  - Cultural: Resistant varieties, wider spacing, avoid overhead irrigation, remove infected plants
  - Chemical: Metalaxyl + Mancozeb, Copper oxychloride at first symptom appearance
• Prevention: Seed treatment with metalaxyl, proper field drainage

2. BROWN SPOT / CERCOSPORA LEAF SPOT (Cercospora canescens):
• Symptoms: Small circular reddish-brown spots on leaves, pods, and stems; spots coalesce causing leaf drop
• Favorable conditions: Warm humid weather, water splash dispersal
• Management:
  - Cultural: Crop rotation, removal of crop debris, balanced nutrition
  - Chemical: Chlorothalonil, Mancozeb, Carbendazim sprays at 10-15 day intervals
• Prevention: Use disease-free seeds, avoid excessive nitrogen

3. CHARCOAL ROT (Macrophomina phaseolina):
• Symptoms: Root and lower stem rot, gray to black discoloration, wilting, sudden plant death in hot weather
• Favorable conditions: High soil temperature (30-35°C), water stress, poor soil fertility
• Management:
  - Cultural: Deep summer plowing, crop rotation with cereals, maintain adequate soil moisture
  - Biological: Trichoderma viride seed and soil treatment
  - Chemical: Carbendazim seed treatment
• Prevention: Avoid water stress, improve soil organic matter

4. SEEDLING DAMPING-OFF (Pythium spp., Rhizoctonia solani):
• Symptoms: Pre-emergence rot, post-emergence wilting and collapse of seedlings
• Favorable conditions: Waterlogged soils, cold wet conditions, poor drainage
• Management:
  - Cultural: Proper seedbed preparation, avoid over-irrigation, improve drainage
  - Biological: Trichoderma seed treatment
  - Chemical: Thiram or Captan seed treatment before planting
• Prevention: Use well-drained fields, optimal planting time

5. POWDERY MILDEW (Erysiphe polygoni):
• Symptoms: White powdery fungal growth on leaves, stems, and pods; yellowing and premature senescence
• Favorable conditions: Dry weather with cool nights, high humidity early morning
• Management:
  - Cultural: Resistant varieties, avoid late planting, remove infected plant parts
  - Chemical: Sulfur dust, Hexaconazole, Propiconazole sprays
• Prevention: Proper spacing for air circulation, balanced nutrition

6. ANTHRACNOSE (Colletotrichum lindemuthianum):
• Symptoms: Dark sunken lesions on pods, stems, and leaves; pod rot; seed infection
• Favorable conditions: Warm humid weather, rain splash
• Management:
  - Cultural: Use disease-free seed, crop rotation, remove infected residues
  - Chemical: Copper fungicides, Mancozeb, Carbendazim sprays
• Prevention: Seed treatment, avoid working in wet fields

7. BACTERIAL BLIGHT (Xanthomonas axonopodis pv. phaseoli):
• Symptoms: Water-soaked lesions on leaves, pods, and stems; leaf yellowing; pod distortion
• Favorable conditions: Warm humid weather, rain splash, wounds from insects
• Management:
  - Cultural: Use disease-free seed, crop rotation, resistant varieties
  - Chemical: Copper-based bactericides (Copper oxychloride, Streptocycline)
  - Biological: Pseudomonas fluorescens seed treatment
• Prevention: Avoid field operations when plants are wet

8. BUD BLIGHT (Multiple pathogens):
• Symptoms: Terminal bud necrosis, leaf malformation, plant stunting
• Favorable conditions: Stress conditions, thrips damage
• Management:
  - Cultural: Control thrips vectors, maintain plant vigor
  - Chemical: Combined pest and disease management
• Prevention: Good cultural practices, stress avoidance

9. PURPLE SEED STAIN (Cercospora kikuchii):
• Symptoms: Purple to reddish-brown discoloration on seeds, reduced seed quality
• Favorable conditions: Humid conditions during pod maturity
• Management:
  - Cultural: Timely harvest, proper seed drying
  - Chemical: Foliar sprays of Mancozeb or Chlorothalonil during pod filling
• Prevention: Use healthy seeds, avoid late harvesting in wet conditions

10. YELLOW MOSAIC VIRUS (Mungbean Yellow Mosaic Virus - MYMV):
• Symptoms: Bright yellow mosaic patterns on leaves, stunted growth, reduced pod set
• Transmission: Whitefly (Bemisia tabaci) vector
• Management:
  - Cultural: Use resistant/tolerant varieties, early planting to escape vector peak
  - Vector control: Imidacloprid seed treatment, control whitefly populations
  - Physical: Remove infected plants immediately, use reflective mulches
• Prevention: Virus-free seed, whitefly management

INTEGRATED DISEASE MANAGEMENT STRATEGIES:

CULTURAL PRACTICES:
• Use certified disease-free seeds
• Crop rotation with non-legume crops (minimum 2-3 years)
• Deep summer plowing to expose soil pathogens
• Proper field sanitation - remove and destroy crop residues
• Balanced nutrition - avoid excessive nitrogen
• Optimal planting density for air circulation
• Timely weeding to reduce humidity
• Proper irrigation management - avoid water stress and waterlogging
• Roguing of diseased plants
• Use resistant or tolerant varieties when available

SEED TREATMENT:
• Fungicide treatment: Thiram @ 2g/kg or Captan @ 2g/kg or Carbendazim @ 2g/kg
• Biological treatment: Trichoderma viride @ 4g/kg
• Combined treatment: Carbendazim + Thiram @ 2g/kg

SOIL MANAGEMENT:
• Trichoderma viride application in soil
• Improve soil drainage
• Maintain soil pH 6.0-7.5
• Add organic matter to improve soil health
• Avoid continuous legume cultivation

FOLIAR SPRAYS:
• Prophylactic: Mancozeb @ 2g/L at 25 and 40 days after sowing
• Curative: Based on disease occurrence and identification
• Rotate fungicide groups to prevent resistance
• Add sticker for better adherence
• Spray during cooler hours (early morning or late evening)

BIOLOGICAL CONTROL:
• Trichoderma species for soil-borne diseases
• Pseudomonas fluorescens for bacterial diseases
• Bacillus subtilis for multiple diseases
• Application: Seed treatment and soil application

AESA PARAMETERS:
• Weekly disease monitoring and incidence recording
• Weather monitoring (temperature, humidity, rainfall)
• Disease severity assessment (% leaf area affected)
• Early symptom recognition
• Soil moisture status
• Crop growth stage
• Vector populations (whiteflies, thrips)

TRAINING TOPICS:
• Disease identification using visual symptoms
• Understanding disease cycle and favorable conditions
• Importance of preventive measures
• Seed treatment techniques
• Proper fungicide selection and application
• Biological control options
• Integrated disease management decision-making
• Record keeping for disease management
• Resistance management in pathogens',
    'start_time' => 0,
    'end_time' => 16,
    'is_compulsory' => 0,
    'photo' => 'https://images.unsplash.com/photo-1584308972272-9e4e7685e80f?w=800',
    'order' => 9,
    'is_active' => 1
]);

// Protocol 10: Post-Harvest Handling
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Post-Harvest Handling & Storage Management',
    'activity_description' => 'Comprehensive guide for proper post-harvest handling, processing, and storage of greengram to minimize losses, maintain quality, and maximize market value.

HARVESTING:

HARVEST TIMING:
• Harvest when 80-90% of pods turn brown/yellow
• Seeds should be hard and rattle in pods
• Moisture content: 18-20% at harvest
• Timing: Early morning or late evening to minimize shattering
• Avoid over-maturity which causes pod shattering
• Complete harvest within 5-7 days after initial maturity

HARVESTING METHODS:
1. Manual Harvesting:
   • Pull entire plant by hand (suitable for small areas)
   • Cut plants at ground level using sickle
   • Bundle plants and stack in field for field drying (2-3 days)
   • Advantages: Suitable for uneven maturity, less mechanical damage

2. Mechanical Harvesting:
   • Use combine harvester for large-scale production
   • Adjust cylinder speed to minimize seed damage
   • Set concave clearance properly
   • Harvest when moisture is 14-16% for mechanical harvest

THRESHING:

THRESHING METHODS:
1. Manual Threshing:
   • Beating dried plants with sticks
   • Trampling by animals (bullock treading)
   • Labor-intensive but suitable for small quantities
   • Minimizes seed damage

2. Mechanical Threshing:
   • Use pedal thresher or power thresher
   • Cylinder speed: 300-400 RPM (lower speed to prevent seed damage)
   • Feed plants gradually to ensure complete threshing
   • Higher capacity and efficiency

THRESHING PRECAUTIONS:
• Ensure plants are sufficiently dry (pods brittle)
• Avoid excessive speed that damages seeds
• Remove long stems before feeding to mechanical thresher
• Separate chaff immediately after threshing

CLEANING AND GRADING:

CLEANING:
• Remove plant debris, broken seeds, and foreign matter
• Use winnowing (traditional) or seed cleaner (mechanical)
• Multiple cleaning stages for export quality
• Remove immature, discolored, and damaged seeds

GRADING:
Grade I (Premium):
• Uniform size and color
• Moisture content: 12-14%
• No discoloration or disease symptoms
• <2% broken seeds
• No insect damage
• Higher market price

Grade II (Standard):
• Minor color variation acceptable
• Moisture: 12-14%
• <5% broken seeds
• Minimal insect damage
• Medium market price

Grade III (Low):
• Mixed sizes and colors
• Up to 10% broken seeds
• Visible pest damage
• Lower market price or animal feed

DRYING:

TARGET MOISTURE:
• Safe storage moisture: 10-12%
• Seed quality maintenance: 12-14%
• Prevent mold growth: <14%

DRYING METHODS:
1. Sun Drying:
   • Spread seeds in thin layer (2-3 cm) on clean surface
   • Dry on mats, tarpaulins, or concrete floors
   • Turn seeds frequently (2-3 times daily)
   • Duration: 2-4 days depending on weather
   • Cover at night to prevent moisture absorption
   • Advantages: Low cost, simple
   • Disadvantages: Weather-dependent, slow, contamination risk

2. Mechanical Drying:
   • Use batch dryers or continuous-flow dryers
   • Drying temperature: 40-45°C (avoid >50°C which damages viability)
   • Faster and more uniform drying
   • Better for large quantities and rainy conditions

MOISTURE TESTING:
• Bite test: Seed should break cleanly, not bend
• Electronic moisture meter for accurate measurement
• Target: 12% for market, 10-11% for long-term storage

STORAGE:

STORAGE STRUCTURES:
1. Traditional Storage:
   • Gunny bags or woven sacks
   • Store in well-ventilated rooms
   • Stack on wooden pallets (15-20 cm above ground)
   • Maximum stack height: 8-10 bags
   • Advantages: Low cost, accessible
   • Disadvantages: Pest susceptible, limited capacity

2. Improved Storage:
   • Hermetic bags (PICS bags, SuperBags)
   • Airtight metal bins or drums
   • Advantages: Excellent pest control, maintains quality
   • Suitable for seed storage

3. Warehouse Storage:
   • Large-scale commercial storage
   • Climate-controlled facilities
   • Fumigation capabilities
   • Professional management

STORAGE CONDITIONS:
• Temperature: <25°C (lower is better)
• Relative humidity: <60%
• Moisture content: 10-12%
• Good ventilation
• Pest-free environment
• Away from chemicals and strong odors

STORAGE PESTS:

MAJOR STORAGE PESTS:
1. Pulse Beetle (Callosobruchus species):
   • Most destructive pest of stored pulses
   • Larvae bore into seeds and develop inside
   • Multiple generations, rapid multiplication
   • Causes weight loss, reduced germination, contamination

2. Lesser Grain Borer (Rhyzopertha dominica):
   • Damages seeds and creates dust
   • Prefers damaged seeds initially

3. Rust-red Flour Beetle (Tribolium castaneum):
   • Secondary pest, feeds on damaged seeds and dust

PEST MANAGEMENT IN STORAGE:

PREVENTIVE MEASURES:
• Clean storage structures thoroughly before storage
• Dry seeds to proper moisture level
• Use sound, unbroken seeds
• Seal all cracks and openings in storage structure
• Use hermetic storage systems
• Proper ventilation to prevent moisture buildup

PHYSICAL CONTROL:
• Hermetic storage (PICS bags, metal bins)
• Kills insects through oxygen depletion
• No chemicals required
• Maintains seed quality
• Cost-effective for small-medium farmers

CHEMICAL CONTROL:
1. Diatomaceous Earth:
   • Natural insecticide
   • Apply @ 1-2 kg per ton of grain
   • Mix thoroughly with seeds
   • Safe for consumption

2. Fumigation:
   • Aluminum phosphide tablets (for large quantities)
   • Professional application required
   • Airtight storage necessary
   • Follow safety protocols strictly
   • Pre-harvest interval: 7-10 days before use/sale

3. Contact Insecticides:
   • Spray storage structure with Malathion or Deltamethrin before storage
   • Do not apply directly to seeds for human consumption

MONITORING:
• Inspect stored seeds monthly
• Check for pest infestation (holes, dust, insects)
• Monitor temperature and moisture
• Check for mold or off-odors
• Early detection allows timely intervention

QUALITY MAINTENANCE:

FACTORS AFFECTING QUALITY:
• Moisture content (most important)
• Temperature
• Pest infestation
• Duration of storage
• Initial seed quality
• Storage conditions

QUALITY INDICATORS:
• Color: Bright green color indicates good quality
• Germination: >80% for seed purpose
• Moisture: 10-12%
• Cleanliness: Free from foreign matter
• Pest damage: <5%
• Broken seeds: <2%

VALUE ADDITION:

PROCESSING OPTIONS:
1. Dehulling:
   • Remove seed coat to produce split greengram (moong dal)
   • Increases market value significantly
   • Requires specialized machinery

2. Whole Green Seeds:
   • Premium market for whole seeds
   • Sprouting purpose commands higher price
   • Organic certification adds value

3. Flour Production:
   • Grind into flour for various food products
   • Extended shelf life
   • Value-added product

MARKET PREPARATION:
• Clean and grade seeds
• Pack in appropriate containers (new gunny bags, pp bags)
• Label with variety, grade, quantity, date
• Store samples for quality verification
• Maintain records of harvest and storage

ECONOMIC CONSIDERATIONS:

YIELD EXPECTATIONS:
• Good management: 1.0-1.5 tons/ha
• Average: 0.6-1.0 tons/ha
• Potential yield: Up to 2.0 tons/ha with optimal conditions

POST-HARVEST LOSSES:
• Shattering at harvest: 5-10%
• Threshing losses: 2-5%
• Drying losses: 1-3%
• Storage losses (6 months): 5-15% without proper management
• Total potential loss: 15-30%
• Proper management can reduce losses to <5%

COST-BENEFIT:
• Investment in hermetic storage: Returns within 1-2 seasons
• Mechanical drying: Justified for large-scale production
• Grading increases price by 15-25%
• Quality seed storage commands 20-30% premium

AESA PARAMETERS:
• Harvest timing and maturity assessment
• Pod shattering losses
• Threshing efficiency
• Seed moisture content
• Drying rate and uniformity
• Pest infestation levels
• Storage conditions (temperature, humidity)
• Quality parameters during storage
• Market prices and trends

TRAINING TOPICS:
• Optimal harvest timing and indicators
• Proper harvesting techniques to minimize losses
• Efficient threshing methods
• Seed cleaning and grading standards
• Drying techniques and moisture testing
• Storage pest identification and management
• Hermetic storage technology
• Quality assessment methods
• Market requirements and grading
• Value addition opportunities
• Record keeping for traceability
• Food safety and hygiene in post-harvest handling',
    'start_time' => 15,
    'end_time' => 16,
    'is_compulsory' => 0,
    'photo' => 'https://images.unsplash.com/photo-1586864387967-d02119156377?w=800',
    'order' => 10,
    'is_active' => 1
]);

echo "✓ Created Protocols 8-10 (Management Protocols)\n\n";
echo "==============================================\n";
echo "✅ GREENGRAM ENTERPRISE COMPLETE!\n";
echo "==============================================\n";
echo "Enterprise ID: {$enterprise->id}\n";
echo "Total Protocols Created: " . count($protocols) . "\n\n";
echo "GROWTH STAGES: 7 protocols\n";
echo "MANAGEMENT: 3 protocols\n";
echo "==============================================\n";
