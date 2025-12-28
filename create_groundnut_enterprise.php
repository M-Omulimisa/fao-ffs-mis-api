<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;

// Create Groundnut (Peanut) Production Enterprise
$enterprise = Enterprise::create([
    'name' => 'Groundnut (Peanut) Production',
    'description' => 'Complete phenological guide for groundnut (peanut) cultivation, covering all growth stages from planting to harvest. Groundnut is a valuable legume crop grown for its edible seeds rich in oil and protein. This nitrogen-fixing crop is important for food security, oil production, and soil improvement. The guide covers the complete 3-month production cycle with emphasis on pegging management, pest control, and optimal harvesting practices for maximum yield and quality.',
    'type' => 'crop',
    'duration' => 3,
    'photo' => 'https://images.unsplash.com/photo-1608797178974-15b35a64ede9?w=800',
    'is_active' => 1
]);

echo "✓ Created Groundnut Enterprise (ID: {$enterprise->id})\n\n";

$protocols = [];

// Protocol 1: Planting Stage
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Planting Stage & Land Preparation',
    'activity_description' => 'The planting stage is critical for establishing a good groundnut crop. Proper land preparation, seed selection, and planting techniques ensure uniform emergence and optimal plant population for maximum yield.

MORPHOLOGY:
Groundnut seeds are enclosed in shells (pods). Seeds vary in size, shape, and color depending on variety. Quality seeds should be viable, disease-free, and properly graded. Shell removal should be done carefully to avoid seed coat damage.

MOISTURE SENSITIVITY:
High moisture requirement (++). Soil should have adequate moisture at planting to ensure rapid germination. Groundnuts require well-drained sandy loam soils. Avoid waterlogged conditions which cause seed rot and poor stand establishment.

WEED EFFECTS:
Minimal weed pressure (-) at planting. However, thorough land preparation is essential to reduce weed seed bank. Clean seedbed preparation sets foundation for effective weed management throughout the season.

SUSCEPTIBILITY TO PESTS:
Moderate susceptibility (++). Red ants and millipedes can attack seeds before germination. Termites may also damage seeds and young seedlings. Seed treatment is recommended to protect against soil pests.

SUSCEPTIBILITY TO DISEASES:
Moderate susceptibility (+). Seed rot caused by various fungi can occur in poorly drained soils. Seed treatment with fungicides protects against seed-borne diseases and ensures healthy germination.

CRITICAL MANAGEMENT:
• Recommended spacing: 45cm between rows, 15-20cm within rows (plant population: 220,000-300,000 plants/ha)
• Planting depth: 5-7cm for optimal emergence and pegging
• Seed rate: 80-120kg/ha depending on seed size and spacing
• Fertilizer application (SSP): Apply basal phosphorus at 60-80kg P2O5/ha
• Use certified seeds for better germination and yield potential
• Land preparation: Deep plowing (20-25cm) and fine tilth for good peg penetration
• Treat seeds with Rhizobium inoculant for nitrogen fixation
• Apply fungicide seed treatment (Thiram/Captan) to prevent seed diseases
• Consider ridging for better drainage and pod development

AESA PARAMETERS:
• Soil moisture adequacy for germination
• Seed quality and viability (>75% germination)
• Planting depth uniformity
• Soil structure and tilth quality
• Presence of soil pests (termites, ants)
• Field drainage assessment

TRAINING TOPICS:
• Soil preparation and conservation practices
• Seed selection and quality assessment
• Optimal planting time and methods
• Spacing and seed rate calculation
• Importance of planting depth for pegging
• Fertilizer application techniques
• Rhizobium inoculation benefits for legumes
• Seed treatment procedures',
    'start_time' => 0,
    'end_time' => 0,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=800',
    'order' => 1,
    'is_active' => 1
]);

// Protocol 2: Emergence Stage (Week 1)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Emergence Stage (Week 1)',
    'activity_description' => 'During emergence, groundnut seeds germinate and seedlings push through the soil surface. This critical stage determines final plant population and crop uniformity.

MORPHOLOGY:
• Two cotyledons emerge above ground (epigeal germination)
• Seedlings approximately 2cm high
• Yellowish-green leaves at emergence, gradually turning green
• Primary root system begins development
• Tap root formation with lateral roots starting
• Cotyledons provide initial nutrition for seedling growth

MOISTURE SENSITIVITY:
High moisture requirement (++). Consistent soil moisture is critical for uniform emergence. Moisture stress can cause poor stand establishment. However, avoid waterlogging which causes seedling death and damping-off diseases.

WEED EFFECTS:
Minimal weed competition (-) at this early stage. However, begin monitoring weed emergence as early weed control is crucial for groundnut establishment.

SUSCEPTIBILITY TO PESTS:
Very high susceptibility (+++). Critical pests include:
• Birds - feed on emerging seedlings and cotyledons
• Aphids (+) - may colonize young seedlings
• White flies (+ ++) - early infestation can stunt growth
• Termites - continue to pose threat to young seedlings
• Cutworms - can sever stems at soil level

SUSCEPTIBILITY TO DISEASES:
Moderate susceptibility (+). Seedling damping-off caused by Pythium, Rhizoctonia, and Fusarium species can occur in wet conditions. Proper drainage and seed treatment help prevent losses.

