<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class UserHierarchyTestSeeder extends Seeder
{
    /**
     * Run the database seeds - Create 100 test users with proper hierarchy
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        
        echo "\n";
        echo "==================================================\n";
        echo "  DTEHM USER HIERARCHY TEST DATA GENERATION\n";
        echo "==================================================\n\n";
        
        echo "🚀 Starting generation of 100 test users...\n\n";
        
        // Get existing users to use as potential sponsors
        $existingUsers = User::whereNotNull('business_name')->pluck('business_name')->toArray();
        
        if (empty($existingUsers)) {
            echo "⚠️  No existing users found. Creating root user first...\n";
            
            // Create a root user with no sponsor
            $rootUser = User::create([
                'first_name' => 'Root',
                'last_name' => 'Admin',
                'name' => 'Root Admin',
                'phone_number' => '+256700000000',
                'email' => 'root@dtehm.com',
                'password' => bcrypt('password'),
                'sex' => 'Male',
                'user_type' => 'Customer',
                'status' => 'Active',
                'country' => 'Uganda',
                'address' => 'Kampala, Uganda',
                'dob' => '1990-01-01',
                'sponsor_id' => null,
                'is_dtehm_member' => 'Yes',
                'is_dip_member' => 'Yes',
            ]);
            
            $existingUsers = [$rootUser->business_name];
            echo "✅ Root user created: {$rootUser->business_name}\n\n";
        }
        
        $createdCount = 0;
        $errors = [];
        
        // Create 100 new users
        for ($i = 1; $i <= 100; $i++) {
            try {
                // Randomly select a sponsor from existing users (70% chance) or no sponsor (30% chance)
                $hasSponsor = $faker->boolean(70);
                $sponsorId = $hasSponsor && !empty($existingUsers) 
                    ? $faker->randomElement($existingUsers) 
                    : null;
                
                // Generate random data
                $firstName = $faker->firstName;
                $lastName = $faker->lastName;
                $sex = $faker->randomElement(['Male', 'Female']);
                
                // Ensure unique phone number
                $phoneNumber = '+256' . $faker->numerify('7########');
                while (User::where('phone_number', $phoneNumber)->exists()) {
                    $phoneNumber = '+256' . $faker->numerify('7########');
                }
                
                // Create user
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => "$firstName $lastName",
                    'phone_number' => $phoneNumber,
                    'email' => strtolower($firstName) . '.' . strtolower($lastName) . $i . '@test.com',
                    'password' => bcrypt('password'),
                    'sex' => $sex,
                    'user_type' => 'Customer',
                    'status' => $faker->randomElement(['Active', 'Active', 'Active', 'Pending']), // 75% active
                    'country' => 'Uganda',
                    'address' => $faker->city . ', Uganda',
                    'dob' => $faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                    'sponsor_id' => $sponsorId,
                    'is_dtehm_member' => $faker->randomElement(['Yes', 'Yes', 'No']), // 66% members
                    'is_dip_member' => $faker->randomElement(['Yes', 'No']),
                    'father_name' => $faker->name('male'),
                    'mother_name' => $faker->name('female'),
                ]);
                
                // Add to existing users array for future sponsoring
                if ($user->business_name) {
                    $existingUsers[] = $user->business_name;
                }
                
                $createdCount++;
                
                // Progress indicator
                if ($i % 10 === 0) {
                    echo "✅ Created $i users...\n";
                }
                
            } catch (\Exception $e) {
                $errors[] = "User $i: " . $e->getMessage();
                echo "❌ Error creating user $i: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
        echo "==================================================\n";
        echo "  GENERATION COMPLETE\n";
        echo "==================================================\n\n";
        echo "✅ Successfully created: $createdCount users\n";
        echo "❌ Errors encountered: " . count($errors) . "\n\n";
        
        if (!empty($errors)) {
            echo "Error details:\n";
            foreach ($errors as $error) {
                echo "  - $error\n";
            }
            echo "\n";
        }
        
        // Show hierarchy statistics
        echo "📊 HIERARCHY STATISTICS:\n";
        echo "==================================================\n\n";
        
        $usersWithSponsor = User::whereNotNull('sponsor_id')->count();
        $usersWithoutSponsor = User::whereNull('sponsor_id')->count();
        
        echo "Users with sponsor: $usersWithSponsor\n";
        echo "Users without sponsor: $usersWithoutSponsor\n\n";
        
        // Check parent population
        $usersWithParents = [];
        for ($level = 1; $level <= 10; $level++) {
            $count = User::whereNotNull("parent_$level")->count();
            $usersWithParents[$level] = $count;
            echo "Users with parent_$level: $count\n";
        }
        
        echo "\n";
        echo "🎉 Test data generation completed successfully!\n";
        echo "==================================================\n\n";
    }
}
