<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;

// Create APFS Training Enterprise
$enterprise = Enterprise::create([
    'name' => 'Agro Pastoral Field School (APFS) Training Program',
    'description' => 'Comprehensive season-long training curriculum for agro-pastoral farmers covering community organization, livestock management, sustainable agriculture practices, and farmer empowerment through participatory learning and practical field activities.',
    'type' => 'livestock',
    'duration' => 12,
    'photo' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800',
    'is_active' => 1
]);

$protocols = [];

// Protocol 1: Community Mobilization & APFS Foundation (Sessions 1-6)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Community Mobilization & APFS Foundation (Sessions 1-6)',
    'activity_description' => 'Initial phase establishing the Agro Pastoral Field School through stakeholder engagement, community sensitization, and group formation. This foundation phase creates awareness, identifies participants, and establishes group structures for effective participatory learning.

OBJECTIVES:
• Create awareness on APFS approach among sub-county and parish leadership
• Mobilize community for sensitization and awareness meetings
• Identify interested and potential APFS participants
• Form functional farmer learning groups with common interests
• Establish group structure, leadership, and operational guidelines
• Initiate savings mechanisms and group registration

SESSION 1: MEETING SUB-COUNTY LEADERSHIP
Content: Methodology of APFS implementation, stakeholder identification, partnership development
Activities: Introduction, stakeholder identification, partner mapping
Materials: Introductory letter, APFS brochures

SESSION 2: MEETING PARISH LEADERSHIP
Content: Overview of APFS methodology, leader and community roles in APFS approach
Activities: Briefing parish leadership on APFS, making program for community awareness meeting
Materials: APFS brochures

SESSION 3: COMMUNITY SENSITIZATION
Content: Overview of APFS methodology, level of community involvement
Activities: Holding awareness meeting, fixing date for follow-up meeting
Materials: Stationery

SESSION 4: FOLLOW-UP MEETING
Content: What is APFS approach, basic concepts, requirements of an APFS
Activities: Enrollment of interested participants, comparison with other approaches, fixing date for group formation
Materials: Stationery

SESSION 5: GROUP FORMATION
Content: Criteria for APFS membership, mainstreaming gender, group constitution and norms, leadership structure
Activities: Formation of group name and slogan, developing group norms, formation of mini-groups (host teams), initiate developing group bylaws and constitution
Materials: Sample APFS constitution/guidelines

SESSION 6: GROUP FORMALIZATION
Content: Leadership structure, group dynamics, record keeping, savings mechanism, group registration, joint group bank accounts
Activities: Election of leaders with stipulation of roles and responsibilities, mobilization of funds from members for registration and account opening
Materials: List of requirements for registration and opening group bank accounts

PARTICIPATORY LEARNING PRINCIPLES:
• Farmers learn by doing through hands-on field activities
• Knowledge sharing among farmers (farmer-to-farmer learning)
• Group decision-making and problem-solving approaches
• Gender mainstreaming ensuring equal participation
• Community ownership of the learning process
• Integration of indigenous and scientific knowledge

GROUP STRUCTURE ELEMENTS:
• Group size: 25-30 members optimal for active participation
• Leadership positions: Chairperson, Secretary, Treasurer
• Host teams: Mini-groups of 3-5 members rotating field hosting duties
• Savings mechanism: Weekly/monthly contributions for group sustainability
• Meeting schedule: Regular weekly sessions throughout the season
• Group bylaws: Rules governing attendance, participation, contributions',
    'start_time' => 0,
    'end_time' => 8,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=800',
    'order' => 1,
    'is_active' => 1
]);

// Protocol 2: Enterprise Selection & Business Planning (Sessions 7-8)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Enterprise Selection & Action Planning (Sessions 7-8)',
    'activity_description' => 'Strategic planning phase where farmers analyze their farming enterprises as businesses, identify problems, and develop practical action plans. This module applies business principles to farming and uses participatory tools for problem analysis and solution development.

SESSION 7: FARMING AS A BUSINESS
Objective: Orient farmers to appreciate their farming enterprises as business entities
Content: Enterprise selection, profitability analysis of common crops grown in the community
Activities: Establish profitability of common crops, cost-benefit analysis, market analysis
Materials: Sample business planning tools required
Key Concepts:
• Farming as an income-generating enterprise
• Input costs versus output value
• Market-oriented production
• Record keeping for profitability tracking
• Value addition opportunities
• Risk management in farming business

SESSION 8: DEVELOPMENT OF GROUP ACTION PLAN
Objective: Develop comprehensive group action plan
Content: Problem identification and analysis, problem-solution analysis, solution assessment
Activities: Enterprise selection, problem tree analysis, solution tree development, prioritization
Materials: Sample tools required (flip charts, markers, cards)

PROBLEM ANALYSIS TOOLS:
• Problem tree: Identifying root causes, core problems, and effects
• Solution tree: Converting problems into actionable solutions
• Ranking and prioritization: Selecting feasible solutions
• Resource mapping: Identifying available local resources
• Opportunity identification: Market and value chain analysis

ACTION PLAN COMPONENTS:
• Selected enterprise(s) for group focus
• Identified problems and their root causes
• Prioritized solutions with timelines
• Resource requirements (inputs, labor, capital)
• Responsibility assignment to group members
• Monitoring and evaluation indicators
• Expected outcomes and targets

ENTERPRISE SELECTION CRITERIA:
• Market demand and accessibility
• Available resources (land, water, inputs)
• Technical knowledge and skills available
• Capital requirements and group capacity
• Risk level and mitigation measures
• Labor requirements and availability
• Expected returns and profitability
• Group member interest and commitment',
    'start_time' => 8,
    'end_time' => 12,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800',
    'order' => 2,
    'is_active' => 1
]);

// Protocol 3: Livestock Cycle & Health Management (Sessions 9-11)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Livestock Production Cycle & Health Management (Sessions 9-11)',
    'activity_description' => 'Comprehensive livestock management covering production cycles, animal selection, health indicators, and housing requirements. Focuses on goats as the primary enterprise with practical demonstrations and observations.

