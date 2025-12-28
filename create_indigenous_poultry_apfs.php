<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Enterprise;
use App\Models\ProductionProtocol;

$enterprise = Enterprise::create([
    'name' => 'Indigenous Poultry APFS Training Program',
    'description' => 'Season-long Agro-Pastoral Field School training for local chicken production covering community organization, indigenous breed management, low-cost housing, disease control, and sustainable backyard poultry enterprise development.',
    'type' => 'livestock',
    'duration' => 12,
    'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
    'is_active' => 1
]);

$protocols = [];

// Protocol 1: Community Mobilization & Group Formation (Sessions 1-6)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Community Mobilization & Group Formation (Sessions 1-6)',
    'activity_description' => 'Establishing the Indigenous Poultry FFS through stakeholder engagement, participant recruitment, and functional group formation with focus on local chicken production.

SESSION 1-2: LEADERSHIP ENGAGEMENT
• Meet sub-county and parish leadership for FFS support
• Present FFS methodology and expected benefits
• Identify stakeholders and development partners
• Clarify roles of local leaders and committees
• Materials: Introductory letters, FFS brochures

SESSION 3: COMMUNITY SENSITIZATION
• Conduct awareness meeting on indigenous poultry benefits
• Explain FFS methodology and farmer roles
• Highlight income opportunities from local chickens
• Address community commitment needed
• Fix enrollment date
• Materials: Stationery

SESSION 4: PARTICIPANT IDENTIFICATION
• Register interested poultry keepers
• Identify characteristics of good participants
• Compare FFS approach with other training methods
• Schedule group formation meeting
• Materials: Stationery

SESSION 5: GROUP FORMATION
• Form learning groups with shared interest in poultry
• Develop group name and slogan
• Establish membership criteria ensuring gender balance
• Create mini-teams for hosting field activities
• Initiate group constitution and norms
• Materials: Sample constitution/guidelines

SESSION 6: GROUP FORMALIZATION
• Elect group leaders with defined roles
• Establish record-keeping systems
• Set up group savings mechanism
• Open group bank account
• Register group with authorities
• Materials: Registration checklist, bank requirements',
    'start_time' => 0,
    'end_time' => 8,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=800',
    'order' => 1,
    'is_active' => 1
]);

// Protocol 2: Business Planning & Poultry Cycle (Sessions 7-9)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Poultry Enterprise Planning (Sessions 7-9)',
    'activity_description' => 'Strategic planning for indigenous chicken production as a viable business, understanding the production cycle, and developing seasonal activity calendars.

SESSION 7: FARMING AS A BUSINESS
• Cost-benefit analysis of local chicken production
• Enterprise selection considering local markets
• Profitability assessment: Eggs vs meat production
• Investment requirements for starting
• Materials: Enterprise templates

INDIGENOUS CHICKEN ADVANTAGES:
• Hardy, disease-resistant breeds
• Dual purpose: Eggs and meat
• Low input costs, scavenging ability
• Cultural preference, premium prices
• Suitable for free-range systems
• Breeds: Local, Kuroiler, improved indigenous

SESSION 8: GROUP ACTION PLAN
• Problem identification in current poultry keeping
• Problem-solution analysis for challenges
• Develop seasonal poultry activity plan
• Resource mobilization strategies
• Materials: Planning cards

SESSION 9: LEARNING SCHEDULE DEVELOPMENT
• Align learning to poultry lifecycle stages
• Chick stage: 0-8 weeks
• Grower stage: 9-20 weeks
• Layer stage: 21+ weeks
• Build group poultry lifecycle calendar
• Materials: Lifecycle charts

INDIGENOUS CHICKEN PRODUCTION CYCLE:
Brooding (0-8 weeks): Intensive care, vaccination
Growing (9-20 weeks): Less intensive, develop immunity
Laying (21-72 weeks): Egg production 150-180 eggs/year
Natural brooding: Hens go broody, hatch own chicks
Cockerel marketing: 5-6 months at 1.5-2kg',
    'start_time' => 8,
    'end_time' => 12,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800',
    'order' => 2,
    'is_active' => 1
]);

