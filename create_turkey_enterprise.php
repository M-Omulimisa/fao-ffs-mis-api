<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;

// Create Turkey Enterprise
$enterprise = Enterprise::create([
    'name' => 'Turkey Production & Management',
    'description' => 'Comprehensive phenological guide for turkey production from day-old poults through breeding, covering all growth stages, disease management, and optimal production practices for meat and breeding stock.',
    'type' => 'livestock',
    'duration' => 18,
    'photo' => 'https://images.unsplash.com/photo-1542838309-3c0d4e62b1e4?w=800',
    'is_active' => 1
]);

$protocols = [];

// Protocol 1: Poult Stage (0-3 Months / 0-12 Weeks)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Poult Stage (Day-Old to 12 Weeks)',
    'activity_description' => 'The poult stage is the most critical phase in turkey production, requiring intensive management, precise environmental control, and high-quality nutrition. Poults are highly vulnerable to environmental stress, diseases, and nutritional deficiencies during this period.

MORPHOLOGICAL APPEARANCE:
• Day-old poults covered with down feathers
• Closed or partially closed eyes at hatch, fully open within 24-48 hours
• Weak legs requiring non-slip flooring for proper development
• High-pitched vocalizations indicating comfort or distress
• Rapid feather development starting from week 2
• Body weight increases from 50-60g at hatch to 2-3kg by week 12
• Sexual dimorphism begins to appear around week 8-10

FEEDING/WATERING REQUIREMENTS:
• Starter crumble feed (28-30% crude protein) for first 4 weeks
• Feed conversion ratio (FCR): 1.5-1.8 in starter phase
• Protein-rich diet essential for rapid growth and feather development
• Electrolytes and clean water available 24/7 from day one
• Water temperature: 18-21°C for optimal intake
• Introduce grit after 2 weeks to aid digestion
• Gradual transition to grower feed starting week 4
• Feed intake: 50g/day (week 1) increasing to 150-180g/day (week 12)

HYGIENE REQUIREMENTS:
• Strict brooder sanitation before poult placement
• Litter management: Use dry wood shavings or rice hulls (5-8cm depth)
• Remove wet litter spots daily to prevent ammonia buildup
• Maintain litter moisture below 25%
• Warm, draft-free housing: 35-37°C at poult level for first week
• Reduce temperature by 3°C per week until 21°C reached
• Footbath disinfection for all entering brooder area
• All-in-all-out system preferred to break disease cycles

SUSCEPTIBILITY TO DISEASES:
• Omphalitis (navel infection) in first 3 days - highly vulnerable
• Coccidiosis (Eimeria species) - peaks at 3-8 weeks
• Blackhead (Histomoniasis) transmitted by cecal worms - highly fatal
• Newcastle Disease - requires strict vaccination protocol
• Aspergillosis from moldy litter or feed
• Nutritional deficiencies: vitamin E/selenium deficiency causing weak legs
• Starve-outs: Poults that fail to learn eating/drinking behavior

SUSCEPTIBILITY TO PARASITES:
• Intestinal worms (roundworms, capillaria) through contaminated litter
• External parasites (lice, northern fowl mites) from parent stock
• Coccidian parasites requiring anticoccidial medication in feed
• Regular monitoring essential as parasites stunt growth significantly

CRITICAL MANAGEMENT REQUIREMENTS:
• Temperature regulation is paramount for survival
• Brooder temperature at 35-37°C at placement, reduce by 3°C weekly
• Colostrum-like nutrition: High-energy, high-protein starter feed
• Beak trimming (if practiced) at 3-5 days to prevent cannibalism
• Vaccination schedule: Newcastle (day 7), bronchitis (day 14)
• Training poults to find feed and water using bright colors
• Lighting program: 23 hours light first week, reduce gradually
• Monitor poult behavior: Clustering indicates cold, panting indicates heat
• Cull weak or deformed poults immediately
• Record mortality daily and investigate causes

AESA PARAMETERS:
• Body temperature monitoring: 40-41°C normal for healthy poults
• Poult survival rate tracking: Target >95% survival to week 12
• Weekly weight gain monitoring to detect growth issues early
• Early mortality prevention through optimal brooding practices',
    'start_time' => 0,
    'end_time' => 12,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=800',
    'order' => 1,
    'is_active' => 1
]);

// Protocol 2: Weaner Stage (3-4 Months / 13-16 Weeks)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Weaner Stage (13-16 Weeks)',
    'activity_description' => 'The weaner stage represents the transition from intensive brooding to grower management. Turkeys develop stronger immunity, establish dominance hierarchies, and show rapid muscle development. This stage requires careful nutritional management to support continued growth.

MORPHOLOGICAL APPEARANCE:
• Adult feathers rapidly replacing down feathers
• Clear sexual dimorphism: Males (toms) significantly larger than females (hens)
• Leg strength increases dramatically
• Head becomes more defined with snood development in males
• Body mass increases to 5-7kg (hens) and 7-9kg (toms) by week 16
• Caruncle (fleshy growths) and snood development becomes prominent in males

FEEDING/WATERING REQUIREMENTS:
• Grower ration with 24-26% crude protein
• Feed conversion ratio: 2.0-2.3 during this phase
• Continuous access to fresh, clean water
• Water consumption: 200-300ml per bird daily
• Grit provision essential for optimal digestion
• Pelleted feed preferred over mash for better FCR
• Monitor feed intake: 180-250g per bird daily
• Ensure adequate feeder space: 8-10cm per bird

HYGIENE REQUIREMENTS:
• Clean drinking water systems weekly to prevent biofilm
• Regular litter turning to maintain dry conditions
• Frequent bedding changes in wet areas
• Ventilation rate increases with bird size
• Aim for ammonia levels below 10ppm
• Dust control through proper ventilation
• Weekly cleaning of feeders and waterers