CRITICAL MANAGEMENT:
• Scare birds from the field using bird scarers, netting, or human presence
• Gap filling: Replace missing seedlings within 7-10 days using pre-germinated seeds
• Light irrigation if needed to maintain soil moisture
• Monitor for pest and disease incidence
• Protect emerging seedlings from birds and rodents
• Control white flies if population exceeds threshold
• Assess stand uniformity and plant population
• Begin planning first weeding operation

AESA PARAMETERS:
• Percentage emergence (target: >80%)
• Seedling vigor and uniformity
• Cotyledon size and color
• Root development assessment
• Bird and pest damage levels
• Disease symptoms on seedlings
• Soil moisture status
• Weed emergence patterns

TRAINING TOPICS:
• Identification of pests and diseases at emergence
• Gap filling techniques and timing
• Bird scaring methods
• Early weed identification
• Importance of uniform stand establishment
• Water management for seedlings',
    'start_time' => 0,
    'end_time' => 1,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=800',
    'order' => 2,
    'is_active' => 1
]);

// Protocol 3: Establishment/Vegetative Stage (Week 2-3)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Establishment & Vegetative Growth (Week 2-3)',
    'activity_description' => 'The establishment stage is characterized by rapid vegetative growth, leaf development, and branching. Plants build vegetative framework that will support later reproductive structures.

MORPHOLOGY:
• Significant increase in number of leaves (>10 leaves per plant)
• Onset of lateral branching from main stem
• Development of compound leaves with 4 leaflets (typical groundnut leaf structure)
• Plant height increases to 15-25cm
• Strong taproot system with active nodulation beginning
• Stem thickness increases

MOISTURE SENSITIVITY:
High moisture requirement (++). Consistent moisture promotes vigorous vegetative growth and active nitrogen fixation through root nodules. Water stress reduces branching and plant vigor.

WEED EFFECTS:
High weed competition (++). This is the critical period for weed control. Weeds compete aggressively with young groundnut plants for nutrients, water, and light. First weeding should be completed during this stage.

SUSCEPTIBILITY TO PESTS:
Very high susceptibility (+++). Major pests include:
• Leaf miner (+++) - creates mines in leaves, reduces photosynthesis
• Aphids (+++) - suck sap, transmit viruses, cause leaf curling
• White flies (+++) - feed on lower leaf surface, transmit viral diseases
• Thrips - cause leaf silvering and deformation
• Jassids - cause leaf yellowing and stunting

SUSCEPTIBILITY TO DISEASES:
High susceptibility (+++). Important diseases include:
• Rosette disease (+++) - viral disease transmitted by aphids, causes severe stunting
• Early leaf spot (++) - brown lesions on leaves
• Leaf rust - small rust-colored pustules on leaves
• Collar rot - affects stem at soil level

CRITICAL MANAGEMENT:
• First weeding at 2-3 weeks after planting (hand weeding or herbicide)
• Spraying: Apply appropriate insecticides if pest levels exceed threshold
• Control aphids to prevent rosette virus transmission
• Roguing of diseased plants, especially rosette-infected plants
• Monitor nodulation status - pink/red nodules indicate active nitrogen fixation
• Apply gypsum (200-400 kg/ha) to improve pod filling and quality
• Ensure adequate soil moisture through irrigation if needed
• Begin earthing up if ridging wasn\'t done at planting

AESA PARAMETERS:
• Plant height and vigor
• Number of branches per plant
• Leaf number and color (nitrogen status)
• Nodulation assessment (number and color of nodules)
• Pest incidence and damage levels
• Disease symptoms and spread
• Weed density and competition
• Soil moisture levels

TRAINING TOPICS:
• Safe use of agrochemicals for pest control
• Weed identification and control methods
• Recognition of rosette disease and its management
• Pest scouting and economic thresholds
• Nodulation assessment techniques
• Importance of gypsum application for groundnut
• Water management practices',
    'start_time' => 2,
    'end_time' => 3,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=800',
    'order' => 3,
    'is_active' => 1
]);

// Protocol 4: Flowering Stage (Week 4-6)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Flowering & Pegging Initiation (Week 4-6)',
    'activity_description' => 'The flowering stage is unique in groundnut production. After pollination, the flower stalk (peg) elongates downward and penetrates the soil where pods develop underground. This stage is critical for yield determination.

MORPHOLOGY:
• Bright yellow flowers appear on lateral branches
• Flowers develop in leaf axils
• Pegs begin to develop after self-pollination (24-48 hours after flower opening)
• Spreading of branches continues, creating wider canopy
• Plant height reaches 30-40cm with good lateral spread
• Strong vegetative growth continues simultaneously with flowering

MOISTURE SENSITIVITY:
High moisture requirement (++). Adequate moisture is critical during flowering and peg formation. Water stress causes flower abortion and peg failure, directly reducing yield. However, avoid waterlogging.

WEED EFFECTS:
Moderate weed competition (++). Second weeding may be needed if weed pressure persists. Late-emerging weeds should be removed before they compete for resources needed for pegging and pod development.

SUSCEPTIBILITY TO PESTS:
Very high susceptibility (+++). Key pests include:
• Leaf miner (++) - continues to damage leaves
• Aphids (+) - populations may increase, monitor for rosette transmission
• White flies (+) - continue feeding on leaves
• Flower beetles (thrips) (+++) - damage flowers, reduce pod set
• Jassids - cause leaf damage

