<?php
/**
 * FFS Articles Import Script
 * 
 * This script imports FFS training materials, phenology guides, and season calendars
 * into the advisory system as published articles.
 * 
 * Run: php import_ffs_articles.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryPost;

echo "==============================================\n";
echo "FFS ARTICLES IMPORT SCRIPT\n";
echo "==============================================\n\n";

// Configuration
$sourceFolder = __DIR__ . '/FFS_ARTICLES';
$destinationFolder = __DIR__ . '/public/storage/advisory/documents';

// Create destination folder if not exists
if (!File::exists($destinationFolder)) {
    File::makeDirectory($destinationFolder, 0755, true);
    echo "✓ Created destination folder: $destinationFolder\n\n";
}

// ===== STEP 1: Truncate tables =====
echo "Step 1: Truncating tables...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    DB::table('advisory_posts')->truncate();
    DB::table('advisory_categories')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "   ✓ advisory_posts truncated\n";
    echo "   ✓ advisory_categories truncated\n\n";
} catch (\Exception $e) {
    echo "   ⚠ Error truncating: " . $e->getMessage() . "\n";
    exit(1);
}

// ===== STEP 2: Create Categories =====
echo "Step 2: Creating advisory categories...\n";

$categories = [
    [
        'name' => 'FFS Methodologies',
        'description' => 'Core Farmer Field School participatory tools and methods including AESA, transect walks, visioning, and problem-solution analysis.',
        'icon' => 'fa-users',
        'order' => 1,
        'status' => 'Active',
    ],
    [
        'name' => 'Crop Phenology',
        'description' => 'Phenology profiles for various crops showing growth stages, seasonal activities, and management practices throughout the crop cycle.',
        'icon' => 'fa-leaf',
        'order' => 2,
        'status' => 'Active',
    ],
    [
        'name' => 'Livestock Phenology',
        'description' => 'Phenology profiles for livestock including cattle, goats, poultry, pigs, and beekeeping - covering breeding, health, and management cycles.',
        'icon' => 'fa-paw',
        'order' => 3,
        'status' => 'Active',
    ],
    [
        'name' => 'Season Calendars',
        'description' => 'Season-long learning calendars for crops and livestock, providing week-by-week activities and topics for FFS sessions.',
        'icon' => 'fa-calendar',
        'order' => 4,
        'status' => 'Active',
    ],
    [
        'name' => 'Soil & Water Conservation',
        'description' => 'Techniques and methods for soil conservation, water harvesting, contour farming, and sustainable land management.',
        'icon' => 'fa-tint',
        'order' => 5,
        'status' => 'Active',
    ],
    [
        'name' => 'Livestock Feed & Nutrition',
        'description' => 'Guides on hay making, silage, mineral supplements, and feed formulation for livestock.',
        'icon' => 'fa-cutlery',
        'order' => 6,
        'status' => 'Active',
    ],
    [
        'name' => 'Sustainable Practices',
        'description' => 'Energy-saving technologies, climate-smart agriculture, and environmentally friendly farming practices.',
        'icon' => 'fa-recycle',
        'order' => 7,
        'status' => 'Active',
    ],
    [
        'name' => 'Data Collection Tools',
        'description' => 'Templates, forms, and tools for collecting and recording FFS data and monitoring activities.',
        'icon' => 'fa-clipboard',
        'order' => 8,
        'status' => 'Active',
    ],
    [
        'name' => 'Financial Tools',
        'description' => 'Cash flow analysis, enterprise budgets, and financial planning tools for farming activities.',
        'icon' => 'fa-calculator',
        'order' => 9,
        'status' => 'Active',
    ],
];

$categoryMap = [];
foreach ($categories as $cat) {
    $created = AdvisoryCategory::create($cat);
    $categoryMap[$cat['name']] = $created->id;
    echo "   ✓ Created: {$cat['name']} (ID: {$created->id})\n";
}
echo "\n";

// ===== STEP 3: Define Articles to Import =====
echo "Step 3: Preparing articles for import...\n";

$articles = [
    // FFS MATERIALS - Core Methodologies
    [
        'title' => 'Agro-Ecosystem Analysis (AESA) Guide',
        'file' => 'FFS Materials/FAO_AESA_Rev1.pdf',
        'category' => 'FFS Methodologies',
        'content' => '<h3>Agro-Ecosystem Analysis (AESA)</h3>
<p>AESA is a core tool in Farmer Field Schools that helps farmers become experts in their own fields through systematic observation and analysis.</p>
<h4>What is AESA?</h4>
<p>Agro-Ecosystem Analysis is a participatory method where farmers observe, analyze, and make decisions about their crops, livestock, soil, water, and overall farming system. It helps farmers understand the complex interactions in their fields.</p>
<h4>Key Components:</h4>
<ul>
<li><strong>Observation</strong> - Systematic field observation of crops, pests, diseases, soil, water</li>
<li><strong>Drawing</strong> - Visual representation of field conditions</li>
<li><strong>Analysis</strong> - Group discussion of observations</li>
<li><strong>Decision Making</strong> - Collective decisions on management actions</li>
</ul>
<h4>When to Conduct AESA:</h4>
<p>AESA should be conducted weekly during the growing season, preferably in the morning when pest activity is visible.</p>
<p><em>Download the complete guide below for detailed instructions and templates.</em></p>',
        'tags' => 'AESA, observation, analysis, FFS methodology, participatory',
    ],
    [
        'title' => 'Building an A-Frame for Contour Farming',
        'file' => 'FFS Materials/FAO_Building an A-Frame_rev1.pdf',
        'category' => 'Soil & Water Conservation',
        'content' => '<h3>Building an A-Frame Tool</h3>
<p>The A-Frame is a simple yet effective tool for marking contour lines on sloping land, essential for soil and water conservation.</p>
<h4>Why Use an A-Frame?</h4>
<ul>
<li>Prevents soil erosion on slopes</li>
<li>Improves water infiltration</li>
<li>Creates terraces for farming</li>
<li>Low-cost and easy to make</li>
</ul>
<h4>Materials Needed:</h4>
<ul>
<li>3 straight poles (2-3 meters long)</li>
<li>String or rope</li>
<li>A weight (stone or bottle with water)</li>
</ul>
<p><em>Download the PDF guide for step-by-step construction and usage instructions.</em></p>',
        'tags' => 'A-frame, contour farming, soil conservation, erosion control, terracing',
    ],
    [
        'title' => 'Introduction to Crop and Animal Phenology',
        'file' => 'FFS Materials/FAO_Crop and Animal Phenology_Rev1.pdf',
        'category' => 'FFS Methodologies',
        'content' => '<h3>Understanding Phenology</h3>
<p>Phenology is the study of seasonal biological events in plants and animals, and how they are influenced by environmental factors.</p>
<h4>What is Phenology?</h4>
<p>In FFS, phenology helps farmers understand the life cycles of crops and animals, enabling better timing of management activities.</p>
<h4>Importance of Phenology:</h4>
<ul>
<li>Timing of planting and harvesting</li>
<li>Understanding pest and disease cycles</li>
<li>Planning breeding and animal management</li>
<li>Adapting to climate variability</li>
</ul>
<p><em>This guide introduces the concept of phenology for use in Farmer Field Schools.</em></p>',
        'tags' => 'phenology, life cycles, seasons, timing, FFS',
    ],
    [
        'title' => 'Energy-Saving Stove Construction Guide',
        'file' => 'FFS Materials/FAO_Energy-saving stove instructions_Rev1.pdf',
        'category' => 'Sustainable Practices',
        'content' => '<h3>Building an Energy-Saving Stove</h3>
<p>Energy-saving stoves reduce firewood consumption by up to 60% and significantly decrease indoor air pollution.</p>
<h4>Benefits:</h4>
<ul>
<li>Reduces firewood consumption by 50-60%</li>
<li>Decreases cooking time</li>
<li>Reduces smoke and improves health</li>
<li>Saves time collecting firewood</li>
<li>Helps protect forests</li>
</ul>
<h4>Materials:</h4>
<ul>
<li>Clay or mud</li>
<li>Sand</li>
<li>Water</li>
<li>Old pot for mold</li>
</ul>
<p><em>Download the complete instructions for building your own energy-saving stove.</em></p>',
        'tags' => 'energy saving, stove, firewood, sustainable, cooking',
    ],
    [
        'title' => 'Hay Making Guide for Livestock Feed',
        'file' => 'FFS Materials/FAO_Making Hay_rev1.pdf',
        'category' => 'Livestock Feed & Nutrition',
        'content' => '<h3>Hay Making for Dry Season Feeding</h3>
<p>Hay is dried grass or fodder that can be stored and fed to livestock during the dry season when fresh pasture is scarce.</p>
<h4>Why Make Hay?</h4>
<ul>
<li>Provides feed during dry season</li>
<li>Preserves nutritional value of grass</li>
<li>Reduces livestock weight loss</li>
<li>Maintains milk production</li>
</ul>
<h4>Best Practices:</h4>
<ul>
<li>Cut grass at the right stage (before flowering)</li>
<li>Dry thoroughly to prevent mold</li>
<li>Store in a dry, covered area</li>
</ul>
<p><em>Download the complete guide for hay making techniques.</em></p>',
        'tags' => 'hay, fodder, livestock feed, dry season, preservation',
    ],
    [
        'title' => 'Making Mineral Lick for Livestock',
        'file' => 'FFS Materials/FAO_Making mineral lick_Rev1.pdf',
        'category' => 'Livestock Feed & Nutrition',
        'content' => '<h3>Homemade Mineral Lick</h3>
<p>Mineral licks provide essential minerals that may be lacking in pastures and forages, improving animal health and productivity.</p>
<h4>Why Animals Need Minerals:</h4>
<ul>
<li>Strong bones and teeth</li>
<li>Better reproduction</li>
<li>Improved milk production</li>
<li>Disease resistance</li>
</ul>
<h4>Common Ingredients:</h4>
<ul>
<li>Salt</li>
<li>Bone meal</li>
<li>Cement (small amount as binder)</li>
<li>Available mineral supplements</li>
</ul>
<p><em>Download the guide for complete recipes and instructions.</em></p>',
        'tags' => 'mineral lick, livestock nutrition, salt, supplements',
    ],
    [
        'title' => 'Pair-wise Ranking Method',
        'file' => 'FFS Materials/FAO_Pair-wise Ranking_Rev1.pdf',
        'category' => 'FFS Methodologies',
        'content' => '<h3>Pair-wise Ranking for Decision Making</h3>
<p>Pair-wise ranking is a participatory method for comparing and prioritizing options by comparing them two at a time.</p>
<h4>When to Use:</h4>
<ul>
<li>Selecting priority enterprises</li>
<li>Choosing varieties to test</li>
<li>Prioritizing problems to solve</li>
<li>Ranking preferred solutions</li>
</ul>
<h4>How It Works:</h4>
<p>Each option is compared with every other option. Participants decide which is more important in each pair. The option that "wins" most comparisons ranks highest.</p>
<p><em>Download the guide for step-by-step facilitation instructions.</em></p>',
        'tags' => 'pair-wise ranking, prioritization, decision making, participatory',
    ],
    [
        'title' => 'Problem-Solution Analysis Guide',
        'file' => 'FFS Materials/FAO_Problem-solution analysis and options assessment_Rev1.pdf',
        'category' => 'FFS Methodologies',
        'content' => '<h3>Problem-Solution Analysis</h3>
<p>A systematic approach to identifying root causes of problems and developing appropriate solutions through participatory analysis.</p>
<h4>Steps:</h4>
<ol>
<li><strong>Problem Identification</strong> - What is the main problem?</li>
<li><strong>Problem Tree Analysis</strong> - Identify causes and effects</li>
<li><strong>Solution Brainstorming</strong> - Generate possible solutions</li>
<li><strong>Options Assessment</strong> - Evaluate each solution</li>
<li><strong>Action Planning</strong> - Plan implementation</li>
</ol>
<h4>Tools Used:</h4>
<ul>
<li>Problem tree diagrams</li>
<li>Solution tree diagrams</li>
<li>Options matrix</li>
</ul>
<p><em>Download the complete facilitation guide.</em></p>',
        'tags' => 'problem analysis, solution tree, root cause, participatory',
    ],
    [
        'title' => 'Participant Registration Guide',
        'file' => 'FFS Materials/FAO_Register Participants_Rev1.pdf',
        'category' => 'Data Collection Tools',
        'content' => '<h3>Registering FFS Participants</h3>
<p>Proper registration of FFS participants is essential for monitoring, evaluation, and follow-up activities.</p>
<h4>Information to Collect:</h4>
<ul>
<li>Name and contact details</li>
<li>Gender and age</li>
<li>Location/village</li>
<li>Farm size and enterprises</li>
<li>Experience and education</li>
</ul>
<h4>Why Registration Matters:</h4>
<ul>
<li>Track attendance and participation</li>
<li>Monitor gender balance</li>
<li>Enable follow-up visits</li>
<li>Support impact assessment</li>
</ul>
<p><em>Download the registration template and guidelines.</em></p>',
        'tags' => 'registration, participants, monitoring, data collection',
    ],
    [
        'title' => 'Transect Walk Methodology',
        'file' => 'FFS Materials/FAO_TransectWalkThumbnaill_rev1.pdf',
        'category' => 'FFS Methodologies',
        'content' => '<h3>Transect Walk for Community Assessment</h3>
<p>A transect walk is a systematic walk through a community or farm to observe and record resources, problems, and opportunities.</p>
<h4>Purpose:</h4>
<ul>
<li>Map natural resources</li>
<li>Identify different land uses</li>
<li>Observe farming practices</li>
<li>Identify problems and opportunities</li>
</ul>
<h4>How to Conduct:</h4>
<ol>
<li>Plan the route to cover different areas</li>
<li>Walk slowly and observe carefully</li>
<li>Ask questions to local people</li>
<li>Record observations with drawings</li>
<li>Discuss findings with the group</li>
</ol>
<p><em>Download the guide for detailed facilitation steps.</em></p>',
        'tags' => 'transect walk, community mapping, resources, observation',
    ],
    [
        'title' => 'Visioning Exercise for FFS',
        'file' => 'FFS Materials/FAO_Visioning_Rev1.pdf',
        'category' => 'FFS Methodologies',
        'content' => '<h3>Visioning: Creating a Shared Future</h3>
<p>Visioning is a participatory exercise where farmers imagine their ideal future and plan steps to achieve it.</p>
<h4>Why Visioning?</h4>
<ul>
<li>Creates shared goals among group members</li>
<li>Motivates action towards improvement</li>
<li>Guides activity planning</li>
<li>Provides basis for monitoring progress</li>
</ul>
<h4>Steps:</h4>
<ol>
<li>Current situation analysis</li>
<li>Drawing the ideal future (5 years)</li>
<li>Identifying gaps</li>
<li>Planning actions to close gaps</li>
</ol>
<p><em>Download the facilitation guide for conducting visioning exercises.</em></p>',
        'tags' => 'visioning, future planning, goals, participatory',
    ],
    
    // CROP PHENOLOGIES
    [
        'title' => 'Maize Phenology Profile',
        'file' => 'Phenologies/CROP PHENOLOGY PROFILES FOR MAIZE.docx',
        'category' => 'Crop Phenology',
        'content' => '<h3>Maize Crop Phenology</h3>
<p>A comprehensive guide to maize growth stages and management activities throughout the cropping season.</p>
<h4>Growth Stages:</h4>
<ul>
<li><strong>Germination</strong> (Days 1-7)</li>
<li><strong>Seedling</strong> (Weeks 1-3)</li>
<li><strong>Vegetative Growth</strong> (Weeks 3-8)</li>
<li><strong>Tasseling/Silking</strong> (Weeks 8-10)</li>
<li><strong>Grain Filling</strong> (Weeks 10-14)</li>
<li><strong>Maturity</strong> (Weeks 14-16)</li>
</ul>
<h4>Key Management Activities:</h4>
<ul>
<li>Land preparation and planting</li>
<li>Weeding (critical at weeks 2-3 and 5-6)</li>
<li>Fertilizer application timing</li>
<li>Pest and disease monitoring</li>
<li>Harvesting at the right moisture content</li>
</ul>
<p><em>Download the complete phenology profile with weekly activities.</em></p>',
        'tags' => 'maize, corn, phenology, growth stages, crop management',
    ],
    [
        'title' => 'Bean Crop Phenology Profile',
        'file' => 'Phenologies/CROP PHENOLOGY PROFILES FOR BEAN1.docx',
        'category' => 'Crop Phenology',
        'content' => '<h3>Bean Crop Phenology</h3>
<p>Detailed phenology profile for common bean showing growth stages and management throughout the season.</p>
<h4>Growth Stages:</h4>
<ul>
<li><strong>Germination</strong> (Days 5-8)</li>
<li><strong>Primary Leaves</strong> (Week 1-2)</li>
<li><strong>Vegetative Growth</strong> (Weeks 2-5)</li>
<li><strong>Flowering</strong> (Weeks 5-7)</li>
<li><strong>Pod Formation</strong> (Weeks 7-9)</li>
<li><strong>Maturity</strong> (Weeks 10-12)</li>
</ul>
<h4>Critical Activities:</h4>
<ul>
<li>Inoculation before planting</li>
<li>Proper spacing for good aeration</li>
<li>Pest monitoring especially during flowering</li>
<li>Timely harvesting to prevent shattering</li>
</ul>
<p><em>Download the full phenology guide for beans.</em></p>',
        'tags' => 'beans, legumes, phenology, growth stages',
    ],
    [
        'title' => 'Groundnut Phenology Profile',
        'file' => 'Phenologies/Groundnut phenology_2007.doc',
        'category' => 'Crop Phenology',
        'content' => '<h3>Groundnut Phenology</h3>
<p>Complete phenology profile for groundnut/peanut cultivation with stage-by-stage management guide.</p>
<h4>Key Features:</h4>
<ul>
<li>Underground pod development</li>
<li>Nitrogen fixation benefits</li>
<li>Critical pegging stage</li>
</ul>
<h4>Growth Stages:</h4>
<ul>
<li>Germination and emergence</li>
<li>Vegetative growth</li>
<li>Flowering and pegging</li>
<li>Pod development</li>
<li>Maturity and harvesting</li>
</ul>
<p><em>Download the detailed phenology profile.</em></p>',
        'tags' => 'groundnuts, peanuts, phenology, legumes',
    ],
    [
        'title' => 'Green Gram Phenology Profile',
        'file' => 'Phenologies/Greengram phenology_2007.doc',
        'category' => 'Crop Phenology',
        'content' => '<h3>Green Gram (Mung Bean) Phenology</h3>
<p>Phenology profile for green gram cultivation, a short-duration legume crop.</p>
<h4>Characteristics:</h4>
<ul>
<li>Short duration (60-75 days)</li>
<li>Drought tolerant</li>
<li>Good nitrogen fixer</li>
<li>Suitable for intercropping</li>
</ul>
<p><em>Download the phenology guide for green gram.</em></p>',
        'tags' => 'green gram, mung bean, phenology, legumes',
    ],
    [
        'title' => 'Cabbage Phenology Profile',
        'file' => 'Phenologies/Phenology of Cabbage updated.doc',
        'category' => 'Crop Phenology',
        'content' => '<h3>Cabbage Phenology</h3>
<p>Growth stages and management guide for cabbage production.</p>
<h4>Key Stages:</h4>
<ul>
<li>Seedling/Nursery stage</li>
<li>Transplanting</li>
<li>Vegetative growth</li>
<li>Head formation</li>
<li>Harvest</li>
</ul>
<h4>Management Focus:</h4>
<ul>
<li>Pest control (caterpillars, aphids)</li>
<li>Nutrient management</li>
<li>Irrigation scheduling</li>
</ul>
<p><em>Download the complete phenology guide.</em></p>',
        'tags' => 'cabbage, vegetables, phenology, horticulture',
    ],
    
    // LIVESTOCK PHENOLOGIES
    [
        'title' => 'Cattle Management Phenology Profile',
        'file' => 'Phenologies/Cattle_Phenology_Profile.docx',
        'category' => 'Livestock Phenology',
        'content' => '<h3>Cattle Phenology Profile</h3>
<p>Comprehensive guide to cattle management throughout the year, covering breeding, health, feeding, and production cycles.</p>
<h4>Annual Cycle Components:</h4>
<ul>
<li><strong>Breeding Management</strong> - Heat detection, mating timing</li>
<li><strong>Health Calendar</strong> - Vaccination and deworming schedules</li>
<li><strong>Feeding Management</strong> - Seasonal feed planning</li>
<li><strong>Production Cycle</strong> - Calving, weaning, milk production</li>
</ul>
<h4>Key Activities by Season:</h4>
<ul>
<li>Dry season: Supplementary feeding, water management</li>
<li>Wet season: Parasite control, pasture management</li>
</ul>
<p><em>Download the complete cattle phenology profile.</em></p>',
        'tags' => 'cattle, livestock, phenology, beef, dairy',
    ],
    [
        'title' => 'Goat Management Phenology Profile',
        'file' => 'Phenologies/Goat_Phenology_Profile.docx',
        'category' => 'Livestock Phenology',
        'content' => '<h3>Goat Phenology Profile</h3>
<p>Year-round management guide for goat production including breeding, health, and nutrition.</p>
<h4>Management Areas:</h4>
<ul>
<li><strong>Breeding</strong> - Buck management, kidding care</li>
<li><strong>Health</strong> - Common diseases, vaccination, deworming</li>
<li><strong>Nutrition</strong> - Browse, feed supplementation</li>
<li><strong>Housing</strong> - Shelter requirements</li>
</ul>
<h4>Reproductive Cycle:</h4>
<ul>
<li>Gestation: 150 days</li>
<li>Kidding management</li>
<li>Weaning at 3 months</li>
</ul>
<p><em>Download the goat phenology guide.</em></p>',
        'tags' => 'goats, livestock, phenology, small ruminants',
    ],
    [
        'title' => 'Local Chicken Phenology Profile',
        'file' => 'Phenologies/Livestock_Phenology_Local_Chicken.docx',
        'category' => 'Livestock Phenology',
        'content' => '<h3>Local Chicken Phenology</h3>
<p>Management guide for indigenous chicken production, adapted for village poultry systems.</p>
<h4>Production Cycle:</h4>
<ul>
<li>Incubation: 21 days</li>
<li>Brooding: 4-6 weeks</li>
<li>Growing: 8-16 weeks</li>
<li>Laying starts: 5-6 months</li>
</ul>
<h4>Management Focus:</h4>
<ul>
<li>Improved housing (predator protection)</li>
<li>Vaccination (Newcastle disease)</li>
<li>Supplementary feeding</li>
<li>Egg collection and brooding management</li>
</ul>
<p><em>Download the local chicken management guide.</em></p>',
        'tags' => 'chicken, poultry, phenology, indigenous breeds',
    ],
    [
        'title' => 'Poultry Phenology Profile',
        'file' => 'Phenologies/Poultry_Phenology_Profile.docx',
        'category' => 'Livestock Phenology',
        'content' => '<h3>Poultry Phenology Profile</h3>
<p>Comprehensive poultry management phenology covering layers and broilers.</p>
<h4>Layer Management:</h4>
<ul>
<li>Brooding (0-8 weeks)</li>
<li>Growing (8-18 weeks)</li>
<li>Laying (18-72 weeks)</li>
</ul>
<h4>Key Activities:</h4>
<ul>
<li>Vaccination schedule</li>
<li>Feed management by stage</li>
<li>Lighting programs</li>
<li>Disease prevention</li>
</ul>
<p><em>Download the poultry phenology guide.</em></p>',
        'tags' => 'poultry, chicken, layers, broilers, phenology',
    ],
    [
        'title' => 'Turkey Phenology Profile',
        'file' => 'Phenologies/Turkey_Phenology_Profile.docx',
        'category' => 'Livestock Phenology',
        'content' => '<h3>Turkey Phenology Profile</h3>
<p>Management guide for turkey production from poults to market weight.</p>
<h4>Production Cycle:</h4>
<ul>
<li>Incubation: 28 days</li>
<li>Brooding: 6-8 weeks</li>
<li>Growing: 12-20 weeks</li>
<li>Market weight: 16-24 weeks</li>
</ul>
<h4>Special Requirements:</h4>
<ul>
<li>Higher protein feeds</li>
<li>More space requirements</li>
<li>Blackhead disease prevention</li>
</ul>
<p><em>Download the turkey management guide.</em></p>',
        'tags' => 'turkey, poultry, phenology',
    ],
    [
        'title' => 'Pig Phenology Profile',
        'file' => 'Phenologies/Pig_Phenology_Profile.docx',
        'category' => 'Livestock Phenology',
        'content' => '<h3>Pig Phenology Profile</h3>
<p>Complete pig management phenology for breeding and fattening operations.</p>
<h4>Reproductive Cycle:</h4>
<ul>
<li>Heat cycle: 21 days</li>
<li>Gestation: 114 days (3 months, 3 weeks, 3 days)</li>
<li>Farrowing management</li>
<li>Weaning: 6-8 weeks</li>
</ul>
<h4>Fattening Cycle:</h4>
<ul>
<li>Weaner stage (8-12 weeks)</li>
<li>Grower stage (12-20 weeks)</li>
<li>Finisher stage (20-24 weeks)</li>
</ul>
<p><em>Download the pig phenology guide.</em></p>',
        'tags' => 'pigs, swine, phenology, livestock',
    ],
    [
        'title' => 'Beekeeping (Apiary) Phenology Profile',
        'file' => 'Phenologies/Apiary_Phenology_Profile.docx',
        'category' => 'Livestock Phenology',
        'content' => '<h3>Apiary Phenology Profile</h3>
<p>Year-round beekeeping management guide aligned with flowering seasons.</p>
<h4>Seasonal Activities:</h4>
<ul>
<li><strong>Build-up Season</strong> - Colony inspection, feeding</li>
<li><strong>Honey Flow</strong> - Supering, monitoring</li>
<li><strong>Harvest Season</strong> - Honey extraction</li>
<li><strong>Dearth Period</strong> - Feeding, pest management</li>
</ul>
<h4>Key Management:</h4>
<ul>
<li>Hive inspection routines</li>
<li>Swarm prevention</li>
<li>Pest and disease control</li>
<li>Queen management</li>
</ul>
<p><em>Download the beekeeping phenology guide.</em></p>',
        'tags' => 'beekeeping, apiary, honey, phenology',
    ],
    [
        'title' => 'Rangeland Management Phenology',
        'file' => 'Phenologies/Rangeland_Phenology_Profile.docx',
        'category' => 'Livestock Phenology',
        'content' => '<h3>Rangeland Phenology Profile</h3>
<p>Seasonal guide for rangeland and pasture management for livestock production.</p>
<h4>Seasonal Management:</h4>
<ul>
<li><strong>Wet Season</strong> - Rotational grazing, reserve areas</li>
<li><strong>Dry Season</strong> - Strategic grazing, supplementation</li>
</ul>
<h4>Key Activities:</h4>
<ul>
<li>Grazing planning and rotation</li>
<li>Bush control</li>
<li>Fire management</li>
<li>Water point management</li>
</ul>
<p><em>Download the rangeland management guide.</em></p>',
        'tags' => 'rangeland, pasture, grazing, phenology',
    ],
    
    // SEASON CALENDARS
    [
        'title' => 'Season-Long Calendar: Maize FFS',
        'file' => 'Season long calender for Crop & Livestock Ents/APFS maize season long learning calender.doc',
        'category' => 'Season Calendars',
        'content' => '<h3>Maize Season-Long Learning Calendar</h3>
<p>Week-by-week FFS session topics and activities for a complete maize growing season.</p>
<h4>Calendar Structure:</h4>
<ul>
<li>16 weekly sessions</li>
<li>AESA activities each week</li>
<li>Special topics aligned with crop stage</li>
<li>Practical exercises</li>
</ul>
<h4>Sample Topics:</h4>
<ul>
<li>Week 1: Land preparation, variety selection</li>
<li>Week 4: First weeding, pest scouting</li>
<li>Week 8: Fertilizer top-dressing</li>
<li>Week 14: Harvest planning</li>
</ul>
<p><em>Download the complete season calendar.</em></p>',
        'tags' => 'maize, calendar, FFS, season long',
    ],
    [
        'title' => 'Season-Long Calendar: Goat Management',
        'file' => 'Season long calender for Crop & Livestock Ents/Season long Calendar for Goat Group 4.docx',
        'category' => 'Season Calendars',
        'content' => '<h3>Goat Season-Long Learning Calendar</h3>
<p>Complete FFS learning calendar for goat management covering all aspects of goat production.</p>
<h4>Topics Covered:</h4>
<ul>
<li>Housing and facilities</li>
<li>Breeding management</li>
<li>Health and disease control</li>
<li>Feeding and nutrition</li>
<li>Record keeping</li>
<li>Marketing</li>
</ul>
<p><em>Download the goat FFS season calendar.</em></p>',
        'tags' => 'goats, calendar, FFS, livestock',
    ],
    [
        'title' => 'Season-Long Calendar: Tomato Production',
        'file' => 'Season long calender for Crop & Livestock Ents/Season-long calender for Tomatoes production_2007.doc',
        'category' => 'Season Calendars',
        'content' => '<h3>Tomato Season-Long Learning Calendar</h3>
<p>FFS learning calendar for tomato production from nursery to harvest.</p>
<h4>Key Phases:</h4>
<ul>
<li>Nursery management (4 weeks)</li>
<li>Transplanting and establishment</li>
<li>Vegetative growth</li>
<li>Flowering and fruit set</li>
<li>Harvesting and marketing</li>
</ul>
<h4>Special Focus:</h4>
<ul>
<li>Disease management (blight, wilt)</li>
<li>Staking and pruning</li>
<li>Irrigation management</li>
</ul>
<p><em>Download the tomato production calendar.</em></p>',
        'tags' => 'tomatoes, vegetables, calendar, FFS',
    ],
    [
        'title' => 'Season-Long Calendar: Cattle Management',
        'file' => 'Season long calender for Crop & Livestock Ents/Season_Long_Cattle_Calendar_APFS_Full.docx',
        'category' => 'Season Calendars',
        'content' => '<h3>Cattle Season-Long Learning Calendar</h3>
<p>Comprehensive FFS calendar for cattle management through all seasons.</p>
<h4>Annual Cycle:</h4>
<ul>
<li>Dry season activities and challenges</li>
<li>Wet season opportunities</li>
<li>Breeding season planning</li>
<li>Vaccination and health calendar</li>
</ul>
<h4>Weekly Topics:</h4>
<ul>
<li>Feed assessment and planning</li>
<li>Health monitoring</li>
<li>Record keeping</li>
<li>Market preparation</li>
</ul>
<p><em>Download the cattle FFS calendar.</em></p>',
        'tags' => 'cattle, calendar, FFS, livestock',
    ],
    [
        'title' => 'Season-Long Calendar: Chicken Production',
        'file' => 'Season long calender for Crop & Livestock Ents/Season_Long_Chicken_Calendar_APFS.docx',
        'category' => 'Season Calendars',
        'content' => '<h3>Chicken Season-Long Learning Calendar</h3>
<p>FFS learning calendar for village chicken improvement.</p>
<h4>Session Topics:</h4>
<ul>
<li>Housing improvement</li>
<li>Feeding strategies</li>
<li>Disease prevention (Newcastle)</li>
<li>Brooding management</li>
<li>Record keeping</li>
<li>Marketing eggs and birds</li>
</ul>
<p><em>Download the chicken FFS calendar.</em></p>',
        'tags' => 'chicken, poultry, calendar, FFS',
    ],
    [
        'title' => 'Poultry FFS Season Calendar with Cash Flow',
        'file' => 'Phenologies/Poultry FFS Season Long Calendar.docx',
        'category' => 'Season Calendars',
        'content' => '<h3>Poultry FFS Season Calendar</h3>
<p>Integrated poultry FFS calendar with financial planning components.</p>
<h4>Features:</h4>
<ul>
<li>Weekly learning topics</li>
<li>Practical activities</li>
<li>Cash flow tracking</li>
<li>Record keeping templates</li>
</ul>
<p><em>Download the integrated poultry calendar.</em></p>',
        'tags' => 'poultry, calendar, FFS, cash flow',
    ],
    
    // DATA COLLECTION & FINANCIAL TOOLS
    [
        'title' => 'AESA Data Collection Sheet for Livestock',
        'file' => 'AESA_Data_Collection_Sheet_Livestock.docx',
        'category' => 'Data Collection Tools',
        'content' => '<h3>Livestock AESA Data Collection Sheet</h3>
<p>Standardized form for collecting Agro-Ecosystem Analysis data for livestock FFS sessions.</p>
<h4>Data Collected:</h4>
<ul>
<li>Animal health observations</li>
<li>Body condition scoring</li>
<li>Feed availability assessment</li>
<li>Water sources and quality</li>
<li>Disease signs and symptoms</li>
<li>Management recommendations</li>
</ul>
<p><em>Download the data collection template.</em></p>',
        'tags' => 'AESA, data collection, livestock, forms',
    ],
    [
        'title' => 'FFS Group Database Template',
        'file' => 'Copy of FFS Group database_ Template_Foster Project _2025.xlsx',
        'category' => 'Data Collection Tools',
        'content' => '<h3>FFS Group Database Template</h3>
<p>Excel template for managing FFS group information and member registration.</p>
<h4>Template Sections:</h4>
<ul>
<li>Group registration details</li>
<li>Member list with demographics</li>
<li>Attendance tracking</li>
<li>Activity records</li>
</ul>
<p><em>Download the database template.</em></p>',
        'tags' => 'database, template, registration, groups',
    ],
    [
        'title' => 'Poultry Enterprise Cash Flow Analysis',
        'file' => 'Phenologies/Cash Flow FFS Poultry.xls',
        'category' => 'Financial Tools',
        'content' => '<h3>Poultry Cash Flow Analysis Tool</h3>
<p>Excel-based tool for analyzing cash flow in poultry enterprises.</p>
<h4>Features:</h4>
<ul>
<li>Input cost tracking</li>
<li>Revenue projection</li>
<li>Break-even analysis</li>
<li>Profit calculation</li>
</ul>
<h4>Use Cases:</h4>
<ul>
<li>Planning new poultry enterprise</li>
<li>Evaluating profitability</li>
<li>Comparing production systems</li>
</ul>
<p><em>Download the cash flow template.</em></p>',
        'tags' => 'cash flow, poultry, financial analysis, enterprise',
    ],
];

echo "   Found " . count($articles) . " articles to import\n\n";

// ===== STEP 4: Copy Files and Create Articles =====
echo "Step 4: Copying files and creating articles...\n";

$imported = 0;
$errors = [];

foreach ($articles as $index => $article) {
    $num = $index + 1;
    
    // Source file
    $sourceFile = $sourceFolder . '/' . $article['file'];
    
    if (!File::exists($sourceFile)) {
        $errors[] = "File not found: {$article['file']}";
        echo "   ⚠ [$num] Skipped: {$article['title']} (file not found)\n";
        continue;
    }
    
    // Copy file to destination
    $fileName = basename($article['file']);
    $safeFileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
    $destFile = $destinationFolder . '/' . $safeFileName;
    
    try {
        File::copy($sourceFile, $destFile);
    } catch (\Exception $e) {
        $errors[] = "Failed to copy: {$article['file']} - " . $e->getMessage();
        continue;
    }
    
    // Determine file type and set appropriate fields
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $hasPdf = in_array($extension, ['pdf']) ? 'Yes' : 'No';
    $pdfUrl = "advisory/documents/{$safeFileName}";
    
    // Get category ID
    $categoryId = $categoryMap[$article['category']] ?? 1;
    
    // Create the article
    try {
        $post = AdvisoryPost::create([
            'category_id' => $categoryId,
            'title' => $article['title'],
            'content' => $article['content'],
            'tags' => $article['tags'],
            'has_pdf' => 'Yes', // All files are attached as documents
            'pdf_url' => $pdfUrl,
            'has_video' => 'No',
            'has_audio' => 'No',
            'has_youtube_video' => 'No',
            'status' => 'Published',
            'featured' => 'No',
            'language' => 'English',
            'view_count' => 0,
            'likes_count' => 0,
            'published_at' => now(),
            'author_id' => 1,
            'author_name' => 'FAO FFS Team',
        ]);
        
        $imported++;
        echo "   ✓ [$num] Created: {$article['title']}\n";
        
    } catch (\Exception $e) {
        $errors[] = "Failed to create article: {$article['title']} - " . $e->getMessage();
        echo "   ⚠ [$num] Error: {$article['title']}\n";
    }
}

// ===== SUMMARY =====
echo "\n==============================================\n";
echo "IMPORT SUMMARY\n";
echo "==============================================\n\n";

echo "Categories Created: " . count($categories) . "\n";
echo "Articles Imported: $imported / " . count($articles) . "\n";

if (!empty($errors)) {
    echo "\nErrors Encountered:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}

// Verification
$totalCategories = AdvisoryCategory::count();
$totalPosts = AdvisoryPost::count();
$postsByCategory = AdvisoryPost::select('category_id', DB::raw('count(*) as count'))
    ->groupBy('category_id')
    ->get();

echo "\nDatabase Verification:\n";
echo "   Total Categories: $totalCategories\n";
echo "   Total Posts: $totalPosts\n";
echo "\nPosts by Category:\n";
foreach ($postsByCategory as $pc) {
    $catName = AdvisoryCategory::find($pc->category_id)->name ?? 'Unknown';
    echo "   - $catName: {$pc->count}\n";
}

echo "\n==============================================\n";
echo "IMPORT COMPLETE!\n";
echo "==============================================\n";
