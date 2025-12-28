<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;

// Create Rangeland Management Enterprise
$enterprise = Enterprise::create([
    'name' => 'Rangeland & Pasture Management',
    'description' => 'Comprehensive rangeland phenological management covering dormancy through recovery phases, focusing on sustainable grazing practices, vegetation monitoring, and ecosystem restoration for optimal livestock production and environmental conservation.',
    'type' => 'livestock',
    'duration' => 18,
    'photo' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800',
    'is_active' => 1
]);

$protocols = [];

// Protocol 1: Dormant Phase (0-3 Months)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Dormant Phase Management (0-3 Months)',
    'activity_description' => 'Dry season rangeland management when grasses are dormant, brown, and have minimal nutritional value. Critical phase requiring strategic interventions to prevent degradation and prepare for the coming growing season.

MORPHOLOGICAL APPEARANCE:
• Dry, brown grasses dominate the landscape
• Leaf fall is extensive across all vegetation
• Minimal ground cover exposing bare soil
• Standing dead material from previous season
• Seed heads dried and dispersed
• Root systems dormant underground
• Woody plants may retain leaves (evergreen species)

FEEDING/WATER VALUE:
• Low nutritive value in standing dry forage (crude protein: 3-5%)
• Limited palatability - animals selective for any green material
• Energy content low, mainly fibrous material
• Supplementary feeding essential for productive animals
• Water sources critical - natural points may dry up
• Dig shallow wells or provide water troughs
• Water quality monitoring important

HYGIENE/VEGETATION MANAGEMENT:
• Avoid overgrazing - critical to prevent permanent damage
• Implement rotational grazing if possible
• Fire risk extremely high - create firebreaks
• Remove livestock from severely degraded areas
• Monitor stocking rates carefully
• Plan for controlled burning (if appropriate and legal)
• Protect water points from trampling and contamination

SUSCEPTIBILITY TO DEGRADATION:
• Highly susceptible to soil erosion (wind and water)
• Bare soil exposed to erosive forces
• Soil compaction from concentrated grazing
• Loss of topsoil and soil structure
• Gully formation in overgrazed areas
• Formation of bare patches and erosion rills

SUSCEPTIBILITY TO INVASIVE SPECIES:
• Opportunistic weeds establish in bare patches
• Woody encroachment advances during dormancy
• Annual invasive grasses from previous season seed bank
• Unpalatable species gain competitive advantage
• Bush thickening in degraded areas

CRITICAL MANAGEMENT REQUIREMENTS:
• Fire control and firebreak maintenance paramount
• Soil conservation measures essential
• Strategic destocking or moving livestock
• Supplement feeding programs for retained stock
• Water point maintenance and development
• Fence maintenance for rotational grazing
• Monitor body condition scores of livestock

POSSIBLE TOPICS FOR AESA:
• Dormancy mapping - identify severely degraded zones
• Dry season grazing planning and stocking rate adjustment
• Fire risk assessment and prevention strategies
• Water source inventory and reliability assessment
• Soil conservation needs identification
• Bush encroachment mapping

AESA PARAMETERS:
• Ground cover extent (target: >40% even in dry season)
• Soil exposure percentage (minimize bare ground)
• Erosion features presence and severity
• Standing biomass quantity
• Water point functionality and distribution',
    'start_time' => 0,
    'end_time' => 12,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1597181042109-e4fafa6deaa0?w=800',
    'order' => 1,
    'is_active' => 1
]);

// Protocol 2: Sprouting Phase (3-4 Months)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Sprouting Phase (3-4 Months)',
    'activity_description' => 'Early wet season when rangelands green up with new shoots emerging. Critical period requiring careful management to allow establishment while beginning to provide improved nutrition.

MORPHOLOGICAL APPEARANCE:
• New shoots appear as first rains fall
• Green flush across the landscape
• Basal growth of perennial grasses
• Germination of annual grasses
• Leaf emergence on trees and shrubs
• Soil softening with moisture

FEEDING/WATER VALUE:
• Nutritional quality increases rapidly (crude protein: 8-12%)
• Young shoots highly palatable and digestible
• Suitable for early grazers including young stock
• Water availability improves with rain
• Natural water points begin to fill
• Reduced supplementation needs

HYGIENE/VEGETATION MANAGEMENT:
• Encourage regrowth by limiting early grazing pressure
• Control early weeds before seed set
• Implement deferred grazing in priority areas
• Remove livestock from sensitive recovery zones
• Plan rotational grazing schedules
• Repair fences and water points before full season

SUSCEPTIBILITY TO DEGRADATION:
• Moderate susceptibility as soil still soft
• Disturbance affects seedling establishment
• Trampling damage to emerging plants
• Compaction risk in wet soil conditions
• Erosion risk decreasing as cover increases