SUSCEPTIBILITY TO DISEASES:
• Moderate susceptibility to respiratory diseases
• Histomoniasis (blackhead) remains a major concern
• Newcastle Disease if vaccination lapses
• Airsacculitis from poor ventilation
• Hemorrhagic enteritis can cause sudden mortality
• Turkey rhinotracheitis (TRT) causing respiratory signs

SUSCEPTIBILITY TO PARASITES:
• Cecal worms (Heterakis gallinarum) - vectors for blackhead
• Roundworms (Ascaridia) affecting nutrient absorption
• Lice and mites causing feather damage and irritation
• Strategic deworming recommended at 12-14 weeks

CRITICAL MANAGEMENT REQUIREMENTS:
• Vaccination schedule maintenance crucial
• Growth monitoring through weekly weighing
• Separate sexes if processing at different ages
• Monitor for leg problems due to rapid growth
• Adequate space: 0.2-0.3 square meters per bird
• Photoperiod: 14 hours light, 10 hours dark
• Record feed consumption and calculate FCR weekly

AESA PARAMETERS:
• Weight gain monitoring: Target 500-700g weekly gain
• Feather development assessment for health status
• Feed conversion tracking for economic efficiency
• Immune response monitoring through vaccination titers',
    'start_time' => 13,
    'end_time' => 16,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
    'order' => 2,
    'is_active' => 1
]);

// Protocol 3: Grower Stage (5-9 Months / 17-36 Weeks)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Grower Stage (17-36 Weeks)',
    'activity_description' => 'The grower stage focuses on optimal meat production or preparation for breeding. Birds show full feather coverage, strong frame development, and significant muscle mass accumulation. Management emphasizes biosecurity, respiratory health, and controlled growth rates.

MORPHOLOGICAL APPEARANCE:
• Full adult feather coverage achieved
• Prominent caruncle, snood, and wattle development in males
• Strong muscular development, especially breast muscles
• Body mass increase: Females 8-12kg, Males 12-18kg by week 36
• Sexual characteristics fully developed
• Tail fan display behavior in males
• Beard development visible in males (hair-like feathers on chest)

FEEDING/WATERING REQUIREMENTS:
• Grower-finisher feed (18-20% protein for meat birds)
• Higher protein (20-22%) for birds destined for breeding
• Ample clean water: 400-600ml per bird daily
• Feed intake: 250-350g per bird daily
• Controlled feeding for breeding stock to prevent obesity
• Ad libitum feeding for meat production birds
• Calcium supplementation if birds approaching laying

HYGIENE REQUIREMENTS:
• Ventilated housing with air exchange rates of 1-2 cubic meters per bird per hour
• Regular removal of caked litter
• Periodic disinfection of housing between batches
• Biosecurity enforcement: Limit visitor access
• Pest exclusion measures: Rodent control, bird netting
• Clean water delivery system maintenance

SUSCEPTIBILITY TO DISEASES:
• Respiratory diseases including airsacculitis
• Fowl cholera if biosecurity breached
• Erysipelas causing sudden death
• Mycoplasma infections affecting respiratory system
• Cannibalism and feather picking under stress
• Leg disorders from rapid growth (especially males)

SUSCEPTIBILITY TO PARASITES:
• High risk of blackhead via Heterakis cecal worms
• Internal parasites: Capillaria, roundworms
• External parasites: Stick-tight fleas, fowl mites
• Regular monitoring and treatment protocols essential

CRITICAL MANAGEMENT REQUIREMENTS:
• Biosecurity enforcement to prevent disease introduction
• Weight tracking every 2 weeks for breeding stock
• Light management: Natural daylight or 14-16 hours for growers
• Space requirements: 0.4-0.5 square meters per bird
• Separate housing for males and females if breeding stock
• Market weight for meat birds: Males 14-18kg, Females 8-12kg at 20-24 weeks
• Health monitoring for early disease detection
• Foot pad health checks for welfare assessment

AESA PARAMETERS:
• Feed conversion ratio monitoring: Target 2.5-3.0 overall
• Muscle development assessment (breast yield)
• Mortality tracking and cause investigation
• Weight uniformity within flock
• Immune response through vaccination monitoring',
    'start_time' => 17,
    'end_time' => 36,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1612170153139-6f881ff1c76a?w=800',
    'order' => 3,
    'is_active' => 1
]);

// Protocol 4: Pre-Breeder Stage (9-15 Months / 37-60 Weeks)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Pre-Breeder Stage (37-60 Weeks)',
    'activity_description' => 'Pre-breeder stage prepares turkeys for reproduction. Females develop reproductive organs while males show full secondary sexual characteristics. Critical management focuses on controlled weight gain, reproductive tract development, and breeding stock selection.

MORPHOLOGICAL APPEARANCE:
• Sexual dimorphism fully apparent: Males display elaborate plumage
• Tail fan and beard prominent in males
• Puberty signs: Increased vocalization, mating behaviors
• Males show color changes in head (red, white, blue) when displaying
• Female vent examination shows reproductive tract development
• Body conformation suitable for breeding

FEEDING/WATERING REQUIREMENTS:
• Transition to breeder diet (16-18% protein)
• Controlled feeding regime to prevent obesity in females
• Males: 350-400g feed daily
• Females: 250-300g feed daily
• Calcium supplementation begins (2.5-3% of diet)
• Water intake increases: 500-700ml daily
• Vitamin and mineral supplementation for fertility
• Gradual increase in lighting stimulates reproductive development

HYGIENE REQUIREMENTS:
• Nest sanitation program initiated
• Clean, dry housing essential for breeding success
• Roost cleaning to prevent foot infections
• Pest exclusion critical - rodents reduce egg production
• Regular health monitoring increases in frequency