SUSCEPTIBILITY TO DISEASES:
High susceptibility (++). Important diseases include:
• White mold/Sclerotinia rot (++) - attacks flowers and pegs in humid conditions
• Rosette (++) - infected plants show severe symptoms, remove immediately
• Early leaf spot (++) - lesions increase on older leaves
• Rust - orange pustules on leaves

CRITICAL MANAGEMENT:
• Rouging: Remove and destroy rosette-infected and off-type plants immediately
• Hand pulling of weeds to avoid root disturbance during pegging
• Monitor flower production and peg formation
• Ensure optimal soil moisture for successful peg penetration
• Apply calcium (gypsum) if not done earlier - critical for pod and kernel development
• Control flower beetles/thrips to prevent flower damage
• Weed control to reduce humidity around plant base
• Protect crop from excessive rainfall if possible (drainage)
• Apply appropriate fungicides if disease pressure is high

AESA PARAMETERS:
• Flower density and distribution
• Peg formation and development
• Percentage of successful pollination
• Pest damage on flowers and leaves
• Disease incidence on leaves and flowers
• Soil moisture and structure for peg penetration
• Canopy spread and ground cover
• Weed pressure assessment

TRAINING TOPICS:
• Weed control methods during flowering
• Effect of pests and diseases on yield
• Importance of moisture and calcium for pegging
• Flower biology and peg formation process
• Economic thresholds for pest intervention
• Disease management during flowering',
    'start_time' => 4,
    'end_time' => 6,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=800',
    'order' => 4,
    'is_active' => 1
]);

// Protocol 5: Pegging & Podding Stage (Week 6-8)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Pegging & Pod Development (Week 6-8)',
    'activity_description' => 'This is the most critical stage for groundnut production. Pegs penetrate the soil and pods begin to form underground. The number of successful pegs directly determines final yield.

MORPHOLOGY:
• Visible pegs penetrating soil (5-7cm deep)
• Thickening of leaves and increased plant height (40-50cm)
• Extensive lateral branching with good ground cover
• Peg elongation continues - pegs grow downward into soil
• Pod formation begins at the tip of pegs underground
• Some pegs may remain above ground (peg abortion occurs if soil is too hard)
• Continued flowering on upper branches

MOISTURE SENSITIVITY:
Very high moisture requirement (+++). Peak water demand during pod initiation and development. Adequate moisture ensures:
• Successful peg penetration into soil
• Pod initiation and development
• Continued flowering and pegging
Water stress severely reduces yield by causing peg and pod abortion.

WEED EFFECTS:
Moderate weed competition (++). Crop canopy provides good ground cover, reducing new weed emergence. Hand pulling of remaining weeds to avoid disturbing pegs in the soil.

SUSCEPTIBILITY TO PESTS:
Moderate to high susceptibility (++). Important pests:
• Termites (+) - attack pegs and developing pods underground
• Aphids (+) - populations usually decline but monitor
• White flies - reduced activity under canopy
• Leaf-eating caterpillars - may appear in late vegetative stage

SUSCEPTIBILITY TO DISEASES:
High susceptibility (++). Critical diseases:
• Leaf spot diseases (++) - early and late leaf spot cause significant defoliation
• Rosette (+) - late infections still problematic
• Stem rot - affects stem base and pegs
• Pod rots - can attack developing pods underground

CRITICAL MANAGEMENT:
• Hand pulling of weeds to avoid peg damage
• Maintain consistent soil moisture through regular irrigation
• Light earthing up around plant base to facilitate peg penetration
• Monitor for leaf spot diseases - apply fungicides if threshold exceeded
• Rouging of late-appearing rosette-infected plants
• Control termites if damage observed on pegs
• Ensure good soil structure for peg penetration (not too compacted)
• Continue gypsum application if deficiency symptoms appear
• Avoid any practice that disturbs the soil around plants

AESA PARAMETERS:
• Number of pegs per plant (target: 20-40 pegs)
• Percentage of pegs penetrating soil successfully
• Pod initiation and development underground
• Leaf spot disease severity (% leaf area affected)
• Pest damage assessment
• Soil moisture status
• Canopy cover percentage
• Root nodulation status

TRAINING TOPICS:
• Critical nature of moisture management during pegging
• Understanding peg-to-pod development process
• Soil management for optimal peg penetration
• Leaf spot disease identification and management
• Termite control in groundnut production
• Importance of avoiding soil disturbance
• Yield estimation based on peg numbers',
    'start_time' => 6,
    'end_time' => 8,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1587735243615-c03f25aaff15?w=800',
    'order' => 5,
    'is_active' => 1
]);

// Protocol 6: Pod Filling Stage (Week 8-9)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Pod Filling Stage (Week 8-9)',
    'activity_description' => 'During pod filling, kernels (seeds) develop and grow within the pods underground. This stage determines final seed size, weight, and quality - all critical for market value.

MORPHOLOGY:
• Pods contain immature seeds that are developing rapidly
• Seeds are white to cream-colored and soft
• Pod shells are still soft and pliable
• Plant maintains green canopy to supply photosynthates to developing seeds
• Continued pod development from late-formed pegs
• Full canopy cover achieved