SUSCEPTIBILITY TO INVASIVE SPECIES:
• Invasive seed germination peak period
• Early weed control most effective now
• Prevent invasives from establishing competitive advantage
• Monitor for new invasive species introductions

CRITICAL MANAGEMENT REQUIREMENTS:
• Controlled access to allow grass establishment
• Strategic seedling protection through rest periods
• Early weed control interventions
• Water point preparation for coming demand
• Fertilizer application if practiced (manure or chemical)

POSSIBLE TOPICS FOR AESA:
• Early season indicators of rangeland health
• Soil moisture status assessment
• Germination success monitoring
• Weed species identification and mapping
• Optimal grazing commencement timing

AESA PARAMETERS:
• Plant density - count emerging tillers per square meter
• Species composition - desirable vs undesirable species ratio
• Soil moisture levels
• Early growth rates
• Weed emergence and density',
    'start_time' => 12,
    'end_time' => 16,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=800',
    'order' => 2,
    'is_active' => 1
]);

// Protocol 3: Growth Phase (5-9 Months)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Growth Phase (5-9 Months)',
    'activity_description' => 'Peak growing season with lush green pasture, rapid stem elongation, and maximum productivity. Optimal grazing period requiring active management to utilize forage efficiently while maintaining rangeland health.

MORPHOLOGICAL APPEARANCE:
• Lush green pasture dominates landscape
• Rapid stem elongation and leaf production
• High biomass accumulation
• Dense canopy development
• Plants reach 30-60cm height depending on species
• Vigorous vegetative growth before flowering

FEEDING/WATER VALUE:
• High forage quality (crude protein: 12-18%)
• Peak palatability and digestibility
• Maximum nutritional value for livestock
• Excellent weight gains possible
• Abundant water from rains and natural sources
• Reduced need for supplementation

HYGIENE/VEGETATION MANAGEMENT:
• Rotational grazing implementation critical
• Controlled stocking to prevent overutilization
• Some areas reserved for hay/silage production
• Monitor grazing pressure continuously
• Move livestock before overgrazing occurs
• Adaptive grazing based on plant response

SUSCEPTIBILITY TO DEGRADATION:
• Low susceptibility due to vigorous growth
• Rangeland can sustain moderate grazing pressure
• Good soil protection from dense canopy
• Recovery capacity high if damage occurs
• Monitor for localized overgrazing near water/shade

SUSCEPTIBILITY TO INVASIVE SPECIES:
• Low vulnerability - dense canopy inhibits invaders
• Desirable species competitive and vigorous
• Established perennials suppress weed germination
• However, disturbed areas still susceptible

CRITICAL MANAGEMENT REQUIREMENTS:
• Monitor carrying capacity to avoid overstocking
• Implement rotational grazing systems
• Adaptive grazing based on plant height and cover
• Reserve high-quality areas for conservation
• Plan for hay/silage making from surplus
• Control bush encroachment through mechanical means

POSSIBLE TOPICS FOR AESA:
• Forage biomass estimation using quadrat sampling
• Grazing capacity calculations
• Rotational grazing system design
• Hay making opportunity assessment
• Grazing pressure impact monitoring

AESA PARAMETERS:
• Biomass yield (kg dry matter per hectare)
• Species diversity and composition
• Nutritional value (protein, energy content)
• Plant height and vigor
• Utilization rate by livestock',
    'start_time' => 16,
    'end_time' => 36,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=800',
    'order' => 3,
    'is_active' => 1
]);

// Protocol 4: Flowering/Seeding Phase (9-15 Months)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Flowering & Seeding Phase (9-15 Months)',
    'activity_description' => 'Reproductive phase when grasses flower and set seed. Critical period for rangeland sustainability requiring seed production protection while continuing productive grazing.

MORPHOLOGICAL APPEARANCE:
• Seed heads develop and emerge above leaves
• Flowering occurs across grass species
• Plant height reaches maximum (60-120cm)
• Canopy closure complete in healthy rangelands
• Seed maturation progresses through stage
• Color changes from green to golden as seeds mature

FEEDING/WATER VALUE:
• Declining nutrient value as plants mature (crude protein: 8-10%)
• Fiber content increases with seed production
• Still adequate for maintenance and moderate production
• Palatability decreases slightly
• Water sources remain adequate
• Transition period requiring attention to animal nutrition

HYGIENE/VEGETATION MANAGEMENT:
• Seed dispersal management crucial for sustainability
• Rest periods allow seed drop and establishment
• Deferred grazing in priority reseeding areas
• Controlled access to flowering stands
• Plan for next season seed bank replenishment