SESSION 9: DEVELOPMENT OF LEARNING PROGRAM
Objective: Develop schedule of learning activities tailored to enterprise production cycle
Content: Livestock cycle (particularly goats)
Activities: Developing livestock cycle calendar for selected enterprise
Materials: Study materials for goat lifecycle

SESSION 10: AGREEING ON STUDY ANIMALS
Objective: Select 2-3 animals for group study purposes
Content: Indicators of healthy and sick animals, housing conditions
Activities: Observation of animals, assessment of health conditions
Materials: Thermometer for temperature monitoring

SESSION 11: UNDERSTANDING HERD HEALTH
Objective: Appreciate key management practices in animal health
Content: Concept of healthy animals, management of kids and goats, housing requirements
Activities: Different housing designs, isolation of sick kids, setting and constructing goat houses suitable for pregnant and birthing does
Materials: Timber and other building materials

ANIMAL SELECTION CRITERIA:
• Body condition scoring (1-5 scale)
• Physical appearance and conformation
• Age determination techniques
• Breed characteristics
• Behavioral indicators of health
• Reproductive history and performance
• Absence of physical deformities
• Appropriate size for purpose

HEALTH INDICATORS:
• Normal temperature: 38.5-40°C (goats)
• Respiratory rate: 12-20 breaths per minute
• Heart rate: 70-90 beats per minute
• Bright, alert eyes with no discharge
• Smooth, shiny coat without patches
• Good appetite and rumination
• Normal fecal consistency
• Active movement and posture

HOUSING REQUIREMENTS:
• Space allocation: 1.5-2 square meters per adult goat
• Ventilation: Adequate airflow preventing drafts
• Flooring: Raised slatted floors for hygiene
• Roof height: 2-2.5 meters for air circulation
• Separate pens: Kids, pregnant does, breeding bucks
• Feeding and watering facilities
• Easy cleaning and waste removal
• Protection from predators and extreme weather

DISEASE PREVENTION PRACTICES:
• Regular observation and health monitoring
• Isolation facilities for sick animals
• Quarantine for new animals (2-3 weeks)
• Vaccination schedule adherence
• Proper nutrition for immunity
• Clean water provision
• Parasite control program
• Biosecurity measures',
    'start_time' => 12,
    'end_time' => 16,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=800',
    'order' => 3,
    'is_active' => 1
]);

// Protocol 4: Ecosystem Analysis & PESA (Sessions 12-14)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Ecosystem Analysis & PESA Methodology (Sessions 12-14)',
    'activity_description' => 'Ecological understanding and Participatory Ecosystem Analysis (PESA) training enabling farmers to understand relationships in their farming ecosystem and make informed management decisions based on field observations.

SESSION 12: INTRODUCTION TO ECOLOGICAL RELATIONSHIPS
Objective: Enable farmers appreciate interaction between crops and organisms in the ecosystem
Content: Ecosystem (ecological relationships), life cycle, food chain, food web
Activities: Constructing simple food chain and food web diagrams
Materials: Roll of knitting thread, Manila cards for illustrations

ECOLOGICAL CONCEPTS:
• Ecosystem: Community of living organisms and their environment
• Food chain: Linear sequence of organisms eating one another
• Food web: Complex interconnected food chains
• Beneficial organisms: Pollinators, predators of pests, decomposers
• Pest organisms: Insects, diseases, weeds affecting production
• Natural balance: Ecosystem self-regulation mechanisms
• Biodiversity importance in farm sustainability

SESSION 13: INTRODUCTION TO PESA
Objective: Enable farmers carryout Agro Ecosystem Analysis
Content: 4-stage process of PESA, developing parameters to observe, PESA data sheet, PESA presentation format
Activities: Demonstrate entire PESA process with all stages
Materials: Flip charts and markers, note books, crayons, pencils

PESA FOUR-STAGE PROCESS:
1. OBSERVATION: Groups visit study plots to observe and collect data
   - Crop growth stages and conditions
   - Pest and beneficial insect populations
   - Disease incidence and severity
   - Weather conditions
   - Soil moisture and conditions

2. ANALYSIS: Groups analyze collected information
   - Drawing observations on chart paper
   - Counting and categorizing organisms
   - Identifying problems and their causes
   - Discussing pest-predator relationships

3. PRESENTATION: Groups present findings to whole class
   - Visual presentations using drawings
   - Explanation of observations
   - Sharing insights and discoveries
   - Questions and clarifications

4. DECISION-MAKING: Group collectively decides management actions
   - Based on economic thresholds
   - Considering natural pest control
   - Timing of interventions
   - Integrated management approaches

SESSION 14: CONDUCTING PESA
Objective: Enable farmers carry out critical observations
Content: What is a pest and natural enemy, insect zoo creation
Activities: Collection of insect samples, characterizing insects as pests or beneficial, making insect zoos and boxes
Materials: Manila cards, rulers, collection jars/bottles

PESA OBSERVATION PARAMETERS:
• Plant growth stage and vigor
• Number and type of pests per plant
• Number and type of beneficial insects
• Disease symptoms and severity
• Weed pressure and competition
• Weather conditions (temperature, rainfall)
• Soil conditions (moisture, structure)
• Management practices applied

INSECT CLASSIFICATION:
PESTS: Cause economic damage to crops/livestock
- Chewing insects (caterpillars, beetles)
- Sucking insects (aphids, whiteflies)
- Boring insects (stem borers)
- Vectors (disease transmitters)

BENEFICIAL INSECTS: Support production
- Predators (ladybirds, lacewings, spiders)
- Parasitoids (wasps that parasitize pests)
- Pollinators (bees, butterflies)
- Decomposers (breaking down organic matter)

