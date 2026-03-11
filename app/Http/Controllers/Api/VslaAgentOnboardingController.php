<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FfsGroup;
use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use App\Models\Utils;
use App\Traits\ApiResponser;
use App\Traits\PhoneNumberNormalization;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * VSLA Agent Onboarding Controller
 *
 * Allows a Field Officer (Facilitator) to register a brand-new VSLA group on
 * behalf of community members through a 3-step wizard:
 *
 *   Step 1 – Create Group        POST  api/agent-vsla/create-group
 *   Step 2 – Register Officers   POST  api/agent-vsla/register-officers
 *   Step 3 – Create Cycle        POST  api/agent-vsla/create-cycle
 *
 * Key differences from the regular chairperson self-onboarding flow:
 *   • The logged-in user is the FIELD OFFICER, not the chairperson.
 *   • The field officer becomes the group's facilitator_id.
 *   • All three leadership roles (chairperson, secretary, treasurer) are
 *     created/updated by the field officer in Step 2.
 *   • The group_id is carried from step to step as a request parameter.
 */
class VslaAgentOnboardingController extends Controller
{
    use ApiResponser, PhoneNumberNormalization;

    // ──────────────────────────────────────────────────────────────────────────
    // STEP 1 – Create VSLA Group
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Create a new VSLA group.  The currently authenticated field officer is
     * stored as the group's facilitator_id.
     */
    public function createGroup(Request $request)
    {
        $officer = $request->userModel ?? auth('api')->user();
        if (!$officer) {
            return $this->error('Authentication required.');
        }

        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|min:3|max:255',
            'description'           => 'nullable|string|max:1000',
            'meeting_frequency'     => 'nullable|in:Weekly,Bi-weekly,Monthly',
            'establishment_date'    => 'nullable|date|before_or_equal:today',
            'year_of_establishment' => 'nullable|integer|min:1900|max:' . date('Y'),
            'district_id'           => 'required|exists:locations,id',
            'estimated_members'     => 'nullable|integer|min:5|max:100',
            'subcounty_text'        => 'nullable|string|max:100',
            'parish_text'           => 'nullable|string|max:100',
            'village'               => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $district     = Location::find($request->district_id);
            $districtCode = strtoupper(substr($district->name, 0, 3));
            $year         = date('y');

            $lastGroup = FfsGroup::where('code', 'like', "$districtCode-VSLA-$year-%")
                ->orderBy('code', 'desc')
                ->first();

            $nextNumber = 1;
            if ($lastGroup && preg_match('/-(\d{4})$/', $lastGroup->code, $m)) {
                $nextNumber = intval($m[1]) + 1;
            }
            $groupCode = sprintf('%s-VSLA-%s-%04d', $districtCode, $year, $nextNumber);

            // Smart defaults
            $meetingFrequency  = $request->meeting_frequency ?? 'Weekly';
            $estimatedMembers  = $request->estimated_members ?? 25;
            $description       = $request->description ?? "{$request->name} VSLA Group";

            // Establishment date: use explicit date, or derive from year, or default to today
            $establishmentDate = $request->establishment_date;
            if (!$establishmentDate && $request->year_of_establishment) {
                $establishmentDate = $request->year_of_establishment . '-01-01';
            }
            if (!$establishmentDate) {
                $establishmentDate = Carbon::now()->format('Y-m-d');
            }

            $group = new FfsGroup();
            $group->code               = $groupCode;
            $group->type               = 'VSLA';
            $group->name               = $request->name;
            $group->description        = $description;
            $group->meeting_frequency  = $meetingFrequency;
            $group->establishment_date = $establishmentDate;
            $group->district_id        = $request->district_id;
            $group->subcounty_text     = $request->subcounty_text;
            $group->parish_text        = $request->parish_text;
            $group->village            = $request->village;
            $group->estimated_members  = $estimatedMembers;
            $group->status             = 'Active';
            $group->registration_date  = Carbon::now();
            $group->facilitator_id     = $officer->id;   // field officer is facilitator
            $group->created_by_id      = $officer->id;
            $group->ip_id              = $officer->ip_id; // inherit IP from officer

            $group->save();

            DB::commit();

            Log::info("AgentOnboarding: Group [{$group->id}] created by officer [{$officer->id}]");

            return $this->success('VSLA group created successfully!', [
                'group_id'   => $group->id,
                'group_code' => $group->code,
                'group'      => $group,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AgentOnboarding createGroup error: ' . $e->getMessage());
            return $this->error('Failed to create group: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STEP 2 – Register Officers (Chairperson, Secretary, Treasurer)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Register all three group leadership roles.
     * Creates user accounts if the phone number is new; otherwise updates the
     * existing account and assigns the appropriate role.
     */
    public function registerOfficers(Request $request)
    {
        $officer = $request->userModel ?? auth('api')->user();
        if (!$officer) {
            return $this->error('Authentication required.');
        }

        $validator = Validator::make($request->all(), [
            'group_id'          => 'required|exists:ffs_groups,id',

            // Chairperson
            'chairperson_name'  => 'required|string|min:3|max:255',
            'chairperson_phone' => ['required', 'string', 'regex:/^(\+256|0)[7][0-9]{8}$/'],
            'chairperson_email' => 'nullable|email',
            'chairperson_nin'   => 'nullable|string|max:20',
            'chairperson_password' => 'nullable|string|min:4|max:50',

            // Secretary
            'secretary_name'    => 'required|string|min:3|max:255',
            'secretary_phone'   => [
                'required',
                'string',
                'regex:/^(\+256|0)[7][0-9]{8}$/',
                'different:chairperson_phone'
            ],
            'secretary_email'   => 'nullable|email',
            'secretary_nin'     => 'nullable|string|max:20',

            // Treasurer
            'treasurer_name'    => 'required|string|min:3|max:255',
            'treasurer_phone'   => [
                'required',
                'string',
                'regex:/^(\+256|0)[7][0-9]{8}$/',
                'different:chairperson_phone',
                'different:secretary_phone'
            ],
            'treasurer_email'   => 'nullable|email',
            'treasurer_nin'     => 'nullable|string|max:20',

            'send_sms'          => 'nullable|in:0,1,true,false',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $group = FfsGroup::find($request->group_id);
        if (!$group) {
            return $this->error('Group not found.');
        }

        // Field officer must be the group's facilitator
        if ($group->facilitator_id != $officer->id) {
            return $this->error('You are not the facilitator of this group.');
        }

        DB::beginTransaction();
        try {
            // Chairperson uses custom password (default 4321) — always encrypted
            $chairPassword = $request->chairperson_password ?? '4321';
            $chairperson   = $this->createOrUpdateOfficer(
                $request->chairperson_name,
                $request->chairperson_phone,
                $request->chairperson_email,
                $chairPassword,
                'Chairperson',
                $group,
                $officer,
                $request->chairperson_nin,
                $request->chairperson_sex
            );

            $secretaryPassword = $this->generatePassword();
            $secretary         = $this->createOrUpdateOfficer(
                $request->secretary_name,
                $request->secretary_phone,
                $request->secretary_email,
                $secretaryPassword,
                'Secretary',
                $group,
                $officer,
                $request->secretary_nin,
                $request->secretary_sex
            );

            $treasurerPassword = $this->generatePassword();
            $treasurer         = $this->createOrUpdateOfficer(
                $request->treasurer_name,
                $request->treasurer_phone,
                $request->treasurer_email,
                $treasurerPassword,
                'Treasurer',
                $group,
                $officer,
                $request->treasurer_nin,
                $request->treasurer_sex
            );

            // Assign roles on the group record
            $group->admin_id     = $chairperson->id;
            $group->secretary_id = $secretary->id;
            $group->treasurer_id = $treasurer->id;
            $group->save();

            DB::commit();

            // Optionally send SMS
            $sendSms = in_array($request->send_sms, ['1', 'true', true, 1], true);
            $smsResults = [];
            if ($sendSms) {
                $smsResults['chairperson'] = $this->sendCredentialsSMS($chairperson, $chairPassword, $group, 'Chairperson');
                $smsResults['secretary']   = $this->sendCredentialsSMS($secretary, $secretaryPassword, $group, 'Secretary');
                $smsResults['treasurer']   = $this->sendCredentialsSMS($treasurer, $treasurerPassword, $group, 'Treasurer');
            }

            Log::info("AgentOnboarding: Officers registered for group [{$group->id}] by officer [{$officer->id}]");

            return $this->success('Officers registered successfully!', [
                'group_id'    => $group->id,
                'chairperson' => $this->cleanUser($chairperson),
                'secretary'   => $this->cleanUser($secretary),
                'treasurer'   => $this->cleanUser($treasurer),
                'sms_sent'    => $sendSms,
                'sms_results' => $smsResults,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AgentOnboarding registerOfficers error: ' . $e->getMessage());
            return $this->error('Failed to register officers: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STEP 3 – Create Savings Cycle
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Create (or replace) the active savings cycle for the group.
     */
    public function createCycle(Request $request)
    {
        $officer = $request->userModel ?? auth('api')->user();
        if (!$officer) {
            return $this->error('Authentication required.');
        }

        $normalized = [
            'group_id' => $request->input('group_id', $request->input('groupId')),
            'cycle_name' => $request->input('cycle_name', $request->input('name')),
            'start_date' => $request->input('start_date', $request->input('startDate')),
            'end_date' => $request->input('end_date', $request->input('endDate')),
            'saving_type' => $request->input('saving_type', $request->input('savingType')),
            'share_value' => $request->input('share_value', $request->input('shareValue')),
            'meeting_frequency' => $request->input('meeting_frequency', $request->input('meetingFrequency')),
            'loan_interest_rate' => $request->input('loan_interest_rate', $request->input('loanInterestRate')),
            'interest_frequency' => $request->input('interest_frequency', $request->input('interestFrequency')),
            'weekly_loan_interest_rate' => $request->input('weekly_loan_interest_rate', $request->input('weeklyLoanInterestRate')),
            'monthly_loan_interest_rate' => $request->input('monthly_loan_interest_rate', $request->input('monthlyLoanInterestRate')),
            'minimum_loan_amount' => $request->input('minimum_loan_amount', $request->input('minimumLoanAmount')),
            'maximum_loan_multiple' => $request->input('maximum_loan_multiple', $request->input('maximumLoanMultiple')),
            'late_payment_penalty' => $request->input('late_payment_penalty', $request->input('latePaymentPenalty')),
        ];

        $validator = Validator::make($normalized, [
            'group_id'               => 'required|exists:ffs_groups,id',
            'cycle_name'             => 'nullable|string|min:2|max:200',
            'start_date'             => 'nullable|date',
            'end_date'               => 'nullable|date',
            'saving_type'            => 'nullable|in:shares,any_amount',
            'share_value'            => 'nullable|numeric|min:0|max:1000000',
            'meeting_frequency'      => 'nullable|in:Weekly,Bi-weekly,Monthly',
            'loan_interest_rate'     => 'nullable|numeric|min:0|max:100',
            'interest_frequency'     => 'nullable|in:Weekly,Monthly',
            'weekly_loan_interest_rate'  => 'nullable|numeric|min:0|max:100',
            'monthly_loan_interest_rate' => 'nullable|numeric|min:0|max:100',
            'minimum_loan_amount'    => 'nullable|numeric|min:0',
            'maximum_loan_multiple'  => 'nullable|integer|min:1|max:100',
            'late_payment_penalty'   => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $group = FfsGroup::find($normalized['group_id']);
        if (!$group) {
            return $this->error('Group not found.');
        }

        if ($group->facilitator_id != $officer->id) {
            return $this->error('You are not the facilitator of this group.');
        }

        DB::beginTransaction();
        try {
            // Deactivate any existing active cycle first
            Project::where('group_id', $group->id)
                ->where('is_vsla_cycle', 'Yes')
                ->where('is_active_cycle', 'Yes')
                ->update(['is_active_cycle' => 'No']);

            // Smart defaults for cycle fields
            $currentYear       = date('Y');
            $savingType        = $normalized['saving_type'] ?? 'shares';
            $cycleName         = $normalized['cycle_name'] ?? "Cycle 1 $currentYear";
            $startDate         = $normalized['start_date'] ?? "$currentYear-01-01";
            $endDate           = $normalized['end_date'] ?? ($currentYear . '-12-31');
            $meetingFrequency  = $normalized['meeting_frequency'] ?? 'Weekly';
            $interestFrequency = $normalized['interest_frequency'] ?? 'Monthly';
            $loanInterestRate  = $normalized['loan_interest_rate'] ?? 10;
            $minimumLoan       = $normalized['minimum_loan_amount'] ?? 0;
            $maxLoanMultiple   = $normalized['maximum_loan_multiple'] ?? 3;
            $latePenalty       = $normalized['late_payment_penalty'] ?? 0;
            $shareValue        = $normalized['share_value'] ?? 0;

            if (Carbon::parse($endDate)->lt(Carbon::parse($startDate))) {
                return $this->error('End date must be after start date.');
            }

            $cycle = new Project();
            $cycle->created_by_id          = $officer->id;
            $cycle->is_vsla_cycle          = 'Yes';
            $cycle->is_active_cycle        = 'Yes';
            $cycle->group_id               = $group->id;
            $cycle->status                 = 'ongoing';
            $cycle->title                  = $cycleName;
            $cycle->cycle_name             = $cycleName;
            $cycle->description            = "VSLA Savings Cycle for {$group->name}";
            $cycle->start_date             = $startDate;
            $cycle->end_date               = $endDate;
            $cycle->saving_type            = $savingType;
            $cycle->meeting_frequency      = $meetingFrequency;
            $cycle->loan_interest_rate     = $loanInterestRate;
            $cycle->interest_frequency     = $interestFrequency;
            $cycle->minimum_loan_amount    = $minimumLoan;
            $cycle->maximum_loan_multiple  = $maxLoanMultiple;
            $cycle->late_payment_penalty   = $latePenalty;

            // Set interest rate by frequency
            if ($interestFrequency === 'Weekly') {
                $cycle->weekly_loan_interest_rate  = $normalized['weekly_loan_interest_rate'] ?? $loanInterestRate;
                $cycle->monthly_loan_interest_rate = $normalized['monthly_loan_interest_rate'];
            } else {
                $cycle->monthly_loan_interest_rate = $normalized['monthly_loan_interest_rate'] ?? $loanInterestRate;
                $cycle->weekly_loan_interest_rate  = $normalized['weekly_loan_interest_rate'];
            }

            if ($savingType === 'shares') {
                $cycle->share_value = $shareValue;
                $cycle->share_price = $shareValue;
            }

            $cycle->save();

            // Update group cycle metadata
            $group->cycle_number     = ($group->cycle_number ?? 0) + 1;
            $group->cycle_start_date = $startDate;
            $group->cycle_end_date   = $endDate;
            $group->save();

            DB::commit();

            Log::info("AgentOnboarding: Cycle created for group [{$group->id}] by officer [{$officer->id}]");

            return $this->success('Savings cycle created successfully!', [
                'group_id' => $group->id,
                'cycle_id' => $cycle->id,
                'cycle'    => $cycle,
                'group'    => $group,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AgentOnboarding createCycle error: ' . $e->getMessage());
            return $this->error('Failed to create cycle: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FACILITATOR INFO – Returns the current user's details for display
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return the logged-in officer's basic info (name, IP, etc.) so
     * the mobile form can display the facilitator as a read-only field.
     */
    public function facilitatorInfo(Request $request)
    {
        $officer = $request->userModel ?? auth('api')->user();
        if (!$officer) {
            return $this->error('Authentication required.');
        }

        $ipName = '';
        if ($officer->ip_id) {
            $ip = \App\Models\ImplementingPartner::find($officer->ip_id);
            $ipName = $ip ? $ip->name : '';
        }

        return $this->success('Facilitator info.', [
            'id'        => $officer->id,
            'name'      => $officer->name ?? ($officer->first_name . ' ' . $officer->last_name),
            'phone'     => $officer->phone_number,
            'ip_id'     => $officer->ip_id,
            'ip_name'   => $ipName,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Create a new user account for an officer, or update an existing one.
     */
    private function createOrUpdateOfficer(
        string $name,
        string $phoneRaw,
        ?string $email,
        string $password,
        string $role,
        FfsGroup $group,
        User $officer,
        ?string $nin = null,
        ?string $sex = null
    ): User {
        $phone = $this->normalizePhone($phoneRaw);

        // Try to find existing user
        $user = $this->findUserByPhone($phoneRaw, User::class);

        if (!$user) {
            $user = new Administrator();
        }

        $nameParts = preg_split('/\s+/', trim($name));
        $user->first_name = $nameParts[0];
        $user->last_name  = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : $nameParts[0];
        $user->name       = $name;

        $user->phone_number  = $phone;
        $user->username      = $phone;
        $user->reg_number    = $phone;
        $user->email         = $email ?: (preg_replace('/[^\d]/', '', $phone) . '@faoffsmis.org');
        $user->password      = Hash::make($password);
        $user->country       = 'Uganda';
        $user->user_type     = 'Customer';
        $user->status        = 'Active';
        $user->group_id      = $group->id;
        $user->district_id   = $group->district_id;
        $user->ip_id         = $officer->ip_id;

        if ($nin) {
            $user->national_id_number = $nin;
        }

        if ($sex) {
            $user->sex = $sex;
        }

        // Set appropriate role flags
        $user->is_group_admin     = ($role === 'Chairperson') ? 'Yes' : 'No';
        $user->is_group_secretary = ($role === 'Secretary')   ? 'Yes' : 'No';
        $user->is_group_treasurer = ($role === 'Treasurer')   ? 'Yes' : 'No';
        $user->onboarding_step    = 'step_7_complete';

        // Optional fields
        $user->profile_photo_large = $user->profile_photo_large ?? '';
        $user->location_lat        = $user->location_lat ?? '';
        $user->location_long       = $user->location_long ?? '';
        $user->facebook            = $user->facebook ?? '';
        $user->twitter             = $user->twitter ?? '';
        $user->linkedin            = $user->linkedin ?? '';
        $user->website             = $user->website ?? '';
        $user->other_link          = $user->other_link ?? '';
        $user->cv                  = $user->cv ?? '';
        $user->language            = $user->language ?? '';
        $user->about               = $user->about ?? '';
        $user->address             = $user->address ?? '';
        $user->occupation          = $user->occupation ?? '';

        $user->saveQuietly();

        return User::find($user->id);
    }

    private function sendCredentialsSMS(User $user, string $password, FfsGroup $group, string $role): array
    {
        try {
            $message = "Welcome to FFS-MIS! You are registered as {$role} of {$group->name} (Code: {$group->code}). "
                . "Login: Phone={$user->phone_number}, Password={$password}. Download the FFS-MIS app to get started.";

            // Use the same SMS utility as the rest of the system
            $result = Utils::send_sms($user->phone_number, $message);
            return ['sent' => true, 'result' => $result];
        } catch (\Throwable $e) {
            Log::warning("AgentOnboarding SMS failed for [{$user->phone_number}]: " . $e->getMessage());
            return ['sent' => false, 'error' => $e->getMessage()];
        }
    }

    private function generatePassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    private function cleanUser(User $user): array
    {
        $data = $user->toArray();
        unset($data['district'], $data['subcounty'], $data['parish'], $data['group']);
        return $data;
    }
}