SUSCEPTIBILITY TO DEGRADATION:
• Increased vulnerability compared to growth phase
• Compaction and trampling affect seed establishment
• Heavy grazing prevents adequate seed production
• Need careful management during seed drop
• Plan rest-rotation to allow natural reseeding

SUSCEPTIBILITY TO INVASIVE SPECIES:
• Weed emergence continues if soil disturbed
• Bush encroachment pressure remains
• Aggressive management of new weed patches
• Prevent invasive seed production

CRITICAL MANAGEMENT REQUIREMENTS:
• Seed retention strategies implementation
• Deferred grazing for seed drop (4-6 weeks minimum)
• Rotational rest periods for different pasture areas
• Light grazing pressure during seed maturation
• Hay making from mature stands
• Bush/tree thinning if needed

POSSIBLE TOPICS FOR AESA:
• Seed harvesting opportunities assessment
• Rest-rotation grazing strategies
• Seed bank recovery planning
• Nutritional supplementation needs evaluation

AESA PARAMETERS:
• Flowering ratio (percentage of plants flowering)
• Seed production potential
• Grazing impact on seed retention
• Plant species reproductive success
• Seed viability assessment',
    'start_time' => 36,
    'end_time' => 60,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd86?w=800',
    'order' => 4,
    'is_active' => 1
]);

// Protocol 5: Post-Maturity/Recovery Phase (15+ Months)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Post-Maturity & Recovery Phase (15+ Months)',
    'activity_description' => 'Late season through early dormancy when plants enter senescence, requiring strategic management for sustainability and preparation for next cycle.

MORPHOLOGICAL APPEARANCE:
• Leaf senescence begins as plants age
• Standing dead material accumulates
• Seeds dropped, establishing next generation
• Plants prepare for dormancy
• Recovery growth after grazing possible with moisture
• Color shifts from green to brown/golden

FEEDING/WATER VALUE:
• Low quality roughage source (crude protein: 5-7%)
• High fiber, low digestibility
• Adequate for maintenance of dry stock
• Supplementation needed for productive animals
• Water sources declining as dry season approaches
• Plan water provision strategies

HYGIENE/VEGETATION MANAGEMENT:
• Bush clearing operations if planned
• Residue management through grazing or burning
• Strategic removal of accumulated biomass
• Prepare for dormant season management
• Rest heavily grazed areas
• Infrastructure maintenance

SUSCEPTIBILITY TO DEGRADATION:
• Soil compaction in frequently used areas
• Bare patches from overgrazing vulnerable
• Trampling damage accumulates
• Erosion risk increases as cover decreases
• Rehabilitation needed for degraded zones

SUSCEPTIBILITY TO INVASIVE SPECIES:
• Residual invasives produce seed
• Seed bank buildup of undesirable species
• Plan control strategies for next season
• Bush control before dormancy

CRITICAL MANAGEMENT REQUIREMENTS:
• Rehabilitation planning for degraded areas
• Grazing pressure reduction on poor condition areas
• Supplementary feeding programs
• Water point development and maintenance
• Manure and compost application to depleted soils
• Seed collection of desirable species for restoration

POSSIBLE TOPICS FOR AESA:
• Pasture rejuvenation needs assessment
• Bush control strategy development
• Seed collection for restoration programs
• Water infrastructure evaluation
• Season performance review

AESA PARAMETERS:
• Regrowth rate after grazing
• Degradation extent and severity
• Bare ground percentage
• Species composition changes
• Carrying capacity evaluation',
    'start_time' => 60,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1566374357640-4a4a6c96c2e7?w=800',
    'order' => 5,
    'is_active' => 1
]);

// Protocol 6: Sustainable Grazing Systems
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Sustainable Grazing Systems Implementation',
    'activity_description' => 'Comprehensive grazing management systems to optimize rangeland productivity while maintaining or improving ecosystem health through strategic livestock movement and stocking rate management.

ROTATIONAL GRAZING PRINCIPLES:
• Divide rangeland into multiple paddocks (minimum 4-6)
• Graze each paddock for short period (7-21 days)
• Allow adequate rest period (30-90 days depending on season)
• Move livestock before overgrazing occurs
• Adjust rotation speed based on forage growth rate
• Monitor plant recovery during rest periods

STOCKING RATE DETERMINATION:
• Carrying capacity varies by season and rainfall
• Conservative stocking prevents degradation
• Calculate based on forage production and animal needs
• Livestock Unit (LU) concept: 1 LU = 250kg animal
• Dry season: 4-8 hectares per LU in semi-arid areas
• Wet season: 1-3 hectares per LU in same areas
• Adjust based on actual rangeland condition

