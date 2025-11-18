<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Service;
use App\Models\OrganizationalUser;
use Illuminate\Support\Facades\Hash;
// Import the Log facade
use App\Models\Organization;
use Illuminate\Support\Facades\Log; // Import the Log facade
use Illuminate\Support\Facades\Auth;

class CustomerUserController extends Controller
{
    //

    public function registerUserByCustomer(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'services' => 'nullable|array',
            // 'org_id' => 'required',
            'is_user_organizational' => 'nullable|boolean',
            'organizational_user_id' => 'exists:users,id', // Ensure that the organizational user ID exists in the users table
        ], [
            'name.required' => 'Der Name ist erforderlich.',

            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

            'services.array' => 'Die Dienste müssen ein Array sein.',

            // 'org_id.required' => 'Die Organisations-ID ist erforderlich.',

            'is_user_organizational.boolean' => 'Der Organisationsstatus muss ein boolescher Wert sein.',

            'organizational_user_id.exists' => 'Der Organisationsbenutzer muss ein gültiger Benutzer sein.',
        ]);

        // Create the new user
        $user = new User();
        $user->name = $request->name;
        $user->org_id = $request->org_id;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        if ($request->services) {
            $user->services = $request->services;
        }

        // Set the is_user_organizational flag
        $user->is_user_organizational = $request->is_user_organizational;
        $user->is_user_customer = 0;

        $user->expiration_date =  $request->expirationDate;
        $user->counter_limit =  $request->counterLimit;
        // Save the user
        $user->save();

        // Link the new user with the organizational user
        OrganizationalUser::create([
            'customer_id' => $request->creator_id, // Ensure this is the correct ID
            'organizational_id' => $user->id,
            'user_id' => $request->orgi_id
        ]);

        // Create a token for the new user
        $token = $user->createToken('user_token')->plainTextToken;

        // Return the response
        return response()->json([
            "message" => "User registered successfully. Please verify your email to continue.",
            "user" => $user,
            "token" => $token,
        ], 200);
    }

    public function registerOrganizationalUserByCustomer(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            // 'org_id' => 'required',
            'services' => 'nullable|array',
            'is_user_organizational' => 'nullable|boolean',
            'counterLimit' => 'required|numeric',
            // 'currentUsage' => 'required|numeric',
            'expirationDate' => 'required|date'
        ], [
            'name.required' => 'Der Name ist erforderlich.',

            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

            // 'org_id.required' => 'Die Organisations-ID ist erforderlich.',

            'services.array' => 'Die Dienste müssen ein Array sein.',

            'is_user_organizational.boolean' => 'Der Organisationsstatus muss ein boolescher Wert sein.',
        ]);

        // Create the new user
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;

        $user->password = Hash::make($request->password);

        if ($request->services) {
            $user->services = $request->services;
        }
        if ($request->org_id) {
            $user->org_id = $request->org_id;
        }

        // Set the is_user_organizational flag
        $user->is_user_organizational = $request->is_user_organizational;
        $user->is_user_customer = 0;
        $user->expiration_date =  $request->expirationDate;
        $user->counter_limit =  $request->counterLimit;


        // Save the user
        $user->save();

        // Link the new user with the customer
        OrganizationalUser::create([
            'customer_id' => $request->creator_id, // Store creator_id as customer_id
            'user_id' => $user->id, // Store the new user's id as user_id
        ]);

        // Create a token for the new user
        $token = $user->createToken('user_token')->plainTextToken;

        // Return the response
        return response()->json([
            "message" => "Organizational user registered successfully.",
            "user" => $user,
            "token" => $token,
        ], 200);
    }

    public function updateCustomerUser(Request $request, $id)
    {
        // dd($request->all());
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        // Validation for inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'services' => 'required|array',
            'services.*' => 'exists:services,id',
            'counterLimit' => 'required|integer|min:1',
            'expirationDate' => 'required|date|after:today',
        ], [
            'name.required' => 'Der Name ist erforderlich.',
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
            'services.array' => 'Die Dienste müssen ein Array sein.'
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->org_id) {
            $user->org_id = $request->org_id;
        }

        if ($request->services) {
            $user->services = $request->services;
        } // Assuming services is an array of IDs
        $user->counter_limit = $request->counterLimit;
        $user->expiration_date = $request->expirationDate;
        $userData = $user->save();
        if ($request->counterLimit || $request->expirationDate) {
            $orgUsers = OrganizationalUser::where('user_id', $id)->get();

            // Check if organizational users exist
            if ($orgUsers->isEmpty()) {
                return response()->json(['message' => 'No organizational users found for the given user ID'], 404);
            }

            // Extract organizational IDs
            $organizationalIds = $orgUsers->pluck('organizational_id')->filter();

            if ($organizationalIds->isEmpty()) {
                return response()->json(['message' => 'No valid organizational IDs found'], 404);
            }

            // Fetch related users from the users table
            $users = User::whereIn('id', $organizationalIds)->get();

            // Update counter_limit and expiration_date
            foreach ($users as $user) {
                if ($request->has('counterLimit')) {
                    $user->counter_limit = $request->counterLimit;
                }
                if ($request->has('expirationDate')) {
                    $user->expiration_date = $request->expirationDate;
                }
                $user->save();
            }
        }

        if ($userData) {
            return response()->json(['status' => 'success', 'message' => 'User updated successfully']);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to update user'], 500);
    }
    public function getOrganizationUsersForCustomer(Request $request)
    {
        // Get the authenticated user (the user who has created other users)
        $user = $request->user();

        // Fetch all the records from organizational_user where user_id is the creator's ID
        $createdUsers = OrganizationalUser::where('customer_id', $user->id)->pluck('organizational_id');

        // Fetch the user details for the users created by this user
        $usersInOrganization = User::whereIn('id', $createdUsers)->get();

        // Get the service IDs from the users
        $serviceIds = $usersInOrganization->pluck('services')->flatten();

        // Fetch service names based on service IDs
        $serviceNames = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names for each user based on org_id
        $orgIds = $usersInOrganization->pluck('org_id');
        $organizationNames = Organization::whereIn('id', $orgIds)->pluck('name', 'id');

        // Map the users and replace the service IDs with service names and include organization names
        $usersWithServiceNames = $usersInOrganization->map(function ($user) use ($serviceNames, $organizationNames) {
            // Get the service names for the user
            $userServiceNames = collect($user->services)->map(function ($serviceId) use ($serviceNames) {
                return $serviceNames->get($serviceId);
            });

            // Return the user data with service names and organization name
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServiceNames,
                'organization_name' => $organizationNames->get($user->org_id),
                'is_user_organizational' => $user->is_user_organizational,
            ];
        });

        // Return the created users with service names and organization names
        return response()->json([
            'organization_users' => $usersWithServiceNames,
        ], 200);
    }

    public function getAllCustomerUsers()
    {
        // Fetch all users where is_user_customer is 1
        $customerUsers = User::where('is_user_customer', 1)->get();

        // If no users are found, return a message
        if ($customerUsers->isEmpty()) {
            return response()->json([
                'message' => 'No customer users found.'
            ], 200);
        }

        // Fetch service IDs for each user
        $serviceIds = $customerUsers->pluck('services')->flatten();

        // Fetch service names based on service IDs
        $serviceNames = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names based on org_id
        $orgIds = $customerUsers->pluck('org_id');
        $organizationNames = Organization::whereIn('id', $orgIds)->pluck('name', 'id');

        // Map users and include services, organization names, and user count data
        $usersWithServiceAndOrgNames = $customerUsers->map(function ($user) use ($serviceNames, $organizationNames) {
            // Get the service names for the user
            $userServiceNames = collect($user->services)->map(function ($serviceId) use ($serviceNames) {
                return $serviceNames->get($serviceId);
            });

            // Call getUserCount to retrieve the user's specific data
            $userCountData = $this->getUserCount($user->id)->getData(true);

            // Return the user data with service names, organization name, and user count data
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServiceNames,
                'organization_name' => $organizationNames->get($user->org_id),
                'total_document_count' => $userCountData['total_document_count'] ?? 0,
                'total_contract_solution_count' => $userCountData['total_contract_solution_count'] ?? 0,
                'total_data_process_count' => $userCountData['total_data_process_count'] ?? 0,
                'total_free_data_process_count' => $userCountData['total_free_data_process_count'] ?? 0,
                'total_clone_data_process_count' => $userCountData['total_clone_data_process_count'] ?? 0,
                'total_werthenbach_count' => $userCountData['total_werthenbach_count'] ?? 0,
                'total_scheren_count' => $userCountData['total_scheren_count'] ?? 0,
                'total_sennheiser_count' => $userCountData['total_sennheiser_count'] ?? 0,
                'total_verbund_count' => $userCountData['total_verbund_count'] ?? 0,
            ];
        });

        // Return the list of customer users with service names, organization names, and user count data
        return response()->json([
            'customer_users' => $usersWithServiceAndOrgNames,
        ], 200);
    }


    private function getUserCount($id)
    {
        // Fetch initial user IDs excluding the logged-in user
        $ids = OrganizationalUser::where('customer_id', $id)
            ->where('user_id', '!=', Auth::id())
            ->distinct()
            ->pluck('user_id');

        // Collect all unique organizational IDs related to the initial user IDs
        $additionalIds = OrganizationalUser::whereIn('user_id', $ids)
            ->whereNotNull('organizational_id')
            ->pluck('organizational_id');

        // Merge all IDs, including the provided $id
        $uniqueIds = $ids->merge($additionalIds)->push((int)$id)->unique();

        // Preload necessary relationships for efficiency
        $users = User::whereIn('id', $uniqueIds)
            ->with(['documents', 'contractSolutions', 'dataprocesses', 'freedataprocesses', 'clonedataprocesses', 'werthenbachs', 'scherens', 'sennheisers', 'verbunds', 'demodataprocesses'])
            ->get();

        // Initialize counters
        $totalDocumentCount = 0;
        $totalContractSolutionCount = 0;
        $totalDataProcessCount = 0;
        $totalFreeDataProcessCount = 0;
        $totalCloneDataProcessCount = 0;
        $totalWerthenbachCount = 0;
        $totalScherenCount = 0;
        $totalSennheiserCount = 0;
        $totalVerbundCount = 0;
        $totalDemoDataProcessCount = 0;
        // Process each user
        foreach ($users as $user) {
            $userServices = $user->services ?? [];

            if (in_array('1', $userServices)) {
                $totalDocumentCount += $user->documents->count();
            }
            if (in_array('3', $userServices)) {
                $totalContractSolutionCount += $user->contractSolutions->count();
            }
            if (in_array('4', $userServices)) {
                $totalDataProcessCount += $user->dataprocesses->count();
            }
            if (in_array('5', $userServices)) {
                $totalFreeDataProcessCount += $user->freedataprocesses->count();
            }
            if (in_array('7', $userServices)) {
                $totalCloneDataProcessCount += $user->clonedataprocesses->count();
            }
            if (in_array('8', $userServices)) {
                $totalWerthenbachCount += $user->werthenbachs->count();
            }
            if (in_array('9', $userServices)) {
                $totalScherenCount += $user->scherens->count();
            }
            if (in_array('10', $userServices)) {
                $totalSennheiserCount += $user->sennheisers->count();
            }
            if (in_array('11', $userServices)) {
                $totalVerbundCount += $user->verbunds->count();
            }
            if (in_array('12', $userServices)) {
                $totalDemoDataProcessCount += $user->demodataprocesses->count();
            }
        }

        return response()->json([
            'total_document_count' => $totalDocumentCount,
            'total_contract_solution_count' => $totalContractSolutionCount,
            'total_data_process_count' => $totalDataProcessCount,
            'total_free_data_process_count' => $totalFreeDataProcessCount,
            'total_clone_data_process_count' => $totalCloneDataProcessCount,
            'total_werthenbach_count' => $totalWerthenbachCount,
            'total_scheren_count' => $totalScherenCount,
            'total_sennheiser_count' => $totalSennheiserCount,
            'total_verbund_count' => $totalVerbundCount,
            'total_demo_data_process_count' => $totalDemoDataProcessCount,
        ]);
    }
}