SUSCEPTIBILITY TO DISEASES:
• Reproductive tract infections in females
• Egg peritonitis risk as laying approaches
• Bumblefoot from heavy weight on roosts
• Respiratory diseases affecting fertility
• Mycoplasma reducing hatchability

SUSCEPTIBILITY TO PARASITES:
• Mite and flea infestations affecting breeding performance
• Internal parasites reducing nutrient absorption
• Strategic deworming 4-6 weeks before breeding season

CRITICAL MANAGEMENT REQUIREMENTS:
• Light management crucial: Gradual increase from 8 to 14 hours
• Controlled weight gain: Target females 10-12kg, males 16-20kg
• Breeding stock selection based on conformation and health
• Male-to-female ratio planning: 1 male per 6-8 females for natural mating
• Artificial insemination preparation if practiced
• Nutritional optimization for egg production preparation
• Nest box introduction and training

AESA PARAMETERS:
• Age at sexual maturity monitoring
• Body conformation assessment for breeding suitability
• Reproductive hygiene evaluation
• Breeding stock selection criteria application',
    'start_time' => 37,
    'end_time' => 60,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=800',
    'order' => 4,
    'is_active' => 1
]);

// Protocol 5: Breeder Stage (15+ Months / 60+ Weeks)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Breeder Stage (60+ Weeks / 15+ Months)',
    'activity_description' => 'Mature breeding stage where turkeys produce fertile eggs for hatching. Females lay eggs while males provide fertility. This stage demands optimal nutrition, health management, and biosecurity to ensure high hatchability and healthy offspring.

MORPHOLOGICAL APPEARANCE:
• Fully matured physical traits including prominent comb, snood, wattles
• Males display vibrant coloration during courtship
• Reproductive organs fully active and functional
• Females show abdominal distension when in lay
• Males maintain displaying behavior and territorial aggression

FEEDING/WATERING REQUIREMENTS:
• Breeder-specific ration (18-20% crude protein)
• High calcium content (3.5-4%) for eggshell quality
• Vitamin E and selenium for fertility
• Males: 400-450g feed daily to maintain condition
• Females: 300-350g feed daily during laying
• Nutrient-dense feed supports high egg production
• Ample clean water: 700-1000ml daily during laying
• Oyster shell or limestone grit for calcium supplementation

HYGIENE REQUIREMENTS:
• Strict egg hygiene: Collect eggs 3-4 times daily
• Nest boxes kept clean and dry
• Sanitary egg handling and storage procedures
• Housing disinfection between breeding cycles
• Fumigation of hatching eggs if required

SUSCEPTIBILITY TO DISEASES:
• Mycoplasma gallisepticum and M. meleagridis affecting hatchability
• Salmonellosis with vertical transmission to offspring
• Fowl cholera causing sudden mortality
• Infectious coryza in breeding flocks
• Egg drop syndrome affecting production
• Increased reproductive stress makes birds vulnerable

SUSCEPTIBILITY TO PARASITES:
• Persistent ectoparasites (mites, lice) require ongoing control
• Strategic deworming program essential
• External parasites affect egg production and fertility

CRITICAL MANAGEMENT REQUIREMENTS:
• Fertility evaluation through egg candling at 7-10 days
• Hatchability tracking: Target >80% of fertile eggs
• Egg collection 3-4 times daily to prevent soiling
• Proper egg storage: 12-16°C, 70-75% humidity
• Male fertility assessment through semen evaluation
• Cull non-laying hens and infertile males
• Lighting: 14-16 hours to maintain production
• Nest box management: 1 box per 4-5 hens
• Health screening to prevent vertical disease transmission
• Record keeping: Egg production, fertility, hatchability

AESA PARAMETERS:
• Fertility and hatchability monitoring
• Egg production tracking: Peak 80-100 eggs per hen annually
• Laying rate recording
• Genetic performance tracking for breeding selection',
    'start_time' => 60,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1612170153139-6f881ff1c76a?w=800',
    'order' => 5,
    'is_active' => 1
]);

// Protocol 6: Integrated Disease Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Integrated Disease Management for Turkeys',
    'activity_description' => 'Comprehensive disease management program covering major turkey diseases with prevention strategies, early detection methods, and integrated control approaches including biosecurity, vaccination, and therapeutic interventions.

MAJOR DISEASES AND MANAGEMENT:

1. BLACKHEAD (Histomoniasis)
Description: Fatal protozoal disease transmitted by cecal worm eggs. Causes liver necrosis and cecal inflammation.
Symptoms: Sulfur-yellow droppings, drooping wings, dark head discoloration, sudden death.
Prevention: Strict separation from chickens, regular deworming, clean litter management.
Control: No approved treatment in many countries; prevention through hygiene critical.
Economic Impact: Can cause 100% mortality in severe outbreaks.

2. NEWCASTLE DISEASE
Description: Highly contagious viral disease affecting respiratory, nervous, and digestive systems.
Symptoms: Respiratory distress, greenish diarrhea, twisted neck, paralysis, sudden death.
Prevention: Strict vaccination schedule (day 7, week 4, week 12, then every 3 months).
Control: No treatment; cull affected birds, quarantine farm, report to authorities.
Economic Impact: Devastating with mortality up to 100% in unvaccinated flocks.

3. FOWL CHOLERA (Pasteurellosis)
Description: Bacterial disease causing septicemia and sudden death, especially in older birds.
Symptoms: Sudden death, fever, ruffled feathers, nasal discharge, swollen wattles.
Prevention: Biosecurity, rodent control, clean water, vaccination in endemic areas.
Control: Antibiotics (sulfadimethoxine, penicillin) under veterinary guidance.
Economic Impact: 20-40% mortality in acute outbreaks.