ADAPTIVE GRAZING MANAGEMENT:
• Monitor rangeland condition continuously
• Adjust stocking rates based on forage availability
• Flexible livestock movement responding to growth
• Drought response plans including early destocking
• Opportunistic stocking during good seasons
• Conservative approach in uncertain conditions

GRAZING INDICATORS:
Take Half, Leave Half Rule:
• Never graze more than 50% of annual forage production
• Leave residual for soil protection and plant recovery
• Maintain 1000-1500 kg/ha residual biomass
• Critical for sustainability and productivity

Plant Height Management:
• Begin grazing when grasses reach 20-25cm
• Move livestock when grazed to 8-10cm height
• Never allow grazing below 5cm (growing points destroyed)
• Taller residual in dry areas for erosion control

SEASONAL GRAZING STRATEGIES:

DORMANT SEASON:
• Sacrifice areas - concentrate grazing to protect others
• Strategic supplementation to reduce grazing pressure
• Rest most vulnerable areas completely
• Use standing hay on ungrazed areas

SPROUTING SEASON:
• Defer grazing on 30-50% of rangeland
• Light grazing pressure on accessed areas
• Prioritize good condition range for early grazing
• Protect degraded areas for full growing season

GROWTH SEASON:
• Optimal utilization period
• Rapid rotations for even utilization
• Harvest surplus as hay or silage
• Control selective grazing through movement

SEEDING SEASON:
• Strategic rest for seed production
• Light grazing after seed drop
• Ensure seed rain on priority areas
• Plan next season\'s rotation

INFRASTRUCTURE FOR ROTATIONAL GRAZING:
• Fencing: Permanent boundaries, temporary internal divisions
• Water reticulation: Multiple water points for even grazing
• Handling facilities: Centrally located for easy livestock work
• Shade and shelter: Distributed across paddocks
• Access tracks: Enable livestock movement and monitoring

MONITORING AND RECORD KEEPING:
• Paddock grazing history (dates in, dates out)
• Rest period duration tracking
• Stocking rates per paddock per period
• Forage condition at entry and exit
• Rainfall records
• Animal performance data (weights, condition scores)
• Rangeland photo monitoring (fixed point photography)
• Species composition changes over time',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=800',
    'order' => 6,
    'is_active' => 1
]);

// Protocol 7: Rangeland Rehabilitation
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Rangeland Rehabilitation & Restoration',
    'activity_description' => 'Systematic approaches to restore degraded rangelands, control invasive species, and improve productivity through ecological and mechanical interventions.

DEGRADATION ASSESSMENT:
• Ground cover percentage (target >70%)
• Bare ground and erosion features
• Gully formation and severity
• Soil compaction levels
• Species composition shift toward undesirable plants
• Loss of perennial grasses
• Bush encroachment extent

REHABILITATION STRATEGIES:

1. EROSION CONTROL:
Physical Structures:
• Contour trenches to slow water flow
• Check dams in gullies
• Stone lines across slopes
• Terracing on steep areas
• Gabions for severe gully erosion

Biological Measures:
• Grass strips along contours
• Vetiver grass barriers
• Tree planting for stabilization
• Mulching bare areas
• Brush packing in gullies

2. SOIL IMPROVEMENT:
• Manure application (5-10 tons per hectare)
• Compost incorporation
• Lime application if soil acidic (pH <5.5)
• Gypsum for sodic soils
• Organic matter addition
• Minimal tillage to preserve structure

3. RESEEDING DEGRADED AREAS:
Seed Selection:
• Indigenous grass species adapted to area
• Drought-tolerant varieties
• Palatable and productive species
• Mix of different grass types (short, medium, tall)

Seeding Methods:
• Broadcasting with light coverage
• Drilling for better establishment
• Seed with early rains for moisture
• Protect seeded areas from grazing (6-12 months)
• Seed rate: 5-10 kg/ha depending on species

4. BUSH CONTROL:
Woody encroachment reduces grass production significantly.

Mechanical Methods:
• Chainsaw cutting of trees
• Tractor-drawn chains for clearing
• Manual cutting and removal
• Uprooting with machinery

Chemical Control:
• Selective herbicides (triclopyr, picloram)
• Stem application or cut-stump treatment
• Foliar spraying for shrubs
• Follow safety protocols strictly

Integrated Approach:
• Combination of cutting and herbicide
• Follow-up control of regrowth
• Prevent overgrazing that favors bush
• Maintain grass competition