MOISTURE SENSITIVITY:
Very high moisture requirement (+++). Critical period for water supply:
• Maximum seed filling requires continuous moisture
• Water stress reduces seed size and weight significantly
• Kernel shriveling occurs if moisture is inadequate
• However, begin reducing irrigation frequency as harvest approaches

WEED EFFECTS:
Low weed competition (+). Complete canopy cover suppresses weed growth. Focus on preventing seed production in any remaining weeds.

SUSCEPTIBILITY TO PESTS:
Moderate susceptibility (++). Late-season pests:
• Rodents (++) - begin to dig for developing pods
• Leaf-eating caterpillars - may defoliate late in season
• Aphids - usually low populations
• Termites - continue to attack pods underground

SUSCEPTIBILITY TO DISEASES:
Moderate susceptibility (++). Important diseases:
• Leaf spot (++) - can cause premature defoliation
• Pod rots - threaten pod quality in wet soils
• Collar rot - may appear under high moisture conditions
• Aflatoxin risk increases in stressed or damaged pods

CRITICAL MANAGEMENT:
• Maintain adequate soil moisture but begin tapering off irrigation
• Monitor for premature defoliation from leaf spot diseases
• Protect crop from rodent damage using traps or baits
• Apply final protective fungicide spray if disease pressure remains
• Prevent waterlogging which encourages pod rots and aflatoxin
• Monitor pods by digging sample plants to assess maturity
• Plan harvest logistics based on pod development assessment
• Ensure good field drainage

AESA PARAMETERS:
• Pod fill assessment (percentage of pods with well-filled seeds)
• Seed development within pods (size and color)
• Leaf spot disease severity
• Premature leaf drop assessment
• Rodent activity and damage
• Pod rot incidence (sample pods)
• Soil moisture levels
• Weather conditions affecting maturity

TRAINING TOPICS:
• Pod and seed development assessment
• Leaf spot disease management
• Rodent control methods
• Importance of moisture management during filling
• Harvest timing indicators
• Aflatoxin prevention strategies
• Pod rot prevention',
    'start_time' => 8,
    'end_time' => 9,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1592984645643-e5f4ddaa8c0e?w=800',
    'order' => 6,
    'is_active' => 1
]);

// Protocol 7: Maturity Stage (Week 10-11)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Maturity Stage (Week 10-11)',
    'activity_description' => 'At maturity, pods and seeds reach full development. Proper maturity assessment is crucial as premature or delayed harvest significantly affects yield, quality, and market value.

MORPHOLOGY:
• Fully developed seeds with mature kernel color (variety-specific: white, pink, red, or tan)
• Yellowing and shedding of lower leaves (natural senescence)
• Seeds attain variety-specific color and maximum size
• Dark coloration (veining) appears inside pod shells - key maturity indicator
• Pod shells become hard and brittle
• Seeds separate easily from shell lining
• Upper leaves may remain green (indeterminate growth habit)

MOISTURE SENSITIVITY:
Low moisture requirement (+). Stop irrigation 10-14 days before harvest to allow:
• Pods to dry properly
• Easier harvesting with less soil adhesion
• Reduced pod rot incidence
• Better curing and storage quality

WEED EFFECTS:
Minimal impact (-). Crop cycle is complete. However, clean fields facilitate easier harvesting and reduce moisture retention.

SUSCEPTIBILITY TO PESTS:
Moderate susceptibility (+). Late-season pests:
• Rodents (+) - major threat, actively dig for mature pods
• Birds - may dig for pods
• Storage pests may appear if harvest is delayed
• Aphids - minimal impact at this stage

SUSCEPTIBILITY TO DISEASES:
Low to moderate susceptibility (+). Late-season diseases:
• Late leaf spot (+) - affects remaining leaves
• Stem rot - may cause lodging
• Pod rots - in wet conditions
• Aflatoxin contamination risk if harvest is delayed or pods are damaged

CRITICAL MANAGEMENT:
• Monitor maturity by digging sample plants from different field areas
• Assess pod maturity: Check for dark veining inside shells, kernel color, and shell hardness
• Stop irrigation 10-14 days before anticipated harvest
• Implement rodent control measures intensively
• Harvest timing: When 75-80% of pods show mature characteristics
• Avoid over-maturity which causes:
  - Peg breakage during harvesting
  - Pod shattering
  - Increased aflatoxin risk
  - Reduced seed viability
• Plan harvesting equipment and labor
• Prepare drying and storage facilities
• Monitor weather for optimal harvest conditions

AESA PARAMETERS:
• Percentage of mature pods (target: >75%)
• Pod shell hardening and veining pattern
• Kernel color and size uniformity
• Leaf senescence pattern
• Moisture content of kernels (target: 15-18% at harvest)
• Pod attachment strength (peg integrity)
• Rodent damage assessment
• Weather forecast for harvest window

TRAINING TOPICS:
• Indicators of groundnut maturity
• Importance of timely harvesting
• Pod and kernel assessment techniques
• Methods of harvest timing determination
• Consequences of premature vs. delayed harvest
• Rodent damage prevention
• Weather monitoring for harvest planning
• Aflatoxin prevention through proper timing',
    'start_time' => 10,
    'end_time' => 11,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1599909533144-7d77de19edd3?w=800',
    'order' => 7,
    'is_active' => 1
]);

