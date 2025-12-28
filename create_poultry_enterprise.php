<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;

$enterprise = Enterprise::create([
    'name' => 'Poultry (Chicken) Production',
    'description' => 'Comprehensive chicken production system covering chick rearing, grower management, and layer/broiler production with emphasis on biosecurity, nutrition, disease control, and optimal productivity for both meat and egg production.',
    'type' => 'livestock',
    'duration' => 24,
    'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
    'is_active' => 1
]);

$protocols = [];

// Protocol 1: Chick Stage (0-4 Weeks)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Chick Stage Management (0-4 Weeks)',
    'activity_description' => 'Critical brooding period requiring intensive management for chick survival and development. This foundation stage determines future productivity through proper temperature control, nutrition, and disease prevention.

MORPHOLOGICAL APPEARANCE:
• Covered with soft downy feathers (yellow, brown, or white depending on breed)
• Small body size: Day-old weight 40-45g, reaching 300-400g by week 4
• Weak legs and beak requiring non-slip surfaces
• Eyes open and alert in healthy chicks
• Rapid growth visible daily
• Pin feathers begin emerging by week 3-4

FEEDING/WATERING REQUIREMENTS:
• Starter feed: High protein 20-22% crude protein
• Crumble form for easy consumption
• Feed ad libitum (always available)
• Consumption: 20-30g per chick per day
• Clean water essential - provide immediately upon arrival
• Water temperature: Room temperature (18-21°C)
• Drinker space: 2cm per chick minimum
• Add electrolytes and vitamins first 3 days to reduce stress
• Ensure all chicks find feed and water within first 6 hours

HYGIENE REQUIREMENTS:
• Clean, warm brooder prepared before chick arrival
• Temperature at chick level: 32-35°C week 1, reduce 3°C weekly
• Disinfect all equipment before use
• Fresh litter: Wood shavings, rice husks, or chopped straw (5-8cm depth)
• Remove wet litter daily
• Clean feeders and drinkers twice daily
• Strict biosecurity - limit visitors
• Footbath at entrance with disinfectant

SUSCEPTIBILITY TO DISEASES:
• Very susceptible to environmental stress and pathogens
• Coccidiosis: Caused by Eimeria parasites, bloody droppings
• Newcastle Disease: Highly contagious viral disease
• Gumboro (Infectious Bursal Disease): Affects immune system
• Aspergillosis: Fungal infection from moldy litter
• Omphalitis: Navel infection in poorly hatched chicks
• Salmonellosis: Bacterial infection causing mortality
• Vaccination schedule critical: Newcastle day 7-10, Gumboro day 14

SUSCEPTIBILITY TO PARASITES:
• Susceptible to worms if floor dirty
• External mites and lice from contaminated housing
• Coccidiosis prevention through medicated feed or vaccination
• Clean brooder essential for parasite prevention

CRITICAL MANAGEMENT REQUIREMENTS:
• Brooder management paramount for survival
• Temperature regulation: Use heat lamps, gas brooders, or charcoal stoves
• Observe chick behavior: Clustering = cold, panting = hot, spread out = comfortable
• Light: 23 hours light first week to help chicks find feed/water
• Gradually reduce to 14-16 hours by week 4
• Ventilation without drafts essential
• Check for mortality daily, investigate causes
• Cull weak or deformed chicks early
• Record daily: Temperature, mortality, feed consumption

POSSIBLE TOPICS FOR AESA:
• Brooding management techniques
• Chick nutrition requirements
• Disease recognition and prevention
• Temperature and ventilation balance
• Early mortality causes and solutions

AESA PARAMETERS:
• Weight gain: Target 300-400g by week 4
• Mortality rate: Should be under 3-5%
• Behavior observation: Active, alert, eating
• Uniformity: Flock should have similar sizes',
    'start_time' => 0,
    'end_time' => 4,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=800',
    'order' => 1,
    'is_active' => 1
]);

// Protocol 2: Grower Stage (5-18 Weeks)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Grower Stage Management (5-18 Weeks)',
    'activity_description' => 'Development phase where birds build frame, develop immune system, and prepare for production. Management focuses on controlled growth, disease prevention, and proper feather development.

MORPHOLOGICAL APPEARANCE:
• Growing feathers replace down completely
• Increased body weight: 400g at week 5 to 1.5-1.8kg at week 18
• Comb and wattles begin to appear and develop color
• Sexual differentiation becomes clear
• Frame development with muscle mass increasing
• Broilers: Rapid muscular development, ready for market by week 6-8
• Layers: Slower growth, skeletal development priority

FEEDING/WATERING REQUIREMENTS:
• Grower feed: 16-18% crude protein
• Pelleted or crumble form
• Feed consumption: 50-100g per bird daily increasing with age
• Ad lib feeding for broilers, restricted for layers to prevent obesity
• Clean water essential, consumption increases with growth
• Feeder space: 8-10cm per bird
• Drinker space: 2-3cm per bird
• Grit provision helps digestion
• Gradual diet changes over 5-7 days to prevent stress

