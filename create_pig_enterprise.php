<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;

$enterprise = Enterprise::create([
    'name' => 'Pig (Swine) Production',
    'description' => 'Comprehensive pig farming enterprise covering piglet management through breeding stock, including intensive and semi-intensive systems for pork and breeding production.',
    'type' => 'livestock',
    'duration' => 18,
    'photo' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=800',
    'is_active' => 1
]);

// Protocol 1: Piglet Stage (0-3 months)
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Piglet Stage Management (0-3 Months)',
    'activity_description' => 'Critical early life management focusing on survival, colostrum intake, warmth provision, and disease prevention during the most vulnerable period.

MORPHOLOGICAL APPEARANCE:
• Birth weight: 1.2-1.5kg (improved breeds), 0.8-1.0kg (local)
• Fresh umbilical cord visible at birth
• Closed eyes at birth, open within hours
• Thin, weak legs requiring immediate assistance
• Fine body hair, no fat reserves
• Rapid growth: 200-250g daily weight gain
• Week 1: 2kg, Week 4: 7-8kg, Week 12: 25-30kg

FEEDING/WATERING:
Colostrum Critical:
• First 6 hours: Colostrum provides immunity
• Ensure all piglets suckle within 30 minutes
• Weak piglets need assistance to teat
• Split suckling for large litters (12+ piglets)

Sow\'s Milk Phase (0-4 weeks):
• Primary nutrition from sow
• 10-12 functional teats, match piglets to teats
• Piglets suckle every 45-60 minutes
• Monitor for adequate milk supply
• Fresh water from day 3

Creep Feeding (Week 2-8):
• Introduce creep feed at 7-10 days
• High protein starter (18-20%)
• Pellets or crumbles preferred
• 50-100g per piglet daily by week 4
• Increases to 400-500g by week 8
• Fresh water always available

Weaning (8 weeks standard):
• Early weaning possible at 3-4 weeks
• Traditional: 8-12 weeks
• Gradual feed transition critical

HYGIENE REQUIREMENTS:
Warmth Essential:
• Birth temperature: 30-32°C in farrowing area
• Heat lamp/source for piglets mandatory
• Reduce by 2°C weekly to 24°C
• Hypothermia major cause of death
• Provide draft-free sleeping area

Clean Bedding:
• Dry bedding changed daily
• Straw, wood shavings, or sawdust
• Separate dunging area from sleeping
• Disinfect pens between batches

Processing Within 3 Days:
• Iron injection: Day 2-3 (prevent anemia)
• Teeth clipping (optional, reduce injuries)
• Tail docking (optional, prevent tail biting)
• Ear notching/tagging for identification
• Castration of males: Week 1-2 if for meat

DISEASE SUSCEPTIBILITY:
Highly Susceptible:
• Diarrhea/scours (E. coli, rotavirus)
• Pneumonia (cold, damp conditions)
• Hypothermia (inadequate heating)
• Anemia (iron deficiency)
• Joint infections (dirty floors)

Prevention:
• Colostrum within first 6 hours
• Warm, dry environment
• Iron supplementation mandatory
• Vaccination as per schedule
• Hygiene and sanitation

PARASITE CONTROL:
• External: Mange mites, lice from sow
• Control through sow treatment pre-farrowing
• Keep bedding clean and dry

CRITICAL MANAGEMENT:
• Assistance at birth (clear airways, dry piglets)
• Ensure colostrum intake all piglets
• Warmth provision continuously
• Monitor feed intake and weight gain
• Early disease detection and treatment
• Record birth weights and litter performance

AESA PARAMETERS:
• Birth weight: Target >1.2kg
• Survival rate: Target >90%
• Daily weight gain: 200-250g
• Weaning weight: 20-30kg at 8 weeks
• Health indicators: Active, good appetite, shiny coat',
    'start_time' => 0,
    'end_time' => 12,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=800',
    'order' => 1,
    'is_active' => 1
]);

// Protocol 2: Weaner Stage (3-4 months)
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Weaner Stage Management (3-4 Months)',
    'activity_description' => 'Post-weaning growth phase requiring careful nutrition, stress management, and parasite control to establish healthy growing pigs.

MORPHOLOGICAL APPEARANCE:
• Ears erect and alert
• Increased activity level
• Visible teeth development
• Fast weight gain: 400-600g daily
• Starting weight: 25-30kg
• Ending weight: 40-55kg
• Body conformation becoming evident

FEEDING/WATERING:
Creep Feed + Milk Transition:
• Weaner pellets or meal
• Protein: 16-18%
• Energy: High digestibility required
• Feed 3 times daily initially
• 1.0-1.5kg per pig daily
• Gradual increase as pig grows

Feed Composition:
• Maize/grain: 60-65%
• Protein source (soybean/fishmeal): 20-25%
• Wheat bran: 10-15%
• Vitamins and minerals: 2-3%
• Salt: 0.5%

Fresh Water:
• Clean water always available
• 3-5 liters per pig daily
• Check drinkers functioning
• Water critical for feed digestion

HYGIENE REQUIREMENTS:
Separate Weaning Pen:
• Remove from sow at 8 weeks
• Group similar-sized pigs together
• 0.5 square meter per pig
• Good ventilation but draft-free

Sanitation:
• Clean pens daily
• Remove waste to separate area
• Disinfect feeders and drinkers weekly
• Control flies and rodents

Parasite Prevention:
• First deworming at 8 weeks (weaning)
• Treat for internal worms
• Check for mange, treat if present
• Spray for external parasites

DISEASE SUSCEPTIBILITY:
Stress-Related Infections:
• Weaning stress lowers immunity
• Post-weaning diarrhea common
• Respiratory diseases (pneumonia)
• Streptococcal infections

Prevention:
• Minimize weaning stress
• Maintain hygiene standards
• Vaccination program
• Early treatment of sick pigs
• Isolate sick animals immediately

CRITICAL MANAGEMENT:
• Early deworming mandatory
• Record and monitor weight weekly
• Biosecurity measures strict
• Group pigs by size
• Prevent fighting and bullying
• Monitor for coughing or diarrhea

AESA PARAMETERS:
• Weight gain: 400-600g daily
• Feed conversion ratio: 2.0-2.5:1
• Target weight at 4 months: 40-55kg
• Health: Active, good appetite, normal temperature',
    'start_time' => 12,
    'end_time' => 16,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1602529958936-c9dbd0a85d95?w=800',
    'order' => 2,
    'is_active' => 1
]);