// Protocol 3: Chick Management (Session 10)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Indigenous Chick Management (Session 10)',
    'activity_description' => 'Comprehensive chick care focusing on brooding, vaccination, and feeding practices adapted for indigenous breeds with emphasis on improving survival rates.

BROODING INDIGENOUS CHICKS:
Natural Brooding:
• Mother hen broods 8-12 chicks
• Provide shelter from weather and predators
• Supplement feeding while scavenging
• Advantages: Low cost, natural immunity transfer
• Disadvantages: Limited scale, seasonal

Artificial Brooding:
• Use local materials: Charcoal stove, kerosene lamp
• Temperature: 35°C week 1, reduce 3°C weekly
• Brooder size: 50-100 chicks per square meter
• Duration: 4-6 weeks depending on weather
• Construct demo brooder during session

CHICK FEEDING:
Weeks 1-4:
• Chick mash 20% protein or local alternatives
• Finely ground grains: Maize, millet, sorghum
• Protein sources: Termites, fish meal, boiled eggs
• Green vegetables chopped finely
• Clean water with sugar first day
• Feed 4-5 times daily

Weeks 5-8:
• Grower mash or mixed grains
• Begin scavenging training
• Supplement with kitchen waste
• Ensure calcium source (crushed shells)

VACCINATION SCHEDULE:
Day 1-7: Newcastle Disease (eye drop)
Day 14-21: Gumboro (water)
Week 6-8: Fowl Pox (wing web)
Week 12: Newcastle booster
Every 3 months: Newcastle booster

COMMON CHICK PROBLEMS:
• High mortality: Improve brooding temperature
• Coccidiosis: Keep dry, use anticoccidials
• Predators: Secure housing, guard dog
• Starvation: Ensure all chicks eating/drinking
• Cold stress: Adequate heat provision

FIELD ACTIVITIES:
• Construct demonstration brooder using local materials
• Practice vaccination techniques
• Mix chick feed from local ingredients
• Observe chick behavior for health assessment
• Materials: Charcoal stove, brooder samples',
    'start_time' => 12,
    'end_time' => 16,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=800',
    'order' => 3,
    'is_active' => 1
]);

// Protocol 4: Grower Management (Session 11)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Grower Stage Management (Session 11)',
    'activity_description' => 'Managing growing indigenous chickens focusing on housing systems, parasite control, and health practices to ensure proper development before production stage.

GROWER HOUSING (9-20 Weeks):
Semi-Intensive System:
• Night shelter with daytime ranging
• House size: 8-10 birds per square meter
• Perches: 20cm per bird
• Nest boxes: Start providing week 18
• Separate cockerels if aggressive

HOUSING INSPECTION:
• Check ventilation adequacy
• Ensure predator-proof construction
• Assess cleanliness and litter condition
• Verify perch installation
• Identify improvement areas

DEWORMING PROGRAM:
First Deworming: 8 weeks
• Drugs: Piperazine, levamisole, albendazole
• Dosage: Follow label instructions
• Withdraw eggs/meat per label (typically 7-14 days)

Second Deworming: 16 weeks

Ongoing: Every 3-4 months

Signs Requiring Deworming:
• Weight loss or poor growth
• Ruffled feathers, lethargy
• Diarrhea with visible worms
• Pale comb and wattles (anemia)

DEWORMING DEMONSTRATION:
• Calculate correct dosage by bird weight
• Demonstrate oral drenching technique
• Show water medication method
• Record treatment dates
• Materials: Dewormers, housing model

HEALTH PRACTICES:
Daily Observation:
• Activity level and alertness
• Appetite and water intake
• Droppings consistency
• Respiratory sounds
• Feather condition

Weekly Tasks:
• Clean and disinfect drinkers
• Replace wet litter
• Check for external parasites
• Weigh sample birds

Monthly:
• Comprehensive health check
• Debeaking if feather pecking occurs
• Cull poor performers

GROWTH MONITORING:
Target Weights:
• 8 weeks: 400-500g
• 12 weeks: 700-900g
• 16 weeks: 1.0-1.3kg
• 20 weeks: 1.3-1.6kg (females), 1.8-2.2kg (males)

Indigenous breeds grow slower than commercial but are hardier and command premium prices.',
    'start_time' => 16,
    'end_time' => 24,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1612170153139-6f881ff1c76a?w=800',
    'order' => 4,
    'is_active' => 1
]);