HYGIENE REQUIREMENTS:
• Dry litter maintained, add fresh as needed
• Clean housing essential, good ventilation
• Remove caked litter regularly
• Disinfection of feeders and drinkers weekly
• Ammonia levels below 10ppm
• Proper stocking density: 8-10 birds per square meter maximum
• Separate by sex if growth rates differ significantly

SUSCEPTIBILITY TO DISEASES:
• Susceptible to respiratory and enteric diseases
• Marek\'s Disease: Viral causing paralysis, vaccinate at hatch
• Fowl Pox: Virus causing skin lesions, vaccinate 6-8 weeks
• Coccidiosis: Still a risk, use anticoccidial drugs
• Infectious Coryza: Bacterial respiratory disease
• Chronic Respiratory Disease: Mycoplasma infections
• Booster vaccinations: Newcastle, Gumboro as per schedule

SUSCEPTIBILITY TO PARASITES:
• Susceptible to ecto and endoparasites
• Lice and mites common if hygiene poor
• Roundworms and tapeworms from contaminated litter
• Regular deworming recommended at 8 and 16 weeks
• External parasite control through dusting or spraying

CRITICAL MANAGEMENT REQUIREMENTS:
• Vaccination schedule adherence critical
• Deworming program implementation
• Feed and light control for layers (prevent early laying)
• Monitor growth through weekly weighing
• Biosecurity maintenance
• Separate layers and broilers if both kept
• Broilers: Market at 6-8 weeks (2-2.5kg live weight)
• Layers: Transfer to layer house at 16-18 weeks

POSSIBLE TOPICS FOR AESA:
• Grower health monitoring
• Vaccination schedule management
• Light and feed management for layers
• Disease signs recognition
• Growth rate optimization

AESA PARAMETERS:
• Weight gain: Track weekly, compare to standard
• Disease signs monitoring: Respiratory, digestive
• Feathering quality: Good coverage, no pecking damage
• Uniformity: 80% of flock within 10% of average weight',
    'start_time' => 5,
    'end_time' => 18,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1612170153139-6f881ff1c76a?w=800',
    'order' => 2,
    'is_active' => 1
]);

// Protocol 3: Layer Production (19+ Weeks)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Layer Production Stage (19+ Weeks)',
    'activity_description' => 'Egg production phase requiring precise nutrition, consistent management, and optimal conditions for sustained high production over 12-18 months.

MORPHOLOGICAL APPEARANCE:
• Fully developed features: Bright red comb and wattles
• Mature body weight: 1.8-2.2kg for layers
• Point of lay indicators: Pelvic bones spread, red comb, squatting behavior
• Broilers: Fully muscular, 2.5-3.5kg at 8 weeks

FEEDING/WATERING REQUIREMENTS FOR LAYERS:
• Layer mash with high calcium: 16-18% protein, 3.5-4% calcium
• Feed consumption: 110-130g per bird per day
• Provide calcium supplement (oyster shells, limestone grit) separately
• Unlimited clean water: 250-300ml per bird daily
• Feeder space: 10-12cm per bird
• Water critical: No water = no eggs within 24 hours

FEEDING FOR BROILERS:
• Finisher feed: 18-20% protein, high energy
• Ad libitum feeding for maximum growth
• Feed conversion ratio target: 1.8-2.0

HYGIENE REQUIREMENTS:
• Clean nests essential: 1 nest per 4-5 hens
• Daily manure removal or deep litter system
• Biosecurity enforcement strictly
• Rodent and wild bird control
• Regular disinfection schedule
• Ventilation for ammonia control
• Dust reduction measures

SUSCEPTIBILITY TO DISEASES:
• Highly susceptible if biosecurity fails
• Avian Influenza: Devastating viral disease, reportable
• Egg Peritonitis: Bacterial infection of reproductive tract
• Infectious Bronchitis: Affects egg production
• Fowl Cholera: Bacterial causing sudden death
• Layer fatigue: Calcium deficiency causing paralysis
• Mycoplasma: Reduces egg production and hatchability

SUSCEPTIBILITY TO PARASITES:
• Susceptible if hygiene poor
• Northern fowl mite: Lives on birds, causes anemia
• Red mites: Hide in cracks, feed at night
• Lice: Cause irritation and feather damage
• Worms: Regular deworming every 3-4 months
• Coccidiosis: Can occur in adults under stress

CRITICAL MANAGEMENT REQUIREMENTS:
• Routine health checks daily
• Egg production recording
• Feed and water quality monitoring
• Light program: 16 hours for layers
• Nest management and egg collection 2-3 times daily
• Cull poor layers and sick birds promptly
• Molting management: Induced or natural
• Replacement planning: Replace flock after 72-80 weeks

POSSIBLE TOPICS FOR AESA:
• Egg production optimization
• Biosecurity protocols
• Market readiness for broilers
• Layer nutrition management
• Culling strategies