// Protocol 3: Grower Stage (5-9 months)
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Grower Stage Management (5-9 Months)',
    'activity_description' => 'Rapid growth phase for market-ready pigs or future breeding stock selection, emphasizing efficient feed conversion and parasite control.

MORPHOLOGICAL APPEARANCE:
• Developing teats and genitalia visible
• Body length increases significantly
• Stronger, more muscular build
• Stronger hooves for grazing/rooting
• Starting weight: 40-55kg
• Ending weight: 80-110kg
• Daily gain: 600-800g

FEEDING/WATERING:
Grower Feed Requirements:
• Protein: 14-16%
• Energy density moderate
• Feed quantity: 2-3kg per pig daily
• Fed twice daily
• Can include forage/vegetables if available

Feed Formulation:
• Maize/cereal grains: 65-70%
• Protein source: 15-20%
• Wheat bran/fiber: 8-12%
• Minerals and vitamins: 2%
• Can supplement with kitchen waste (boiled)

Protein-Rich Diet:
• Fish meal, soybean meal, sunflower cake
• Groundnut cake if available
• Supplement with greens (sweet potato vines)

Water Always Available:
• 5-10 liters per pig daily
• Critical for growth
• Clean drinkers daily

HYGIENE REQUIREMENTS:
Housing for Growers:
• 0.8-1.0 square meter per pig
• Frequent cleaning necessary
• Separate sleeping and dunging areas
• Good drainage essential
• Reduce stocking density if crowded

Deworming Schedule:
• Second deworming at 4-5 months
• Third deworming at 7-8 months
• Use broad-spectrum anthelmintics
• Rotate drug classes

Reduce Stocking Density:
• Overcrowding causes stress
• Fighting increases
• Disease transmission higher
• Separate aggressive pigs

DISEASE SUSCEPTIBILITY:
Moderate Risk:
• Swine fever (African/Classical)
• Parasitic diseases (worms, mange)
• Respiratory infections
• Foot and mouth disease (if endemic)

Management:
• Vaccination for swine fever
• Regular deworming
• Monitor for coughing, diarrhea
• Quarantine new pigs

CRITICAL MANAGEMENT:
Growth Monitoring:
• Weigh monthly
• Record weights and feed consumed
• Calculate feed conversion ratio
• Target: 3.0-3.5kg feed per kg gain

Housing Hygiene:
• Daily cleaning
• Weekly disinfection of pens
• Control flies with insecticides
• Proper manure disposal

Health Monitoring:
• Daily observation for illness signs
• Check body condition regularly
• Treat promptly any sick pigs
• Separate sick from healthy

MARKET PREPARATION:
• Pigs ready for market at 80-100kg (6-7 months)
• Breeding stock selection at 7-8 months
• Gilts for breeding: 90-110kg, 7-8 months
• Boars for breeding: 100-120kg, 8-9 months

AESA PARAMETERS:
• Body length and girth measurements
• Growth monitoring: Weekly weighing
• Feed efficiency: Target FCR <3.5
• Health: Normal temperature, active, eating well',
    'start_time' => 16,
    'end_time' => 36,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1609060161098-ab2a19aec8f7?w=800',
    'order' => 3,
    'is_active' => 1
]);

// Protocol 4: Gilt/Boar Stage (9-15 months)
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Gilt/Boar Development (9-15 Months)',
    'activity_description' => 'Selection and development of breeding stock focusing on heat detection, nutrition for reproduction, and genetic improvement.

MORPHOLOGICAL APPEARANCE:
Gilts (Young Females):
• Puberty signs evident: 6-8 months
• Swollen, reddened vulva during heat
• Teats well-developed (minimum 12)
• Body weight: 110-140kg
• Body condition score 3-3.5 (ideal)

Boars (Young Males):
• Testicle development complete
• Aggressive behavior increases
• Thick shoulder development
• Strong legs and good mobility
• Body weight: 120-160kg

FEEDING/WATERING:
High-Energy Feed:
• Protein: 14-16%
• Quality feed for reproductive development
• 2.5-3.0kg per animal daily
• Mineral supplements essential
• Calcium and phosphorus for bone strength

Water Ad Libitum:
• 8-15 liters per animal daily
• Essential for developing reproductive organs
• Unlimited access required

Mineral Supplements:
• Vitamins A, D, E for reproduction
• Calcium and phosphorus
• Salt lick available
• Zinc for boar fertility

HYGIENE REQUIREMENTS:
Proper Drainage and Cleanliness:
• Dry, clean pens essential
• 1.5-2.0 square meters per animal
• Separate boar housing from gilts
• Good ventilation

Heat Control:
• Shade provision in hot climates
• Wallows for cooling (mud bath)
• Sprinkler systems if available
• Heat stress reduces fertility

Clean Mating Areas:
• Designated breeding pens
• Clean before each mating
• Safe surfaces to prevent injury
• Observation possible for AI or natural service

REPRODUCTIVE MANAGEMENT:
Gilt Heat Detection:
• First heat: 6-8 months
• Heat cycle: Every 21 days
• Heat duration: 2-3 days
• Signs: Restlessness, mounting others, vulva swelling
• Standing reflex when back pressed

Breeding Time:
• First breeding: 7-8 months old, 110-130kg
• Breed on 2nd or 3rd heat
• Standing heat: 12-24 hours post signs
• Mate twice: 12 hours apart

Boar Management:
• Sexual maturity: 6-7 months
• Start breeding: 8-9 months
• Natural service: 1 boar per 15-20 sows
• Rest between matings required
• Feed well to maintain condition

DISEASE SUSCEPTIBILITY:
Reproductive Diseases:
• Brucellosis (causes abortion)
• Leptospirosis (reproductive failure)
• Parvovirus (embryo death)
• Abortions and stillbirths
• Prolapse risks

Prevention:
• Vaccination for reproductive diseases
• Maintain body condition (not too fat)
• Biosecurity for new breeding stock
• Quarantine new animals 30 days

CRITICAL MANAGEMENT:
Breeding Records:
• Heat dates recorded
• Breeding/mating dates
• Expected farrowing date (114 days)
• Boar used (if multiple boars)
• Body condition scoring