// Protocol 8: Harvest Stage (Week 12)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Harvest & Field Curing (Week 12)',
    'activity_description' => 'Harvesting groundnut requires careful timing and proper technique to minimize losses and maintain quality. The two-stage harvest process (lifting and collection) is unique to groundnut production.

MORPHOLOGY:
• Reduced foliage due to natural desiccation
• No more significant foliar growth
• Complete pod maturation underground
• Brittle stems and branches
• Dried leaves (mostly fallen)
• Firm, well-filled pods with hard shells

MOISTURE SENSITIVITY:
Low requirement (+). Field should be relatively dry for easier harvesting. However, soil should have enough moisture to allow pod release from pegs without breakage.

WEED EFFECTS:
Minimal impact (-). Harvest is imminent. Clean fields allow better visibility and easier operation of harvesting equipment.

SUSCEPTIBILITY TO PESTS:
Moderate susceptibility (+). Harvest-time pests:
• Rodents (+) - continue to threaten stored pods in windrows
• Birds - may feed on exposed pods
• Storage insects may infest harvested pods if not properly handled

SUSCEPTIBILITY TO DISEASES:
Minimal impact (-). However, improper harvesting and curing can lead to:
• Pod rots if rewetting occurs
• Aflatoxin development in damaged or wet pods
• Mold growth during poor curing

CRITICAL MANAGEMENT:

HARVESTING METHODS:
1. Manual Harvesting:
   • Pull plants by hand or use hand hoe
   • Shake off excess soil
   • Suitable for small farms and uneven maturity

2. Mechanical Harvesting:
   • Use groundnut digger/lifter for medium to large areas
   • Blade-type diggers cut taproots and lift plants
   • More efficient for large-scale production

FIELD CURING (Critical Step):
• Invert plants immediately after lifting (pods up, roots/leaves down)
• Arrange in windrows for uniform drying
• Duration: 2-5 days depending on weather and pod moisture
• Protects pods from sun damage while allowing drying
• Reduces moisture from 35-40% to 15-20%
• Turn windrows if rain is expected

POD STRIPPING:
• Remove pods from vines when adequately dried
• Manual picking or mechanical thresher
• Avoid pod damage which increases aflatoxin risk
• Separate immature and damaged pods

TIMELY HARVESTING:
• Harvest when 75-80% of pods are mature
• Early morning or late evening preferred (less pod shattering)
• Complete harvest within 3-5 days of initiation
• Avoid harvesting during or immediately after rain

AESA PARAMETERS:
• Harvest timing appropriateness
• Pod detachment losses (target: <5%)
• Broken peg percentage
• Pod moisture at harvest
• Field curing conditions (weather)
• Pod damage during handling
• Rodent and bird damage in windrows
• Quality of harvested pods

TRAINING TOPICS:
• Proper harvesting techniques and timing
• Importance of field curing process
• Manual vs. mechanical harvesting methods
• Loss assessment and minimization
• Pod stripping techniques
• Handling to prevent pod damage
• Rodent control during field curing
• Weather monitoring for harvest and curing
• Transition to post-harvest handling',
    'start_time' => 12,
    'end_time' => 12,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1599909533144-7d77de19edd3?w=800',
    'order' => 8,
    'is_active' => 1
]);

echo "✓ Created Protocols 1-8 (All Growth Stages)\n\n";

// Protocol 9: Integrated Pest Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Integrated Pest Management for Groundnut',
    'activity_description' => 'Comprehensive IPM strategy for groundnut combining cultural, biological, and chemical controls to minimize pest damage while maintaining environmental safety and economic viability.

MAJOR PESTS AND THEIR MANAGEMENT:

1. APHIDS (Aphis craccivora):
• Damage: Suck sap from leaves, cause curling, transmit groundnut rosette virus
• Critical Period: Seedling to flowering stage
• Economic Threshold: 50-100 aphids per plant
• Management:
  - Cultural: Early planting to avoid peak aphid populations, remove alternate hosts
  - Biological: Conserve natural enemies (ladybirds, lacewings, parasitoid wasps)
  - Chemical: Imidacloprid, Thiamethoxam, Dimethoate (only if threshold exceeded)
  - Use yellow sticky traps for monitoring

2. LEAF MINER (Aproaerema modicella):
• Damage: Larvae mine between leaf surfaces, reduce photosynthetic area
• Critical Period: Throughout vegetative growth
• Economic Threshold: 15-20% leaf damage or 2-3 larvae per plant
• Management:
  - Cultural: Destroy crop residues, deep plowing after harvest
  - Biological: Encourage parasitoid wasps, avoid broad-spectrum insecticides
  - Chemical: Profenofos, Quinalphos, Chlorantraniliprole
  - Regular monitoring and spot treatment

3. WHITE FLIES (Bemisia tabaci):
• Damage: Suck sap, excrete honeydew, transmit viral diseases
• Critical Period: Early to mid-season
• Economic Threshold: 10-15 adults per leaf
• Management:
  - Cultural: Reflective mulches, remove infected plants
  - Biological: Conserve Encarsia parasitoids, use yellow sticky traps
  - Chemical: Spiromesifen, Pyriproxyfen, Thiamethoxam
  - Rotate insecticide classes to prevent resistance