FIRE MANAGEMENT:
• Controlled burning removes dead material
• Timing critical: Early dry season or late wet season
• Firebreaks essential
• Hot fires kill woody plants, stimulate grass
• Cool fires remove thatch, less impact on woody plants
• Requires permits and skilled personnel
• Fire rotation: Every 2-3 years in suitable areas

INVASIVE WEED CONTROL:

Prevention:
• Quarantine and clean vehicles/equipment
• Avoid overgrazing creating opportunities
• Maintain healthy competitive grass cover
• Monitor for new invasions regularly

Control Methods:
• Manual removal for small infestations
• Herbicide application for larger areas
• Biological control agents where available
• Grazing management to disadvantage weeds
• Prevent seed production and spread

Common Invasives:
• Parthenium (Parthenium hysterophorus)
• Lantana (Lantana camara)
• Prickly pear (Opuntia species)
• Mesquite (Prosopis species)
• Various thistle species

REINTRODUCTION OF DESIRABLE SPECIES:
• Collect seed from healthy nearby rangelands
• Nursery propagation of grasses
• Transplanting of grass tufts
• Protection during establishment
• Gradual integration into grazing rotation

TIMELINE FOR RECOVERY:
• Light degradation: 1-2 years with rest and management
• Moderate degradation: 3-5 years with interventions
• Severe degradation: 5-10+ years intensive rehabilitation
• Extremely degraded: May never fully recover to original state
• Patience and persistence essential

REHABILITATION SUCCESS MONITORING:
• Baseline photography before interventions
• Regular photo monitoring (same locations, same angle)
• Ground cover measurements annually
• Species composition surveys
• Erosion feature stabilization assessment
• Productivity comparisons with control areas
• Economic analysis of costs vs benefits',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=800',
    'order' => 7,
    'is_active' => 1
]);

// Protocol 8: Water & Soil Conservation
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Water Management & Soil Conservation',
    'activity_description' => 'Integrated water harvesting and soil conservation practices to improve rangeland productivity, livestock water access, and prevent environmental degradation.

WATER HARVESTING TECHNIQUES:

1. SURFACE WATER HARVESTING:
Contour Bunds:
• Earth embankments along contour lines
• Spacing: 10-30 meters depending on slope
• Height: 30-50cm, width: 1-2 meters
• Capture runoff, increase infiltration
• Plant grass on bunds for stabilization

Half-Moon Structures:
• Semi-circular bunds (diameter 2-4 meters)
• Positioned to catch runoff
• Plant trees or grasses in depression
• Ideal for semi-arid areas
• Low cost, high impact

Zai Pits:
• Small planting pits (20-30cm diameter, 10-15cm deep)
• Concentrate water and nutrients
• Add manure to pits before planting
• Traditional technique from West Africa
• Excellent for crop/grass establishment in hard soils

2. WATER STORAGE:
Farm Ponds:
• Excavated reservoirs for runoff storage
• Size: 10-50 cubic meters depending on catchment
• Line with clay or plastic if sandy soil
• Fence to prevent animal drowning and contamination
• Use for supplementary irrigation of fodder plots

Rock Catchments:
• Collect water from rock outcrops
• Channel to storage tanks or ponds
• Clean collection surface before rains
• High quality water with proper management

Roof Catchment:
• Harvest from buildings and shelters
• Gutters and downpipes to storage tanks
• First flush diversion for cleaner water
• Store in covered tanks

3. WATER POINT DEVELOPMENT:
Shallow Wells:
• Hand-dug wells in areas with high water table
• Depth: 5-20 meters typically
• Line with concrete rings or stones
• Cover to prevent contamination
• Hand pump or bucket system

Boreholes:
• Drilled deep wells (30-200+ meters)
• Professional drilling required
• Solar or diesel pumps
• Pipe water to multiple troughs
• More expensive but reliable

Spring Protection:
• Identify and protect natural springs
• Excavate spring area, install collection box
• Pipe water to livestock trough
• Protect catchment area from grazing
• Plant trees around spring

Water Troughs:
• Cement, plastic, or metal troughs
• Size: 2-4 meters length, 0.5 meter depth
• Float valves for automatic filling
• Cleaning access essential
• Shade near troughs reduces evaporation

LIVESTOCK WATER REQUIREMENTS:
• Cattle: 30-60 liters per day
• Goats/Sheep: 4-8 liters per day
• Calves/kids: 5-10 liters per day
• Needs increase in hot weather
• Lactating animals need more
• Distance to water: Maximum 3-5km for cattle, 2-3km for small stock

SOIL CONSERVATION PRACTICES:

1. VEGETATIVE MEASURES:
Grass Strips:
• Plant grass strips along contours
• Width: 1-2 meters
• Spacing: 10-30 meters based on slope
• Use deep-rooted native grasses
• Trap sediment, reduce runoff velocity