AESA PARAMETERS:
• Egg production rate: Target 85-95% peak, 70-75% annual average
• Feed conversion: 2.0-2.2 kg feed per dozen eggs
• Body weight maintenance
• Egg quality: Shell strength, size, internal quality
• Broiler feed conversion and growth rate',
    'start_time' => 19,
    'end_time' => 96,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
    'order' => 3,
    'is_active' => 1
]);

// Protocol 4: Disease Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Comprehensive Disease Management',
    'activity_description' => 'Integrated disease prevention and control covering major poultry diseases with vaccination schedules, biosecurity measures, and treatment protocols.

MAJOR POULTRY DISEASES:

1. NEWCASTLE DISEASE (NCD)
• Highly contagious viral disease affecting all ages
• Symptoms: Respiratory distress, greenish diarrhea, twisted neck, paralysis, sudden death
• Mortality: Up to 100% in unvaccinated flocks
• Prevention: Vaccination (day 7, week 4, every 2-3 months booster)
• No treatment - supportive care only
• Reportable disease in most countries

2. GUMBORO (Infectious Bursal Disease)
• Viral disease destroying immune system
• Affects 3-6 week old chicks primarily
• Symptoms: Depression, ruffled feathers, watery droppings, sudden death
• Prevention: Vaccination day 14 and day 21
• No specific treatment
• Can cause immunosuppression making other diseases worse

3. FOWL POX
• Viral disease with dry and wet forms
• Dry form: Scabs on comb, wattles, face
• Wet form: Lesions in mouth and throat, breathing difficulty
• Prevention: Vaccination at 6-8 weeks (wing web stab method)
• Treatment: Supportive, remove scabs, apply iodine
• Mosquitoes transmit, so control vectors

4. AVIAN INFLUENZA (Bird Flu)
• Highly pathogenic strains cause high mortality
• Symptoms: Sudden death, respiratory signs, drop in egg production, swollen head
• Prevention: Biosecurity, prevent contact with wild birds
• Reportable disease - notify authorities immediately
• Culling of infected flocks standard response

5. INFECTIOUS BRONCHITIS
• Viral respiratory disease
• Symptoms: Coughing, sneezing, nasal discharge, drop in egg production
• Affects egg quality (thin shells, watery whites)
• Prevention: Vaccination available in some regions
• Treatment: Supportive care, antibiotics for secondary infections

6. COCCIDIOSIS
• Protozoal parasite affecting intestines
• Symptoms: Bloody droppings, weakness, poor growth
• Mortality can be high in chicks
• Prevention: Anticoccidial drugs in feed, coccidiosis vaccine
• Treatment: Sulfa drugs, amprolium
• Good litter management essential

7. FOWL CHOLERA (Pasteurellosis)
• Bacterial disease causing septicemia
• Symptoms: Sudden death, swollen wattles, greenish droppings
• Affects older birds more
• Prevention: Vaccination in endemic areas, biosecurity
• Treatment: Antibiotics (sulfadimethoxine, penicillin)

8. CHRONIC RESPIRATORY DISEASE (CRD)
• Mycoplasma gallisepticum bacterial infection
• Symptoms: Nasal discharge, coughing, swollen sinuses
• Reduces egg production and growth
• Prevention: Buy from Mycoplasma-free sources
• Treatment: Antibiotics (tylosin, enrofloxacin) provide temporary relief

9. MAREK\'S DISEASE
• Viral disease causing tumors and paralysis
• Symptoms: Paralysis of legs/wings, grey eye, tumors
• Affects 2-5 month old birds
• Prevention: Vaccination at hatch
• No treatment

10. INFECTIOUS CORYZA
• Bacterial respiratory disease
• Symptoms: Swollen face, nasal discharge, foul smell
• Spreads rapidly in flock
• Prevention: Vaccination, all-in-all-out management
• Treatment: Antibiotics, cull infected birds

VACCINATION SCHEDULE:
Day 1: Marek\'s disease (at hatchery)
Day 7-10: Newcastle Disease (eye drop or spray)
Day 14: Gumboro (drinking water)
Day 21: Gumboro booster
Week 6: Fowl Pox (wing web)
Week 8: Newcastle booster
Week 16: Newcastle + Infectious Bronchitis (layers)
Every 3 months: Newcastle booster (layers)

BIOSECURITY MEASURES:
• Controlled access - no unnecessary visitors
• Footbaths at all entry points
• Dedicated farm clothing and boots
• All-in-all-out system preferred
• Quarantine new birds 2-3 weeks
• Control rodents and wild birds
• Proper disposal of dead birds
• Clean and disinfect between batches
• Source chicks from reputable hatcheries only',
    'start_time' => 0,
    'end_time' => 96,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1584608473084-e0ce2f0c8716?w=800',
    'order' => 4,
    'is_active' => 1
]);

