<?php

namespace App\Http\Controllers;

use App\Models\CloneDataProcess;
use App\Models\ContractSolutions;
use App\Models\DataProcess;
use App\Models\Document;
use App\Models\FreeDataProcess;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\OrganizationalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Import the Log facade
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register_user(Request $request)
    {
        // dd($request->all());
        // Validate the incoming request
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users', // Check if the email is unique in the users table
            'password' => 'required|min:8',
            'services' => 'nullable|array',
            'is_user_organizational' => 'nullable|boolean',
            'is_user_customer' => 'nullable|boolean',
            'creator_id' => 'nullable|exists:users,id',
        ], [
            'name.required' => 'Der Name ist erforderlich.',

            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

            'services.array' => 'Die Dienste müssen ein Array sein.',

            'is_user_organizational.boolean' => 'Der Organisationsstatus muss ein boolescher Wert sein.',

            'creator_id.exists' => 'Der Ersteller muss ein gültiger Benutzer sein.',
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->is_user_customer = $request->is_user_customer;
        $user->password = Hash::make($request->password);

        if ($request->services) {
            $user->services = $request->services;
        }

        if ($request->org_id) {
            $user->org_id = $request->org_id;
        }
        if ($request->expirationDate) {
            $user->expiration_date =  $request->expirationDate;
        }
        if ($request->counterLimit) {
            $user->counter_limit =  $request->counterLimit;
        }

        // Check if counterLimit is 'super-admin'
        if ($request->input('counterLimit') === "super-admin") {
            $userData = User::findOrFail($request->creator_id);
            $user->counter_limit = $userData->counter_limit;
            $user->expiration_date = $userData->expiration_date;
        }

        // Set the is_user_organizational flag
        $user->is_user_organizational = $request->is_user_organizational;

        // Save the user
        $user->save();
        $parent_id = OrganizationalUser::where('user_id', $request->creator_id)
            ->whereNotNull('user_id')
            ->pluck('customer_id')
            ->first();
        // Save the creator and new user in OrganizationalUser
        OrganizationalUser::create([
            'user_id' => $request->creator_id,
            'organizational_id' => $user->id,
            'customer_id' => $parent_id,
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
    public function getUserById($id)
    {
        // Find the user by ID
        $user = User::findOrFail($id);

        // Return user data
        return response()->json([
            'user' => $user,
        ], 200);
    }

    public function update_user(Request $request, $id)
    {
        // Validate the request
        // Log::info(['data comes',["data comes" => $request->all()]]);
        $request->validate([
            'services' => 'sometimes|array',
            'email' => 'required|email|unique:users,email,' . $id
        ], [
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
            'is_user_organizational' => 'nullable|boolean',
            'is_user_customer' => 'nullable|boolean',
        ]);
        // $request->validate([
        //     'name' => 'required',
        //     'email' => 'required|email|unique:users,email,' . $id, // Allow the same email
        //     'password' => 'nullable|min:8',
        //     'services' => 'sometime|array',
        //     'is_user_organizational' => 'nullable|boolean',
        //     'is_user_customer' => 'nullable|boolean',

        // ]);

        // Find the user to update
        $user = User::findOrFail($id);

        // Update fields
        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('services')) {
            $user->services = $request->services;
        }
        // Only update counter_limit and expiration_date if provided
        if ($request->counterLimit) {
            $user->counter_limit = $request->counterLimit;
        }

        if ($request->expirationDate) {
            $user->expiration_date = $request->expirationDate;
        }

        // Update other fields
        $user->is_user_customer = $request->is_user_customer;
        $user->is_user_organizational = $request->is_user_organizational;
        $user->services = $request->services ?? $user->services;
        // if($user->user_register_type === "out"){
        //     $user->user_register_type = "in";
        // }
        if ($user->user_register_type === "out") {
            $user->user_register_type = "in";
            $firstCustomerAdmin = User::with('customerUserWithNullOrganization')
                ->has('customerUserWithNullOrganization')
                ->where(['is_user_customer' => 1, 'org_id' => null])
                ->first();


            $organizationalUser = User::find($firstCustomerAdmin->customerUserWithNullOrganization->user_id);
            $user->counter_limit = $organizationalUser->counter_limit;
        }

        // Save updated user
        $user->save();

        return response()->json([
            "message" => "User updated successfully.",
            "user" => $user,
        ], 200);
    }


    // public function delete_user($id)
    // {
    //     // Find the user by ID
    //     $user = User::findOrFail($id);

    //     // You can add checks to delete related data if necessary (e.g., in OrganizationalUser)
    //     $user->delete();

    //     return response()->json([
    //         "message" => "User deleted successfully.",
    //     ], 200);
    // }
    public function registerUserByCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'services' => 'nullable|array',
            'is_user_organizational' => 'nullable|boolean',
            'organizational_user_id' => 'required|exists:users,id', // Organizational user ID is required and must exist
        ], [
            'name.required' => 'Der Name ist erforderlich.',

            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',

            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

            'services.array' => 'Die Dienste müssen ein Array sein.',

            'is_user_organizational.boolean' => 'Der Organisationsstatus muss ein boolescher Wert sein.',

            'organizational_user_id.required' => 'Die ID des Organisationsbenutzers ist erforderlich.',
            'organizational_user_id.exists' => 'Der Organisationsbenutzer muss ein gültiger Benutzer sein.',
        ]);

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

        // Save the user
        $user->save();

        // Save the creator as organizational_user and the new user in OrganizationalUser
        OrganizationalUser::create([
            'user_id' => $request->creator_id, // Creator ID is organizational_user_id
            'organizational_id' => $user->id,
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




    public function getOrganizationUsers(Request $request)
    {
        // Get the authenticated user (the user who has created other users)
        $user = $request->user();

        // Fetch all the records from organizational_user where user_id is the creator's ID
        $createdUsers = OrganizationalUser::where('user_id', $user->id)->pluck('organizational_id');

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

            $documents = $this->countToolDocumentNormalUser($user->id);

            // Return the user data with service names and organization name
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServiceNames,
                'allCount' => $documents['allCount'],
                'organization_name' => $organizationNames->get($user->org_id),
                'counter_limit' => $user->counter_limit,
            ];
        });

        // Return the created users with service names and organization names
        return response()->json([
            'organization_users' => $usersWithServiceNames,
        ], 200);
    }


    public function getOrganizationUsers2($userId)
    {
        // Fetch all the records from organizational_user where user_id is the provided userId
        $createdUsers = OrganizationalUser::where('user_id', $userId)->pluck('organizational_id');
        return $createdUsers;
        // If the user has not created any other users
        if ($createdUsers->isEmpty()) {
            return response()->json([
                'message' => 'This user has not created any other users.'
            ], 200);
        }

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
            ];
        });

        // Return the created users with service names and organization names
        return response()->json([
            'organization_users' => $usersWithServiceNames,
        ], 200);
    }



    public function getCustomerNormalUsers($id)
    {
        // Fetch all the records from organizational_user where organizational_id matches the provided id
        $normalUserIds = OrganizationalUser::where('user_id', $id)->pluck('organizational_id');

        // If no users are assigned to this organizational user
        if ($normalUserIds->isEmpty()) {
            return response()->json([
                'message' => 'No normal users are assigned to this organization.',
            ], 200);
        }

        // Fetch the user details for the normal users assigned to this organizational user
        $normalUsers = User::whereIn('id', $normalUserIds)->get();

        // Get the service IDs from the users
        $serviceIds = $normalUsers->pluck('services')->flatten();

        // Fetch service names based on service IDs
        $serviceNames = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names for each user based on org_id
        $orgIds = $normalUsers->pluck('org_id');
        $organizationNames = Organization::whereIn('id', $orgIds)->pluck('name', 'id');

        // Map the normal users and replace the service IDs with service names and include organization names
        $usersWithServiceNames = $normalUsers->map(function ($user) use ($serviceNames, $organizationNames) {
            // Get the service names for the user
            $userServiceNames = collect($user->services)->map(function ($serviceId) use ($serviceNames) {
                return $serviceNames->get($serviceId);
            });
            $documents = $this->countToolDocumentNormalUser($user->id);
            // Return the user data with service names and organization name
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServiceNames,
                'organization_name' => $organizationNames->get($user->org_id),
                'allCount' => $documents['allCount'],
                'counter_limit' => $user->counter_limit,
            ];
        });

        // Return the normal users with service names and organization names
        return response()->json([
            'normal_users' => $usersWithServiceNames,
        ], 200);
    }

    public function delete_User($id)
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if the user exists
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Delete all records in the OrganizationalUser table where the user is an organizational_id (user created by an organization)
        OrganizationalUser::where('organizational_id', $id)->delete();

        // Optionally, also delete the records where the user is the creator (user_id)
        OrganizationalUser::where('user_id', $id)->delete();

        // Now delete the user from the users table
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function userToolCounter()
    {
        // Get the ID of the currently logged-in user
        $userId = Auth::id();

        // Fetch counts specific to the logged-in user
        $dataProcessCount = DataProcess::where('user_id', $userId)->count();
        $documentsCount = Document::where('user_id', $userId)->count();
        $contractSolutionCount = ContractSolutions::where('user_id', $userId)->count();
        $freeDataProcessCount = FreeDataProcess::where('user_id', $userId)->count();
        $cloneDataProcessCount = CloneDataProcess::where('user_id', $userId)->count();

        // Calculate the total count
        $allCount = $dataProcessCount + $documentsCount + $contractSolutionCount + $freeDataProcessCount + $cloneDataProcessCount;

        // Return counts as an array
        return [
            'dataProcessCount' => $dataProcessCount,
            'documentsCount' => $documentsCount,
            'contractSolutionCount' => $contractSolutionCount,
            'freeDataProcessCount' => $freeDataProcessCount,
            'cloneDataProcessCount' => $cloneDataProcessCount,
            'allCount' => $allCount,
        ];
    }
}