Agroforestry:
• Integrate trees with grazing areas
• Fodder trees: Leucaena, Calliandra, Sesbania
• Multipurpose trees for fruit, timber, fodder
• Spacing: 5-10 meters within grazing area
• Boundary tree lines

Living Fences:
• Plant hedges as boundaries
• Species: Jatropha, Sisal, Euphorbia
• Provide wind protection
• Prevent soil erosion on edges

2. MECHANICAL MEASURES:
Terracing:
• Bench terraces on slopes >15%
• Reduces slope gradient
• Labor intensive but effective
• Requires maintenance
• Increases usable land

Stone Bunds:
• Lines of stones along contours
• Height: 30-50cm
• Permeable - allows water infiltration
• Very durable, minimal maintenance
• Use locally available stones

Cut-off Drains:
• Channels to divert excess water
• Install above vulnerable areas
• Lead water to safe disposal point
• Grade for non-erosive velocity
• Stabilize with grass

3. SOIL MANAGEMENT:
Organic Matter Addition:
• Compost from livestock manure
• Application rate: 5-10 tons per hectare
• Improves soil structure and fertility
• Increases water holding capacity
• Apply before rains

Minimal Tillage:
• Avoid unnecessary soil disturbance
• Direct seeding where possible
• Preserve soil structure
• Maintain organic matter
• Reduces erosion risk

Mulching:
• Apply grass/crop residues on bare soil
• Thickness: 5-10cm
• Reduces evaporation
• Suppresses weeds
• Protects from raindrop impact

EROSION TYPES AND SOLUTIONS:

Sheet Erosion:
• Uniform soil loss across area
• Solution: Increase ground cover, mulch, grass strips

Rill Erosion:
• Small channels forming
• Solution: Fill and grass, contour bunding

Gully Erosion:
• Deep channels >30cm
• Solution: Check dams, brush packing, grass planting, exclude livestock

Wind Erosion:
• Soil blown away in dusty conditions
• Solution: Windbreaks, maintain ground cover, mulch',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1592695674038-d8c18ce8c1e3?w=800',
    'order' => 8,
    'is_active' => 1
]);

// Protocol 9: Fodder Production Systems
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Fodder Production & Conservation',
    'activity_description' => 'Strategic fodder production and preservation systems to supplement grazing during critical periods, improve livestock nutrition, and reduce pressure on natural rangelands.

FODDER CULTIVATION:

IMPROVED PASTURE ESTABLISHMENT:
Species Selection:
• Rhodes grass (Chloris gayana) - drought tolerant
• Napier grass (Pennisetum purpureum) - high yielding
• Bracharia species - good ground cover
• Desmodium - legume for nitrogen and protein
• Lucerne (alfalfa) - high protein, deep rooted
• Oats - annual fodder crop

Land Preparation:
• Clear land and plow or disc
• Level if irrigation planned
• Incorporate manure (5-10 tons per hectare)
• Test soil pH, lime if needed
• Fine seedbed preparation

Planting Methods:
• Broadcasting: 10-15 kg seed per hectare
• Row planting: 8-10 kg per hectare, better establishment
• Vegetative: Stem cuttings for Napier (10,000 cuttings/ha)
• Plant with onset of rains
• Light covering of seed

Establishment Management:
• Weed control critical in first 2-3 months
• First grazing/cutting when 30-40cm tall
• Light grazing to allow establishment
• Fertilizer application after establishment

FODDER TREES/SHRUBS:
Calliandra (Calliandra calothyrsus):
• Protein-rich fodder (20-25% crude protein)
• Plant at 1×1 meter spacing in fodder banks
• First harvest 9-12 months after planting
• Subsequent cuts every 3-4 months
• Feed fresh or dried to livestock

Leucaena (Leucaena leucocephala):
• High protein (22-25%)
• Nitrogen-fixing legume
• Limit to 30% of diet (mimosine toxicity)
• Cut-and-carry system
• Coppices well after cutting

Sesbania (Sesbania sesban):
• Fast-growing shrub
• Protein content 18-22%
• Establishes quickly from seed
• Annual or short-lived perennial
• Good for green manure too

Tree Planting:
• Spacing: 1×1 meter in fodder banks, 5×5 in grazing areas
• Protect from livestock first 1-2 years
• Prune regularly to promote bushy growth
• Combine multiple species for diversity

FODDER CONSERVATION:

HAY MAKING:
Optimal Hay Making Conditions:
• Harvest at flowering stage for quality
• Dry season or dry spell during rains
• 3-5 consecutive sunny days needed
• Cut in morning after dew dries
• Dry to 15-20% moisture before storage