// Protocol 5: Parasite Control
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Parasite Control Program',
    'activity_description' => 'Comprehensive internal and external parasite management to maintain flock health and productivity.

INTERNAL PARASITES:

1. ROUNDWORMS (Ascaridia galli)
• Live in small intestine
• Symptoms: Weight loss, poor growth, reduced egg production
• Heavy infections cause intestinal blockage
• Treatment: Piperazine, fenbendazole, levamisole
• Deworming schedule: 8, 16, 24 weeks, then every 3 months

2. CECAL WORMS (Heterakis gallinarum)
• Carry blackhead disease (Histomoniasis)
• Less pathogenic themselves
• Treatment: Same as roundworms
• Prevention: Clean litter, prevent fecal contamination of feed/water

3. TAPEWORMS
• Require intermediate hosts (beetles, flies, snails)
• Symptoms: Weight loss, poor production
• Treatment: Praziquantel
• Prevention: Control intermediate hosts through sanitation

4. CAPILLARIA (Thread Worms)
• Hair-like worms in intestines
• Cause diarrhea and weight loss
• Treatment: Levamisole, fenbendazole
• Good hygiene essential

EXTERNAL PARASITES:

1. RED MITES (Dermanyssus gallinae)
• Hide in cracks during day, feed on birds at night
• Blood-suckers causing anemia, stress, reduced production
• Check by inspecting roosts and cracks at night with flashlight
• Treatment: Spray housing with acaricides (permethrin), dust birds
• Repeat treatment weekly for 3 weeks

2. NORTHERN FOWL MITE (Ornithonyssus sylviarum)
• Live continuously on birds, especially around vent
• Cause feather damage, anemia, reduced laying
• Check by examining feathers and skin
• Treatment: Dusting with permethrin or carbaryl
• More difficult to control than red mites

3. LICE (Mallophaga)
• Chewing lice feeding on feather and skin debris
• Cause irritation, feather damage, restlessness
• Common species: Shaft louse, body louse, head louse
• Check by parting feathers, lice visible on skin and feathers
• Treatment: Permethrin dust, repeat after 10 days

4. STICKTIGHT FLEA (Echidnophaga gallinacea)
• Embed in skin around eyes, comb, wattles
• Cause anemia and secondary infections
• Treatment: Manual removal, apply petroleum jelly, ivermectin
• Control in environment crucial

5. FOWL TICKS
• Feed periodically, hide in cracks
• Transmit diseases and cause anemia
• Treatment: Spray housing with acaricides
• Seal cracks in housing

DEWORMING PROGRAM:
• Chicks: First deworming at 8 weeks
• Growers: Deworm at 16 weeks
• Layers: Every 3-4 months
• Broilers: May not need if good hygiene and short production cycle
• Rotate drug classes to prevent resistance
• Observe withdrawal periods before consuming eggs/meat

EXTERNAL PARASITE CONTROL:
• Weekly inspection of birds
• Monthly housing inspection for mites
• Dust baths for birds (wood ash, sand)
• Treat immediately when parasites detected
• Whole flock treatment necessary
• Include housing treatment for mites
• Repeat treatments to break life cycle',
    'start_time' => 0,
    'end_time' => 96,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1589923158776-cb4485d99fd6?w=800',
    'order' => 5,
    'is_active' => 1
]);

// Protocol 6: Housing & Environment
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Housing & Environmental Management',
    'activity_description' => 'Optimal housing design and environmental control for different production systems ensuring bird welfare and maximum productivity.

HOUSING SYSTEMS:

DEEP LITTER SYSTEM:
• Most common for smallholders
• Floor covered with litter material (wood shavings, rice husks, sawdust)
• Litter depth: 10-15cm
• Advantages: Low cost, simple, manure incorporated
• Disadvantages: Disease risk if wet, labor for litter management
• Suitable for: All types (layers, broilers)

SLATTED FLOOR/RAISED FLOOR:
• Birds on raised wire or wooden slats
• Droppings fall through to pit below
• Advantages: Better hygiene, less disease
• Disadvantages: Higher cost, leg problems possible
• Suitable for: Layers primarily

BATTERY CAGE SYSTEM:
• Individual or group cages
• Highest stocking density
• Advantages: High production, easy management, clean eggs
• Disadvantages: High capital cost, welfare concerns
• Suitable for: Commercial layer operations

FREE RANGE:
• Birds access outdoor area during day
• Advantages: Natural behavior, marketing advantage
• Disadvantages: Disease exposure, predator risk, lower production
• Suitable for: Organic production, niche markets

SPACE REQUIREMENTS:
Chicks (0-4 weeks): 0.05-0.07 sq.m per bird
Growers (5-18 weeks): 0.10-0.15 sq.m per bird
Layers (deep litter): 0.15-0.20 sq.m per bird
Layers (cages): 450-550 sq.cm per bird
Broilers (finisher): 0.07-0.10 sq.m per bird