ECONOMIC THRESHOLD CONCEPT:
• Not all pests require immediate control
• Natural enemies often control pest populations
• Intervention justified when pest population threatens economic loss
• Premature intervention disrupts natural balance
• Regular monitoring enables timely decisions',
    'start_time' => 16,
    'end_time' => 24,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=800',
    'order' => 4,
    'is_active' => 1
]);

// Protocol 5: Livestock Disease & Parasite Management (Sessions 15-16, 25, 29-30)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Livestock Health: Disease & Parasite Management (Sessions 15, 16, 25, 29, 30)',
    'activity_description' => 'Comprehensive training on identification and management of common livestock parasites and diseases with emphasis on Integrated Pest and Parasite Management (IPPE) for goats and other livestock species.

SESSION 15: PARASITE & DISEASE IDENTIFICATION
Objective: Acquaint farmers with common parasites and diseases, introduce IPPE practices
Content: Common livestock parasites and diseases and their effects, management of parasites and diseases, application of IPPE on goat production
Activities: Identifying common parasites and diseases of goats, making insect zoos and boxes, characterizing insects to parasites and natural enemies
Materials: Samples of different parasite types (endo and ecto parasites), staples & stapler

SESSION 16: KID MANAGEMENT
Objective: Enable farmers appreciate different management practices in kid management
Content: Effects of orf in kids, diarrhoea in kids, methods of disease control
Activities: Identification of common parasites and diseases in kids, trapping various parasites affecting kids and their mothers
Materials: Samples of parasites

SESSION 25: ANIMAL HUSBANDRY PRACTICES
Objective: Ensure proper growth and development
Content: Parasite and disease identification, parasite and disease control
Activities: Early detection through observation of symptoms
Materials: Note books, PESA materials

SESSION 29: WORMS IN ANIMALS
Objective: Enable farmers identify worms and develop control measures
Content: Signs and symptoms of worms, worm lifecycle, prevention strategies
Activities: Deworming demonstration, observation of possible signs and symptoms in study animals
Materials: Stationery, pictures of worm-infested animals

SESSION 30: TICKS IN ANIMALS
Objective: Ensure proper control of ticks
Content: Common sites of tick infestation, methods of control, importance of routine spraying
Activities: Observation of tick infestation sites, tick identification
Materials: Samples of common livestock drugs for tick control

COMMON LIVESTOCK PARASITES:

INTERNAL PARASITES (ENDOPARASITES):
1. Roundworms (Nematodes)
   - Haemonchus (Barber pole worm): Bloodsucking stomach worm
   - Trichostrongylus: Small intestinal worm
   - Oesophagostomum: Nodular worm
   Signs: Weight loss, anemia, diarrhea, rough coat
   Control: Strategic deworming, pasture rotation

2. Tapeworms (Cestodes)
   - Moniezia: Common in young animals
   Signs: Poor growth, pot-bellied appearance
   Control: Appropriate dewormers, hygiene

3. Flukes (Trematodes)
   - Liver flukes in wet areas
   Signs: Bottle jaw, weight loss, poor production
   Control: Drainage, molluscicides, deworming

4. Coccidia (Protozoa)
   - Eimeria species causing diarrhea
   Signs: Bloody diarrhea, dehydration, kid mortality
   Control: Clean housing, anticoccidials

EXTERNAL PARASITES (ECTOPARASITES):
1. Ticks
   - Attach to skin, suck blood, transmit diseases
   Common sites: Ears, udder, between legs, anal area
   Control: Regular dipping/spraying, hand picking

2. Lice
   - Chewing and sucking types
   Signs: Itching, hair loss, poor condition
   Control: Insecticidal dusts, sprays

3. Mites (Mange)
   - Burrow in skin causing intense itching
   Signs: Hair loss, scabs, skin thickening
   Control: Acaricides, isolation

4. Flies
   - Cause irritation, transmit diseases
   Control: Sanitation, fly traps, sprays

COMMON DISEASES:

BACTERIAL DISEASES:
• Pneumonia: Coughing, nasal discharge, difficulty breathing
• Foot rot: Lameness, foul smell from feet
• Mastitis: Swollen udder, abnormal milk
• Orf (Contagious Ecthyma): Scabs around mouth in kids

VIRAL DISEASES:
• Peste des Petits Ruminants (PPR): High fever, diarrhea, respiratory signs
• Goat pox: Skin lesions, fever

PROTOZOAL DISEASES:
• East Coast Fever (cattle): Transmitted by ticks
• Trypanosomiasis: Transmitted by tsetse flies

METABOLIC DISEASES:
• Pregnancy toxemia: Late pregnancy in overburdened does
• Bloat: Gas accumulation in rumen

INTEGRATED PEST & PARASITE MANAGEMENT (IPPE):

PREVENTIVE MEASURES:
• Good nutrition for strong immunity
• Clean, dry housing
• Proper stocking density
• Quarantine new animals
• Regular health monitoring
• Vaccination programs
• Strategic deworming
• Pasture rotation

CONTROL STRATEGIES:
• Early detection through daily observation
• Isolation of sick animals
• Appropriate treatment with correct drugs
• Follow withdrawal periods before consumption
• Proper drug storage and usage
• Combination of chemical and non-chemical methods
• Record keeping of treatments

ORGANIC/NON-CHEMICAL CONTROL:
• Rotational grazing breaks parasite cycles
• Mixed species grazing (cattle and goats)
• Use of medicinal plants (neem, papaya)
• Improving animal nutrition
• Biological control (dung beetles for fly control)
• Physical removal (hand picking ticks)',
    'start_time' => 24,
    'end_time' => 40,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800',
    'order' => 5,
    'is_active' => 1
]);

// Protocol 6: Safe Agricultural Practices & Chemical Use (Session 1/6)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Safe Use of Agro-chemicals (Session 1/6)',
    'activity_description' => 'Critical training on safe handling, application, and storage of agricultural chemicals including pesticides, herbicides, and veterinary drugs to protect farmer health, animals, and environment.