4. HEMORRHAGIC ENTERITIS
Description: Viral disease affecting turkeys 4-12 weeks old causing intestinal bleeding.
Symptoms: Bloody droppings, depression, sudden death, pale carcasses.
Prevention: Vaccination at 4 weeks, all-in-all-out management.
Control: Supportive care, no specific treatment available.
Economic Impact: 10-60% mortality depending on strain virulence.

5. TURKEY RHINOTRACHEITIS (TRT)
Description: Respiratory disease causing upper respiratory inflammation and reduced egg production.
Symptoms: Nasal discharge, sneezing, foamy eyes, reduced growth, drop in egg production.
Prevention: Biosecurity, avoid multi-age housing, vaccination where available.
Control: Supportive therapy, prevent secondary bacterial infections.
Economic Impact: Reduced growth rates and hatchability by 20-40%.

6. COCCIDIOSIS
Description: Intestinal protozoal infection causing bloody diarrhea and poor growth.
Symptoms: Blood in droppings, huddling, ruffled feathers, decreased feed intake.
Prevention: Anticoccidial drugs in feed, coccidiosis vaccination, dry litter management.
Control: Therapeutic anticoccidials (amprolium, sulfadimethoxine).
Economic Impact: 20-30% growth rate reduction if untreated.

7. ASPERGILLOSIS (Brooder Pneumonia)
Description: Fungal infection affecting lungs, especially in young poults.
Symptoms: Gasping, rapid breathing, high early mortality, yellowish nodules in lungs.
Prevention: Use mold-free litter and feed, proper ventilation, avoid dusty conditions.
Control: No effective treatment; cull severely affected birds.
Economic Impact: Can cause 50% mortality in poorly managed brooders.

8. ERYSIPELAS
Description: Bacterial infection causing sudden death and septicemia in growing turkeys.
Symptoms: Sudden death, purple discoloration of skin, swollen snood.
Prevention: Vaccination at 12-16 weeks, biosecurity.
Control: Antibiotics (penicillin) effective if caught early.
Economic Impact: Sporadic but can cause significant losses in outbreak.

9. MYCOPLASMA INFECTIONS (MG & MM)
Description: Chronic respiratory disease reducing performance and hatchability.
Symptoms: Nasal discharge, coughing, airsacculitis, reduced fertility.
Prevention: Source chicks from Mycoplasma-free breeder flocks, vaccination.
Control: Antibiotics (tylosin, enrofloxacin) provide temporary relief.
Economic Impact: 5-15% reduction in hatchability and growth rates.

10. SALMONELLOSIS
Description: Bacterial infection causing enteritis and vertical transmission to eggs.
Symptoms: Watery diarrhea, depression, poor growth, sudden death in young birds.
Prevention: Biosecurity, clean eggs, vaccination, rodent control.
Control: Antibiotics under veterinary supervision; focus on prevention.
Economic Impact: Public health concern; affects marketability.

INTEGRATED DISEASE PREVENTION STRATEGIES:
• Strict biosecurity: Controlled access, footbaths, vehicle disinfection
• All-in-all-out management system
• Comprehensive vaccination program tailored to farm disease profile
• Regular health monitoring and diagnostic testing
• Prompt isolation of sick birds
• Proper disposal of dead birds (burning or deep burial)
• Clean water supply with periodic disinfection
• Quality feed storage preventing mold growth
• Minimize stress through proper management
• Maintain detailed health records
• Work with veterinarian for disease diagnosis and treatment protocols',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1584608473084-e0ce2f0c8716?w=800',
    'order' => 6,
    'is_active' => 1
]);

// Protocol 7: Comprehensive Parasite Control
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Comprehensive Parasite Control for Turkeys',
    'activity_description' => 'Complete parasite management program addressing internal and external parasites affecting turkeys at all life stages, including prevention strategies, monitoring protocols, and treatment options.

INTERNAL PARASITES:

1. CECAL WORMS (Heterakis gallinarum)
Description: Small worms living in ceca; vectors for blackhead disease.
Impact: Minimal direct damage but critical blackhead transmission risk.
Detection: Fecal examination under microscope.
Control: Strategic deworming with fenbendazole or levamisole.
Prevention: Good sanitation, avoid overcrowding, regular litter management.
Treatment Schedule: Deworm at 8 weeks, 16 weeks, and before breeding.

2. LARGE ROUNDWORMS (Ascaridia dissimilis)
Description: Large intestinal worms causing nutrient malabsorption.
Impact: Reduced growth rates (10-20%), poor feed conversion, intestinal blockage.
Detection: Visible worms in droppings or at processing.
Control: Piperazine, fenbendazole, levamisole effective.
Prevention: Clean litter, prevent fecal contamination of feed/water.
Treatment: Deworm when prevalence exceeds 20% on fecal examination.

3. CAPILLARIA (Thread Worms)
Description: Small hair-like worms affecting intestinal lining.
Impact: Hemorrhagic enteritis, anemia, weight loss, mortality in heavy infections.
Detection: Microscopic examination of feces for eggs.
Control: Levamisole or fenbendazole treatment.
Prevention: Dry litter management to break life cycle.
Treatment: Treat entire flock when detected.

4. TAPEWORMS (Cestodes)
Description: Segmented worms requiring intermediate hosts (beetles, flies).
Impact: Poor growth, reduced feed efficiency.
Detection: Segments visible in droppings.
Control: Niclosamide or praziquantel treatment.
Prevention: Control intermediate hosts through sanitation.
Treatment: As needed based on monitoring.

5. COCCIDIOSIS (Eimeria species)
Description: Protozoal parasites damaging intestinal lining.
Impact: Bloody diarrhea, 20-30% growth reduction, death in severe cases.
Detection: Oocyst counting in feces, post-mortem lesion scoring.
Control: Anticoccidial drugs in feed (monensin, salinomycin) or vaccination.
Prevention: Dry litter, avoid overcrowding, prophylactic medication.
Treatment: Therapeutic anticoccidials if outbreak occurs.