// Protocol 5: Layer Care & Egg Production (Session 12)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Layer Care & Egg Production (Session 12)',
    'activity_description' => 'Managing laying hens and broody behavior in indigenous chickens, focusing on egg production optimization, nest hygiene, calcium supplementation, and natural brooding management.

EGG PRODUCTION IN INDIGENOUS HENS:
Production Characteristics:
• Start laying: 5-6 months (20-24 weeks)
• Annual production: 120-180 eggs (less than commercial)
• Egg size: 45-55g
• Better egg quality, richer yolk color
• Higher market value than commercial eggs

NEST HYGIENE:
Nest Box Requirements:
• 1 nest per 4-5 hens
• Size: 30×30×30cm
• Elevated 40cm off ground
• Dark, comfortable bedding
• Clean weekly, change bedding

Nest Management:
• Collect eggs 2-3 times daily
• Remove broody hens if eggs not needed
• Discourage floor laying through timely collection
• Use dummy eggs to train pullets

EGG COLLECTION DEMONSTRATION:
• Proper handling to prevent cracks
• Sorting and grading
• Storage methods (cool, large end up)
• Materials: Nest boxes, egg collection basket

CALCIUM SUPPLEMENTATION:
Importance:
• Essential for strong eggshells
• Prevents soft-shelled eggs
• Maintains hen bone health
• Reduces egg breakage

Sources:
• Crushed oyster shells (best)
• Limestone grit
• Crushed snail shells
• Eggshells (dried, crushed)
• Feed separately, not mixed in feed

Provision:
• Free choice in separate container
• Always available
• Hens consume as needed
• Especially important for layers

EGG HANDLING:
Collection:
• Minimum twice daily
• Use clean, dry containers
• Handle gently
• Separate dirty/cracked eggs

Storage:
• Cool place (15-18°C if possible)
• Large end up
• Use within 7-10 days for best quality
• Never wash unless for immediate use

MANAGING BROODY HENS:
Broodiness in Indigenous Hens:
• Natural behavior, goes broody regularly
• Good mothers, high chick survival
• Stops laying when broody (3-4 weeks)

If Hatching Eggs Desired:
• Provide quiet, separate nest
• Place 10-12 fertile eggs under hen
• Ensure feed and water nearby
• Minimize disturbance
• Hatch in 21 days

If Eggs Not Desired:
• Remove hen from nest repeatedly
• Place in wire cage (no bedding)
• Provide feed/water
• Usually breaks broody behavior in 3-5 days
• Returns to laying in 1-2 weeks

BIOSECURITY PRACTICES:
• Collect eggs promptly (prevents disease)
• Wash hands before/after handling eggs
• Don\'t eat cracked eggs raw
• Control flies and rodents
• Isolate sick birds immediately',
    'start_time' => 24,
    'end_time' => 32,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1569288052389-dac9b01c9c05?w=800',
    'order' => 5,
    'is_active' => 1
]);

// Protocol 6: Disease Prevention & Parasite Control (Sessions 13-14)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Disease Prevention & Parasite Management (Sessions 13-14)',
    'activity_description' => 'Comprehensive health management focusing on major poultry diseases, vaccination programs, and integrated parasite control for indigenous chickens.

SESSION 13: DISEASE PREVENTION

NEWCASTLE DISEASE (Most Important):
• Highly contagious viral disease
• Signs: Twisted neck, paralysis, greenish diarrhea, sudden death
• Mortality: Up to 100% in unvaccinated flocks
• Prevention: Vaccination every 2-3 months
• Vaccine: I-2 strain, eye drop or drinking water
• Local name varies (Kienyeji disease, fowl plague)

AVIAN INFLUENZA (Bird Flu):
• Highly pathogenic virus
• Signs: Sudden death, respiratory distress, swollen head
• Mortality: Very high
• Prevention: Biosecurity, separate from wild birds
• Reportable disease - notify authorities

FOWL POX:
• Viral disease with skin scabs
• Signs: Scabs on comb, wattles, around eyes
• Prevention: Vaccination at 6-8 weeks (wing web)
• Treatment: Remove scabs, apply iodine tincture