VENTILATION:
• Essential for ammonia removal and temperature control
• Natural ventilation: Open sides with curtains
• Mechanical ventilation: Fans for large operations
• Minimum: 0.03 cubic meters per minute per kg bird weight
• Install vents near roof, avoid drafts at bird level

TEMPERATURE CONTROL:
Chicks: 32-35°C week 1, reduce 3°C weekly
Growers: 18-24°C optimal
Adults: 18-21°C optimal for production
Above 27°C: Reduced feed intake and egg production
Below 15°C: Increased feed consumption

LIGHTING PROGRAM:
CHICKS: 23 hours light first week, reduce to 14-16 hours
GROWERS (layers): 10-12 hours to delay maturity, gradually increase
LAYERS: Increase to 16 hours at 18 weeks, maintain or gradually increase to 17 hours
BROILERS: 23 hours light for maximum feed intake

NESTING BOXES (Layers):
• 1 nest per 4-5 hens
• Size: 30×30×30cm
• Elevated 40-60cm off floor
• Dark, comfortable bedding (straw, shavings)
• Sloped roof prevents roosting on top

PERCHES (Layers):
• 20-25cm perch space per bird
• Height: 60-80cm above floor
• Smooth round poles 4-5cm diameter
• Prevents leg problems and dirty eggs

FEEDERS AND DRINKERS:
Trough feeders: 8-10cm per bird
Hanging tube feeders: 1 per 25-30 birds
Bell drinkers: 1 per 80-100 birds
Nipple drinkers: 1 per 10-15 birds

BIOSECURITY INFRASTRUCTURE:
• Footbaths at each house entrance
• Hand washing facilities
• Dedicated equipment per house
• Fence around farm perimeter
• Wild bird exclusion (netting, closed sides)
• Rodent-proof feed storage',
    'start_time' => 0,
    'end_time' => 96,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1589421771101-486e8b0b32d2?w=800',
    'order' => 6,
    'is_active' => 1
]);

// Protocol 7: Nutrition Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Nutrition & Feed Management',
    'activity_description' => 'Complete feeding program covering nutritional requirements, feed formulation, and feeding strategies for optimal growth and production.

NUTRITIONAL REQUIREMENTS:

CHICK STARTER (0-4 weeks):
Crude Protein: 20-22%
Energy: 2900-3000 kcal/kg
Calcium: 1.0%
Phosphorus: 0.45%
Lysine: 1.1%
Methionine: 0.5%

GROWER (5-18 weeks):
Crude Protein: 16-18%
Energy: 2800-2900 kcal/kg
Calcium: 0.9%
Phosphorus: 0.42%

LAYER (19+ weeks):
Crude Protein: 16-18%
Energy: 2750-2850 kcal/kg
Calcium: 3.5-4.0%
Phosphorus: 0.40%
Essential amino acids adequate

BROILER FINISHER (4-8 weeks):
Crude Protein: 18-20%
Energy: 3100-3200 kcal/kg
High energy for rapid growth

FEED INGREDIENTS:

ENERGY SOURCES:
• Maize (corn): 60-70% of feed, primary energy
• Wheat: Alternative to maize
• Sorghum: Lower energy than maize
• Rice bran: Up to 15% (high in fiber)

PROTEIN SOURCES:
• Soybean meal: 15-25%, best quality plant protein
• Fish meal: 3-5%, excellent for chicks
• Sunflower cake: 5-10%
• Cotton seed cake: Up to 10% (limit due to gossypol)
• Groundnut cake: 10-15%

MINERAL/VITAMIN SUPPLEMENTS:
• Limestone: Calcium for layers (8-10%)
• Bone meal: Phosphorus and calcium
• Salt: 0.3-0.5%
• Vitamin-mineral premix: 0.5%
• Oyster shells: Free choice for layers

FEED FORMULATION EXAMPLE (Layer Feed):
Maize: 60kg
Soybean meal: 20kg
Sunflower cake: 8kg
Fish meal: 3kg
Limestone: 7kg
Bone meal: 1kg
Salt: 0.3kg
Premix: 0.5kg
Oil: 0.2kg

FEED QUALITY:
• Purchase from reputable suppliers
• Check for mold, insects, rancid smell
• Store in dry, cool, rodent-proof containers
• Use within 1 month of manufacturing
• Mold can produce aflatoxins (deadly)

FEEDING MANAGEMENT:

CHICKS:
• Feed immediately upon arrival
• Use chick trays first 3-5 days
• Transition to feeders gradually
• Always keep feed available
• Fresh feed daily

GROWERS:
• Ad lib for broilers
• Restricted feeding for layers (control body weight)
• Feed in morning for layers
• Ensure simultaneous access

LAYERS:
• Feed once daily in morning
• 110-130g per bird per day
• Always available calcium supplement
• Consistent feeding time
• No sudden diet changes