Nutrition Monitoring:
• Maintain moderate body condition
• Not too fat or too thin
• Adjust feed based on condition
• Increase feed slightly pre-breeding

Vet Support:
• Pregnancy diagnosis at 30 days
• Ultrasound or blood test
• Monitor for abortion signs
• Vaccinations up to date

AESA PARAMETERS:
• Age at puberty: 6-8 months
• Body condition score: Target 3.0-3.5
• Heat detection success rate
• Conception rates monitored
• Genetic traits recorded for selection',
    'start_time' => 36,
    'end_time' => 60,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1533093818119-ac1fa47f854a?w=800',
    'order' => 4,
    'is_active' => 1
]);

// Protocol 5: Sow/Boar Production (15+ months)
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Sow/Boar Production Management (15+ Months)',
    'activity_description' => 'Mature breeding stock management covering gestation, farrowing, lactation, and boar maintenance for sustained production.

MORPHOLOGICAL APPEARANCE:
Mature Sows:
• Large body mass: 150-250kg
• Full udder development (12-14 teats)
• Mature at 12-15 months
• Signs of estrus every 21 days if not pregnant
• Abdominal enlargement during pregnancy

Mature Boars:
• Heavy, muscular build: 180-300kg
• Prominent testicles
• Thick shoulders and neck
• Aggressive temperament
• Strong legs for mating

FEEDING/WATERING:
Lactation or Mating Feed:
• High protein: 16-18%
• High energy for milk production
• Sow lactation: 5-7kg daily
• Boar maintenance: 2.5-3.5kg daily

Pregnant Sow Nutrition:
• Gestation period: 114 days (3 months, 3 weeks, 3 days)
• Early pregnancy: 2.0-2.5kg daily
• Late pregnancy (last month): 3.0-3.5kg daily
• Avoid overfeeding (causes farrowing problems)

Lactating Sow Nutrition:
• High demands for milk production
• 5-7kg feed daily
• Litter size affects requirement
• Protein 16-18%, high energy

Extra Nutrition for Pregnant/Lactating:
• Calcium for bone development
• Iron supplementation
• Vitamins A, D, E
• Fresh green forages
• Root crops (sweet potatoes, cassava)

Unlimited Water:
• Pregnant sow: 15-20 liters daily
• Lactating sow: 20-30 liters daily
• Boar: 10-15 liters daily
• Critical for milk production

HYGIENE REQUIREMENTS:
Daily Cleaning:
• Remove waste daily
• Wet-dry system preferred
• Separate dunging area
• Control ammonia levels

Sanitation During Farrowing:
• Clean farrowing pen thoroughly
• Disinfect before sow enters
• Fresh bedding provided
• Warm, dry environment

Waste Management:
• Manure removed to compost area
• Biogas potential if scale allows
• Reduces fly breeding
• Environmental management

FARROWING MANAGEMENT:
Pre-Farrowing (1 week before):
• Move to clean farrowing pen
• Farrowing crate if available
• Wash sow with mild disinfectant
• Provide nesting materials
• Reduce feed slightly

Farrowing (Birth):
• Gestation: 114 days average
• Signs: Restlessness, nest building, milk in teats
• Most farrow at night
• Litter size: 8-12 piglets (improved breeds)
• Attendance beneficial for piglet survival

Post-Farrowing:
• Ensure all piglets suckle within 6 hours
• Remove afterbirth
• Monitor sow for fever/infection
• Piglet processing (iron, teeth, tail)

LACTATION MANAGEMENT:
Milking Period: 8 weeks standard
• Peak milk: 3-4 weeks
• Feed sow heavily for milk production
• Gradual weaning reduces mastitis
• Monitor sow body condition

Sow Comfort:
• Heat stress affects milk
• Provide cooling in hot weather
• Adequate water critical
• Rest between farrowings important

BOAR MANAGEMENT:
• Separate housing from sows
• Exercise important for fertility
• Not overused (max 2-3 matings/week)
• Good nutrition maintains libido
• Monitor for injuries during mating

DISEASE PREVENTION:
• Mastitis (udder infection)
• Metritis (uterine infection post-farrowing)
• Farrowing complications
• Agalactia (no milk syndrome)

CRITICAL MANAGEMENT:
Farrowing Preparation:
• Deworm 2 weeks before farrowing
• Vaccinations current
• Body condition score 3.0-3.5
• Farrowing kit ready (disinfectant, towels, heat lamp)

Milking Hygiene:
• Check udder daily for mastitis
• Hot, hard udder indicates infection
• Treat promptly with antibiotics
• Encourage piglet suckling

Sow Comfort:
• Comfortable lying area
• Protection from extreme weather
• Stress reduces milk production
• Separate from other pigs during lactation

REPRODUCTIVE HEALTH:
• Sow comes into heat 3-7 days after weaning
• Rebreed immediately for efficiency
• Target: 2.2-2.4 litters per sow per year
• Productive life: 5-7 years (8-10 litters)
• Cull poor performers

AESA PARAMETERS:
• Litter size: Target 10-12 born alive
• Milk production quality
• Sow longevity and health
• Piglet survival rate: >90%
• Weaning weights: 20-30kg at 8 weeks',
    'start_time' => 60,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1504315851767-e7d95a6e2e17?w=800',
    'order' => 5,
    'is_active' => 1
]);

// Protocol 6: Disease Management
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Comprehensive Disease Management',
    'activity_description' => 'Prevention, identification, and control of major swine diseases affecting profitability and herd health.

AFRICAN SWINE FEVER (ASF):
Critical Disease:
• Highly contagious viral disease
• NO TREATMENT OR VACCINE available
• Mortality: Up to 100%
• Signs: High fever (40-42°C), red skin blotches, bloody diarrhea, sudden death
• Spread: Direct contact, ticks, contaminated feed/water
• Prevention: Strict biosecurity, quarantine, no swill feeding

CLASSICAL SWINE FEVER (Hog Cholera):
• Viral disease, vaccine available
• Signs: High fever, huddling, trembling, purple skin, diarrhea
• Mortality: 80-100% in unvaccinated
• Vaccination: At 2-3 months, annual boosters
• Treatment: None, prevention critical

SWINE INFLUENZA:
• Respiratory viral disease
• Signs: Sudden onset, fever, coughing, nasal discharge, loss of appetite
• Usually not fatal but reduces growth
• Prevention: Vaccination, biosecurity, reduce stress
• Treatment: Supportive care, antibiotics for secondary infections