EXTERNAL PARASITES:

6. NORTHERN FOWL MITE (Ornithonyssus sylviarum)
Description: Blood-sucking mites living on birds continuously.
Impact: Anemia, reduced egg production, feather damage, stress.
Detection: Examine feathers around vent; mites visible on eggs.
Control: Permethrin, spinosad dust or spray treatments.
Prevention: Monitor birds weekly, treat promptly when detected.
Treatment: Two treatments 7 days apart to break cycle.

7. POULTRY LICE (Mallophaga species)
Description: Chewing lice feeding on feathers and skin debris.
Impact: Feather damage, skin irritation, reduced production, restlessness.
Detection: Part feathers to see lice on skin and feather shafts.
Control: Permethrin dust, pyrethroid sprays.
Prevention: Regular visual inspection, biosecurity to prevent introduction.
Treatment: Apply insecticide thoroughly, repeat in 10 days.

8. STICKTIGHT FLEA (Echidnophaga gallinacea)
Description: Fleas that embed in bare skin areas (head, comb, wattles).
Impact: Anemia, secondary infections, facial lesions, production losses.
Detection: Visible dark clusters on head and wattles.
Control: Manual removal, petroleum jelly application, ivermectin.
Prevention: Control in environment through premise treatment.
Treatment: Individual bird treatment plus environmental control.

9. BEDBUGS AND FOWL TICKS
Description: Nocturnal blood-feeders hiding in cracks during day.
Impact: Anemia, disease transmission, reduced performance.
Detection: Inspect housing cracks at night with flashlight.
Control: Carbaryl or permethrin premise treatment.
Prevention: Seal cracks in housing, regular cleaning.
Treatment: Multiple applications needed for complete control.

STRATEGIC PARASITE CONTROL PROGRAM:

POULT STAGE (0-12 weeks):
• Monitor for external parasites weekly
• Anticoccidial medication in feed
• Environmental management to prevent parasite buildup

GROWER STAGE (13-36 weeks):
• Fecal examination at 12 weeks for worm eggs
• Strategic deworming at 16 weeks if needed
• Continue external parasite monitoring
• Rotate anticoccidial drugs to prevent resistance

PRE-BREEDER STAGE (37-60 weeks):
• Comprehensive deworming 6-8 weeks before breeding
• Intensive external parasite control
• Ensure breeding stock parasite-free

BREEDER STAGE (60+ weeks):
• Monthly external parasite checks
• Strategic deworming during non-laying periods
• Clean nests to prevent parasite accumulation
• Monitor for vertical transmission of parasites to eggs

INTEGRATED PARASITE MANAGEMENT PRACTICES:
• Regular fecal examination (every 2-3 months)
• Maintain dry, clean litter to break parasite life cycles
• Control intermediate hosts (beetles, flies) through sanitation
• Rotate chemical classes to prevent drug resistance
• Use proper drug withdrawal periods before slaughter
• Environmental treatment in addition to on-bird treatments
• All-in-all-out system reduces parasite pressure
• Quarantine and treat new birds before mixing with flock
• Keep wild birds away from turkey housing',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1589923158776-cb4485d99fd6?w=800',
    'order' => 7,
    'is_active' => 1
]);

// Protocol 8: Housing & Biosecurity Management  
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Housing & Biosecurity Management',
    'activity_description' => 'Comprehensive housing design and biosecurity protocols for optimal turkey health, welfare, and disease prevention throughout all production stages.

HOUSING REQUIREMENTS BY STAGE:

BROODER HOUSE (0-4 Weeks):
• Floor space: 0.09-0.14 square meters per poult
• Temperature control: Start at 35-37°C, reduce 3°C weekly
• Brooder type: Gas or electric heaters with thermostats
• Ventilation: 0.15-0.25 cubic meters per hour per bird
• Lighting: 23 hours light first 3 days, reduce to 18 hours by week 4
• Litter depth: 5-8cm wood shavings or rice hulls
• Equipment: Bell drinkers or nipple lines, pan feeders or trays
• Walls: Insulated to maintain stable temperature
• Biosecurity: Restricted access, footbaths, protective clothing required

GROWER HOUSE (5-20 Weeks):
• Floor space: 0.28-0.37 square meters per bird
• Temperature: Maintain 18-24°C through ventilation
• Natural or mechanical ventilation with fans
• Lighting: 14-16 hours light for growth optimization
• Litter: Maintain 8-10cm depth, keep dry
• Feeders: 8-10cm trough space per bird or tube feeders
• Waterers: One bell drinker per 80-100 birds or nipple drinkers
• Perches: Optional but beneficial for leg health
• Height: Minimum 2.5 meters for adequate air circulation

FINISHER/BREEDER HOUSE (20+ Weeks):
• Floor space: 0.37-0.46 square meters per bird (finishers), 0.5-0.7 (breeders)
• Temperature: 15-21°C optimal for production
• Ventilation: 1-2 cubic meters per hour per bird
• Natural lighting supplemented as needed for breeders
• Deep litter system: 10-15cm depth
• Nest boxes for breeders: 1 box per 4-5 hens, 40×50×50cm size
• Roosts: 30cm per bird, 40-60cm above floor
• Drinkers: Ample supply, one per 100 birds or nipple system
• Feeders: Adequate space to prevent competition

BIOSECURITY PROTOCOLS:

PERIMETER BIOSECURITY:
• Fence entire farm to exclude wildlife and unauthorized entry
• Single controlled entry point with vehicle disinfection station
• Wild bird netting over open-sided houses
• Rodent control program with bait stations monitored weekly
• No other poultry species on farm (especially chickens - blackhead risk)
• Buffer zone between turkey houses and other farms