COCCIDIOSIS:
• Intestinal parasites
• Signs: Bloody droppings, weakness
• Prevention: Keep housing dry, anticoccidial drugs
• Treatment: Sulfa drugs, amprolium

CHRONIC RESPIRATORY DISEASE:
• Bacterial infection (Mycoplasma)
• Signs: Coughing, nasal discharge, swollen face
• Treatment: Antibiotics (tylosin, enrofloxacin)

VACCINATION DEMONSTRATION:
• Newcastle vaccine preparation
• Eye drop technique
• Drinking water method
• Record keeping
• Materials: Vaccine samples, posters

SESSION 14: PARASITE CONTROL

INTERNAL PARASITES:
Roundworms:
• Most common in chickens
• Signs: Weight loss, poor growth, diarrhea
• Treatment: Piperazine, levamisole, fenbendazole
• Deworming schedule: Every 3-4 months

Tapeworms:
• Less common, require intermediate hosts
• Signs: Weight loss, poor production
• Treatment: Praziquantel
• Prevention: Control beetles and flies

EXTERNAL PARASITES:
Lice (Chewing):
• Live on birds permanently
• Signs: Feather damage, restlessness, reduced production
• Check: Part feathers, see lice on skin/feathers
• Treatment: Dusting with permethrin powder

Mites (Sucking):
• Red mites hide in cracks, feed at night
• Northern fowl mites live on birds
• Signs: Anemia, reduced laying, stress
• Treatment: Spray housing with acaricide, dust birds

Ticks:
• Attach to skin, suck blood
• Transmit diseases
• Remove manually or use acaricides

Fleas:
• Sticktight fleas embed in face
• Cause anemia and irritation
• Remove manually, apply petroleum jelly

PARASITE CONTROL DEMONSTRATION:
Dusting Technique:
• Hold bird firmly
• Apply dust against feathers
• Cover all body including under wings
• Repeat after 10 days

Sanitation:
• Remove old litter
• Spray house with insecticide
• Allow to dry before restocking
• Materials: Dusting powder, sprayers

INTEGRATED CONTROL:
• Good hygiene primary prevention
• Regular deworming schedule
• Treat whole flock simultaneously
• Include housing treatment for external parasites
• Rotate drug classes to prevent resistance
• Natural methods: Wood ash dust baths, neem leaves',
    'start_time' => 32,
    'end_time' => 40,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1584608473084-e0ce2f0c8716?w=800',
    'order' => 6,
    'is_active' => 1
]);

// Protocol 7: Housing, Nutrition & Marketing (Sessions 15-17)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Housing, Nutrition & Marketing (Sessions 15-17)',
    'activity_description' => 'Practical training on low-cost poultry housing construction, local feed formulation, and marketing strategies for indigenous chicken products.

SESSION 15: POULTRY HOUSING

LOW-COST HOUSING DESIGNS:
Materials Available Locally:
• Poles: Eucalyptus, cypress, treated timber
• Walls: Mud and wattle, timber offcuts, wire mesh
• Roof: Grass thatch, iron sheets, tiles
• Floor: Compacted earth, concrete (better)

Housing Requirements:
• Ventilation: Openings near roof, avoid drafts
• Light: Windows facing east for morning sun
• Dry: Raised floor or good drainage
• Secure: Predator-proof (dogs, cats, snakes, mongoose)
• Size: 3-4 birds per square meter floor space

Improved Indigenous Chicken House Design:
• 4×3 meters houses 40-50 birds
• Raised floor (30cm) for ventilation and cleaning
• Perches: 30cm above floor, 20cm per bird
• Nests: Built-in or removable boxes, 1 per 4-5 hens
• Pop hole: 30×30cm for free-range access
• Cost: $50-150 depending on materials

SHELTER CONSTRUCTION DEMONSTRATION:
• Lay foundation using local stones
• Frame construction with poles
• Wall options: Mud, timber, wire mesh (half-wall)
• Roofing techniques
• Perch and nest installation
• Materials: Timber, wire mesh samples

Free-Range Management:
• Housing for night and laying
• Daytime scavenging in compound/field
• Fencing optional but protects vegetables
• Reduces feed costs significantly

SESSION 16: POULTRY NUTRITION