FOOT AND MOUTH DISEASE:
• Highly contagious viral disease
• Signs: Fever, blisters on snout/feet/teats, lameness, drooling
• Affects all cloven-hoofed animals
• Vaccination required in endemic areas
• Reportable disease

PARASITIC DISEASES:
• Mange, worms severely impact growth
• Signs: Scratching, poor growth, anemia
• Treatment: Regular deworming and acaricide treatment
• See Parasite Control protocol

RESPIRATORY DISEASES:
Pneumonia:
• Bacterial/viral causes
• Signs: Coughing, labored breathing, nasal discharge
• Common in poorly ventilated housing
• Treatment: Antibiotics (penicillin, tetracyclines)
• Prevention: Good ventilation, reduce dust

Pleuropneumonia:
• Severe bacterial lung infection
• Sudden death or chronic coughing
• Treatment: Long-acting antibiotics
• Vaccination available

DIARRHEAL DISEASES:
E. coli Scours:
• Common in piglets
• Watery diarrhea, dehydration
• Treatment: Antibiotics, electrolytes
• Prevention: Hygiene, colostrum intake

Transmissible Gastroenteritis (TGE):
• Viral, highly fatal in piglets
• Severe diarrhea, vomiting
• No specific treatment
• Prevention: Biosecurity

REPRODUCTIVE DISEASES:
Brucellosis:
• Bacterial, causes abortion
• Signs: Late-term abortion, retained placenta
• Zoonotic (infects humans)
• Test and cull infected animals

Parvovirus:
• Causes embryonic death, mummification
• Vaccination of gilts before breeding
• No treatment

SKIN DISEASES:
Erysipelas:
• Bacterial, diamond-shaped skin lesions
• Fever, sudden death, chronic arthritis
• Treatment: Penicillin very effective
• Vaccination recommended

Ringworm:
• Fungal infection
• Circular hairless patches
• Treatment: Antifungal ointments
• Contagious to humans

BIOSECURITY MEASURES:
• All-in, all-out production system
• Quarantine new pigs 30 days
• Control visitor access
• Disinfect footwear (footbaths)
• Avoid swill feeding (illegal in many countries)
• Separate age groups
• Rodent and bird control
• Proper manure disposal

VACCINATION SCHEDULE:
• 6-8 weeks: Classical Swine Fever
• 10-12 weeks: Erysipelas, FMD (if endemic)
• Breeding stock: Annual boosters
• Pregnant sows: E. coli vaccine pre-farrowing

TREATMENT PROTOCOLS:
• Early detection critical
• Isolate sick pigs immediately
• Consult veterinarian for diagnosis
• Follow drug withdrawal periods
• Record all treatments
• Cull chronic poor-doers

DISEASE MONITORING:
• Daily health checks
• Record morbidity and mortality
• Post-mortem examination of deaths
• Laboratory testing when needed
• Herd health plans with vet',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1584308972272-9e4e7685e80f?w=800',
    'order' => 6,
    'is_active' => 1
]);

// Protocol 7: Parasite Control
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Integrated Parasite Management',
    'activity_description' => 'Comprehensive internal and external parasite control program for optimal pig health and productivity.

INTERNAL PARASITES:

ROUNDWORMS (Ascaris suum):
Most Common:
• Large white worms, up to 30cm long
• Live in small intestine
• Heavy infections cause poor growth, pot-belly
• Piglets most affected
• Migration through liver causes "milk spots" (condemned liver)
• Signs: Poor growth, coughing (lung migration), diarrhea

TAPEWORMS:
• Less common, flat segmented worms
• Intermediate hosts required
• Usually low pathogenicity
• Control through hygiene and deworming

WHIPWORMS (Trichuris):
• Thin worms in large intestine
• Cause bloody diarrhea
• Difficult to eliminate from environment
• Regular deworming essential

KIDNEY WORMS (Stephanurus):
• Affect kidneys and liver
• Pigs appear healthy but reduced growth
• Detected at slaughter
• Control through regular deworming

LUNGWORMS (Metastrongylus):
• Live in bronchi and bronchioles
• Cause coughing and respiratory distress
• Earthworms are intermediate hosts
• More common in outdoor systems

DEWORMING PROGRAM:
Piglets:
• First deworming: 6-8 weeks (at weaning)
• Second deworming: 4-5 months

Growers/Finishers:
• Deworm every 2-3 months
• Before marketing if grazing

Breeding Stock:
• Deworm gilts/boars before first breeding
• Deworm sows 1-2 weeks before farrowing
• Every 6 months for boars
• Treat all animals at once for effectiveness

Drugs Used:
• Ivermectin: Broad spectrum, internal and external
• Fenbendazole: Broad spectrum roundworms
• Levamisole: Roundworms, lungworms
• Piperazine: Roundworms only
• Rotate drugs to prevent resistance
• Follow label dosage carefully
• Observe meat withdrawal periods

EXTERNAL PARASITES:

MANGE MITES (Sarcoptes scabiei):
Most Important:
• Microscopic mites burrow in skin
• Intense itching, scratching, rubbing
• Hair loss, crusty skin (especially ears, neck, legs)
• Severe irritation reduces growth and feed conversion
• Spreads rapidly through herd
• Can persist in housing for weeks

Treatment:
• Injectable ivermectin (most effective)
• Repeat after 10-14 days
• Treat all pigs simultaneously
• Spray housing with acaricide
• Treat new pigs before mixing with herd

LICE (Haematopinus suis):
Swine Louse:
• Large louse, visible to naked eye
• Sucks blood, causes anemia and irritation
• Found behind ears, neck, flanks
• Severe infestations debilitate pigs
• Especially bad in winter (pigs huddled)

Treatment:
• Ivermectin injectable
• Permethrin pour-on or spray
• Treat twice, 14 days apart
• All pigs treated together

TICKS:
• More common in outdoor/extensive systems
• Attach to skin, suck blood
• Transmit diseases
• Remove manually or use acaricides
• Ivermectin effective

FLEAS:
• Less common but can infest pig housing
• Cause irritation and allergic reactions
• Control through housing hygiene
• Insecticide sprays for housing

INTEGRATED CONTROL PROGRAM:

Housing Hygiene:
• Remove all pigs before treatment
• Clean thoroughly, remove all organic matter
• Spray housing with acaricide
• Allow to dry before restocking
• Burn old bedding if heavily infested

Regular Inspection:
• Check pigs weekly for scratching
• Look for hair loss, skin lesions
• Examine skin folds, behind ears
• Early detection prevents spread

Biosecurity:
• Quarantine and treat new pigs
• Treat before mixing with herd
• Avoid contact with infected animals
• Clean transport vehicles

Record Keeping:
• Date of treatments
• Products used and dosage
• Response to treatment
• Plan next treatment date

Resistance Management:
• Rotate anthelmintic drug classes
• Use correct dosages
• Treat all pigs simultaneously
• Clean environment during treatment
• Don\'t under-dose

ENVIRONMENTAL CONTROL:
• Separate age groups
• Good drainage prevents reinfection
• Concrete floors easier to clean
• Regular manure removal
• Control flies (mechanical transmission)

MONITORING EFFECTIVENESS:
• Fecal egg counts periodically
• Improved growth rates after treatment
• Reduced scratching behavior
• Better feed conversion
• Cleaner skin and coat',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1628788159885-e1b3d69fd61e?w=800',
    'order' => 7,
    'is_active' => 1
]);

// Protocol 8: Housing & Infrastructure
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Housing Systems & Infrastructure',
    'activity_description' => 'Comprehensive housing design and management for different production stages ensuring pig welfare, biosecurity, and efficiency.

HOUSING SYSTEMS:

INTENSIVE SYSTEM:
Complete Confinement:
• Pigs housed permanently indoors
• All feeds brought to pigs
• Higher capital investment
• Better disease control possible
• Higher stocking density
• Suitable for commercial production
• Climate control possible

Advantages:
• Disease and parasite control easier
• Feed conversion more efficient
• Better record keeping
• Protection from weather
• Easier management

Disadvantages:
• High initial capital
• Higher feed costs (no scavenging)
• Waste management challenges
• Requires skilled labor

SEMI-INTENSIVE SYSTEM:
Partial Confinement:
• Housing for sleeping and farrowing
• Outdoor paddock for exercise/rooting
• Balance of cost and control
• Most common in smallholder systems
• Lower capital than intensive
• Some grazing/foraging possible

Paddock Requirements:
• Shade trees or structures
• Water points accessible
• Fencing strong (woven wire or concrete)
• Rotation to prevent parasite buildup
• Area: 10-20 sq.m per pig

EXTENSIVE SYSTEM:
Free Range/Scavenging:
• Minimal housing
• Pigs roam freely
• Low input, low output
• Indigenous breeds suited
• Minimal disease control
• Not suitable for commercial production
• Common in traditional systems

PEN DESIGN BY STAGE:

FARROWING PENS:
Size: 2.0×2.5 meters
• Farrowing crate: 2.0×0.6m (optional but saves piglets)
• Creep area for piglets: 1.5 sq.m
• Heat source for piglets
• Sow cannot turn (crate system)
• Separate feeding and resting areas
• Easy piglet access to teats
• Guard rails prevent crushing

PIGLET/WEANER PENS:
Space: 0.3-0.5 sq.m per piglet
• Group sizes: 10-15 piglets
• Heated sleeping area (25-28°C)
• Separate dunging area
• Easy-to-clean floors
• Good ventilation without drafts
• Creep feeders accessible

GROWER PENS:
Space: 0.8-1.0 sq.m per pig
• Group sizes: 10-20 pigs
• Solid concrete floors
• Slatted dunging area (optional)
• Trough access: 30cm per pig
• Separate lying and dunging areas

FINISHER PENS:
Space: 1.0-1.2 sq.m per pig
• Groups: 10-15 pigs
• Strong construction (heavy pigs)
• Good drainage essential
• Adequate trough space
• Exercise area beneficial

BREEDING STOCK HOUSING:
Gilt/Sow Pens:
• Individual: 1.5-2.0 sq.m per sow
• Group housing: 2.0-2.5 sq.m per sow
• Access to outdoor area ideal
• Wallow for cooling in tropics
• Strong fencing/walls

Boar Pen:
• Size: 5-6 sq.m minimum
• Solid construction (strong animal)
• Good visibility but separate from sows
• Exercise area important
• Safe restraint system for breeding

CONSTRUCTION MATERIALS:

Floors:
• Concrete: Durable, easy to clean (most common)
• Slatted floors: Good drainage, less labor
• Earth: Cheap but hard to disinfect
• Slope: 2-3% toward drain

Walls:
• Brick/concrete blocks: Durable, expensive
• Timber: Cheaper, less durable
• Woven wire: Cheap, good ventilation
• Height: 1.0-1.2m for growers, 1.2-1.5m for adults

Roofing:
• Iron sheets: Durable, hot (needs insulation)
• Tiles: Expensive, cooler
• Thatch: Cheap, traditional, harbors parasites
• Height: 2.5-3.0m at eaves for ventilation

ENVIRONMENTAL CONTROL:

Ventilation:
• Critical for health
• Open sides with adjustable panels
• Ridge vents for hot air escape
• Avoid drafts on pigs
• Ammonia smell indicates poor ventilation

Temperature:
• Piglets: 28-32°C (heating required)
• Weaners: 24-28°C
• Growers/finishers: 18-24°C
• Breeding stock: 15-25°C
• Cooling: Wallows, sprinklers, shade in hot climates

Lighting:
• Natural light preferred
• Windows: 10-15% of floor area
• Artificial lighting: 8-12 hours daily
• Dim lighting reduces fighting

WASTE MANAGEMENT:

Solid Waste System:
• Daily scraping to collection point
• Composting for 3-6 months
• Excellent fertilizer high in NPK
• Biogas production if scale allows

Liquid Waste:
• Flush systems with water
• Lagoon treatment
• Separation of solids
• Irrigation use after treatment

Fly Control:
• Major nuisance and disease vector
• Larvicides in manure
• Traps and baits
• Frequent manure removal

WATER SYSTEMS:

Drinkers:
• Nipple drinkers: Clean, less waste
• Bowl drinkers: Easy for pigs to use
• Troughs: Cheap but wasteful
• Height: Pig shoulder level
• Number: 1 drinker per 10-12 pigs

Water Quality:
• Clean, fresh water always
• Test periodically for contamination
• Chlorinate if necessary
• Adequate pressure for nipple drinkers