OBJECTIVE:
Enable farmers understand safe use of livestock drugs and agricultural chemicals

CONTENT:
• Safety precautions before, during, and after chemical application
• Proper mixing and calibration of chemicals
• Protective equipment requirements
• Acaricide mixing and application techniques
• Chemical storage and disposal

ACTIVITIES:
• Identifying common acaricides and pesticides in the market
• Demonstration of calibration, direction of spraying, and handling of spray pumps
• Practical mixing exercises
• Proper disposal methods for containers

MATERIALS:
• Common acaricides and pesticide samples
• Bucket pump or knapsack sprayer
• Water for demonstrations
• Protective equipment examples

CHEMICAL SAFETY PRINCIPLES:

BEFORE APPLICATION:
• Read and understand product label completely
• Check expiry dates - never use expired chemicals
• Select appropriate chemical for target pest/disease
• Calculate correct dosage based on area/animal weight
• Prepare only required amount
• Ensure proper protective equipment available
• Check weather - avoid windy or rainy conditions
• Inform household members of planned spraying
• Remove children and animals from area

DURING APPLICATION:
• Wear protective clothing: Long sleeves, trousers, boots, gloves, face mask
• Do not eat, drink, or smoke while handling chemicals
• Spray in direction away from body
• Maintain recommended spray distance
• Cover entire target area systematically
• Avoid spray drift to non-target areas
• Watch for signs of poisoning (dizziness, nausea)
• Stop immediately if feeling unwell

AFTER APPLICATION:
• Wash spray equipment thoroughly away from water sources
• Wash hands and exposed skin with soap and water
• Wash protective clothing separately from household clothes
• Shower/bathe before touching food or family
• Store remaining chemical properly
• Dispose of empty containers safely - puncture and bury or return to supplier
• Observe withdrawal period before consuming animal products
• Keep treated areas isolated as per label instructions

PROTECTIVE EQUIPMENT:
• Overalls or long-sleeved shirt and long trousers
• Rubber boots (gumboots)
• Chemical-resistant gloves
• Face shield or goggles
• Respirator or face mask for toxic chemicals
• Hat or head covering
• All equipment should be washable and in good condition

CHEMICAL STORAGE:
• Store in original labeled containers
• Keep in locked, well-ventilated storage area
• Away from food, feed, and water sources
• Out of reach of children and animals
• Away from direct sunlight and heat
• On shelves, not on floor
• Separate from fertilizers and seeds
• Maintain inventory of chemicals stored

SPRAY EQUIPMENT MAINTENANCE:
• Calibrate sprayers regularly for accurate application
• Check for leaks before use
• Clean nozzles to prevent blockage
• Rinse equipment thoroughly after each use
• Store clean and dry
• Replace worn parts promptly
• Maintain at recommended pressure

WITHDRAWAL PERIODS:
• Time between treatment and safe consumption
• Meat: Typically 14-28 days depending on drug
• Milk: Usually 72 hours to 7 days
• Eggs: Generally 7-14 days
• Always check product label for specific periods
• Mark treated animals for identification
• Record treatment dates

POISONING FIRST AID:
• Remove person from contaminated area immediately
• Remove contaminated clothing
• Wash affected skin with soap and water for 15 minutes
• If swallowed: Do NOT induce vomiting unless advised
• Seek medical attention immediately
• Bring chemical container/label to hospital
• Keep person calm and monitor breathing

ENVIRONMENTAL PROTECTION:
• Never spray near water sources
• Avoid application during flowering (protects pollinators)
• Dispose of wash water in waste pit, not water bodies
• Bury or return empty containers - never reuse for other purposes
• Use recommended doses - excess harms environment
• Consider non-chemical alternatives when possible
• Protect beneficial insects and organisms',
    'start_time' => 8,
    'end_time' => 12,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800',
    'order' => 6,
    'is_active' => 1
]);

// Protocol 7: Nutrition & Household Wellbeing (Sessions 20-22)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Nutrition & Household Wellbeing (Sessions 20-22)',
    'activity_description' => 'Family nutrition training emphasizing balanced diet, proper food preparation, and special nutritional needs for children and mothers to improve household health and food security.

SESSION 20-21 (MAB 7-8): BASIC PRINCIPLES OF NUTRITION
Objective: Create awareness on importance and basic principles of nutrition in a household
Content: Importance of vitamins, proteins and carbohydrates in diet, feeding for school-going children, feeding for breastfeeding mothers, feeding for the sick
Activities: Preparation of nutritious recipes using locally available foods
Materials: Samples of local foods, transport, cooking equipment

SESSION 22 (MAB 10): NUTRITION CONTINUATION
Follow-up session reinforcing nutrition principles and food preparation techniques

BASIC NUTRITION CONCEPTS:

MACRONUTRIENTS:
1. Carbohydrates (Energy Foods)
   - Sources: Maize, millet, sorghum, cassava, sweet potatoes, rice
   - Function: Provide energy for daily activities
   - Recommended: 50-60% of daily calories

2. Proteins (Body-Building Foods)
   - Sources: Beans, peas, groundnuts, meat, milk, eggs, fish
   - Function: Growth, repair tissues, build muscles
   - Recommended: 15-20% of daily calories
   - Special need: Children, pregnant/breastfeeding mothers

3. Fats and Oils (Energy Storage)
   - Sources: Cooking oil, groundnuts, avocado, animal fat
   - Function: Concentrated energy, vitamin absorption
   - Recommended: 20-30% of daily calories

MICRONUTRIENTS:
1. Vitamins
   - Vitamin A: Carrots, mangoes, pumpkins, dark green vegetables - for eyes and immunity
   - Vitamin C: Citrus fruits, tomatoes, guavas - for immunity and wound healing
   - Vitamin D: Sunlight, eggs, fish - for bones
   - B Vitamins: Whole grains, legumes, meat - for energy and nerves