4. THRIPS (Frankliniella schultzei, Scirtothrips dorsalis):
• Damage: Rasp flower parts, causing flower drop and reduced pod set
• Critical Period: Flowering stage
• Economic Threshold: 5-10 thrips per flower
• Management:
  - Cultural: Remove alternate hosts, maintain field hygiene
  - Biological: Predatory mites and minute pirate bugs
  - Chemical: Fipronil, Spinosad, Acetamiprid
  - Blue sticky traps for monitoring

5. TERMITES (Odontotermes spp., Microtermes spp.):
• Damage: Attack seeds, seedlings, pegs, and developing pods underground
• Critical Period: Throughout crop cycle, especially during dry periods
• Management:
  - Cultural: Remove crop residues, destroy termite mounds around field
  - Physical: Dust furrows with wood ash or cinder
  - Chemical: Chlorpyrifos soil treatment, seed treatment with Fipronil
  - Furrow application of Chlorpyrifos at planting

6. RED ANTS (Dorylus spp.):
• Damage: Attack seeds at planting and damage seedlings
• Critical Period: Planting to emergence
• Management:
  - Cultural: Clean seedbed, remove ant colonies
  - Chemical: Seed treatment with Thiamethoxam, furrow application of Chlorpyrifos
  - Spot treatment of ant trails and colonies

7. JASSIDS/LEAFHOPPERS (Empoasca spp.):
• Damage: Suck sap, cause leaf yellowing and hopper burn
• Critical Period: Vegetative to flowering stage
• Economic Threshold: 5-10 hoppers per plant
• Management:
  - Cultural: Weed control, remove alternate hosts
  - Biological: Conserve spiders and parasitoids
  - Chemical: Imidacloprid, Acetamiprid, Thiamethoxam

8. SPODOPTERA/ARMYWORM (Spodoptera litura):
• Damage: Larvae defoliate plants, especially during late vegetative stage
• Economic Threshold: 25% defoliation or 2-3 larvae per plant
• Management:
  - Cultural: Hand-pick egg masses and young larvae
  - Biological: Bacillus thuringiensis (Bt), NPV (Nuclear Polyhedrosis Virus)
  - Chemical: Chlorantraniliprole, Emamectin benzoate, Spinosad
  - Pheromone traps for monitoring and mass trapping

9. POD BORERS/CATERPILLARS (Helicoverpa armigera):
• Damage: Bore into pods underground, cause direct yield loss
• Economic Threshold: 1-2 larvae per 5 plants
• Management:
  - Cultural: Deep plowing to expose pupae
  - Biological: Trichogramma egg parasitoids, Bt sprays
  - Chemical: Quinalphos, Profenofos + Cypermethrin
  - Pheromone traps for monitoring

10. RODENTS (Rats, Mice, Squirrels):
• Damage: Dig and consume developing and mature pods, significant yield loss
• Critical Period: Pod filling to harvest
• Management:
  - Cultural: Clean field boundaries, remove harboring sites
  - Physical: Rat traps, snap traps around field
  - Biological: Encourage natural predators (owls, snakes)
  - Chemical: Zinc phosphide baits (use with extreme caution)
  - Community-wide control programs most effective

INTEGRATED PEST MANAGEMENT STRATEGIES:

CULTURAL PRACTICES:
• Crop rotation with cereals (2-3 years)
• Early planting to escape peak pest populations
• Optimal plant spacing for air circulation
• Destroy crop residues after harvest
• Deep summer plowing to kill soil-dwelling pests
• Border crops to trap pests
• Timely weeding to remove alternate hosts
• Maintain field sanitation

MECHANICAL/PHYSICAL CONTROLS:
• Handpicking of visible pests and egg masses
• Bird perches for encouraging insectivorous birds
• Light traps for moths (armyworm, pod borer)
• Sticky traps: Yellow for whiteflies and aphids, Blue for thrips
• Traps for rodents around field perimeters
• Netting to prevent bird damage

BIOLOGICAL CONTROLS:
• Conserve natural enemies (predators and parasitoids)
• Release Trichogramma wasps for caterpillar egg control
• Application of Bacillus thuringiensis (Bt) for caterpillars
• Nuclear Polyhedrosis Virus (NPV) for armyworm
• Encourage spiders, ladybirds, and lacewings
• Avoid broad-spectrum insecticides that harm beneficials
• Provide nectar sources for beneficial insects

CHEMICAL CONTROLS (Last Resort):
• Apply only when pest populations exceed economic thresholds
• Use selective, less toxic insecticides
• Seed treatment for early-season pest protection
• Rotate insecticide classes to prevent resistance
• Follow pre-harvest intervals strictly
• Use proper application equipment and calibration
• Spot treatment preferred over blanket application
• Biopesticides preferred when effective

MONITORING AND DECISION MAKING:
• Weekly pest scouting throughout growing season
• Use pheromone traps for moth pest monitoring
• Sticky traps for small insects
• Economic threshold-based interventions
• Weather-based pest outbreak predictions
• Record keeping for pest incidence and control measures

AESA PARAMETERS:
• Pest population levels and damage assessment
• Natural enemy populations
• Weather conditions affecting pest development
• Crop growth stage and vulnerability
• Economic threshold levels for each pest
• Effectiveness of control measures