ENTRY PROTOCOLS:
• Shower-in/shower-out facilities for strict biosecurity farms
• Dedicated farm clothing and footwear
• Footbath disinfection at each house entrance (2-4% formaldehyde or virucidal disinfectant)
• Hand washing stations with soap and water
• Visitor log maintained with health declaration
• Minimum 48-hour poultry contact restriction for visitors
• Equipment disinfection before entering houses

FLOCK BIOSECURITY:
• All-in-all-out management by house or farm
• Minimum 2-week downtime between flocks
• Thorough cleaning: Remove litter, wash, disinfect, dry
• Source poults from single certified hatchery
• Quarantine new stock for 2-3 weeks
• Age separation: Never mix different age groups
• Start with youngest birds when doing farm rounds

VEHICLE BIOSECURITY:
• Dedicated farm vehicles only or thorough disinfection
• Feed trucks must not visit other poultry farms same day
• Carcass removal vehicles restricted to dead bird storage area
• Egg collection vehicles disinfected between farms

FEED AND WATER BIOSECURITY:
• Protected feed storage preventing wild bird and rodent access
• Water source protected from contamination
• Chlorination or acidification of drinking water
• Test water quality quarterly for bacterial contamination
• Store feed in sealed bins elevated off ground

HEALTH MONITORING:
• Daily visual inspection of all birds
• Weekly mortality recording and investigation
• Post-mortem examination of casualties
• Submit sick birds to lab for diagnosis
• Maintain vaccination and treatment records
• Productive performance tracking to detect health issues early

WASTE MANAGEMENT:
• Dead bird disposal: Incineration, composting, or deep burial away from houses
• Litter disposal or composting in designated area
• Manure composting before sale or land application
• Prevent runoff from waste storage areas

PEST EXCLUSION:
• Seal all gaps in housing to exclude rodents and wild birds
• Insect screens on ventilation openings
• Regular inspection and repair of housing
• Remove vegetation around houses (1-2 meter clear zone)
• Control flies using baits, traps, and sanitation

EMERGENCY RESPONSE PLAN:
• Disease outbreak response protocol documented
• Isolation facilities for sick birds
• Rapid culling and disposal procedures
• Communication plan with veterinary authorities
• List of emergency contacts and supplies',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1589421771101-486e8b0b32d2?w=800',
    'order' => 8,
    'is_active' => 1
]);

// Protocol 9: Nutrition & Feeding Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Nutrition & Feeding Management Program',
    'activity_description' => 'Complete nutritional program for turkeys covering dietary requirements, feed formulations, feeding schedules, and nutritional management strategies for optimal growth, reproduction, and health across all production stages.

NUTRITIONAL REQUIREMENTS BY STAGE:

POULT STARTER (0-4 Weeks):
• Crude Protein: 28-30%
• Metabolizable Energy: 2,900-3,000 kcal/kg
• Lysine: 1.60-1.70%
• Methionine: 0.55-0.60%
• Calcium: 1.2%
• Available Phosphorus: 0.65%
• Feed Form: Fine crumbles for easy consumption
• Purpose: Support rapid growth and immune system development

GROWER RATION (5-8 Weeks):
• Crude Protein: 26-28%
• Metabolizable Energy: 3,000-3,100 kcal/kg
• Lysine: 1.50%
• Methionine: 0.50%
• Calcium: 1.2%
• Available Phosphorus: 0.60%
• Feed Form: Crumbles transitioning to small pellets
• Purpose: Maintain growth rate and skeletal development

GROWER RATION (9-12 Weeks):
• Crude Protein: 24-26%
• Metabolizable Energy: 3,100-3,200 kcal/kg
• Lysine: 1.40%
• Methionine: 0.47%
• Calcium: 1.1%
• Available Phosphorus: 0.55%
• Feed Form: 3-4mm pellets
• Purpose: Continue muscle development

FINISHER RATION (13-20 Weeks):
• Crude Protein: 20-22%
• Metabolizable Energy: 3,200-3,300 kcal/kg
• Lysine: 1.10-1.20%
• Methionine: 0.42-0.45%
• Calcium: 1.0%
• Available Phosphorus: 0.50%
• Feed Form: 5mm pellets
• Purpose: Optimize meat production and feed conversion

BREEDER DEVELOPER (20-30 Weeks):
• Crude Protein: 14-16%
• Metabolizable Energy: 2,800-2,900 kcal/kg
• Lysine: 0.65%
• Methionine: 0.30%
• Calcium: 2.0-2.5%
• Available Phosphorus: 0.45%
• Feed Form: Pellets or crumbles
• Purpose: Controlled growth, prevent obesity, prepare for laying

BREEDER LAY RATION (30+ Weeks):
• Crude Protein: 16-18%
• Metabolizable Energy: 2,800-2,900 kcal/kg
• Lysine: 0.75%
• Methionine: 0.35%
• Calcium: 3.0-3.5% (for eggshell formation)
• Available Phosphorus: 0.45%
• Vitamin E: 50-75 IU/kg (fertility)
• Selenium: 0.3 ppm (fertility and hatchability)
• Feed Form: Pellets with oyster shell supplement
• Purpose: Support egg production and maintain body condition

FEED INGREDIENTS:

ENERGY SOURCES:
• Maize (corn): Primary energy, 60-70% of ration
• Wheat: Alternative energy source
• Sorghum: Useful in hot climates
• Fats/oils: Increase energy density (2-5% addition)

PROTEIN SOURCES:
• Soybean meal: Primary protein source (20-35%)
• Fish meal: High-quality protein, especially for young poults (3-5%)
• Meat and bone meal: Protein and minerals (up to 5%)
• Sunflower meal: Alternative protein source