FEEDING EQUIPMENT:

Troughs:
• Concrete: Durable, heavy, expensive
• Metal: Durable, can be moved
• Plastic: Light, cheap, less durable
• Length: 30cm per grower, 40cm per adult
• Height: 30-40cm off floor

Automated Feeders:
• Self-feeders reduce labor
• Suitable for dry feed only
• Reduces waste
• One space per 5-8 pigs

BIOSECURITY FEATURES:

Perimeter Fencing:
• Keeps pigs in, predators out
• Height: 1.5m minimum
• Strong construction
• Lock gates

Footbaths:
• Entrance to each building
• 10cm deep, 1m long
• Change disinfectant weekly
• All persons use before entry

Quarantine Facility:
• Separate building for new pigs
• Minimum 30-day isolation
• Treat for parasites during quarantine
• Observe for disease signs

Change Room:
• Farm clothes and boots
• Prevents disease introduction
• Shower facilities ideal',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1626370167565-826c67c43945?w=800',
    'order' => 8,
    'is_active' => 1
]);

// Protocol 9: Nutrition & Feed Management
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Comprehensive Nutrition Management',
    'activity_description' => 'Complete feeding program and feed formulation for all pig production stages optimizing growth and reproductive performance.

NUTRITIONAL REQUIREMENTS BY STAGE:

PIGLET STARTER (0-8 weeks):
Nutrient Requirements:
• Crude protein: 18-20%
• Energy (DE): 3,300-3,400 kcal/kg
• Lysine: 1.3-1.5%
• Calcium: 0.9-1.0%
• Phosphorus: 0.7-0.8%
• Highly digestible ingredients essential

Feed Intake:
• Week 2-4: 50-100g per day
• Week 5-8: 300-500g per day
• Ad libitum (free choice) feeding
• Fresh feed daily, remove stale feed

WEANER FEED (8-16 weeks):
Nutrient Requirements:
• Crude protein: 16-18%
• Energy (DE): 3,200-3,300 kcal/kg
• Lysine: 1.1-1.3%
• Feed conversion: 2.0-2.5:1
• Transition gradually from starter

Feed Intake:
• 1.0-1.5kg per pig daily
• Restricted feeding prevents waste
• Fed 2-3 times daily

GROWER FEED (16 weeks-market):
Nutrient Requirements:
• Crude protein: 14-16%
• Energy (DE): 3,100-3,200 kcal/kg
• Lysine: 0.9-1.1%
• Can include more fiber
• Cost-effective formulation

Feed Intake:
• 2.0-3.0kg per pig daily
• Increases as pig grows
• Fed twice daily
• Target weight gain: 600-800g daily

GESTATING SOW FEED:
Nutrient Requirements:
• Crude protein: 12-14%
• Energy (DE): 2,900-3,100 kcal/kg
• Higher fiber acceptable (15-20%)
• Bulk without excessive calories
• Prevent obesity

Feed Intake:
• First 85 days: 2.0-2.5kg daily
• Last 30 days: 2.5-3.0kg daily
• Increase gradually late pregnancy
• Body condition score maintained at 3.0-3.5

LACTATING SOW FEED:
Nutrient Requirements:
• Crude protein: 16-18%
• Energy (DE): 3,300-3,400 kcal/kg
• Lysine: 1.0-1.2%
• High quality, digestible ingredients
• Calcium: 0.9-1.0%

Feed Intake:
• 4-7kg per day depending on litter size
• Increase feed each week post-farrowing
• Peak at week 3-4 of lactation
• Ad libitum feeding preferred
• Water critical for milk production

BOAR FEED:
Nutrient Requirements:
• Crude protein: 14-16%
• Energy (DE): 3,000-3,100 kcal/kg
• Quality protein for fertility
• Vitamins and minerals adequate

Feed Intake:
• Maintenance: 2.0-2.5kg daily
• During heavy use: 2.5-3.0kg daily
• Maintain body condition score 3.0

FEED INGREDIENTS:

ENERGY SOURCES:
Maize (Corn):
• 60-70% of pig diet
• High energy (3,350 kcal/kg)
• Low protein (9%)
• Most palatable
• Can cause soft fat if excessive

Wheat:
• Good energy source
• Can replace up to 50% of maize
• Higher protein than maize (12%)
• May cause soft feces if >40%

Barley:
• Lower energy than maize
• Good for breeding stock
• 30-50% of diet maximum
• High fiber

Cassava:
• Cheap energy source
• Must be well-processed (cyanide risk)
• 20-30% of diet maximum
• Low protein

Sweet Potato:
• Can replace 30-40% maize
• Well-liked by pigs
• Fresh or dried
• Seasonal availability

Rice Bran:
• By-product, relatively cheap
• High fiber (12-15%)
• 10-20% of diet
• Can become rancid (store properly)

PROTEIN SOURCES:
Soybean Meal:
• Best plant protein (44-48%)
• Excellent amino acid profile
• 10-25% of diet depending on stage
• Relatively expensive

Fish Meal:
• High quality protein (60-65%)
• Excellent for young pigs
• 3-5% of diet
• Expensive, fish smell in meat if excessive

Groundnut (Peanut) Cake:
• 40-45% protein
• Can replace some soybean meal
• Watch for aflatoxin contamination
• 10-15% of diet

Sunflower Cake:
• 30-35% protein
• High fiber
• 10-15% of diet
• Cheaper than soybean

Cotton Seed Cake:
• 35-40% protein
• Contains gossypol (toxic if raw)
• Heat-treated only
• 5-10% maximum

Blood Meal:
• Very high protein (80-85%)
• From slaughterhouses
• 2-3% of diet maximum
• Excellent for piglets

MINERALS AND VITAMINS:
Essential Minerals:
• Calcium: Limestone, bone meal
• Phosphorus: Bone meal, dicalcium phosphate
• Salt: 0.3-0.5% of diet
• Trace minerals: Premix (iron, zinc, copper, manganese)

Vitamins:
• Fat-soluble: A, D, E, K
• Water-soluble: B-complex
• Commercial premix recommended
• 1-2% of total diet
• Critical for reproduction and immunity

OTHER INGREDIENTS:
Wheat Bran:
• Bulky, high fiber (15%)
• Good for breeding stock
• Reduces cost
• 10-20% of diet