FEED TYPES AND LOCAL FORMULATION:

Scavenging:
• Provides 40-60% of nutrition in free-range
• Insects, worms, seeds, green vegetation
• Encourage by spreading grain in yard
• Compost heaps attract insects

Supplementary Feeding:
Grains (Energy):
• Maize, sorghum, millet, wheat, rice bran
• 60-70% of supplementary feed

Proteins:
• Fish meal, termites, fried fish waste
• Sunflower cake, groundnut cake
• Beans, cowpeas (boiled and crushed)
• 20-25% of feed

Minerals/Vitamins:
• Crushed bones (calcium, phosphorus)
• Oyster shells (calcium for layers)
• Green vegetables (vitamins)
• Salt: 0.5% of feed

Sample Local Feed Formula:
• Maize (crushed): 50kg
• Millet/sorghum: 20kg
• Sunflower cake: 15kg
• Fish meal or termites: 10kg
• Crushed bones: 3kg
• Oyster shells: 1.5kg
• Salt: 0.5kg
Total: 100kg mixed feed

FEED MIXING DEMONSTRATION:
• Weigh ingredients
• Mix thoroughly
• Store in dry, rodent-proof containers
• Feed twice daily plus free range
• Materials: Feed ingredients, containers, scale

Feeding Indigenous Chickens:
• Chicks: 20-30g per day
• Growers: 50-70g per day
• Layers: 80-100g per day
• Adjust based on scavenging availability

SESSION 17: MARKETING AND VALUE ADDITION

MARKET OPPORTUNITIES:
Live Bird Sales:
• Cockerels: 5-6 months at 1.5-2kg
• Culled layers: After 18-24 months
• Premium price: 30-50% above commercial
• Local markets, hotels, direct consumers

Egg Marketing:
• Farm-gate sales highest margin
• Weekly markets
• Regular customers (subscription)
• Hotels and restaurants
• Premium for indigenous eggs

VALUE ADDITION:
Dressed Chicken:
• Slaughter, clean, package
• Higher price than live bird
• Need proper facilities
• Vacuum packaging extends shelf life

Egg Processing:
• Cleaning, grading, packaging
• Branded egg trays
• Organic certification possible

By-Products:
• Manure for gardens (compost)
• Feathers for crafts/pillows

MARKETING STRATEGIES:
Market Survey Activity:
• Visit local markets
• Identify price ranges
• Find potential buyers
• Assess competition
• Role play: Farmer-buyer negotiation

Packaging and Presentation:
• Clean, attractive packaging
• Proper labeling with farm name
• Highlight "indigenous/organic/free-range"
• Build customer relationships

Market Linkages:
• Hotels and restaurants
• Supermarkets (regular supply needed)
• Farmers\' markets
• Online platforms
• Export to urban areas

PRICING:
• Cost all inputs (chicks, feed, labor, housing)
• Calculate production cost per kg/per egg
• Add profit margin (20-30%)
• Consider market competition
• Premium for quality and indigenous breeds

Materials: Sample packaging, market price list',
    'start_time' => 40,
    'end_time' => 48,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=800',
    'order' => 7,
    'is_active' => 1
]);