ADDITIVES AND SUPPLEMENTS:
• Limestone: Calcium source for laying birds
• Dicalcium phosphate: Phosphorus source
• Salt: 0.3-0.4% of diet
• Vitamin premix: A, D3, E, K, B-complex
• Mineral premix: Iron, copper, zinc, manganese, iodine, selenium
• Methionine: Synthetic amino acid supplementation
• Anticoccidials: Monensin, salinomycin for disease prevention
• Enzymes: Phytase for phosphorus release, NSP enzymes

FEEDING MANAGEMENT PRACTICES:

POULT FEEDING (0-4 Weeks):
• Provide feed immediately upon arrival to prevent starve-outs
• Use brightly colored feed trays first 2 days to attract poults
• Feed on paper or shallow trays first 3-5 days
• Transition to pan feeders or tube feeders by day 5
• Keep feeders 3/4 full to prevent spoilage
• Ad libitum feeding for maximum growth
• Ensure at least 5cm feeder space per bird

GROWER FEEDING (5-20 Weeks):
• Ad libitum feeding for meat production birds
• Maintain feed in front of birds at all times
• Provide 8-10cm linear feeder space per bird
• Minimize feed wastage through proper feeder adjustment
• Remove caked or moldy feed immediately
• Transition between diets gradually over 3-5 days
• Separate feeding for males and females if needed

BREEDER FEEDING (20+ Weeks):
• Controlled feeding to prevent obesity
• Females: 250-350g per bird daily depending on production stage
• Males: 350-450g per bird daily to maintain condition
• Feed once daily in morning for breeders
• Ensure all birds have simultaneous access to prevent bullying
• Provide adequate feeder space (15cm per bird) for competition-free feeding
• Adjust ration based on body condition scoring
• Increase feed during peak lay period

WATER MANAGEMENT:
• Fresh, clean water available 24/7
• Water consumption: 1.5-2.5 times feed intake by weight
• Water temperature: 10-15°C optimal
• Chlorination: 3-5 ppm for sanitation
• Regular cleaning of drinkers to prevent biofilm
• Medication or vitamins via water when needed
• Monitor water intake as health indicator

SPECIAL NUTRITIONAL CONSIDERATIONS:

HOT WEATHER FEEDING:
• Increase nutrient density as intake drops
• Add fat for extra energy without bulk
• Provide cool water and electrolytes
• Feed during cooler parts of day
• Vitamin C supplementation reduces heat stress

COLD WEATHER FEEDING:
• Increase energy content to meet thermoregulation needs
• Ensure adequate feeding to prevent hypothermia
• Warm water provision increases intake

LEG HEALTH NUTRITION:
• Adequate calcium and phosphorus with proper ratio (2:1)
• Vitamin D3 for calcium absorption
• Manganese and zinc for bone development
• Biotin for foot pad health
• Controlled growth rate prevents leg problems

FEED QUALITY CONTROL:
• Purchase feed from reputable manufacturers
• Check feed freshness - use within 3 weeks of milling
• Store feed in dry, cool, rodent-proof conditions
• Reject moldy or contaminated feed
• Test feed samples periodically for nutritional content
• Maintain feed storage time logs',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1616680214084-22670a6c1b2a?w=800',
    'order' => 9,
    'is_active' => 1
]);

// Protocol 10: Breeding & Egg Production Management
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Breeding & Egg Production Management',
    'activity_description' => 'Comprehensive management program for turkey breeding operations covering breeder selection, mating systems, egg production, collection, storage, and incubation for optimal reproductive performance and poult quality.

BREEDING STOCK SELECTION:

FEMALE SELECTION CRITERIA:
• Age: 28-32 weeks at first egg
• Body weight: 10-13kg depending on strain
• Body conformation: Well-proportioned, no deformities
• Health status: Free from Mycoplasma, Salmonella
• Reproductive history: From high-fertility parents
• Temperament: Calm, easy to handle
• Feather coverage: Complete, no pecking damage
• Leg strength: Sound, no bumblefoot or deformities

MALE SELECTION CRITERIA:
• Age: 32-36 weeks at breeding commencement
• Body weight: 18-22kg depending on strain
• Masculinity: Well-developed secondary sexual characteristics
• Fertility: Proven semen quality if AI practiced
• Conformation: Broad breast, strong frame
• Health: Disease-free, especially Mycoplasma
• Behavior: Vigorous but not overly aggressive
• Genetic merit: From high-fertility, fast-growing lines

MATING SYSTEMS:

NATURAL MATING:
• Male-to-female ratio: 1 male per 6-8 females for light breeds
• Ratio: 1 male per 4-6 females for heavy commercial breeds
• Pen mating: Small groups (10-15 hens per pen) with 1-2 males
• Flock mating: Larger groups with appropriate male numbers
• Male rotation: Rotate males between pens weekly to ensure fertility
• Fertility issues: Heavy males may have difficulty mounting, leading to low fertility
• Advantages: Lower labor, natural behavior expression
• Disadvantages: Variable fertility, male aggression, males consume feed

ARTIFICIAL INSEMINATION (AI):
• Standard practice for heavy commercial breeds due to natural mating difficulties
• Semen collection: 2-3 times per week from males
• Semen volume: 0.2-0.4ml per ejaculate
• Sperm concentration: 8-10 billion per ml
• Insemination dose: 0.025ml diluted semen
• Insemination frequency: Every 10-14 days
• Timing: Inseminate in afternoon when hens receptive
• Technique: Gentle eversion of oviduct, shallow (2-3cm) insemination
• Advantages: Better fertility (90-95%), fewer males needed, genetic control
• Disadvantages: Labor intensive, requires skilled technicians

EGG PRODUCTION MANAGEMENT:

LIGHTING PROGRAM:
• Pre-lay (24-28 weeks): Increase from 8 to 14 hours gradually (30 min/week)
• Laying period: Maintain 14-16 hours light
• Light intensity: 40-60 lux at bird eye level
• Never decrease day length during production
• Consistent timing: Use timer for reliability
• Red light at night reduces disturbance