Kitchen Waste:
• Must be boiled (disease control)
• Variable composition
• Can supplement up to 30% of diet
• Illegal in some countries (swill feeding)

Green Forages:
• Sweet potato vines, legumes
• Vitamins and minerals
• Free or low cost
• 10-20% of diet
• Chop finely for young pigs

SAMPLE FEED FORMULATIONS:

Piglet Starter (18% CP):
• Maize: 50kg
• Wheat: 10kg
• Soybean meal: 22kg
• Fish meal: 5kg
• Rice bran: 5kg
• Skim milk powder: 3kg
• Limestone: 1kg
• Dicalcium phosphate: 0.8kg
• Salt: 0.3kg
• Vitamin/mineral premix: 2kg
• Lysine supplement: 0.5kg
• Vegetable oil: 0.4kg
Total: 100kg

Weaner Feed (16% CP):
• Maize: 60kg
• Soybean meal: 20kg
• Wheat bran: 10kg
• Fish meal: 3kg
• Limestone: 1kg
• Dicalcium phosphate: 1kg
• Salt: 0.3kg
• Vitamin/mineral premix: 2kg
• Lysine: 0.3kg
Total: 100kg (adjust to 100)

Grower Feed (14% CP):
• Maize: 65kg
• Soybean meal: 15kg
• Sunflower cake: 5kg
• Wheat bran: 12kg
• Limestone: 0.8kg
• Dicalcium phosphate: 0.8kg
• Salt: 0.3kg
• Vitamin/mineral premix: 1kg
Total: 100kg

Lactating Sow Feed (17% CP):
• Maize: 58kg
• Soybean meal: 23kg
• Wheat bran: 10kg
• Fish meal: 3kg
• Limestone: 1.5kg
• Dicalcium phosphate: 1.2kg
• Salt: 0.3kg
• Vitamin/mineral premix: 2kg
• Vegetable oil: 1kg
Total: 100kg

FEEDING MANAGEMENT:

Mixing Feed:
• Weigh ingredients accurately
• Mix thoroughly (paddle mixer ideal)
• Add premix last (prevents loss)
• Store in dry, rodent-proof containers
• Use within 1-2 months (vitamins degrade)

Feeding Schedule:
• Piglets: Ad libitum (always available)
• Weaners/growers: Twice daily, measured amounts
• Pregnant sows: Once or twice daily
• Lactating sows: 2-3 times daily or ad libitum
• Boars: Once daily

Feed Storage:
• Dry, cool location
• Rodent-proof bins or bags on pallets
• First in, first out system
• Check for mold, discard if present
• Ingredients: <3 months storage

Feed Quality:
• No mold or rancid smell
• Free from stones, sticks
• Appropriate particle size
• Dusty feed reduced palatability
• Add water/fat to reduce dust

MONITORING PERFORMANCE:

Feed Conversion Ratio (FCR):
• kg feed consumed ÷ kg weight gain
• Piglets: 1.5-2.0:1
• Weaners: 2.0-2.5:1
• Growers/finishers: 2.8-3.5:1
• Lower FCR = better efficiency
• Record weekly to monitor

Body Condition Scoring:
• Scale 1-5 (1=thin, 5=fat)
• Target: 3.0-3.5
• Feel ribs, spine, hip bones
• Adjust feeding accordingly

Weight Monitoring:
• Weigh pigs monthly
• Target daily gains by stage
• Adjust feed if below target
• Individual or group weighing',
    'start_time' => 0,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1591950911723-16fd2fc9a04e?w=800',
    'order' => 9,
    'is_active' => 1
]);

// Protocol 10: Breeding & Reproductive Management
ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Breeding & Reproductive Management',
    'activity_description' => 'Complete reproductive program covering selection, breeding, pregnancy, farrowing, and piglet management for sustainable production.

BREEDING STOCK SELECTION:

GILT SELECTION (7-8 months):
Physical Characteristics:
• Minimum 12 teats, evenly spaced
• No inverted or damaged teats
• Straight legs, strong pasterns
• Good body length and depth
• Sound feet and legs
• Alert, active temperament
• Weight: 110-130kg at first breeding
• No hernias or physical defects

Genetic Considerations:
• From productive dam (large litters)
• Good growth rate history
• Healthy, disease-free
• Not from same litter as boar (inbreeding)
• Avoid extremes (too fat/thin)

BOAR SELECTION (8-9 months):
Physical Characteristics:
• Two normal, equal-sized testicles
• Good body conformation
• Strong legs and feet
• Aggressive but manageable temperament
• Good libido
• Weight: 120-150kg at first use

Breeding Value:
• From large litters
• Good growth genetics
• Proven fertility (if mature)
• 1 boar services 15-20 sows
• Replace after 2-3 years

ESTRUS (HEAT) DETECTION:

Signs of Heat:
• Restlessness, reduced appetite
• Frequent urination
• Swollen, red vulva
• Clear mucus discharge
• Mounting other sows
• Standing reflex (most reliable)
• Ears pricked when back pressed

Heat Cycle:
• First heat: 6-8 months
• Cycle length: 21 days (18-24 range)
• Heat duration: 2-3 days
• Best breeding: Day 2 of heat
• Standing heat: 12-36 hours

Standing Reflex Test:
• Press firmly on sow\'s back
• If in standing heat, sow stands motionless
• This is optimal breeding time
• Boar presence enhances response

Post-Weaning Heat:
• Sow comes in heat 3-7 days after weaning
• Most fertile time
• Breed immediately for efficiency
• Check daily after weaning

BREEDING METHODS:

Natural Service:
• Boar runs with sow
• Observe to confirm mating
• Mate twice, 12-24 hours apart
• More than once increases conception
• Rest boar between services
• Record date and boar used

Hand Mating:
• Bring sow to boar pen
• Supervised mating
• Confirm successful service
• Better control and records
• Safer for animals

Artificial Insemination (AI):
• Requires skilled technician
• More expensive equipment
• Better genetics accessible
• Disease control easier
• One boar serves many sows
• Breeding records accurate

PREGNANCY MANAGEMENT:

Pregnancy Diagnosis:
• Failure to return to heat (21 days)
• Ultrasound: 30 days (most accurate)
• Blood test: 30 days
• Behavioral changes (calmer)
• Abdominal enlargement: 60+ days

Gestation Period:
• 114 days (3 months, 3 weeks, 3 days)
• Range: 110-118 days
• Calculate expected farrowing date
• Prepare 1 week before