TRAINING TOPICS:
• Pest identification and lifecycle understanding
• Scouting techniques and threshold determination
• Safe pesticide handling and application
• Biological control and natural enemy conservation
• IPM decision-making process
• Sprayer calibration and maintenance
• Personal protective equipment use
• Pesticide resistance management
• Record keeping for IPM programs',
    'start_time' => 0,
    'end_time' => 12,
    'is_compulsory' => 0,
    'photo' => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=800',
    'order' => 9,
    'is_active' => 1
]);

// Protocol 10: Disease Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Comprehensive Disease Management for Groundnut',
    'activity_description' => 'Integrated disease management strategies covering major fungal, bacterial, and viral diseases of groundnut with emphasis on prevention, early detection, and appropriate control measures.

MAJOR DISEASES AND THEIR MANAGEMENT:

1. GROUNDNUT ROSETTE DISEASE (GRV - Groundnut Rosette Virus):
• Symptoms: Severe stunting, rosette appearance of leaves, chlorotic mottling, bushy growth, little to no pod production
• Transmission: Aphid-transmitted (Aphis craccivora)
• Impact: Can cause 100% yield loss if infection occurs early
• Management:
  - Cultural: Early planting to avoid peak aphid populations, use resistant varieties, roguing infected plants immediately
  - Vector control: Control aphids with systemic insecticides (imidacloprid seed treatment)
  - Preventive: Remove and destroy infected plants, maintain weed-free fields
  - Use certified disease-free seeds
• No cure once infected - prevention is critical

2. EARLY LEAF SPOT (Cercospora arachidicola):
• Symptoms: Small circular brown spots on upper leaf surface, yellow halo around lesions, premature defoliation
• Favorable Conditions: Warm humid weather, rain splash
• Impact: 50% yield loss through defoliation and reduced photosynthesis
• Management:
  - Cultural: Crop rotation (3 years), wide spacing, remove crop residues, avoid overhead irrigation
  - Resistant varieties: Use moderately resistant cultivars
  - Chemical: 
    * Prophylactic sprays: Mancozeb or Chlorothalonil every 14 days starting at 30 DAS
    * Curative sprays: Carbendazim, Propiconazole, Tebuconazole
    * Alternate fungicide groups to prevent resistance
  - Integrated approach: Combine cultural practices with 3-4 fungicide applications

3. LATE LEAF SPOT (Phaeoisariopsis personata/Cercosporidium personatum):
• Symptoms: Darker brown to black spots on lower leaf surface, less distinct borders than early leaf spot, severe defoliation
• Favorable Conditions: Late season, high humidity
• Impact: More aggressive than early leaf spot, can cause 70% yield loss
• Management:
  - Cultural: Same as early leaf spot - rotation, residue removal, spacing
  - Chemical: 
    * More aggressive fungicide schedule than early leaf spot
    * Tebuconazole, Difenconazole, Azoxystrobin
    * Alternate systemic and contact fungicides
  - Apply fungicides even if early leaf spot is controlled
  - Continue until 2 weeks before harvest

4. RUST (Puccinia arachidis):
• Symptoms: Small orange-brown pustules on both leaf surfaces, pustules release orange spores, premature leaf drop
• Favorable Conditions: Moderate temperatures (20-25°C), high humidity
• Impact: 30-50% yield loss through defoliation
• Management:
  - Cultural: Use resistant varieties, crop rotation, field sanitation
  - Chemical: 
    * Sulfur dust or wettable sulfur
    * Tebuconazole, Propiconazole
    * Mancozeb (protective)
  - Often occurs together with leaf spots - combined control needed

5. COLLAR ROT/STEM ROT (Aspergillus niger, Sclerotium rolfsii):
• Symptoms: Water-soaked lesions at soil line, stem girdling, wilting, white fungal growth with small sclerotia
• Favorable Conditions: High soil moisture, high temperature
• Impact: Plant death, yield loss, 10-30% field losses possible
• Management:
  - Cultural: Improve drainage, avoid waterlogging, wider spacing, crop rotation
  - Soil amendments: Gypsum application improves calcium and reduces disease
  - Biological: Trichoderma viride soil application
  - Chemical: Carbendazim, Thiram seed treatment; soil drenching with Carbendazim in affected areas

6. ROOT ROT/CROWN ROT COMPLEX (Rhizoctonia, Pythium, Fusarium spp.):
• Symptoms: Yellowing, wilting, root discoloration and rot, poor nodulation, stunted growth
• Favorable Conditions: Poor drainage, waterlogging, soil compaction
• Impact: Variable, 10-40% loss depending on severity
• Management:
  - Cultural: Improve drainage, avoid compaction, crop rotation with cereals
  - Seed treatment: Thiram, Captan, Carbendazim
  - Biological: Trichoderma seed and soil treatment
  - Chemical: Metalaxyl for Pythium, general fungicides for others

7. AFLATOXIN CONTAMINATION (Aspergillus flavus):
• Nature: Not a disease but mycotoxin contamination - serious health and market concern
• Conditions: Drought stress during pod development, insect/pest damage to pods, delayed harvest, poor storage
• Impact: Makes crop unmarketable, dangerous for human/animal consumption
• Management:
  - Prevent pod damage by pests (termites, pod borers)
  - Maintain adequate soil moisture during pod filling
  - Timely harvest (avoid over-maturity)
  - Proper curing and drying (moisture <10%)
  - Good storage conditions (dry, cool, pest-free)
  - Use resistant varieties where available
  - Application of Trichoderma reduces A. flavus populations