NESTING MANAGEMENT:
• Provide nests at 26-28 weeks before laying starts
• Nest boxes: 40×50×50cm (one per 4-5 hens)
• Nest location: Darker areas of house, 30-60cm above floor
• Nesting material: Wood shavings, straw, or artificial turf
• Train hens to use nests by placing dummy eggs
• Keep nests clean and dry to minimize egg contamination

PRODUCTION PARAMETERS:
• Age at first egg: 28-32 weeks
• Peak production: 34-38 weeks
• Eggs per hen: 80-100 annually for commercial strains
• Laying rate at peak: 65-75%
• Production cycle: 24-26 weeks typically
• Persistency: Gradual decline after peak
• Broodiness: Some hens go broody; discourage by removing from nest

EGG COLLECTION AND HANDLING:

COLLECTION SCHEDULE:
• Collect eggs minimum 3-4 times daily
• Peak laying: 10am - 3pm
• More frequent collection in hot weather
• Night collection if hens lay late
• Purpose: Minimize floor eggs, prevent soiling and damage

EGG QUALITY ASSESSMENT:
• Size: 75-90g for hatching eggs
• Shape: Avoid very round or elongated eggs
• Shell quality: Smooth, uniform thickness, no cracks
• Cleanliness: Use only clean eggs; discard heavily soiled
• Internal quality: Candle to check for blood spots, meat spots

EGG CLEANING:
• Dry cleaning: Use fine sandpaper for lightly soiled eggs
• Wet cleaning: Last resort, use water warmer than egg (38-43°C)
• Disinfection: Fumigation or approved egg sanitizer
• Never use cold water - draws bacteria through pores
• Avoid washing if possible as it removes cuticle

EGG STORAGE:
• Temperature: 12-16°C optimal
• Humidity: 70-75%
• Storage time: Maximum 7 days for best hatchability
• Position: Store large end up or horizontal
• Turn eggs: If storing >4 days, tilt 45° daily
• Hatchability decline: 1-2% per day after 7 days storage
• Room: Separate cool room with good ventilation

EGG CANDLING AND SELECTION:
• Pre-incubation candling: Remove cracked, misshapen eggs
• Set only clean, properly sized eggs
• Grade eggs by weight for uniform incubation
• Mark eggs with farm ID and collection date

INCUBATION MANAGEMENT:

INCUBATOR SETTINGS (Forced-Air Incubator):
• Temperature: 37.5-37.6°C (99.5-99.7°F)
• Humidity: 55-60% (wet bulb 29-30°C) days 1-25
• Humidity: 70-75% (wet bulb 32-33°C) days 26-28
• Turning: Every 1-2 hours, minimum 3 times daily
• Ventilation: Gradually increase as embryo grows
• Incubation period: 28 days total

CANDLING SCHEDULE:
• Day 7-10: Check fertility, remove clears (infertile)
• Day 25: Final candling, remove dead-in-shell
• Check for blood ring, embryonic development
• Fertility goal: >90% of set eggs
• Hatchability goal: >80% of fertile eggs

HATCHING PROCESS:
• Transfer to hatcher: Day 25 (stop turning)
• First pip: Day 26-27
• Hatch window: 24-36 hours
• Optimal hatch: 60-70% of eggs hatch within 12 hours
• Remove poults when 90% dried off
• Grade poults: Cull weak, deformed, or small poults

POULT QUALITY ASSESSMENT:
• Body weight: 50-65g
• Activity: Alert, standing, responsive
• Eyes: Bright, open, clear
• Legs: Strong, no deformities
• Navel: Well-healed, no bleeding
• Down: Clean, fluffy, dried
• Cull rate: Typically 2-5%

BREEDER HEALTH MANAGEMENT:

DISEASE MONITORING:
• Monthly blood testing for Mycoplasma, Salmonella
• Prevent vertical disease transmission to offspring
• Quarantine new breeding stock
• Vaccination program maintained throughout production

MALE MANAGEMENT:
• Monitor semen quality every 2 weeks if using AI
• Cull infertile males promptly
• Maintain male condition through proper nutrition
• Avoid overuse - rest males 1 day per week

FEMALE MANAGEMENT:
• Body condition scoring monthly
• Adjust feed to maintain optimal weight
• Monitor for egg peritonitis, prolapse
• Remove non-layers consuming feed without production
• Provide calcium grit to prevent eggshell problems

GENETIC PERFORMANCE TRACKING:
• Record egg production per hen
• Track fertility and hatchability by male
• Monitor offspring performance
• Select replacement breeders from best performers
• Maintain pedigree records for genetic improvement

ECONOMIC CONSIDERATIONS:
• Breeder hen cost: High initial investment
• Optimal production cycle: 24-26 weeks
• Replacement strategy: Annual or after molt
• Male replacement: Higher turnover than females
• Target: 65-75 saleable poults per hen housed',
    'start_time' => 28,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
    'order' => 10,
    'is_active' => 1
]);

echo "✓ Created Turkey Enterprise (ID: {$enterprise->id})" . PHP_EOL;
echo "✓ Created All 10 Protocols" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "✅ TURKEY ENTERPRISE COMPLETE!" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "Enterprise ID: {$enterprise->id}" . PHP_EOL;
echo "Total Protocols Created: " . count($protocols) . PHP_EOL;
echo PHP_EOL;
echo "PROTOCOL BREAKDOWN:" . PHP_EOL;
echo "- Growth Stages: 5 protocols (Poult, Weaner, Grower, Pre-Breeder, Breeder)" . PHP_EOL;
echo "- Management: 5 protocols (Disease, Parasites, Housing, Nutrition, Breeding)" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