2. Minerals
   - Iron: Red meat, beans, dark green vegetables - prevents anemia
   - Calcium: Milk, small fish, vegetables - for strong bones and teeth
   - Iodine: Iodized salt - prevents goiter
   - Zinc: Meat, legumes - for growth and immunity

BALANCED DIET:
• Combination of all food groups in proper proportions
• Variety: Different foods provide different nutrients
• Color diversity on plate indicates nutrient diversity
• Traditional food combinations often nutritionally balanced

SPECIAL NUTRITIONAL NEEDS:

PREGNANT WOMEN:
• Extra energy: Additional 300-500 calories daily
• Increased protein for fetal growth
• Iron supplementation to prevent anemia
• Folic acid for neural tube development
• Calcium for bone development
• Adequate fluids
• Small frequent meals if nausea present

BREASTFEEDING MOTHERS:
• Extra energy: Additional 500 calories daily
• High protein intake
• Adequate fluids: 8-10 glasses water daily
• Calcium-rich foods
• Variety of fruits and vegetables
• Avoid alcohol and limit caffeine
• Nutritious diet improves milk quality and quantity

CHILDREN (0-2 YEARS):
• Exclusive breastfeeding: 0-6 months
• Introduction of complementary foods: 6 months
• Continued breastfeeding with foods: Up to 2 years or beyond
• Frequent feeding: 5-6 times daily
• Mashed/soft consistency
• Protein-rich porridges with groundnut paste, milk
• Egg, mashed beans, fish, liver for iron

SCHOOL-AGE CHILDREN:
• Regular meals: 3 main meals plus 2 snacks
• Protein for growth
• Iron for concentration and learning
• Adequate energy for active play
• Healthy breakfast before school
• Avoid too many sweets and sodas

SICK FAMILY MEMBERS:
• Small frequent meals
• Easy to digest foods
• High protein for recovery
• Plenty of fluids to prevent dehydration
• Continue feeding during illness
• Nutrient-dense foods even in small amounts

FOOD PREPARATION FOR NUTRITION:

COOKING METHODS:
• Steam or boil vegetables lightly to retain vitamins
• Use vegetable cooking water in soups/porridge
• Soak and sprout legumes to improve digestibility
• Combine foods: Beans with maize improves protein quality
• Add small amounts of fat for vitamin absorption
• Ferment foods (yogurt, traditional fermented drinks)

FOOD PRESERVATION:
• Sun drying: Vegetables, fruits, fish, meat
• Smoking: Fish and meat
• Proper storage to prevent nutrient loss
• Use preserved foods during lean seasons

KITCHEN GARDEN:
• Grow vegetables near homestead
• Year-round access to fresh vegetables
• Leafy greens: Amaranth, kale, spinach
• Vitamin A vegetables: Pumpkin, carrots
• Tomatoes, onions, egg plants
• Fruit trees: Mango, papaya, orange

FOOD SAFETY:
• Wash hands before food preparation
• Wash fruits and vegetables
• Cook food thoroughly especially meat
• Store food covered and away from flies
• Keep raw and cooked foods separate
• Reheat food thoroughly before eating
• Use safe clean water for cooking',
    'start_time' => 28,
    'end_time' => 40,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800',
    'order' => 7,
    'is_active' => 1
]);

// Protocol 8: Water, Sanitation & Rangeland Management (Sessions 24, 26, 27, 28)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Water, Sanitation, Rangeland & Animal Welfare (Sessions 24, 26, 27, 28)',
    'activity_description' => 'Integrated module covering household hygiene, water management, sustainable rangeland practices, HIV/AIDS awareness, and animal feeding management for holistic community wellbeing.

SESSION 24 (MAB 12): WATER AND SANITATION
Objective: Ensure good sanitation in APFS community
Content: Proper waste disposal, protection of water sources, household hygiene, solid waste management
Activities: Construction of drying racks, bath shelters, pit latrines and rubbish pits, cleaning and protecting water points
Materials: Stationery, transport, farm tools

SESSION 26 (MAB 14): PRINCIPLES OF RANGELAND MANAGEMENT
Objective: Create awareness on need for sustainable rangeland management
Content: Afforestation-fodder trees, agroforestry-wood lots, protection of wetlands and water sources, energy structures
Activities: Guidance by environment subject matter specialist
Materials: Stationery, facilitation allowance, transport

SESSION 27 (MAB 15): HIV/AIDS AWARENESS
Objective: Create awareness on effect of HIV/AIDS on agricultural production and mitigation
Content: Impact of HIV/AIDS on farm production, voluntary counselling and testing
Activities: Brainstorming on various experiences
Materials: Stationery, transport, facilitation allowance

SESSION 28 (MAB 16): WEANING STAGE
Objective: Observe proper performance of the kid
Content: Proper feeds for the weaners
Activities: Demonstration on how to make weaner feeds
Materials: Different feed materials, containers

WATER AND SANITATION:

SAFE WATER SOURCES:
• Protected springs with proper drainage
• Boreholes with sealed wellheads
• Rainwater harvesting tanks
• Piped water systems
• Shallow wells with covers

WATER PROTECTION:
• Fence water points to keep animals away
• Construct drainage channels
• Plant grass around water sources
• Regular cleaning of water points
• Designate animal watering points downstream
• Never wash clothes/utensils at water source
• Avoid defecation near water sources

HOUSEHOLD WATER TREATMENT:
• Boiling: Most effective, boil for 1 minute
• Chlorination: 2-4 drops bleach per liter
• Solar disinfection: Clear bottle in sun 6 hours
• Water filters if available
• Store treated water in clean covered containers

SANITATION FACILITIES:
1. Pit Latrines
   - Minimum 6 meters from water source
   - Minimum 30 meters from house
   - Deep pit (minimum 3 meters)
   - Covered slab
   - Ventilation pipe with fly screen
   - Regular cleaning with ash/soap

2. Bath Shelters
   - Privacy for bathing
   - Drainage away from water source
   - Regular cleaning