// Protocol 8: Graduation & Sustainability (Session 18)
$protocols[] = ProductionProtocol::create([
    'enterprise_id' => $enterprise->id,
    'activity_name' => 'Graduation & Group Sustainability (Session 18)',
    'activity_description' => 'Celebrating achievements, reviewing learning outcomes, and establishing mechanisms for continued group activities and knowledge sharing beyond the training period.

GRADUATION CEREMONY:

ACHIEVEMENTS REVIEW:
Individual Farmer Testimonies:
• Increased chicken numbers
• Improved flock health and survival
• Higher egg production
• Better income from poultry sales
• Knowledge gained and confidence built
• Challenges overcome

Group Achievements:
• Flock health improvements (reduced mortality)
• Adoption of vaccination programs
• Improved housing constructed
• Local feed formulation mastered
• Market linkages established
• Group savings accumulated
• Peer support networks formed

IMPACT ASSESSMENT:
Knowledge Gained:
• Disease recognition and control
• Vaccination importance and techniques
• Proper nutrition and feeding
• Housing improvements
• Parasite management
• Business planning

Practices Adopted:
• Regular vaccination
• Deworming schedule
• Improved housing
• Supplementary feeding
• Egg handling and marketing
• Record keeping

Economic Impact:
• Increased income from egg/meat sales
• Reduced losses from disease
• Better prices through quality improvement
• Value addition opportunities
• Investment in expansion

CERTIFICATE AWARD:
• All participants receive certificates
• Special recognition for outstanding farmers
• Group photograph
• Media coverage if possible
• Materials: Certificates, caps, T-shirts

SUSTAINABILITY PLANNING:

CONTINUED GROUP ACTIVITIES:
• Monthly meetings maintained
• Continued savings and credit operations
• Joint input procurement
• Bulk marketing arrangements
• Experience sharing visits
• Training of new members by graduates

KNOWLEDGE TRANSFER:
• Each graduate trains 2-3 neighbors
• Farmer-to-farmer extension
• Demonstration farms at members\' homes
• Share hatching eggs and chicks
• Mentorship of new poultry keepers

INCOME-GENERATING ACTIVITIES:
• Sell day-old chicks to community
• Hatch and brood chicks for others (fee)
• Provide vaccination services (small fee)
• Sell quality breeding stock
• Collective marketing for better prices

LINKAGES MAINTAINED:
• Extension service contact
• Veterinary officer relationship
• Input suppliers (vaccines, drugs)
• Market buyers
• Financial institutions for credit
• Other farmer groups for exchange

GROUP LEADERSHIP TRANSITION:
• Rotate leadership annually
• Develop internal trainers
• Maintain group records
• Financial transparency and audits
• Conflict resolution mechanisms

POST-GRADUATION FOLLOW-UP:
• Quarterly check-in meetings with facilitator
• Self-assessment of progress
• Continued technical backstopping as needed
• Link to advanced training opportunities
• Share success stories for motivation

SCALING IMPACT:
• Establish new FFS groups using same model
• Graduates become facilitators
• Document innovations and adaptations
• Advocate for supportive policies
• Participate in agricultural shows and exhibitions

SUSTAINABILITY INDICATORS:
• Group still meeting 6 months post-graduation
• Majority of members still keeping poultry
• Increased flock sizes
• Continued adoption of improved practices
• Financial sustainability of group activities
• New members joining based on reputation

CHALLENGES AND SOLUTIONS:
Common Challenges Discussed:
• Disease outbreaks - response protocols
• Market fluctuations - diversification strategies
• Limited capital - savings mobilization, credit access
• Feed costs - maximize scavenging, bulk buying
• Predators - improved housing, guard dogs

CELEBRATION ACTIVITIES:
• Cultural performances
• Sharing of meals
• Poultry products showcase
• Best chicken competition
• Role plays summarizing learning
• Gifts and token appreciation

FUTURE VISION:
• Expansion plans discussed
• Support needed identified
• Commitment to continue learning
• Pledge to share knowledge
• Building sustainable poultry enterprise in community',
    'start_time' => 48,
    'end_time' => 48,
    'is_compulsory' => 1,
    'photo' => 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800',
    'order' => 8,
    'is_active' => 1
]);

echo "✓ Indigenous Poultry APFS Created (ID: {$enterprise->id})" . PHP_EOL;
echo "✓ All 8 Training Protocols Created" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "✅ INDIGENOUS POULTRY APFS COMPLETE!" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "Total Protocols: " . count($protocols) . PHP_EOL . PHP_EOL;
echo "TRAINING MODULES:" . PHP_EOL;
echo "1. Community Mobilization (Sessions 1-6)" . PHP_EOL;
echo "2. Business Planning & Poultry Cycle (Sessions 7-9)" . PHP_EOL;
echo "3. Chick Management (Session 10)" . PHP_EOL;
echo "4. Grower Management (Session 11)" . PHP_EOL;
echo "5. Layer Care & Egg Production (Session 12)" . PHP_EOL;
echo "6. Disease & Parasite Control (Sessions 13-14)" . PHP_EOL;
echo "7. Housing, Nutrition & Marketing (Sessions 15-17)" . PHP_EOL;
echo "8. Graduation & Sustainability (Session 18)" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