Hay Making Process:
1. Cutting: Use scythe, slasher, or mower at 5-10cm height
2. Tedding: Spread evenly for drying, turn once or twice
3. Raking: Gather into windrows when dry (2-3 days)
4. Baling: Tie into bales (40-60kg) or stack loose
5. Storage: Under roof, on raised platform, good ventilation

Hay Quality Indicators:
• Green color (not brown/moldy)
• Fresh smell (not musty)
• Leafy with few stems
• Dry, breaks easily
• Free from dust and mold

Storage:
• Roofed structure with ventilation
• Raised floor prevents moisture absorption
• Stack with airspace between bales
• Cover if no roof available
• Protect from rain and rodents

SILAGE MAKING:
Silage is fermented fodder, suitable when drying impossible.

Silage Process:
1. Harvest: When plants at milk stage (maize) or early flower (grasses)
2. Chopping: Cut into 2-5cm pieces for compaction
3. Wilting: Dry to 65-70% moisture if too wet
4. Ensiling: Fill pit/trench/bag, compact well to exclude air
5. Sealing: Cover with plastic, seal edges, add weight
6. Fermentation: 3-4 weeks before opening

Silage Storage Options:
• Silage pit: Excavated trench, lined with plastic
• Silage bags: Plastic bags for small quantities
• Above-ground stack: Compact and seal with plastic
• Tower silos: More permanent but expensive

Quality Silage Characteristics:
• pH 3.8-4.2 (acidic)
• Yellow-green to olive color
• Pleasant acidic smell (not putrid)
• Firm texture, not slimy
• Animals eat readily

FEEDING STRATEGIES:

Dry Season Supplementation:
• Feed hay when rangeland quality declines
• Provide 3-5kg hay per cattle per day
• Small stock: 0.5-1kg per head daily
• Combine with concentrates if available
• Always provide clean water

Drought Feeding:
• Hay, silage, crop residues as base
• Supplement with concentrates, minerals
• Cactus (prickly pear) emergency fodder
• Urea-treated crop residues
• Rationing system to make fodder last

Strategic Feeding:
• Priority: Pregnant and lactating females, young stock
• Dry adults require less supplementation
• Time feeding to critical production periods
• Weaning time, breeding season priority

CROP RESIDUES:
• Maize stover: Good bulk, low protein
• Wheat/barley straw: Similar to maize
• Bean haulms: Higher protein, good quality
• Urea treatment: Improves digestibility of straw (4kg urea per 100kg straw)
• Chopping improves intake and reduces waste

FODDER PRODUCTION ECONOMICS:
• Calculate costs: Land, seed, labor, fertilizer
• Compare to purchased feeds or hay
• Consider labor requirements and timing
• Evaluate benefit to livestock performance
• Strategic small-scale production most practical
• 0.5-1 hectare can provide significant dry season supplement',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=800',
    'order' => 9,
    'is_active' => 1
]);

// Protocol 10: Rangeland Monitoring & Assessment
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Rangeland Monitoring & AESA Implementation',
    'activity_description' => 'Systematic monitoring and assessment protocols using Agro-Ecological System Analysis (AESA) to track rangeland health, guide management decisions, and measure progress toward sustainability goals.

RANGELAND CONDITION ASSESSMENT:

VEGETATION PARAMETERS:

1. Ground Cover Estimation:
Method: Quadrat sampling (1×1 meter frame)
• Throw randomly in representative areas
• Count: 20-30 samples per paddock
• Record: Percentage covered by vegetation, litter, bare soil
• Target: >70% cover in good season, >40% in dry season

2. Species Composition:
• Identify all grass and plant species present
• Classify as desirable, less desirable, undesirable
• Calculate percentage of each category
• Monitor changes over seasons/years
• Good rangeland: >60% desirable species

3. Plant Density:
• Count number of plants per square meter
• Separate by species or category
• Compare to baseline or reference sites
• Dense stand indicates good condition

4. Plant Vigor and Height:
• Measure average plant height
• Assess leaf color (green vs brown)
• Check for disease or pest damage
• Flowering and seed production capacity

SOIL HEALTH INDICATORS:

1. Soil Structure:
• Dig soil pit (30cm deep)
• Observe layers, root penetration
• Test aggregation: Does soil crumble or slump?
• Look for earthworms and soil life

2. Soil Compaction:
• Use penetrometer or metal rod
• Assess resistance to penetration
• Compacted soil limits water infiltration and root growth
• Causes: Overgrazing, machinery, trampling

3. Erosion Features:
• Map gullies, rills, sheet erosion
• Measure gully dimensions
• Photograph for comparison
• Rate severity: Light, moderate, severe