Feeding During Pregnancy:
• Early pregnancy (0-85 days): 2.0-2.5kg daily
• Late pregnancy (85-114 days): 2.5-3.0kg daily
• Avoid obesity (farrowing problems)
• Body condition score: 3.0-3.5
• Fiber important (bulk without calories)

Exercise:
• Outdoor access ideal
• Prevents leg problems
• Reduces farrowing difficulties
• Group housing acceptable until day 110

Health Care:
• Deworm 2 weeks before farrowing
• Vaccinations current
• E. coli vaccine pre-farrowing (if available)
• Iron supplementation if anemic
• Foot trimming if needed

FARROWING PREPARATION:

Move to Farrowing Pen:
• 5-7 days before expected date
• Clean, disinfected pen
• Farrowing crate or rails
• Heat lamp for piglets
• Fresh bedding

Farrowing Kit Ready:
• Clean towels or sacks
• Iodine solution (7%)
• Scissors (cord cutting if needed)
• Scales (weigh piglets)
• Recording sheets
• Lubricant (assist difficult births)
• Oxytocin injection (for retained piglets)
• Vet phone number

Reduce Feed:
• Days before farrowing: 50% reduction
• Prevents constipation
• Full feed after farrowing
• Provide water always

FARROWING MANAGEMENT:

Signs of Imminent Farrowing:
• Restlessness, nest building
• Refuses feed
• Milk in teats (can express)
• Vulva swollen
• Usually farrows at night

Birth Process:
• First piglet to last: 2-6 hours
• Average 10-15 minute intervals
• Piglet born in sac or not
• Break sac if present
• Clear nose/mouth of piglets

Assistance During Farrowing:
• Most farrowings normal, minimal help
• Assist if >30 minutes no piglet
• Wash, lubricate hand/arm
• Gently reach in to feel for piglet
• Pull gently during contractions
• Call veterinarian if difficulty

PIGLET PROCESSING (First 24 hours):

Immediate Care:
• Clear airways (mouth/nose)
• Dry piglet with towel (stimulates)
• Place under heat lamp
• Ensure suckling within 6 hours (colostrum)
• Weak piglets assisted to teat
• Split suckling if litter >12

Iron Injection:
• Day 2-3 of life
• 200mg iron dextran
• Prevents anemia (critical)
• Injection behind ear or ham

Teeth Clipping (optional):
• Sharp needle teeth damage sow\'s teats
• Clip tips only, don\'t break teeth
• Sanitize equipment between piglets
• Some leave teeth unclipped

Tail Docking (optional):
• Prevents tail biting later
• Done day 1-3
• Leave 2-3cm stump
• Apply iodine to prevent infection
• Use sharp side cutters

Castration (males for meat):
• Week 1-2 (less stress)
• Surgical method
• Apply iodine after
• Record and monitor healing

Identification:
• Ear notching or tagging
• Record individual piglets
• Litter identification

LACTATION MANAGEMENT:

Colostrum Critical:
• First 6-24 hours produces colostrum
• Antibodies for immunity
• Every piglet must receive
• Weak piglets need help
• Piglets nurse every 45-60 minutes

Milk Production:
• Peak production: Week 3-4
• Litter size affects production
• 8-12 liters per day
• Feed sow heavily (5-7kg daily)

Fostering/Cross-Fostering:
• Balance litter sizes
• Within first 24-48 hours
• Match piglets to sow\'s milk supply
• Rub piglets with sow\'s milk
• 10-12 piglets maximum per sow

Creep Feeding Piglets:
• Start week 2-3
• High quality creep feed
• Separate creep area
• Reduces weaning stress
• 300-500g per piglet by weaning

Sow Health:
• Monitor udder for mastitis
• Check for fever
• Adequate water (20-30 liters daily)
• Watch for agalactia (no milk)
• Treat metritis if foul discharge

WEANING:

Weaning Age:
• Early weaning: 3-4 weeks (intensive)
• Standard weaning: 8 weeks
• Traditional: 10-12 weeks
• Welfare: Minimum 3-4 weeks

Weaning Process:
• Remove piglets, leave sow in pen
• Reduce stress on piglets
• Group by size
• Continue creep feed
• Reduce sow feed for 2-3 days (dries milk)

Post-Weaning:
• Sow returns to heat 3-7 days
• Rebreed immediately
• Target: 2.2-2.4 litters per sow per year
• Record weaning weights

RECORD KEEPING:

Breeding Records:
• Heat dates
• Breeding dates
• Boar used
• Expected farrowing date
• Actual farrowing date

Farrowing Records:
• Number born alive/dead
• Birth weights
• Piglet identification
• Abnormalities
• Piglets weaned
• Weaning weights

Sow Performance:
• Litters per sow per year
• Average litter size
• Piglet mortality rate
• Weaning weights
• Culling decisions based on performance

Boar Records:
• Services per week/month
• Conception rates
• Litter sizes from boar
• Health and libido
• Replacement planning',
    'start_time' => 36,
    'end_time' => 72,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1598124146163-42d52170b23e?w=800',
    'order' => 10,
    'is_active' => 1
]);

echo "✓ Pig Production Enterprise Created (ID: {$enterprise->id})" . PHP_EOL;
echo "✓ All 10 Protocols Created Successfully" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "✅ PIG (SWINE) PRODUCTION COMPLETE!" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "Total Protocols: 10" . PHP_EOL . PHP_EOL;
echo "GROWTH STAGES:" . PHP_EOL;
echo "1. Piglet Stage (0-3 months)" . PHP_EOL;
echo "2. Weaner Stage (3-4 months)" . PHP_EOL;
echo "3. Grower Stage (5-9 months)" . PHP_EOL;
echo "4. Gilt/Boar Development (9-15 months)" . PHP_EOL;
echo "5. Sow/Boar Production (15+ months)" . PHP_EOL . PHP_EOL;
echo "MANAGEMENT PROTOCOLS:" . PHP_EOL;
echo "6. Disease Management (ASF, CSF, FMD, etc.)" . PHP_EOL;
echo "7. Parasite Control (Internal & External)" . PHP_EOL;
echo "8. Housing & Infrastructure" . PHP_EOL;
echo "9. Nutrition & Feed Management" . PHP_EOL;
echo "10. Breeding & Reproductive Management" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