WATER:
• Clean water always available
• More critical than feed
• Consumption: 2-2.5 times feed intake by weight
• Hot weather: Increase water availability
• Water medications properly mixed',
    'start_time' => 0,
    'end_time' => 96,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1616680214084-22670a6c1b2a?w=800',
    'order' => 7,
    'is_active' => 1
]);

// Protocol 8: Egg Production Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Egg Production & Quality Management',
    'activity_description' => 'Comprehensive egg production management covering collection, handling, storage, and quality control for optimal marketability and profitability.

EGG PRODUCTION CYCLE:

POINT OF LAY:
• Age: 18-22 weeks depending on breed
• Signs: Red comb, pelvic bones spread (3 fingers width), squatting behavior
• First eggs small (40-45g)
• Production increases to peak over 6-8 weeks

PEAK PRODUCTION:
• Reached at 28-32 weeks of age
• Production rate: 85-95%
• Maintain for 8-12 weeks with good management
• Egg size standardizes (55-65g)

POST-PEAK:
• Gradual decline after peak
• Decrease 0.5-1% per week
• Annual average: 70-75% (260-280 eggs per bird)
• Economic laying period: 72-80 weeks of age

EGG COLLECTION:

FREQUENCY:
• Collect 2-3 times daily
• Morning collection most important (60-70% laid)
• Afternoon collection prevents soiling and damage
• More frequent in hot weather

HANDLING:
• Use clean collection trays or baskets
• Handle gently to prevent cracks
• Never wash eggs unless for immediate use
• Dry cleaning better (sandpaper for spots)
• Separate floor eggs (lower quality)

NEST MANAGEMENT:
• Keep nests clean and dry
• Change bedding weekly
• Discourage broodiness by removing broody hens
• Train hens to use nests (place dummy eggs)
• Collect frequently to prevent egg eating

EGG QUALITY FACTORS:

EXTERNAL QUALITY:
Shell Strength:
• Adequate calcium in diet essential
• Vitamin D3 for calcium absorption
• Deteriorates with hen age
• Test: Candling or specific gravity

Shell Color:
• Breed dependent (white, brown, tinted)
• Consistent color indicates health
• Faded color may indicate stress/disease

Shell Cleanliness:
• Clean eggs command premium price
• Prevent by frequent collection
• Clean nests and floors

INTERNAL QUALITY:
Albumen (White):
• Should be thick and gel-like
• Thin watery white indicates old egg or stress
• Haugh Unit measures quality (score >72 = AA)

Yolk:
• Round, firm, centered
• Color depends on diet (marigold, carotene sources)
• Should not break when handled gently

Air Cell:
• Increases with age
• Small air cell = fresh egg
• Large air cell = old or improperly stored egg

CANDLING:
• Use bright light in dark room
• Detects: Cracks, blood spots, meat spots, double yolks
• Check internal quality without breaking
• Remove defective eggs

EGG GRADING:

BY WEIGHT:
Small: 43-53g
Medium: 53-63g
Large: 63-73g
Extra Large: >73g

BY QUALITY:
Grade AA: Excellent quality, firm white, small air cell
Grade A: Good quality, slightly less firm white
Grade B: Acceptable quality, larger air cell, thinner white

STORAGE:

SHORT-TERM (Up to 2 weeks):
• Temperature: 10-15°C
• Humidity: 70-80%
• Store large end up (keeps yolk centered)
• Turn daily if storing >7 days
• Away from strong odors (eggs absorb smells)

LONG-TERM (If necessary):
• Refrigeration: 4-7°C extends to 4-6 weeks
• Commercial coating preserves shell
• Never freeze whole eggs
• Quality declines with storage time

MARKETING:

PACKAGING:
• Clean egg trays (paper or plastic)
• Label with date, farm name, size
• Pack same size eggs together
• Handle with care during transport

PRICING:
• Grade and size premiums
• Farm-fresh premium possible
• Organic/free-range commands higher prices
• Wholesale vs retail price difference
• Contract with buyers for stability

RECORD KEEPING:
• Daily egg production
• Mortality
• Feed consumption
• Egg weights (weekly sample)
• Reject eggs (floor, cracked, dirty)
• Calculate: Hen-day production %, feed per dozen eggs',
    'start_time' => 19,
    'end_time' => 96,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1569288052389-dac9b01c9c05?w=800',
    'order' => 8,
    'is_active' => 1
]);

// Protocol 9: Broiler Production
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Broiler (Meat) Production Management',
    'activity_description' => 'Intensive meat production system focusing on rapid growth, efficient feed conversion, and optimal slaughter weight for maximum profitability.

BROILER PRODUCTION CYCLE:

BROODING (0-2 weeks):
• Intensive management like layer chicks
• Higher temperature requirements
• Starter feed: 20-22% protein
• Target weight: 400-500g by week 2

GROWING (3-5 weeks):
• Rapid growth phase
• Grower feed: 19-20% protein, high energy
• Weight: 500g to 1.5kg
• Watch for leg problems