3. Rubbish Pits
   - For biodegradable waste
   - Covered when full
   - Located away from house
   - Can become compost

4. Drying Racks
   - For dishes and clothes
   - Raised off ground
   - Sun exposure
   - Away from animal areas

HOUSEHOLD HYGIENE:
• Handwashing with soap: Before eating, after toilet, before food preparation
• Food hygiene: Cover food, keep kitchen clean
• Personal hygiene: Regular bathing, clean clothes
• Keep compound clean and swept
• Proper waste disposal
• Control flies and rodents

RANGELAND MANAGEMENT:

SUSTAINABLE GRAZING:
• Rotational grazing prevents overgrazing
• Allow pasture recovery periods
• Controlled stocking rates
• Seasonal movement patterns
• Reserve areas for dry season

PASTURE IMPROVEMENT:
• Plant improved fodder grasses
• Establish fodder banks
• Preserve hay/silage for dry season
• Control bushes and weeds in pasture
• Fertilize pastures with manure

AGROFORESTRY FOR FODDER:
• Fodder trees: Calliandra, Leucaena, Sesbania
• Multipurpose trees: Food, fodder, firewood, timber
• Plant along boundaries and contours
• Cut-and-carry feeding system
• Nitrogen fixation improves soil

WETLAND PROTECTION:
• No cultivation in wetlands
• Buffer zones around wetlands
• Controlled grazing
• Wetlands regulate water flow
• Important for dry season water

ENERGY SOURCES:
• Fuel-efficient stoves reduce firewood use
• Biogas from animal manure
• Solar energy where feasible
• Woodlots for sustainable firewood
• Crop residues for fuel

HIV/AIDS AWARENESS:

IMPACT ON AGRICULTURE:
• Loss of labor due to illness
• Loss of farming knowledge from deaths
• Children missing school to care for sick
• Sale of assets for medical care
• Food insecurity in affected households
• Reduced agricultural productivity

PREVENTION:
• ABC approach: Abstinence, Be faithful, Condoms
• Voluntary counseling and testing (VCT)
• Know your status
• Avoid sharing sharp objects
• Safe practices for caregivers

SUPPORT MECHANISMS:
• Community support for affected families
• Labor sharing arrangements
• Simplified farming techniques for sick members
• Kitchen gardens for nutrition
• Group support within APFS

WEANING MANAGEMENT:

WEANING AGE:
• Goats: 3-4 months
• Cattle: 3-6 months depending on system
• Weight-based: When kids reach 2.5-3 times birth weight

WEANING FEEDS:
• Good quality hay or fresh forage
• Concentrates: Maize bran, wheat bran, molasses
• Gradual introduction of feeds before weaning
• Protein-rich supplements: Sunflower cake, cotton seed cake
• Mineral supplementation
• Clean fresh water ad libitum

WEANING STRESS REDUCTION:
• Gradual separation from mother
• Maintain familiar environment
• Keep with other weaners for companionship
• Avoid other stressors (castration, dehorning) during weaning
• Monitor closely for health issues
• Maintain good nutrition',
    'start_time' => 32,
    'end_time' => 48,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800',
    'order' => 8,
    'is_active' => 1
]);

// Protocol 9: Reproductive Management (Sessions 31-40)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Reproductive Management & Kidding (Sessions 31-40)',
    'activity_description' => 'Comprehensive reproductive management covering heat detection, breeding, pregnancy management, and delivery preparation to ensure successful reproduction and kid survival.

SESSION 31 (MAB 26): SIGNS OF HEAT IN GOATS
Objective: Enable farmers identify signs of heat
Content: Heat signs in goats
Activities: Observation of female goats signs
Materials: Pictoria materials

SESSION 32 (MAB 27): STEAMING IN ANIMALS
Objective: Enable farmers carryout proper and timely steaming of goats
Content: Feed types needed for steaming in goat management
Activities: Observation continued
Materials: History of the goats

SESSION 33 (MAB 28): MANAGEMENT OF PREGNANT GOATS
Objective: Enable farmers appreciate key challenges during pregnancy
Content: Pregnancy indicators
Activities: Observation for pregnancy signs
Materials: Pictoria materials

SESSION 34: FIRST TRIMESTER ISSUES
Objective: Enable herders understand common issues during first trimester
Content: Weight gain, estimating weight
Activities: Restraining to take weight
Materials: All the records

SESSION 35: FEEDS DURING FIRST TRIMESTER
Objective: Enable farmers understand suitable feeds during first trimester
Content: Different feeds and preparation, supplementary feeds
Activities: Feed materials gathering
Materials: Various feed samples

SESSION 36: PASTURE PRODUCTION
Objective: Enable farmers raise suitable pasture for pregnant animals
Content: Samples of good pasture, hay and silage making
Activities: Look for materials of good pasture establishment
Materials: Hay bales

SESSION 37: ZOONOTIC DISEASES
Objective: Enable herders understand common zoonotic diseases
Content: Outline of common zoonotic diseases
Activities: Identification of affected animals
Materials: PESA materials

SESSION 38: SECOND TRIMESTER ISSUES
Objective: Enable herders understand common issues during second trimester
Content: Weight gain, estimating weight, heart beat checks
Activities: Restraining to take weight, monitoring vital signs
Materials: Stethoscope, girth tape

SESSION 39: THIRD TRIMESTER
Objective: Enable herders understand common issues during third trimester
Content: Weight gain, estimating weight, heart beat checks, udder development
Activities: Restraining to take weight, statoscope examination
Materials: Stethoscope, girth tape

SESSION 40: DELIVERY PREPARATION
Objective: Enable herders prepare in advance necessary things at delivery
Content: Housing, umbilical treatment, colostrum use
Activities: Treatment of affected animals
Materials: Tincture of iodine

REPRODUCTIVE MANAGEMENT:

HEAT DETECTION (ESTRUS):
Signs of Heat in Does:
• Restlessness and frequent vocalization (calling)
• Tail wagging rapidly
• Decreased appetite
• Mounting other females
• Swollen, reddish vulva with clear mucus discharge
• Seeking attention from the buck
• Standing still when mounted (standing heat)
• Frequent urination

Heat Cycle: Every 18-21 days
Heat Duration: 24-48 hours
Best Breeding Time: 12-24 hours after heat detected

BREEDING MANAGEMENT:
• Buck-to-doe ratio: 1 buck for 25-30 does
• Controlled mating preferred for record keeping
• Allow 2-3 services during heat period
• Record breeding dates for pregnancy monitoring
• Rest buck between services
• Good buck nutrition important for fertility
• Annual buck replacement prevents inbreeding

PREGNANCY DIAGNOSIS:
Early Signs (3-4 weeks):
• Absence of return to heat
• Behavioral changes - calmer
• Increased appetite

Mid-Pregnancy (2-3 months):
• Abdominal enlargement on right side
• Weight gain
• Udder development begins

Late Pregnancy (4-5 months):
• Obvious abdominal distension
• Fetal movements visible
• Udder enlargement
• Relaxation of ligaments near tail

PREGNANCY MANAGEMENT:

FIRST TRIMESTER (0-50 days):
• Most critical period - risk of abortion
• Avoid stress, rough handling, transport
• Good quality pasture/hay
• Maintain body condition
• Avoid sudden feed changes
• Deworm if needed
• Monitor for signs of abortion

SECOND TRIMESTER (50-100 days):
• Rapid fetal growth
• Increase feed quantity gradually
• Provide concentrates: 200-300g daily
• Continue good quality forage
• Ensure adequate water
• Monitor body condition - neither too fat nor thin
• Exercise through moderate grazing/walking

THIRD TRIMESTER (100-150 days):
• Maximum fetal growth
• Concentrate feeds: 300-500g daily
• High-quality protein supplements
• Mineral supplementation (calcium, phosphorus)
• Reduce bulk feeds as fetus compresses rumen
• Frequent small meals
• Monitor udder development
• Watch for signs of pregnancy toxemia (twins, triplets)

NUTRITION DURING PREGNANCY:
• Energy requirements increase 50% in late pregnancy
• Protein needs increase for fetal growth
• Calcium crucial for skeletal development and milk production
• Avoid moldy feeds (can cause abortion)
• Salt/mineral licks accessible
• Clean fresh water always available

ZOONOTIC DISEASES (Animal-to-Human):
• Brucellosis: Causes abortion in animals, undulant fever in humans
• Anthrax: Fatal disease transmitted through handling infected animals
• Rabies: Transmitted through bites
• Rift Valley Fever: Mosquito-borne during floods
• Q Fever: Inhalation during kidding
• Ringworm: Fungal skin infection

Prevention:
• Wear gloves when handling birthing animals
• Wash hands after animal contact
• Isolate sick animals
• Vaccinate where applicable
• Proper disposal of aborted fetuses

KIDDING PREPARATION:

DELIVERY SIGNS:
• Restlessness, pawing ground
• Udder tight with milk
• Mucus discharge from vulva
• Isolation from herd
• Loss of appetite
• Straining and pushing

KIDDING PEN PREPARATION:
• Clean, dry, draft-free area
• Fresh bedding (straw or dry grass)
• Separate from main herd
• Good lighting for observation
• Kid warming area if needed

DELIVERY SUPPLIES:
• Clean towels or gunny sacks
• Tincture of iodine (7-10%) for navel
• Container for iodine dipping
• Lubricant (petroleum jelly or soap)
• Clean string for tying navel if bleeding
• Disinfectant
• Colostrum saver/bottle if needed

COLOSTRUM MANAGEMENT:
• First milk crucial for immunity
• Kid must receive within 1-2 hours of birth
• Amount: 50-100ml minimum in first 6 hours
• Continue 4-5 times daily first 3 days
• If doe dies: Use stored colostrum or cow colostrum
• Check kid nursing - assist if weak

POST-KIDDING CARE:
• Ensure placenta expelled within 6 hours
• Watch for retained placenta
• Check udder for mastitis
• Monitor doe eating and drinking
• Watch for prolapse or excessive bleeding
• Isolate doe and kids for bonding (2-3 days)
• Identify kids: Tags, tattoos, or records
• Castrate males not for breeding (3-4 weeks)
• Disbud kids if practiced (1-2 weeks)',
    'start_time' => 40,
    'end_time' => 48,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
    'order' => 9,
    'is_active' => 1
]);

// Protocol 10: Graduation & Sustainability (Sessions 29, 41-42)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Field Day, Graduation & Sustainability (Sessions 29, 41, 42)',
    'activity_description' => 'Culminating activities celebrating achievements, demonstrating knowledge gained, and planning for post-training sustainability through community awareness and continued farmer-led activities.

SESSION 29 (MAB 17): FIELD DAY
Objective: Increase community awareness of APFS approach
Content: Relating field activities and farmers knowledge attained to empowerment
Activities: Speeches, presentations by APFS members, role plays, sharing experiences, visiting study sites, demonstrations
Materials: Hand-outs, refreshments, tents
Purpose: Public showcase of learning and achievements to motivate other community members

SESSION 41: GRADUATION
Objective: Reorganize and express appreciation to the perseverance of farmers
Content: Testimonies from grandaunts, reviewing benefits and challenges in APFS implementation
Activities: Sharing experiences, role plays, coming up with recommendations for future APFS implementation, giving certificates to APFS members
Materials: T-shirts, caps, certificates, refreshments

SESSION 42: POST-GRADUATION ACTIVITY
Objective: Enable facilitator formally exit the direct running of APFS activities
Content: Sustainability of the APFS group and follow-up planning
Activities: Mapping out follow-up strategies, the facilitator formally exits the direct running of APFS
Materials: Action plan documents

FIELD DAY PLANNING:

OBJECTIVES:
• Showcase farmer achievements and learning
• Demonstrate improved practices to wider community
• Share experiences and challenges overcome
• Motivate non-members to adopt practices
• Network with other farmers and organizations
• Celebrate group accomplishments

ACTIVITIES:
1. Farm Tours
   - Visit demonstration plots
   - Observe livestock management practices
   - See improved housing and infrastructure
   - Compare before and after situations

2. Presentations
   - Farmer testimonials on benefits gained
   - Technical presentations by group members
   - Display of records and charts
   - PESA demonstrations

3. Demonstrations
   - Proper spraying techniques
   - Feed formulation and preparation
   - Disease identification
   - Delivery assistance techniques

4. Exhibitions
   - Display farm products (vegetables, eggs, milk)
   - Show farm tools and equipment
   - Present group records and savings books
   - Charts showing production increases

5. Cultural Activities
   - Drama/role plays on farming practices
   - Songs with agricultural messages
   - Traditional dances
   - Poetry about farming life

STAKEHOLDER PARTICIPATION:
• Local leaders: Sub-county, parish chiefs
• Extension workers and veterinary officers
• Input suppliers and buyers
• Financial institutions (banks, SACCOs)
• NGOs and development partners
• Media for wider publicity
• Neighboring farmer groups

GRADUATION CEREMONY:

ACHIEVEMENTS RECOGNITION:
Individual Recognition:
• Most improved farmer
• Best record keeper
• Most innovative farmer
• Best attendance
• Outstanding group leader

Group Achievements:
• Increased production (percentages)
• Improved food security
• Income generation realized
• Group savings accumulated
• New skills acquired
• Improved animal health
• Reduced crop losses

TESTIMONIES:
• Personal transformation stories
• Economic improvements achieved
• Knowledge gained and applied
• Challenges overcome
• Family and community impact
• Confidence and leadership developed

CERTIFICATION:
• Certificate of Completion for all participants
• Special awards for outstanding performance
• Group registration documents
• Photograph with certificate
• Media coverage of graduation

CHALLENGES DISCUSSED:
• Limited capital for inputs
• Market access difficulties
• Extreme weather impacts
• Disease outbreaks experienced
• Group conflicts and solutions
• Time management between APFS and other work

RECOMMENDATIONS FOR FUTURE:
• Continued technical backstopping
• Access to credit facilities
• Market linkages needed
• Input supply improvements
• Regular follow-up visits
• Networking with other groups
• Policy advocacy needs

POST-GRADUATION SUSTAINABILITY:

GROUP CONTINUITY:
• Regular meetings continue (monthly minimum)
• Rotation of leadership positions
• Internal training by experienced members
• Peer-to-peer support system
• Group bylaws enforced

FARMER-LED ACTIVITIES:
• Continue PESA sessions
• Group problem-solving
• Experience sharing visits
• Joint marketing initiatives
• Group procurement of inputs
• Savings and credit operations

INCOME-GENERATING ACTIVITIES:
• Bulk buying and selling
• Contract farming arrangements
• Value addition (milk processing, egg trays)
• Service provision (spraying, veterinary)
• Hiring out equipment
• Training other groups (income generation)

LINKAGES DEVELOPED:
• Extension service connections
• Input suppliers relationships
• Market buyers contacts
• Financial institutions
• Other farmer groups
• Research institutions
• Local government

KNOWLEDGE TRANSFER:
• Train family members in techniques learned
• Mentor new farmers in community
• Farmer-to-farmer extension
• Share planting materials
• Demonstrate practices to neighbors
• Participate in farmer field days

RECORD KEEPING:
• Continue production records
• Financial records maintained
• Meeting minutes documented
• Group activities logged
• Success stories documented for learning

FACILITATOR EXIT STRATEGY:
• Gradual reduction of facilitator presence
• Transfer responsibilities to group leaders
• Build capacity of internal trainers
• Establish self-help mechanisms
• Periodic follow-up visits (quarterly)
• Group able to access services independently
• Emergency contact with facilitator maintained

MONITORING AND EVALUATION:
• Group self-assessment quarterly
• Compare current to baseline situation
• Track adoption of practices
• Monitor group cohesion
• Financial audit annually
• Impact on household food security
• Impact on income levels

SCALING UP:
• Successful farmers become trainers
• Establish new APFS groups using same model
• Share learning materials with other communities
• Participate in exchange visits
• Document innovations for wider dissemination
• Advocate for supportive policies',
    'start_time' => 40,
    'end_time' => 48,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800',
    'order' => 10,
    'is_active' => 1
]);

echo "✓ Created APFS Training Enterprise (ID: {$enterprise->id})" . PHP_EOL;
echo "✓ Created All 10 Training Modules" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "✅ APFS TRAINING PROGRAM COMPLETE!" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "Enterprise ID: {$enterprise->id}" . PHP_EOL;
echo "Total Protocols: " . count($protocols) . PHP_EOL;
echo PHP_EOL;
echo "TRAINING MODULES:" . PHP_EOL;
echo "1. Community Mobilization (Sessions 1-6)" . PHP_EOL;
echo "2. Enterprise Selection & Planning (Sessions 7-8)" . PHP_EOL;
echo "3. Livestock Cycle & Health (Sessions 9-11)" . PHP_EOL;
echo "4. Ecosystem Analysis & PESA (Sessions 12-14)" . PHP_EOL;
echo "5. Disease & Parasite Management (Sessions 15-16, 25, 29-30)" . PHP_EOL;
echo "6. Safe Chemical Use (Session 1/6)" . PHP_EOL;
echo "7. Nutrition & Wellbeing (Sessions 20-22)" . PHP_EOL;
echo "8. Water, Sanitation & Rangeland (Sessions 24, 26-28)" . PHP_EOL;
echo "9. Reproductive Management (Sessions 31-40)" . PHP_EOL;
echo "10. Field Day & Graduation (Sessions 29, 41-42)" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