8. GROUNDNUT BUD NECROSIS DISEASE (GBND - Tospo virus):
• Symptoms: Necrosis of terminal buds, stunting, concentric ring patterns on leaves, brown streaks on stems
• Transmission: Thrips-transmitted
• Impact: Significant yield loss, 30-80% depending on stage of infection
• Management:
  - Cultural: Early planting, remove infected plants, weed control
  - Vector control: Control thrips with appropriate insecticides (Fipronil, Spinosad)
  - Use resistant/tolerant varieties
  - Reflective mulches to repel thrips

9. WEB BLOTCH/NET BLOTCH (Phoma arachidicola):
• Symptoms: Web-like patterns on leaves, necrotic lesions with concentric rings
• Favorable Conditions: Cool wet weather
• Impact: Minor disease but can be problematic in certain areas
• Management:
  - Cultural: Crop rotation, residue removal
  - Chemical: Controlled by fungicides used for leaf spots

10. BACTERIAL WILT (Ralstonia solanacearum):
• Symptoms: Sudden wilting, no yellowing, brown discoloration of vascular tissue
• Favorable Conditions: Warm wet soils
• Impact: Variable, can be severe in infected fields
• Management:
  - Cultural: Long crop rotation, use resistant varieties, improve drainage
  - Chemical: Limited chemical control
  - Sanitation: Avoid spreading from infected to clean areas

INTEGRATED DISEASE MANAGEMENT STRATEGIES:

CULTURAL PRACTICES:
• Use certified disease-free seeds
• Crop rotation with non-legumes (cereals) for 3 years minimum
• Deep summer plowing to expose pathogens
• Remove and burn crop residues after harvest
• Optimal planting density for air circulation
• Balanced nutrition (avoid excessive nitrogen)
• Timely weeding to reduce humidity
• Proper water management - avoid water stress and waterlogging
• Roguing of infected plants promptly
• Use resistant or tolerant varieties when available

SEED TREATMENT (Essential):
• Fungicide: Thiram @ 3g/kg or Captan @ 2g/kg or Carbendazim @ 2g/kg
• Biological: Trichoderma viride @ 4-6g/kg
• Insecticide: Imidacloprid @ 7g/kg (for aphid control)
• Combined treatment provides comprehensive protection

SOIL MANAGEMENT:
• Trichoderma viride or Pseudomonas fluorescens soil application
• Gypsum application: 200-400 kg/ha (reduces collar rot, improves pod quality)
• Improve drainage systems
• Maintain soil pH 5.5-6.5
• Organic matter incorporation for soil health

FOLIAR FUNGICIDE PROGRAM:
Prophylactic Schedule:
• 1st spray: 30 days after sowing (Mancozeb 2g/L)
• 2nd spray: 45 DAS (Chlorothalonil 2g/L)
• 3rd spray: 60 DAS (Tebuconazole 1ml/L)
• 4th spray: 75 DAS (Carbendazim + Mancozeb 2g/L)
• Stop sprays 2 weeks before harvest

Disease-Based Schedule:
• Adjust timing based on disease appearance
• Alternate contact and systemic fungicides
• Tank-mix compatible fungicides for broad spectrum
• Add sticker-spreader for better coverage

INTEGRATED APPROACH:
• Combine resistant varieties with cultural practices
• Minimal fungicide use with good cultural practices
• Regular monitoring and early intervention
• Integrated pest management to reduce entry points for pathogens

MONITORING AND ASSESSMENT:
• Weekly disease scouting from 30 DAS
• Early detection crucial for leaf spots and rust
• Assess disease severity using standard scales
• Weather monitoring for disease-favorable conditions
• Record disease incidence for future planning

AESA PARAMETERS:
• Disease incidence (% plants affected)
• Disease severity (% leaf area or plant affected)
• Rate of disease progression
• Weather conditions (rainfall, humidity, temperature)
• Crop growth stage
• Effectiveness of control measures
• Defoliation levels

TRAINING TOPICS:
• Disease identification using visual symptoms
• Understanding disease cycles and favorable conditions
• Seed treatment techniques
• Proper fungicide selection and application
• Spray equipment calibration
• Resistance management in pathogens
• Integrated disease management decision-making
• Aflatoxin prevention strategies
• Economic thresholds for fungicide applications
• Importance of variety selection
• Record keeping for disease management',
    'start_time' => 0,
    'end_time' => 12,
    'is_compulsory' => 0,
    'photo' => 'https://images.unsplash.com/photo-1584308972272-9e4e7685e80f?w=800',
    'order' => 10,
    'is_active' => 1
]);

echo "✓ Created Protocols 9-10 (Management Protocols)\n\n";
echo "==============================================================================\n";
echo "✅ GROUNDNUT ENTERPRISE COMPLETE!\n";
echo "==============================================================================\n";
echo "Enterprise ID: {$enterprise->id}\n";
echo "Total Protocols Created: " . count($protocols) . "\n\n";
echo "GROWTH STAGES: 8 protocols\n";
echo "MANAGEMENT: 2 protocols\n";
echo "==============================================================================\n";