4. Organic Matter:
• Darker soil = higher organic matter
• Laboratory testing for accurate measurement
• Increased with good management
• Target: >2% organic carbon

BIODIVERSITY ASSESSMENT:
• Count number of plant species (richness)
• Record animal diversity: Birds, insects, reptiles
• Diverse system more resilient
• Indicator species: Presence shows healthy ecosystem

FIXED-POINT PHOTOGRAPHY:
• Select permanent photo monitoring points
• Mark locations with GPS coordinates
• Take photos same location, angle, time of year
• Build photo series over years
• Visual evidence of improvement/degradation

AESA METHODOLOGY FOR RANGELANDS:

STEP 1: OBSERVATION
Groups walk transects through rangeland
Record:
• Dominant grass species
• Weed presence and types
• Ground cover percentage estimate
• Soil exposure areas
• Erosion features
• Animal tracks and grazing patterns
• Water availability
• Bush/tree density

STEP 2: ANALYSIS
Group discusses observations:
• What has changed since last observation?
• What is the trend: Improving, stable, declining?
• What factors are influencing rangeland condition?
• Rainfall adequacy
• Stocking rate appropriate?
• Invasive species spreading?

STEP 3: DRAWING AND PRESENTATION
• Create visual representation on chart paper
• Draw rangeland features observed
• Mark problem areas
• Show grazing patterns
• Present to larger group

STEP 4: DECISION-MAKING
Based on AESA findings, group decides:
• Continue current management?
• Reduce stocking rate?
• Rest specific areas?
• Implement interventions (seeding, erosion control)?
• Adjust grazing rotation?
• When to repeat AESA (monthly, quarterly)?

MONITORING FREQUENCY:
• Intensive monitoring: Monthly during growing season
• Basic monitoring: Quarterly year-round
• Annual comprehensive assessment
• After major events: Drought, flood, fire

KEY PERFORMANCE INDICATORS:

Production Indicators:
• Forage biomass (kg/ha)
• Species composition improvements
• Animal productivity per hectare
• Livestock weight gains
• Calving/kidding rates

Environmental Indicators:
• Ground cover percentage
• Erosion severity decline
• Species diversity increase
• Soil organic matter improvement
• Water infiltration rates

Economic Indicators:
• Stocking rate sustained
• Income per hectare
• Cost of supplementation
• Drought resilience
• Market value of livestock

RECORD KEEPING:
Essential Records:
• Rainfall data (daily/weekly)
• Grazing history (paddock use dates)
• Stocking rates and changes
• AESA observations and decisions
• Photographs (dated and located)
• Interventions implemented (dates, locations, costs)
• Animal performance (weights, sales, mortality)
• Vegetation assessments (annual minimum)

Record Format:
• Notebook or digital records
• Maps showing paddocks and features
• Spreadsheets for numeric data
• Photo library organized by date and location

ADAPTIVE MANAGEMENT:
• Review monitoring data regularly
• Adjust practices based on evidence
• Learn from successes and failures
• Share experiences with other farmers
• Flexibility to respond to variable conditions
• Long-term perspective (5-10+ years)

COMMUNITY MONITORING:
• Group monitoring more sustainable
• Shared learning and decision-making
• Peer pressure for good practices
• Exchange visits to compare rangelands
• Collective action on landscape-scale issues',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=800',
    'order' => 10,
    'is_active' => 1
]);

echo "✓ Rangeland Management Enterprise Created (ID: {$enterprise->id})" . PHP_EOL;
echo "✓ All 10 Protocols Created Successfully" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "✅ RANGELAND MANAGEMENT COMPLETE!" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "Enterprise ID: {$enterprise->id}" . PHP_EOL;
echo "Protocols: " . count($protocols) . PHP_EOL . PHP_EOL;
echo "PHENOLOGY PHASES:" . PHP_EOL;
echo "1. Dormant Phase (0-3 months)" . PHP_EOL;
echo "2. Sprouting Phase (3-4 months)" . PHP_EOL;
echo "3. Growth Phase (5-9 months)" . PHP_EOL;
echo "4. Flowering/Seeding Phase (9-15 months)" . PHP_EOL;
echo "5. Post-Maturity/Recovery (15+ months)" . PHP_EOL . PHP_EOL;
echo "MANAGEMENT PROTOCOLS:" . PHP_EOL;
echo "6. Sustainable Grazing Systems" . PHP_EOL;
echo "7. Rangeland Rehabilitation" . PHP_EOL;
echo "8. Water & Soil Conservation" . PHP_EOL;
echo "9. Fodder Production" . PHP_EOL;
echo "10. Monitoring & AESA" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