FINISHING (6-8 weeks):
• Final growth push
• Finisher feed: 18-19% protein, highest energy
• Market weight: 2.0-2.5kg live weight
• Feed conversion critical

BREED SELECTION:
Fast-Growing Broilers:
• Cobb, Ross, Arbor Acres strains
• Market weight in 6-7 weeks
• Feed conversion: 1.8-2.0
• High breast meat yield

Slow-Growing/Dual Purpose:
• Kuroiler, Rainbow Rooster, local improved breeds
• Market weight 10-12 weeks
• Better survivability, foraging ability
• Suitable for smallholders

FEEDING PROGRAM:

STARTER (0-2 weeks):
• Crude protein: 20-22%
• Energy: 2950-3050 kcal/kg
• Feed form: Crumbles
• Ad libitum feeding
• Consumption: 30-40g per bird daily

GROWER (3-5 weeks):
• Crude protein: 19-20%
• Energy: 3050-3150 kcal/kg
• Feed form: Pellets (3-4mm)
• Consumption: 80-120g per bird daily

FINISHER (6-8 weeks):
• Crude protein: 18-19%
• Energy: 3100-3200 kcal/kg
• Feed form: Pellets (4-5mm)
• Consumption: 140-160g per bird daily

MANAGEMENT PRACTICES:

STOCKING DENSITY:
• 10-12 birds per square meter maximum
• Lower density = better growth, less stress
• Adequate ventilation essential at high density

LIGHTING:
• 23 hours light first week
• 20-23 hours light thereafter for maximum feeding
• Brief dark periods prevent panic, reduce heart attacks

LITTER MANAGEMENT:
• Keep dry to prevent breast blisters, hock burns
• Turn or replace wet areas
• Proper ventilation reduces moisture

LEG HEALTH:
• Common problem in fast-growing broilers
• Causes: Rapid growth, genetics, nutrition imbalance
• Prevention: Controlled growth, adequate vitamin D, calcium, phosphorus
• Lighting regimen with dark periods helps

HEALTH MONITORING:
• Daily mortality checks
• Observe walking ability, behavior
• Respiratory sounds (coughing)
• Droppings consistency
• Feed and water consumption
• Weight gain tracking

GROWTH MONITORING:
• Weekly weighing of sample (50 birds)
• Compare to breed standard
• Target weekly gains:
  Week 1: 100-120g
  Week 2: 180-220g
  Week 3: 280-350g
  Week 4: 400-500g
  Week 5: 520-650g
  Week 6: 650-800g

FEED CONVERSION RATIO:
• Critical economic parameter
• Calculate: Total feed consumed ÷ Total live weight gained
• Target: 1.8-2.0 at marketing
• Track weekly to identify problems early

MARKETING:

MARKET READINESS:
• Age: 6-8 weeks for fast-growing strains
• Weight: 2.0-2.5kg live weight
• Uniform flock size preferred
• Healthy appearance, good feathering

PREPARATION:
• Withdraw feed 8-12 hours before slaughter
• Reduce stress during catching
• Catch at night, handle gently
• Transport in ventilated crates

PROCESSING OPTIONS:
• Live bird markets
• On-farm slaughter
• Commercial processing plant
• Vacuum packaged for retail

PRICING:
• Per kg live weight or dressed weight
• Dressed weight = 70-75% of live weight
• Grade premium for better birds
• Contract growing arrangements

PROFITABILITY FACTORS:
• Chick cost
• Feed cost (60-70% of total cost)
• Feed conversion efficiency
• Mortality rate (target <3-5%)
• Market price
• Disease costs
• Labor and utilities

BATCH MANAGEMENT:
• All-in-all-out system preferred
• Clean and disinfect between batches
• 2 week rest period
• 5-6 batches per year possible',
    'start_time' => 0,
    'end_time' => 8,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
    'order' => 9,
    'is_active' => 1
]);

// Protocol 10: Biosecurity & Record Keeping
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Biosecurity Protocols & Record Management',
    'activity_description' => 'Comprehensive biosecurity program and record keeping systems to prevent disease introduction, track performance, and improve management decisions.

BIOSECURITY FUNDAMENTALS:

TRAFFIC CONTROL:
• Limit farm visitors to essential personnel only
• Visitor log book mandatory
• No visitors from other poultry farms same day
• Minimum 48-hour poultry contact restriction
• Dedicated farm clothing and footwear
• Shower facilities for high-security farms

PERIMETER SECURITY:
• Fence entire farm
• Single controlled entry point
• Vehicle disinfection station
• Wild bird exclusion (netting on open houses)
• Rodent control program
• Buffer zone from neighboring poultry farms

ENTRY PROTOCOLS:
• Footbaths at farm entrance and each house entrance
• Disinfectant: 2-4% formaldehyde, virucidal products
• Change footbaths 2-3 times weekly
• Hand washing with soap before entering
• Dedicated tools and equipment per house
• No sharing between houses

BIRD BIOSECURITY:
• Source chicks from certified hatcheries only
• Request health certification
• Quarantine new birds 2-3 weeks if adding to flock
• Never mix different age groups
• All-in-all-out management by house
• No contact with other poultry species

FEED AND WATER SECURITY:
• Covered feed storage (rodent and bird proof)
• Protected water source
• Test water quality quarterly
• Avoid feeding kitchen scraps
• Use only commercial quality feed

EQUIPMENT BIOSECURITY:
• Disinfect equipment before moving between houses
• Dedicated cleaning equipment per house
• Service vehicles stay outside production area
• Egg collection equipment cleaned daily

DEAD BIRD DISPOSAL:
• Remove dead birds immediately
• Incineration (burning) best method
• Deep burial (1.5 meters) if burning not possible
• Compost pits for small numbers
• Never discard where other birds access
• Record and investigate mortality causes

CLEANING AND DISINFECTION:
Between Batches Protocol:
1. Remove all birds
2. Remove all litter and organic matter
3. Dry clean (sweep, scrape)
4. Wet clean with detergent and high-pressure washer
5. Rinse thoroughly
6. Disinfect with approved virucidal disinfectant
7. Allow 10-14 days rest period
8. Fumigate house (formaldehyde gas) if disease occurred
9. Replace curtains, repair any damage
10. Test with sentinel birds if major disease issue

PEST CONTROL:

RODENTS:
• Snap traps and bait stations
• Check and refresh weekly
• Seal all entry points
• Remove spilled feed promptly
• Keep grass short around buildings

WILD BIRDS:
• Net open-sided houses
• Scare devices (reflective tape, predator models)
• Remove bird nests from rafters
• Close gaps in walls and roof

FLIES:
• Sanitation most important
• Remove wet litter
• Fly baits and traps
• Larvicides in litter if severe
• Biological control (fly predators)

RECORD KEEPING:

DAILY RECORDS:
• Mortality count and probable causes
• Egg production (layers)
• Feed consumption
• Water consumption
• Observations (health, behavior)
• Weather conditions

WEEKLY RECORDS:
• Body weight (sample weighing)
• Egg weights (sample)
• Cumulative feed consumption
• Cumulative production

MONTHLY RECORDS:
• Vaccination and treatments administered
• Expenses (feed, labor, utilities)
• Revenue (egg sales, bird sales)
• Feed conversion calculations
• Production percentage

FLOCK RECORDS:
• Flock identification number
• Breed/strain
• Source of birds
• Date of placement
• Initial number
• Vaccination record
• Deworming dates
• Disease outbreaks
• Date of sale/culling
• Final number
• Final average weight
• Total feed consumed
• Total production (eggs or meat)

FINANCIAL RECORDS:
• Capital expenses (housing, equipment)
• Operating expenses (feed, chicks, drugs)
• Labor costs
• Revenue from sales
• Calculate profit/loss per batch
• Cost per egg or per kg meat
• Return on investment

PERFORMANCE INDICATORS:

For Layers:
• Hen-day production % = (Eggs collected ÷ Birds alive) × 100
• Feed per dozen eggs = Feed consumed ÷ (Eggs produced ÷ 12)
• Mortality rate %
• Egg weight average
• Feed cost per dozen eggs

For Broilers:
• Average daily gain = Final weight ÷ Days
• Feed conversion ratio = Feed consumed ÷ Weight gain
• Mortality rate %
• Cost per kg live weight
• Profit per bird

USE OF RECORDS:
• Identify problems early
• Make informed management decisions
• Compare performance across batches
• Benchmark against standards
• Financial planning
• Credit applications
• Insurance claims if needed',
    'start_time' => 0,
    'end_time' => 96,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800',
    'order' => 10,
    'is_active' => 1
]);

echo "✓ Poultry Enterprise Created (ID: {$enterprise->id})" . PHP_EOL;
echo "✓ All 10 Protocols Successfully Created" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "✅ POULTRY PRODUCTION COMPLETE!" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "Enterprise ID: {$enterprise->id}" . PHP_EOL;
echo "Duration: {$enterprise->duration} months" . PHP_EOL;
echo "Total Protocols: " . count($protocols) . PHP_EOL . PHP_EOL;
echo "GROWTH STAGES:" . PHP_EOL;
echo "1. Chick Stage (0-4 weeks)" . PHP_EOL;
echo "2. Grower Stage (5-18 weeks)" . PHP_EOL;
echo "3. Layer Production (19+ weeks)" . PHP_EOL . PHP_EOL;
echo "MANAGEMENT PROTOCOLS:" . PHP_EOL;
echo "4. Disease Management" . PHP_EOL;
echo "5. Parasite Control" . PHP_EOL;
echo "6. Housing & Environment" . PHP_EOL;
echo "7. Nutrition Management" . PHP_EOL;
echo "8. Egg Production" . PHP_EOL;
echo "9. Broiler Production" . PHP_EOL;
echo "10. Biosecurity & Records" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
